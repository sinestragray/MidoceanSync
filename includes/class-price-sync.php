<?php

class MidOcean_Price_Sync {
    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function sync() {
        $prices_data = $this->client->get_product_prices();

        if (!$prices_data || empty($prices_data['price'])) {
            error_log('[MidOcean Price Sync] Brak danych cenowych z API.');
            return;
        }

        foreach ($prices_data['price'] as $price_item) {
            $sku = $price_item['sku'];
            $price = str_replace(',', '.', $price_item['price']); // Zamiana przecinka na kropkę

            if (!$sku || !$price) {
                continue;
            }

            $product_id = wc_get_product_id_by_sku($sku);
            if (!$product_id) {
                error_log("[MidOcean Price Sync] Nie znaleziono produktu dla SKU: $sku");
                continue;
            }

            // Aktualizacja ceny
            update_post_meta($product_id, '_regular_price', $price);
            update_post_meta($product_id, '_price', $price);

            error_log("[MidOcean Price Sync] Zaktualizowano cenę dla SKU $sku: $price PLN");
        }
    }

    public function sync_single($master_code) {
    $prices = $this->client->get_price_by_code($master_code);
    if (!$prices || !is_array($prices)) return;

    foreach ($prices as $row) {
        $sku = $row['sku'];
        $price = floatval($row['price_1']);

        $post_id = wc_get_product_id_by_sku($sku);
        if ($post_id) {
            update_post_meta($post_id, '_regular_price', $price);
            update_post_meta($post_id, '_price', $price);
        }
    }
}

}
