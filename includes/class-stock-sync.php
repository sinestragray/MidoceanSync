<?php

class MidOcean_Stock_Sync {
    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function sync() {
        $stock_data = $this->client->get_product_stock();

        if (empty($stock_data) || empty($stock_data['stock'])) {
            error_log('[MidOcean Importer] Brak danych stock.');
            return;
        }

        foreach ($stock_data['stock'] as $stock_item) {
            $sku = isset($stock_item['sku']) ? $stock_item['sku'] : '';
            $qty = isset($stock_item['qty']) ? (int) $stock_item['qty'] : 0;

            if (empty($sku)) {
                error_log('[MidOcean Importer] Brak SKU w pozycji stock.');
                continue;
            }

            $product_id = wc_get_product_id_by_sku($sku);

            if (!$product_id) {
                error_log("[MidOcean Importer] Nie znaleziono produktu dla SKU: $sku");
                continue;
            }

            $product = wc_get_product($product_id);

            if (!$product) {
                error_log("[MidOcean Importer] Błąd przy ładowaniu produktu o ID: $product_id (SKU: $sku)");
                continue;
            }

            // Ustawienia stanu magazynowego
            $product->set_manage_stock(true);
            $product->set_stock_quantity($qty);
            $product->set_stock_status($qty > 0 ? 'instock' : 'outofstock');

            // Zapisz zmiany
            $product->save();

            error_log("[MidOcean Importer] Zaktualizowano stan SKU: $sku = $qty");
        }
    }

    public function sync_single($master_code) {
    $stocks = $this->client->get_stock_by_code($master_code);
    if (!$stocks || !is_array($stocks)) return;

    foreach ($stocks as $stock) {
        $sku = $stock['sku'];
        $qty = intval($stock['stock']);

        $post_id = wc_get_product_id_by_sku($sku);
        if ($post_id) {
            update_post_meta($post_id, '_stock', $qty);
            update_post_meta($post_id, '_stock_status', $qty > 0 ? 'instock' : 'outofstock');
        }
    }
}

}
