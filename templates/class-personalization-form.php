<?php
// Szablon formularza personalizacji produktu

defined('ABSPATH') || exit;

$product_id = get_the_ID();

?>

<div class="midocean-personalization-form">
    <h3>Personalizacja produktu</h3>

    <form method="post" class="personalization-form">
        <p>
            <label for="personalization_text">Tekst personalizacji:</label>
            <input type="text" id="personalization_text" name="personalization_text" class="input-text" required>
        </p>

        <p>
            <label for="personalization_notes">Uwagi dodatkowe:</label>
            <textarea id="personalization_notes" name="personalization_notes" class="input-text" rows="4"></textarea>
        </p>

        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
        <button type="submit" class="button alt">Dodaj personalizację</button>
    </form>
</div>
