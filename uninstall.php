<?php

// If uninstall is not called from WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die();
}

global $wpdb;

// Pages
$accessRequest = get_pages(array(
    'post_type' => 'page',
    'post_status' => 'publish,private,draft',
    'number' => 1,
    'meta_key' => '_psdsgvo_access_request',
    'meta_value' => '1'
));
if (!empty($accessRequest)) {
    wp_trash_post($accessRequest[0]->ID);
}

// Options
$wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE 'psdsgvo\_%';");

// Tables
$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->base_prefix}psdsgvo_access_requests`");
$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->base_prefix}psdsgvo_delete_requests`");
$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->base_prefix}psdsgvo_consents`");
$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->base_prefix}psdsgvo_log`");

// Cronjobs
wp_clear_scheduled_hook('psdsgvo_deactivate_access_requests');
wp_clear_scheduled_hook('psdsgvo_anonymise_requests');

// Clear any cached data that has been removed
wp_cache_flush();