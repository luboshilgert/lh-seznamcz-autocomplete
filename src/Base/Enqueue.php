<?php

namespace LHSeznamczAutocomplete\Base;

use \LHSeznamczAutocomplete\Base\BaseController;
use \LHSeznamczAutocomplete\Base\Settings;

/**
 * Description of Enqueue
 *
 * @author Lubos Hilgert <luboshilgert@gmail.com>
 */
class Enqueue extends BaseController {

    public function register() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_filter('wpcf7_form_elements', array($this, 'enqueue_for_cf7'));
    }

    /**
     * Zaregistruje vsechny assety. Na checkoutu rovnou zaradi WooCommerce variantu.
     * Pro CF7 se zarazeni provede az pri renderu formulare (filter wpcf7_form_elements).
     */
    public function register_assets() {
        if (empty($this->api_key)) {
            return;
        }

        wp_register_script('lh-seznam-autocomplete-js', $this->plugin_url . 'assets/autoComplete.min.js', [], LH_SEZNAM_VERSION, true);
        wp_register_style('lh-seznam-autocomplete-css', $this->plugin_url . 'assets/autoComplete.02.min.css', [], LH_SEZNAM_VERSION);
        wp_register_style('lh-seznam-autocomplete-custom-css', $this->plugin_url . 'assets/seznam_autocomplete.css', [], LH_SEZNAM_VERSION);

        wp_register_script('lh-seznam-autocomplete', $this->plugin_url . 'assets/seznam_autocomplete.js', array('lh-seznam-autocomplete-js'), LH_SEZNAM_VERSION, true);
        wp_localize_script('lh-seznam-autocomplete', 'lhSeznamData', array(
            'API_KEY'    => $this->api_key,
            // Comma-separated ISO kody pro parametr "locality" suggest API.
            'localities' => implode(',', Settings::get_localities()),
            'i18n'       => array(
                'placeholder' => __('Zadejte adresu...', 'lh-seznamcz-autocomplete'),
                'noResults'   => __('Žádné výsledky pro', 'lh-seznamcz-autocomplete'),
                'poweredBy'   => __('Powered by', 'lh-seznamcz-autocomplete'),
            ),
        ));

        wp_register_script('lh-seznam-woocommerce', $this->plugin_url . 'assets/seznam_woocommerce.js', $this->get_woo_deps(), LH_SEZNAM_VERSION, true);
        wp_register_script('lh-seznam-cf7', $this->plugin_url . 'assets/seznam_cf7.js', array('lh-seznam-autocomplete'), LH_SEZNAM_VERSION, true);

        // is_checkout() plati i pro blokovou pokladnu (jede podle woocommerce_checkout_page_id),
        // rozliseni classic/block resi az JS podle toho, co realne najde v DOM.
        if (Settings::is_woo_enabled() && function_exists('is_checkout') && is_checkout()) {
            $this->enqueue_core();
            wp_enqueue_script('lh-seznam-woocommerce');
        }
    }

    /**
     * Zavislosti WooCommerce skriptu. Kazda varianta pokladny potrebuje neco jineho,
     * a nacitat obe zbytecne by znamenalo tahat na stranku balik navic.
     *
     * @return string[]
     */
    private function get_woo_deps() {
        $deps = array('lh-seznam-autocomplete');

        if (!$this->is_block_checkout()) {
            // Klasicka pokladna - prepocet dopravy jde pres jQuery trigger("update").
            $deps[] = 'jquery';
            return $deps;
        }

        // Blokova pokladna - adresa se zapisuje do data storu wc/store/cart.
        $deps[] = 'wp-data';
        if (wp_script_is('wc-blocks-data-store', 'registered')) {
            // Bez teto zavislosti WooCommerce v konzoli hlasi, ze store pouziva
            // neznamy skript.
            $deps[] = 'wc-blocks-data-store';
        }

        return $deps;
    }

    /**
     * Rozliseni pokladny. Shortcode [woocommerce_checkout] = klasicka varianta,
     * cokoliv jineho (blok v obsahu i v sablone) bereme jako blokovou.
     *
     * @return bool
     */
    private function is_block_checkout() {
        $post = get_post();
        if (!$post) {
            return true;
        }
        return !has_shortcode($post->post_content, 'woocommerce_checkout');
    }

    /**
     * Zaradi nas autocomplete pri renderu CF7 formulare (jakkoliv vlozeneho).
     * Skripty jsou footer, pozdni enqueue v tele stranky tedy funguje.
     *
     * Konfigurace se predava jako data atribut pred formularem, ne pres
     * wp_localize_script - na strance muze byt vic formularu s ruznym mapovanim
     * a localize by se prepsalo.
     *
     * @param string $html HTML formulare.
     * @return string
     */
    public function enqueue_for_cf7($html) {
        if (empty($this->api_key) || !function_exists('wpcf7_get_current_contact_form')) {
            return $html;
        }

        $form = wpcf7_get_current_contact_form();
        if (!$form) {
            return $html;
        }

        $config = Settings::get_cf7_form_config($form->id());

        // Bez pole ulice nema naseptavac co spustit - nezarazujeme nic.
        if (empty($config['enabled']) || empty($config['street'])) {
            return $html;
        }

        $this->enqueue_core();
        wp_enqueue_script('lh-seznam-cf7');

        $payload = [
            'street'   => $config['street'],
            'city'     => $config['city'],
            'postcode' => $config['postcode'],
            'country'  => $config['country'],
        ];

        return '<span class="lh-seznam-cf7-config" hidden data-config="' . esc_attr(wp_json_encode($payload)) . '"></span>' . $html;
    }

    /**
     * Spolecne jadro - autoComplete knihovna, nas autocomplete a styly.
     * lh-seznam-autocomplete-js se zaradi automaticky jako zavislost.
     */
    private function enqueue_core() {
        wp_enqueue_style('lh-seznam-autocomplete-css');
        wp_enqueue_style('lh-seznam-autocomplete-custom-css');
        wp_enqueue_script('lh-seznam-autocomplete');
    }

}
