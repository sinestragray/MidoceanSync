<?php

class MidOcean_Taxonomy_Initializer {

    public static function register_taxonomies() {
        self::register_taxonomy('midocean_cat_level1', 'product', 'Kategoria poziom 1');
        self::register_taxonomy('midocean_cat_level2', 'product', 'Kategoria poziom 2');
        self::register_taxonomy('midocean_cat_level3', 'product', 'Kategoria poziom 3');
    }

    private static function register_taxonomy($taxonomy, $object_type, $label) {
        register_taxonomy($taxonomy, $object_type, [
            'labels' => [
                'name'              => $label,
                'singular_name'     => $label,
                'search_items'      => 'Szukaj ' . $label,
                'all_items'         => 'Wszystkie ' . $label,
                'parent_item'       => 'Rodzic ' . $label,
                'parent_item_colon' => 'Rodzic: ' . $label,
                'edit_item'         => 'Edytuj ' . $label,
                'update_item'       => 'Aktualizuj ' . $label,
                'add_new_item'      => 'Dodaj nowy ' . $label,
                'new_item_name'     => 'Nazwa nowego ' . $label,
                'menu_name'         => $label,
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => $taxonomy],
        ]);
    }

    public static function assign_taxonomies($post_id, $product_data) {
        if (!empty($product_data['variants'][0]['category_level1'])) {
            wp_set_object_terms($post_id, $product_data['variants'][0]['category_level1'], 'midocean_cat_level1', false);
        }
        if (!empty($product_data['variants'][0]['category_level2'])) {
            wp_set_object_terms($post_id, $product_data['variants'][0]['category_level2'], 'midocean_cat_level2', false);
        }
        if (!empty($product_data['variants'][0]['category_level3'])) {
            wp_set_object_terms($post_id, $product_data['variants'][0]['category_level3'], 'midocean_cat_level3', false);
        }
    }
}
