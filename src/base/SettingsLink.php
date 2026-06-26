<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LHSeznamczAutocomplete\Base;
use \LHSeznamczAutocomplete\Base\BaseController;
/**
 * Description of SettingsLink
 *
 * @author Lubos Hilgert <luboshilgert@gmail.com>
 */
class SettingsLink extends BaseController {

    public function register() {
        add_filter("plugin_action_links_$this->plugin", array($this, 'settings_link'));
    }

    public function settings_link($links) {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=lh_seznamcz_autocomplete')) . '">' . esc_html__('Nastaveni', 'lh-seznamcz-autocomplete') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

}
