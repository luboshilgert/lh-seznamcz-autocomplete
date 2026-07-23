<?php

namespace LHSeznamczAutocomplete\Pages;

use LHSeznamczAutocomplete\Base\BaseController;
use LHSeznamczAutocomplete\Base\Settings;

/**
 * Zalozka "Mapy.cz naseptavac" v editoru konkretniho CF7 formulare.
 * Umoznuje prepsat globalni mapovani poli pro jeden formular.
 *
 * @author Lubos Hilgert <luboshilgert@gmail.com>
 */
class Cf7Panel extends BaseController {

    const NONCE_ACTION = 'lh_seznamcz_cf7_panel';
    const NONCE_NAME   = 'lh_seznamcz_cf7_nonce';

    public function register() {
        add_filter('wpcf7_editor_panels', array($this, 'add_panel'));
        add_action('wpcf7_save_contact_form', array($this, 'save'), 10, 1);
    }

    /**
     * @param array $panels Zalozky editoru CF7.
     * @return array
     */
    public function add_panel($panels) {
        $panels['lh-seznamcz-panel'] = [
            'title'    => __('Mapy.cz našeptávač', 'lh-seznamcz-autocomplete'),
            'callback' => array($this, 'render_panel'),
        ];
        return $panels;
    }

    /**
     * @param \WPCF7_ContactForm $contact_form Editovany formular.
     */
    public function render_panel($contact_form) {
        $form_id  = $contact_form->id();
        $meta     = get_post_meta($form_id, LH_SEZNAM_CF7_META, true);
        $meta     = is_array($meta) ? $meta : [];
        $defaults = Settings::get_cf7_defaults();
        $tags     = $this->collect_field_names($contact_form);

        $labels = [
            'street'   => __('Ulice a číslo popisné', 'lh-seznamcz-autocomplete'),
            'city'     => __('Město', 'lh-seznamcz-autocomplete'),
            'postcode' => __('PSČ', 'lh-seznamcz-autocomplete'),
            'country'  => __('Země', 'lh-seznamcz-autocomplete'),
        ];

        $enabled = isset($meta['enabled']) ? (string) $meta['enabled'] : '';
        $global  = Settings::is_cf7_enabled()
            ? __('zapnuto', 'lh-seznamcz-autocomplete')
            : __('vypnuto', 'lh-seznamcz-autocomplete');

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME, false, true);
        ?>
        <h2><?php esc_html_e('Mapy.cz našeptávač', 'lh-seznamcz-autocomplete'); ?></h2>
        <fieldset>
            <p><?php esc_html_e('Nastavení platí jen pro tento formulář a přepisuje globální nastavení pluginu.', 'lh-seznamcz-autocomplete'); ?></p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="lh-seznamcz-cf7-enabled"><?php esc_html_e('Našeptávač', 'lh-seznamcz-autocomplete'); ?></label>
                        </th>
                        <td>
                            <select id="lh-seznamcz-cf7-enabled" name="lh_seznamcz_cf7[enabled]">
                                <option value="" <?php selected($enabled, ''); ?>>
                                    <?php
                                    /* translators: %s: zapnuto/vypnuto */
                                    printf(esc_html__('Zdědit z nastavení pluginu (%s)', 'lh-seznamcz-autocomplete'), esc_html($global));
                                    ?>
                                </option>
                                <option value="yes" <?php selected($enabled, 'yes'); ?>><?php esc_html_e('Zapnout', 'lh-seznamcz-autocomplete'); ?></option>
                                <option value="no" <?php selected($enabled, 'no'); ?>><?php esc_html_e('Vypnout', 'lh-seznamcz-autocomplete'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <?php foreach ($labels as $key => $label) : ?>
                        <tr>
                            <th scope="row">
                                <label for="lh-seznamcz-cf7-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                            </th>
                            <td>
                                <?php
                                $this->render_field_select(
                                    $key,
                                    isset($meta[$key]) ? (string) $meta[$key] : '',
                                    $tags,
                                    isset($defaults[$key]) ? (string) $defaults[$key] : ''
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="description">
                <?php esc_html_e('Nabídka obsahuje pole, která formulář opravdu má. Pokud jste tagy zrovna přidali, formulář nejdřív uložte.', 'lh-seznamcz-autocomplete'); ?>
            </p>
        </fieldset>
        <?php
    }

    /**
     * Vyrenderuje <select> s poli formulare.
     *
     * @param string $key Klic (street/city/postcode/country).
     * @param string $current Aktualne ulozena hodnota.
     * @param array $tags Nazvy poli formulare.
     * @param string $default Globalni default pro tento klic.
     */
    private function render_field_select($key, $current, array $tags, $default) {
        $inherit_label = $default !== ''
            /* translators: %s: nazev pole z globalniho nastaveni */
            ? sprintf(__('Globální default (%s)', 'lh-seznamcz-autocomplete'), $default)
            : __('Globální default (nepoužívat)', 'lh-seznamcz-autocomplete');

        // Ulozena hodnota muze byt z doby, kdy se tag jeste jmenoval jinak -
        // at ji uzivatel v nabidce vidi misto tiche ztraty.
        if ($current !== '' && $current !== '-' && !in_array($current, $tags, true)) {
            $tags[] = $current;
        }

        echo '<select id="lh-seznamcz-cf7-' . esc_attr($key) . '" name="lh_seznamcz_cf7[' . esc_attr($key) . ']">';
        echo '<option value=""' . selected($current, '', false) . '>' . esc_html($inherit_label) . '</option>';
        echo '<option value="-"' . selected($current, '-', false) . '>' . esc_html__('— nepoužívat —', 'lh-seznamcz-autocomplete') . '</option>';
        foreach ($tags as $tag) {
            echo '<option value="' . esc_attr($tag) . '"' . selected($current, $tag, false) . '>' . esc_html($tag) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Nazvy poli formulare, ktera davaji smysl jako cil vyplneni.
     *
     * @param \WPCF7_ContactForm $contact_form Formular.
     * @return string[]
     */
    private function collect_field_names($contact_form) {
        $names = [];

        if (!method_exists($contact_form, 'scan_form_tags')) {
            return $names;
        }

        foreach ($contact_form->scan_form_tags() as $tag) {
            $name = isset($tag->name) ? (string) $tag->name : '';
            $type = isset($tag->basetype) ? (string) $tag->basetype : '';

            if ($name === '' || in_array($type, ['submit', 'textarea', 'file', 'quiz', 'captchac', 'captchar', 'acceptance'], true)) {
                continue;
            }
            if (!in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param \WPCF7_ContactForm $contact_form Ukladany formular.
     */
    public function save($contact_form) {
        $form_id = $contact_form->id();

        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('wpcf7_edit_contact_form', $form_id)) {
            return;
        }

        $raw = isset($_POST['lh_seznamcz_cf7']) ? wp_unslash($_POST['lh_seznamcz_cf7']) : [];
        update_post_meta($form_id, LH_SEZNAM_CF7_META, Settings::sanitize_cf7_meta($raw));
    }

}
