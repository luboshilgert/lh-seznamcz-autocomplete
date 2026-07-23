<?php

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option('lh_seznamcz_api_key');
delete_option('lh_seznamcz_settings');

// Per-form mapovani u formularu Contact Form 7.
delete_post_meta_by_key('_lh_seznamcz_cf7');
