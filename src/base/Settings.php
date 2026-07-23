<?php

namespace LHSeznamczAutocomplete\Base;

/**
 * Jedine misto, ktere vi, jak jsou volby pluginu ulozene.
 *
 * Trida je zamerne cistě staticka - nema register() a neregistruje se
 * v Init::get_services(), pouzivaji ji ostatni tridy jako helper.
 *
 * @author Lubos Hilgert <luboshilgert@gmail.com>
 */
class Settings {

    /**
     * Klice mapovani CF7 poli.
     */
    const FIELD_KEYS = ['street', 'city', 'postcode', 'country'];

    /**
     * Vychozi hodnoty. Obe integrace jsou zapnute kvuli zpetne kompatibilite -
     * instalace, ktere plugin meli pred 1.1.0, se po updatu chovaji stejne.
     *
     * @return array
     */
    public static function defaults() {
        return [
            'enable_woo' => true,
            'enable_cf7' => true,
            'cf7_fields' => [
                'street'   => 'your-street',
                'city'     => 'your-city',
                'postcode' => 'your-postcode',
                'country'  => '',
            ],
        ];
    }

    /**
     * Ulozene nastaveni doplnene o defaulty.
     *
     * @return array
     */
    public static function get() {
        $defaults = self::defaults();
        $stored   = get_option(LH_SEZNAM_OPTION_SETTINGS, []);

        if (!is_array($stored)) {
            return $defaults;
        }

        $settings = array_merge($defaults, $stored);
        $settings['enable_woo'] = !empty($settings['enable_woo']);
        $settings['enable_cf7'] = !empty($settings['enable_cf7']);
        $settings['cf7_fields'] = array_merge(
            $defaults['cf7_fields'],
            is_array($settings['cf7_fields'] ?? null) ? $settings['cf7_fields'] : []
        );

        return $settings;
    }

    /**
     * @return bool
     */
    public static function is_woo_enabled() {
        $settings = self::get();
        return (bool) $settings['enable_woo'];
    }

    /**
     * @return bool
     */
    public static function is_cf7_enabled() {
        $settings = self::get();
        return (bool) $settings['enable_cf7'];
    }

    /**
     * Globalni vychozi mapovani CF7 poli.
     *
     * @return array{street: string, city: string, postcode: string, country: string}
     */
    public static function get_cf7_defaults() {
        $settings = self::get();
        return $settings['cf7_fields'];
    }

    /**
     * Vysledna konfigurace pro konkretni CF7 formular - per-form meta prekryva
     * globalni default, prazdne hodnoty se z defaultu doplni.
     *
     * @param int $form_id ID CF7 formulare.
     * @return array{enabled: bool, street: string, city: string, postcode: string, country: string}
     */
    public static function get_cf7_form_config($form_id) {
        $defaults = self::get_cf7_defaults();
        $meta     = get_post_meta((int) $form_id, LH_SEZNAM_CF7_META, true);
        $meta     = is_array($meta) ? $meta : [];

        $enabled_raw = isset($meta['enabled']) ? (string) $meta['enabled'] : '';
        if ($enabled_raw === 'yes') {
            $enabled = true;
        } elseif ($enabled_raw === 'no') {
            $enabled = false;
        } else {
            $enabled = self::is_cf7_enabled();
        }

        $config = ['enabled' => $enabled];
        foreach (self::FIELD_KEYS as $key) {
            $override = isset($meta[$key]) ? trim((string) $meta[$key]) : '';
            // "-" znamena "u tohoto formulare pole nepouzivat" - odlisuje explicitni
            // vypnuti od prazdne hodnoty, ktera dedi globalni default.
            if ($override === '-') {
                $config[$key] = '';
            } elseif ($override !== '') {
                $config[$key] = $override;
            } else {
                $config[$key] = isset($defaults[$key]) ? (string) $defaults[$key] : '';
            }
        }

        return $config;
    }

    /**
     * Sanitize callback pro register_setting().
     *
     * @param mixed $input Data z formulare.
     * @return array
     */
    public static function sanitize($input) {
        $defaults = self::defaults();

        if (!is_array($input)) {
            return $defaults;
        }

        $clean = [
            'enable_woo' => !empty($input['enable_woo']),
            'enable_cf7' => !empty($input['enable_cf7']),
            'cf7_fields' => [],
        ];

        $fields = is_array($input['cf7_fields'] ?? null) ? $input['cf7_fields'] : [];
        foreach (self::FIELD_KEYS as $key) {
            $clean['cf7_fields'][$key] = isset($fields[$key])
                ? sanitize_text_field($fields[$key])
                : '';
        }

        return $clean;
    }

    /**
     * Sanitizace per-form meta (hodnoty z CF7 editoru).
     *
     * @param mixed $input Data z formulare.
     * @return array
     */
    public static function sanitize_cf7_meta($input) {
        $input = is_array($input) ? $input : [];

        $enabled = isset($input['enabled']) ? (string) $input['enabled'] : '';
        if (!in_array($enabled, ['yes', 'no'], true)) {
            $enabled = '';
        }

        $clean = ['enabled' => $enabled];
        foreach (self::FIELD_KEYS as $key) {
            $clean[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : '';
        }

        return $clean;
    }

}
