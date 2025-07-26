<?php
class MidOcean_Helper_Functions {
    public function assign_attributes($product_id, $product) {
        $attributes = [];

        $global_attributes = [
            'pa_kolor' => $product['color_group'] ?? '',
            'pa_material' => $product['material'] ?? '',
            'pa_rozmiar' => $product['size'] ?? '',
        ];

        foreach ($global_attributes as $taxonomy => $value) {
            if (!$value) continue;
            wp_set_object_terms($product_id, [$value], $taxonomy, true);

            $attributes[$taxonomy] = [
                'name' => $taxonomy,
                'value' => $value,
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 1,
            ];
        }

        update_post_meta($product_id, '_product_attributes', $attributes);
    }
}
