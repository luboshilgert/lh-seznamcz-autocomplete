<?php

namespace LHSeznamczAutocomplete\Base;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Aktualizace pluginu z GitHub Releases.
 *
 * Plugin neni na wordpress.org, update tedy resi Plugin Update Checker -
 * cte tagy/releases z verejneho repa a nabidne novou verzi v Nastenka -> Aktualizace.
 *
 * @author Lubos Hilgert <luboshilgert@gmail.com>
 */
class Updater {

    const REPOSITORY = 'https://github.com/luboshilgert/lh-seznamcz-autocomplete/';

    public function register() {
        if (!class_exists(PucFactory::class) || !defined('LH_SEZNAM_PLUGIN_FILE')) {
            return;
        }

        $checker = PucFactory::buildUpdateChecker(
            self::REPOSITORY,
            LH_SEZNAM_PLUGIN_FILE,
            'lh-seznamcz-autocomplete'
        );

        $checker->setBranch('main');

        // Update se stahuje z prilozeneho ZIPu z release, ne z automaticky
        // generovaneho zdrojaku - ten ma spatny nazev korenove slozky.
        $api = $checker->getVcsApi();
        if ($api && method_exists($api, 'enableReleaseAssets')) {
            $api->enableReleaseAssets();
        }
    }

}
