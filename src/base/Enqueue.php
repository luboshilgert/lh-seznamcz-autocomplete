<?php

namespace LHSeznamczAutocomplete\Base;

use \LHSeznamczAutocomplete\Base\BaseController;

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
        wp_localize_script('lh-seznam-autocomplete', 'scriptData', array(
            'API_KEY' => $this->api_key,
            'i18n'    => array(
                'placeholder' => __('Zadejte adresu...', 'lh-seznamcz-autocomplete'),
                'noResults'   => __('Žádné výsledky pro', 'lh-seznamcz-autocomplete'),
                'poweredBy'   => __('Powered by', 'lh-seznamcz-autocomplete'),
            ),
        ));

        wp_register_script('lh-seznam-woocommerce', $this->plugin_url . 'assets/seznam_woocommerce.js', array('lh-seznam-autocomplete', 'jquery'), LH_SEZNAM_VERSION, true);
        wp_register_script('lh-seznam-cf7', $this->plugin_url . 'assets/seznam_cf7.js', array('lh-seznam-autocomplete'), LH_SEZNAM_VERSION, true);

        if (function_exists('is_checkout') && is_checkout()) {
            $this->enqueue_core();
            wp_enqueue_script('lh-seznam-woocommerce');
        }
    }

    /**
     * Zaradi nas autocomplete pri renderu CF7 formulare (jakkoliv vlozeneho).
     * Skripty jsou footer, pozdni enqueue v tele stranky tedy funguje.
     *
     * @param string $html HTML formulare (vracime beze zmeny).
     * @return string
     */
    public function enqueue_for_cf7($html) {
        if (empty($this->api_key)) {
            return $html;
        }
        $this->enqueue_core();
        wp_enqueue_script('lh-seznam-cf7');
        return $html;
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
