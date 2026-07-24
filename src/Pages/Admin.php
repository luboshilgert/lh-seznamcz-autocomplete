<?php

namespace LHSeznamczAutocomplete\Pages;

use LHSeznamczAutocomplete\Base\BaseController;
use LHSeznamczAutocomplete\Base\Settings;

class Admin extends BaseController {

    public function register() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_options_page(
            __('LH Seznam.cz autocomplete', 'lh-seznamcz-autocomplete'),
            __('LH Seznam.cz autocomplete', 'lh-seznamcz-autocomplete'),
            'manage_options',
            'lh_seznamcz_autocomplete',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        require $this->plugin_path . 'templates/admin.php';
    }

    public function register_settings() {
        register_setting('lh_seznamcz_autocomplete', LH_SEZNAM_OPTION_API_KEY, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting('lh_seznamcz_autocomplete', LH_SEZNAM_OPTION_SETTINGS, [
            'type'              => 'array',
            'sanitize_callback' => array(Settings::class, 'sanitize'),
            'default'           => Settings::defaults(),
        ]);

        add_settings_section(
            'lh_seznamcz_main',
            __('Nastavení API', 'lh-seznamcz-autocomplete'),
            array($this, 'render_section'),
            'lh_seznamcz_autocomplete'
        );

        add_settings_field(
            'lh_seznamcz_api_key_field',
            __('API klíč Mapy.cz', 'lh-seznamcz-autocomplete'),
            array($this, 'render_api_key_field'),
            'lh_seznamcz_autocomplete',
            'lh_seznamcz_main'
        );

        add_settings_section(
            'lh_seznamcz_sources',
            __('Kde se má našeptávač používat', 'lh-seznamcz-autocomplete'),
            array($this, 'render_sources_section'),
            'lh_seznamcz_autocomplete'
        );

        add_settings_field(
            'lh_seznamcz_enable_woo',
            __('WooCommerce', 'lh-seznamcz-autocomplete'),
            array($this, 'render_enable_woo_field'),
            'lh_seznamcz_autocomplete',
            'lh_seznamcz_sources'
        );

        add_settings_field(
            'lh_seznamcz_enable_cf7',
            __('Contact Form 7', 'lh-seznamcz-autocomplete'),
            array($this, 'render_enable_cf7_field'),
            'lh_seznamcz_autocomplete',
            'lh_seznamcz_sources'
        );

        add_settings_section(
            'lh_seznamcz_localities',
            __('Země pro našeptávání', 'lh-seznamcz-autocomplete'),
            array($this, 'render_localities_section'),
            'lh_seznamcz_autocomplete'
        );

        add_settings_field(
            'lh_seznamcz_localities_field',
            __('Země', 'lh-seznamcz-autocomplete'),
            array($this, 'render_localities_field'),
            'lh_seznamcz_autocomplete',
            'lh_seznamcz_localities'
        );

        add_settings_section(
            'lh_seznamcz_cf7',
            __('Výchozí pole pro Contact Form 7', 'lh-seznamcz-autocomplete'),
            array($this, 'render_cf7_section'),
            'lh_seznamcz_autocomplete'
        );

        $cf7_labels = [
            'street'   => __('Ulice a číslo popisné', 'lh-seznamcz-autocomplete'),
            'city'     => __('Město', 'lh-seznamcz-autocomplete'),
            'postcode' => __('PSČ', 'lh-seznamcz-autocomplete'),
            'country'  => __('Země', 'lh-seznamcz-autocomplete'),
        ];

        foreach ($cf7_labels as $key => $label) {
            add_settings_field(
                'lh_seznamcz_cf7_' . $key,
                $label,
                array($this, 'render_cf7_field'),
                'lh_seznamcz_autocomplete',
                'lh_seznamcz_cf7',
                ['key' => $key, 'label_for' => 'lh-seznamcz-cf7-' . $key]
            );
        }
    }

    public function render_section() {
        echo '<p>' . esc_html__('Zadejte API klíč pro Mapy.cz (seznam.cz suggest API).', 'lh-seznamcz-autocomplete') . '</p>';
    }

    public function render_api_key_field() {
        $value = esc_attr(get_option(LH_SEZNAM_OPTION_API_KEY, ''));
        echo '<input type="text" name="' . esc_attr(LH_SEZNAM_OPTION_API_KEY) . '" value="' . $value . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('API klíč získáte na https://developer.mapy.com/. Doporučujeme ho tam omezit na doménu webu (referrer) nebo IP - v prohlížeči je klíč viditelný.', 'lh-seznamcz-autocomplete') . '</p>';
    }

    public function render_sources_section() {
        echo '<p>' . esc_html__('Vyberte, do kterých formulářů se má našeptávač adres zařadit. Skripty pluginu se načtou jen tam.', 'lh-seznamcz-autocomplete') . '</p>';
    }

    public function render_enable_woo_field() {
        $settings = Settings::get();
        $this->render_checkbox(
            'enable_woo',
            !empty($settings['enable_woo']),
            __('Našeptávat adresu na pokladně (klasická i bloková varianta).', 'lh-seznamcz-autocomplete'),
            class_exists('WooCommerce') ? '' : __('WooCommerce není aktivní - volba se projeví, až plugin aktivujete.', 'lh-seznamcz-autocomplete')
        );
    }

    public function render_enable_cf7_field() {
        $settings = Settings::get();
        $this->render_checkbox(
            'enable_cf7',
            !empty($settings['enable_cf7']),
            __('Našeptávat adresu ve formulářích Contact Form 7.', 'lh-seznamcz-autocomplete'),
            defined('WPCF7_VERSION') ? '' : __('Contact Form 7 není aktivní - volba se projeví, až plugin aktivujete.', 'lh-seznamcz-autocomplete')
        );
    }

    public function render_localities_section() {
        echo '<p>' . esc_html__('Vyberte země, ze kterých se mají adresy našeptávat. Mapy.cz mají adresní data jen pro tyto státy. Když nevyberete žádný, hledá se ve všech.', 'lh-seznamcz-autocomplete') . '</p>';
    }

    public function render_localities_field() {
        $selected = Settings::get_localities();

        echo '<fieldset>';
        foreach (Settings::SUPPORTED_LOCALITIES as $code => $label) {
            printf(
                '<label style="display:inline-block; min-width:9em; margin:0 1em .4em 0;"><input type="checkbox" name="%1$s[localities][]" value="%2$s" %3$s /> %4$s</label>',
                esc_attr(LH_SEZNAM_OPTION_SETTINGS),
                esc_attr($code),
                checked(in_array($code, $selected, true), true, false),
                esc_html($label)
            );
        }
        echo '</fieldset>';
    }

    public function render_cf7_section() {
        // Trida slouzi i jako kotva pro inline JS v templates/admin.php, ktery podle
        // zaskrtavatka zasedne celou sekci (h2 pred timto odstavcem, tabulka za nim).
        echo '<p class="lh-seznam-cf7-section-intro">' . esc_html__('Zadejte název (atribut name) pole z CF7 tagu, například "your-street". Prázdné pole se nevyplňuje. U konkrétního formuláře jde toto mapování přepsat přímo v jeho editoru na záložce "Mapy.cz našeptávač".', 'lh-seznamcz-autocomplete') . '</p>';
    }

    /**
     * @param array $args Argumenty z add_settings_field(), ocekava klic "key".
     */
    public function render_cf7_field($args) {
        $key      = isset($args['key']) ? $args['key'] : '';
        $defaults = Settings::get_cf7_defaults();
        $value    = isset($defaults[$key]) ? $defaults[$key] : '';

        printf(
            '<input type="text" id="lh-seznamcz-cf7-%1$s" name="%2$s[cf7_fields][%1$s]" value="%3$s" class="regular-text" />',
            esc_attr($key),
            esc_attr(LH_SEZNAM_OPTION_SETTINGS),
            esc_attr($value)
        );

        if ($key === 'street') {
            echo '<p class="description">' . esc_html__('Povinné - toto pole spouští našeptávání. Bez něj se ve formuláři nenačte nic.', 'lh-seznamcz-autocomplete') . '</p>';
        }
    }

    /**
     * Vyrenderuje zaskrtavatko z pole nastaveni.
     *
     * @param string $key Klic v poli nastaveni.
     * @param bool $checked Zda je zaskrtnute.
     * @param string $label Popisek vedle zaskrtavatka.
     * @param string $notice Volitelna poznamka pod nim.
     */
    private function render_checkbox($key, $checked, $label, $notice = '') {
        printf(
            '<label><input type="checkbox" name="%1$s[%2$s]" id="lh-seznam-%2$s" value="1" %3$s /> %4$s</label>',
            esc_attr(LH_SEZNAM_OPTION_SETTINGS),
            esc_attr($key),
            checked($checked, true, false),
            esc_html($label)
        );

        if ($notice !== '') {
            echo '<p class="description">' . esc_html($notice) . '</p>';
        }
    }

}
