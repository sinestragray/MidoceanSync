<?php
class MidOcean_Media_Handler {
    public function handle_images($product_id, $variants) {
        if (!is_array($variants)) return;
        foreach ($variants as $variant) {
            if (!empty($variant['image_urls']) && is_array($variant['image_urls'])) {
                $image_url = $variant['image_urls'][0];
                $image_id = $this->upload_image($image_url, $variant['sku']);
                if ($image_id) {
                    set_post_thumbnail($product_id, $image_id);
                    break;
                }
            }
        }
    }

    public function handle_variation_images($product_id, $variants) {
        // Future: Assign specific images to variations
    }

    private function upload_image($url, $sku) {
        $tmp = download_url($url);
        if (is_wp_error($tmp)) return false;

        $file_array = [
            'name' => $sku . '.jpg',
            'tmp_name' => $tmp,
        ];

        $id = media_handle_sideload($file_array, 0);
        return is_wp_error($id) ? false : $id;
    }
}
