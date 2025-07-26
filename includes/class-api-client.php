<?php

class MidOcean_API_Client {
    private $api_key;
    private $use_test_api;
    private $base_url;

    public function __construct($api_key, $use_test_api = false) {
        $this->api_key = $api_key;
        $this->use_test_api = $use_test_api;
        $this->base_url = $use_test_api
            ? 'https://apitest.midocean.com'  // ✅ poprawnie bez /gateway/
            : 'https://api.midocean.com';
    }

    private function get_headers($format = 'json') {
        $accept = ($format === 'json') ? 'application/json' : 'text/' . $format;
        return [
            'Accept' => $accept,
            'x-Gateway-APIKey' => $this->api_key
        ];
    }

    private function make_request($endpoint, $format = 'json') {
        $url = $this->base_url . '/' . ltrim($endpoint, '/');

        $args = [
            'headers' => $this->get_headers($format),
            'timeout' => 60,
            'httpversion' => '1.1',
            'redirection' => 5,
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('[MidOcean API] WP_Error: ' . $response->get_error_message() . ' | URL: ' . $url);
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log('[MidOcean API] Response Code: ' . $code . ' | URL: ' . $url . ' | Body Snippet: ' . substr($body, 0, 200));

        if ($code !== 200) {
            error_log("[MidOcean API] Błąd: $code - $body | URL: $url");
            return null;
        }

        return json_decode($body, true);
    }

    // ✅ Alias dla kompatybilności z Product Importerem
    public function fetch_products($language = 'pl') {
        return $this->get_products($language);
    }

    public function get_products($language = 'pl') {
        return $this->make_request('gateway/products/2.0?language=' . $language);
    }

    public function get_product_by_code($code, $language = 'pl') {
        error_log("[MidOcean API] UWAGA: Endpoint /$code nie istnieje, przeszukuję listę produktów lokalnie.");

        $products = $this->get_products($language);
        if (!$products || !is_array($products)) return null;

        foreach ($products as $product) {
            if ($product['master_code'] === $code) {
                return $product;
            }
        }

        return null;
    }

    public function get_product_stock() {
        return $this->make_request('gateway/stock/2.0');
    }

    public function get_product_prices() {
        return $this->make_request('gateway/pricelist/2.0/');
    }

    public function get_print_prices() {
        return $this->make_request('gateway/printpricelist/2.0/');
    }

    public function get_stock_by_code($code) {
        return $this->make_request("gateway/stock/2.0/$code");
    }

    public function get_price_by_code($code) {
        return $this->make_request("gateway/pricelist/2.0/$code");
    }

    public function get_orders() {
        return $this->make_request('gateway/order/2.1/detail');
    }

    public function get_print_data() {
        return $this->make_request('gateway/printdata/1.0');
    }

    public function get_stock() {
        return $this->get_product_stock();
    }
}
