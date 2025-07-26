<?php

class MidOcean_Product_Importer {
    protected $client;
    protected $media_handler;
    protected $helper;

    public function __construct($client) {
        $this->client = $client;
        $this->media_handler = new MidOcean_Media_Handler();
        $this->helper = new MidOcean_Helper_Functions();
    }

    public function sync_products($args = []) {
        $batch_size = isset($args['batch_size']) ? (int) $args['batch_size'] : 10;
        $force_replace = isset($args['force_replace']) ? (bool) $args['force_replace'] : false;

        $products = $this->client->fetch_products($batch_size);
        if (!is_array($products)) {
            error_log('[MidOcean Sync] Brak danych produktów lub błędny format.');
            return;
        }

        foreach ($products as $product) {
            $this->import_product($product, $force_replace);
        }
    }

    protected function import_product($product, $force_replace = false) {
        if (!$product || empty($product['product_name'])) {
            return;
        }

        $existing = new WP_Query([
            'post_type' => 'product',
            'meta_query' => [[
                'key' => '_sku',
                'value' => $product['master_code'],
            ]],
            'posts_per_page' => 1
        ]);

        if ($existing->have_posts() && !$force_replace) {
            return;
        }

        if ($existing->have_posts() && $force_replace) {
            $product_id = $existing->posts[0]->ID;
            wp_delete_post($product_id, true);
        }

        $product_id = wp_insert_post([
            'post_title' => wp_strip_all_tags($product['product_name']),
            'post_content' => $product['long_description'] ?? '',
            'post_excerpt' => $product['short_description'] ?? '',
            'post_status' => 'publish',
            'post_type' => 'product',
        ]);

        if (is_wp_error($product_id) || !$product_id) {
            return;
        }

        update_post_meta($product_id, '_sku', $product['master_code']);
        update_post_meta($product_id, '_weight', $product['gross_weight'] ?? '');
        update_post_meta($product_id, '_length', $product['length'] ?? '');
        update_post_meta($product_id, '_width', $product['width'] ?? '');
        update_post_meta($product_id, '_height', $product['height'] ?? '');

        $this->assign_categories($product_id, $product['variants']);
        $this->helper->assign_attributes($product_id, $product);
        wp_set_object_terms($product_id, 'variable', 'product_type');

        $this->create_variations($product_id, $product['variants'] ?? []);
        $this->media_handler->handle_images($product_id, $product['variants']);
        $this->media_handler->handle_variation_images($product_id, $product['variants']);
    }

    protected function assign_categories($product_id, $variants) {
        if (!empty($variants[0])) {
            $level1 = $variants[0]['category_level1'] ?? null;
            $level2 = $variants[0]['category_level2'] ?? null;
            $level3 = $variants[0]['category_level3'] ?? null;

            $cat_id = 0;
            if ($level1) {
                $cat1 = term_exists($level1, 'product_cat');
                if (!$cat1) $cat1 = wp_insert_term($level1, 'product_cat');
                $cat_id = is_array($cat1) ? $cat1['term_id'] : $cat1;
            }

            if ($level2 && $cat_id) {
                $cat2 = term_exists($level2, 'product_cat');
                if (!$cat2) $cat2 = wp_insert_term($level2, 'product_cat', ['parent' => $cat_id]);
                $cat_id = is_array($cat2) ? $cat2['term_id'] : $cat2;
            }

            if ($level3 && $cat_id) {
                $cat3 = term_exists($level3, 'product_cat');
                if (!$cat3) $cat3 = wp_insert_term($level3, 'product_cat', ['parent' => $cat_id]);
                $cat_id = is_array($cat3) ? $cat3['term_id'] : $cat3;
            }

            if ($cat_id) {
                wp_set_post_terms($product_id, [$cat_id], 'product_cat');
            }
        }
    }

    protected function create_variations($product_id, $variants) {
        if (empty($variants)) return;

        foreach ($variants as $variant) {
            $variation_post = [
                'post_title'  => $variant['sku'],
                'post_status' => 'publish',
                'post_parent' => $product_id,
                'post_type'   => 'product_variation',
                'meta_input'  => [
                    '_sku'           => $variant['sku'],
                    '_regular_price' => $variant['price'] ?? '',
                    '_stock'         => $variant['stock'] ?? '',
                    'attribute_pa_kolor' => sanitize_title($variant['color_group'] ?? '')
                ]
            ];

            wp_insert_post($variation_post);
        }
    }
}
