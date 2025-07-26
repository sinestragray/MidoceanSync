<?php

function midocean_sync_settings_page() {
    ?>
    <div class="wrap">
        <h1>MidOcean Sync – Ustawienia</h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('midocean_sync_settings');
            do_settings_sections('midocean_sync');
            submit_button('Zapisz ustawienia');
            ?>
        </form>

        <hr>

        <h2>Ręczna synchronizacja</h2>
        <form method="post">
            <?php submit_button('Synchronizuj produkty', 'primary', 'sync_products'); ?>
            <?php submit_button('Synchronizuj stany magazynowe', 'secondary', 'sync_stock'); ?>
            <?php submit_button('Synchronizuj ceny', 'secondary', 'sync_prices'); ?>
        </form>

        <hr>

        <h2>Testuj pojedynczy produkt</h2>
        <form method="post">
            <input type="text" name="test_master_code" placeholder="Wpisz master_code" required>
            <?php submit_button('Importuj produkt testowy', 'primary', 'sync_single_product'); ?>
        </form>
    </div>
    <?php
}

function midocean_sync_register_settings() {
    register_setting('midocean_sync_settings', 'midocean_api_key');
    register_setting('midocean_sync_settings', 'midocean_use_test_api');
    register_setting('midocean_sync_settings', 'midocean_force_replace');

    add_settings_section(
        'midocean_sync_api_section',
        'Ustawienia API',
        null,
        'midocean_sync'
    );

    add_settings_field(
        'midocean_api_key',
        'Klucz API',
        'midocean_api_key_field_callback',
        'midocean_sync',
        'midocean_sync_api_section'
    );

    add_settings_field(
        'midocean_use_test_api',
        'Używaj API testowego',
        'midocean_use_test_api_field_callback',
        'midocean_sync',
        'midocean_sync_api_section'
    );

    add_settings_field(
        'midocean_force_replace',
        'Zastępuj istniejące produkty',
        'midocean_force_replace_field_callback',
        'midocean_sync',
        'midocean_sync_api_section'
    );
}

function midocean_api_key_field_callback() {
    $value = esc_attr(get_option('midocean_api_key'));
    echo "<input type='text' name='midocean_api_key' value='$value' class='regular-text'>";
}

function midocean_use_test_api_field_callback() {
    $checked = checked(1, get_option('midocean_use_test_api'), false);
    echo "<input type='checkbox' name='midocean_use_test_api' value='1' $checked> Włącz tryb testowy";
}

function midocean_force_replace_field_callback() {
    $checked = checked(1, get_option('midocean_force_replace'), false);
    echo "<input type='checkbox' name='midocean_force_replace' value='1' $checked> Usuń i utwórz produkt od nowa";
}

add_action('admin_init', 'midocean_sync_register_settings');

add_action('admin_menu', function () {
    add_options_page(
        'MidOcean Sync',
        'MidOcean Sync',
        'manage_options',
        'midocean-sync',
        'midocean_sync_settings_page'
    );
});

add_action('admin_init', function () {
    $api_key = get_option('midocean_api_key');
    $use_test = get_option('midocean_use_test_api') ? true : false;
    $force_replace = get_option('midocean_force_replace') ? true : false;

    if (isset($_POST['sync_products'])) {
        $client = new MidOcean_API_Client($api_key, $use_test);
        $importer = new MidOcean_Product_Importer($client);
        $importer->sync_products([
            'batch_size' => 50,
            'force_replace' => $force_replace,
        ]);
    }

    if (isset($_POST['sync_stock'])) {
        $client = new MidOcean_API_Client($api_key, $use_test);
        $stock_sync = new MidOcean_Stock_Sync($client);
        $stock_sync->sync();
    }

    if (isset($_POST['sync_prices'])) {
        $client = new MidOcean_API_Client($api_key, $use_test);
        $price_sync = new MidOcean_Price_Sync($client);
        $price_sync->sync();
    }

    if (isset($_POST['sync_single_product']) && !empty($_POST['test_master_code'])) {
        $code = sanitize_text_field($_POST['test_master_code']);
        $client = new MidOcean_API_Client($api_key, $use_test);

        $products = $client->get_products('pl');
        $found = array_filter($products, fn($p) => $p['master_code'] === $code);
        $product = reset($found);

        if (!$product) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>Błąd:</strong> Nie znaleziono produktu o podanym master_code.</p></div>';
            });
            return;
        }

        // Ręczne kasowanie istniejącego produktu (dla testu)
        if ($force_replace) {
            $existing = wc_get_product_id_by_sku($code);
            if ($existing) {
                wp_delete_post($existing, true);
            }
        }

        $importer = new MidOcean_Product_Importer($client);
        $importer->import_single_product($product, [
            'force_replace' => $force_replace,
        ]);

        if (method_exists('MidOcean_Stock_Sync', 'sync_single')) {
            (new MidOcean_Stock_Sync($client))->sync_single($code);
        }

        if (method_exists('MidOcean_Price_Sync', 'sync_single')) {
            (new MidOcean_Price_Sync($client))->sync_single($code);
        }

        add_action('admin_notices', function () use ($code) {
            echo '<div class="notice notice-success"><p>Produkt testowy <strong>' . esc_html($code) . '</strong> został poprawnie zaimportowany i zsynchronizowany.</p></div>';
        });
    }
});
