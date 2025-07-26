<?php
/*
Plugin Name: MidOcean Sync
Description: Synchronizacja produktów, stanów, cen i webhooków z MidOcean.
Version: 1.1
Author: CyberyBerry
*/

// 🔄 Ładowanie klas
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-helper-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-media-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-stock-sync.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-price-sync.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-product-importer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-taxonomy-initializer.php'; // ✅ Dodano to

// ✅ Rejestracja dodatkowych taksonomii
if (class_exists('MidOcean_Attribute_Taxonomy_Initializer')) {
    add_action('init', function () {
        (new MidOcean_Attribute_Taxonomy_Initializer())->register_custom_attributes();
    }, 20);
}

// 🔔 Rejestracja webhooków REST API
if (class_exists('MidOcean_Webhook_Handler')) {
    add_action('rest_api_init', function () {
        (new MidOcean_Webhook_Handler())->register_routes();
    });
}

// 🧾 Rejestracja shortcode do formularza personalizacji
if (class_exists('MidOcean_Helper_Functions')) {
    add_action('init', function () {
        add_shortcode('midocean_personalization_form', ['MidOcean_Helper_Functions', 'render_personalization_form']);
    });
}

// 🕒 CRON: codzienna synchronizacja
if (!wp_next_scheduled('midocean_sync_cron_daily')) {
    wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'midocean_sync_cron_daily');
}

add_action('midocean_sync_cron_daily', function () {
    $api_key = get_option('midocean_api_key');
    $use_test = get_option('midocean_use_test_api') ? true : false;
    $force_replace = get_option('midocean_force_replace') ? true : false;

    if (!$api_key) {
        error_log('[MidOcean Sync] Brak API key w ustawieniach.');
        return;
    }

    try {
        $client = new MidOcean_API_Client($api_key, $use_test);

        // Import produktów
        (new MidOcean_Product_Importer($client))->sync_products([
            'batch_size' => 20,
            'force_replace' => $force_replace,
        ]);

        // Synchronizacja stanów
        (new MidOcean_Stock_Sync($client))->sync();

        // Synchronizacja cen
        (new MidOcean_Price_Sync($client))->sync();

    } catch (Exception $e) {
        error_log('[MidOcean Sync] Błąd synchronizacji: ' . $e->getMessage());
    }
});

// 🧹 Usuwanie harmonogramu przy dezaktywacji
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('midocean_sync_cron_daily');
});
