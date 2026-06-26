<?php

namespace LHSeznamczAutocomplete\Pages;

use LHSeznamczAutocomplete\Base\BaseController;

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
    }

    public function render_section() {
        echo '<p>' . esc_html__('Zadejte API klíč pro Mapy.cz (seznam.cz suggest API).', 'lh-seznamcz-autocomplete') . '</p>';
    }

    public function render_api_key_field() {
        $value = esc_attr(get_option(LH_SEZNAM_OPTION_API_KEY, ''));
        echo '<input type="text" name="' . esc_attr(LH_SEZNAM_OPTION_API_KEY) . '" value="' . $value . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('API klíč získáte na https://developer.mapy.cz/', 'lh-seznamcz-autocomplete') . '</p>';
    }

}
