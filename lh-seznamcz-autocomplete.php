<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.hilgert.cz/
 * @since             1.0.0
 * @package           LH Seznam.cz autocomplete
 *
 * @wordpress-plugin
 * Plugin Name:       LH Seznam.cz autocomplete
 * Plugin URI:        https://www.hilgert.cz/
 * Description:       LH Seznam.cz autocomplete
 * Version:           1.3.0
 * Author:            Lubos Hilgert
 * Author URI:        https://www.hilgert.cz/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lh-seznamcz-autocomplete
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('LH_SEZNAM_VERSION', '1.3.0');
define('LH_SEZNAM_PLUGIN_FILE', __FILE__);
define('LH_SEZNAM_OPTION_API_KEY', 'lh_seznamcz_api_key');
define('LH_SEZNAM_OPTION_SETTINGS', 'lh_seznamcz_settings');
define('LH_SEZNAM_CF7_META', '_lh_seznamcz_cf7');

add_action('init', function () {
    load_plugin_textdomain('lh-seznamcz-autocomplete', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

if (file_exists(dirname(__FILE__) . "/vendor/autoload.php")) {
    require_once dirname(__FILE__) . "/vendor/autoload.php";
}

function activate_lh_seznamcz_autocomplete(){
    \LHSeznamczAutocomplete\Base\Activate::activate();
}
register_activation_hook(__FILE__, 'activate_lh_seznamcz_autocomplete');

function deactivate_lh_seznamcz_autocomplete(){
    \LHSeznamczAutocomplete\Base\Deactivate::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_lh_seznamcz_autocomplete');

if(class_exists('LHSeznamczAutocomplete\\Init')){
    // Az na plugins_loaded - Init::get_services() se rozhoduje podle toho,
    // jestli je aktivni Contact Form 7, a to je driv nespolehlive.
    add_action('plugins_loaded', array('LHSeznamczAutocomplete\\Init', 'register_services'));
}