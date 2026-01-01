<?php

/*
 Plugin Name: PSOURCE DSGVO 
 Plugin URI:  https://power-source.github.io/ps-dsgvo/
 Description: Dieses Plugin unterstützt Website- und Webshop-Besitzer bei der Einhaltung der europäischen Datenschutzbestimmungen, die als DSGVO bekannt sind. 
 Version:     1.0.0
 Author:      DerN3rd
 Author URI:  https://github.com/Power-Source
 License:     GPL2
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
 Text Domain: psource-dsgvo
 Domain Path: /languages
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see http://www.gnu.org/licenses.
*/



namespace PSDSGVO;

// PS Update Manager - Hinweis wenn nicht installiert
add_action( 'admin_notices', function() {
    // Prüfe ob Update Manager aktiv ist
    if ( ! function_exists( 'ps_register_product' ) && current_user_can( 'install_plugins' ) ) {
        $screen = get_current_screen();
        if ( $screen && in_array( $screen->id, array( 'plugins', 'plugins-network' ) ) ) {
            // Prüfe ob bereits installiert aber inaktiv
            $plugin_file = 'ps-update-manager/ps-update-manager.php';
            $all_plugins = get_plugins();
            $is_installed = isset( $all_plugins[ $plugin_file ] );
            
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>PSOURCE MANAGER:</strong> ';
            
            if ( $is_installed ) {
                // Installiert aber inaktiv - Aktivierungs-Link
                $activate_url = wp_nonce_url(
                    admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
                    'activate-plugin_' . $plugin_file
                );
                echo sprintf(
                    __( 'Aktiviere den <a href="%s">PS Update Manager</a> für automatische Updates von GitHub.', 'psource-chat' ),
                    esc_url( $activate_url )
                );
            } else {
                // Nicht installiert - Download-Link
                echo sprintf(
                    __( 'Installiere den <a href="%s" target="_blank">PS Update Manager</a> für automatische Updates aller PSource Plugins & Themes.', 'psource-chat' ),
                    'https://github.com/Power-Source/ps-update-manager/releases/latest'
                );
            }
            
            echo '</p></div>';
        }
    }
});


use WP_Query;
use PSDSGVO\Includes\AccessRequest;
use PSDSGVO\Includes\Action;
use PSDSGVO\Includes\Ajax;
use PSDSGVO\Includes\Consent;
use PSDSGVO\Includes\Cron;
use PSDSGVO\Includes\Helper;
use PSDSGVO\Includes\Integration;
use PSDSGVO\Includes\Page;
use PSDSGVO\Includes\SessionHelper;
use PSDSGVO\Includes\Shortcode;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

define('PS_DSGVO_C_SLUG', 'ps-dsgvo-tool');
define('PS_DSGVO_C_PREFIX', 'psdsgvo');
define('PS_DSGVO_C_ROOT_FILE', __FILE__);
define('PS_DSGVO_C_BASENAME', plugin_basename(PS_DSGVO_C_ROOT_FILE));
define('PS_DSGVO_C_DIR', plugin_dir_path(PS_DSGVO_C_ROOT_FILE));
define('PS_DSGVO_C_DIR_ASSETS', PS_DSGVO_C_DIR . 'assets');
define('PS_DSGVO_C_DIR_VENDOR', PS_DSGVO_C_DIR_ASSETS . '/vendor');
define('PS_DSGVO_C_DIR_JS', PS_DSGVO_C_DIR_ASSETS . '/js');
define('PS_DSGVO_C_DIR_CSS', PS_DSGVO_C_DIR_ASSETS . '/css');
define('PS_DSGVO_C_DIR_SVG', PS_DSGVO_C_DIR_ASSETS . '/svg');
define('PS_DSGVO_C_URI', plugin_dir_url(PS_DSGVO_C_ROOT_FILE));
define('PS_DSGVO_C_URI_ASSETS', PS_DSGVO_C_URI . 'assets');
define('PS_DSGVO_C_URI_VENDOR', PS_DSGVO_C_URI_ASSETS . '/vendor');
define('PS_DSGVO_C_URI_JS', PS_DSGVO_C_URI_ASSETS . '/js');
define('PS_DSGVO_C_URI_CSS', PS_DSGVO_C_URI_ASSETS . '/css');
define('PS_DSGVO_C_URI_SVG', PS_DSGVO_C_URI_ASSETS . '/svg');

// Let's do this!
spl_autoload_register(__NAMESPACE__ . '\\autoload');

// Content Embeds Filter - blockiert externe Inhalte bis Zustimmung erteilt wird
// Muss SEHR FRÜH registriert werden, bevor the_content Filter angewendet wird
add_filter('the_content', function($content) {
    return \PSDSGVO\Includes\Extensions\ContentEmbeds::getInstance()->filterContent($content);
}, 999);
add_filter('widget_text', function($content) {
    return \PSDSGVO\Includes\Extensions\ContentEmbeds::getInstance()->filterContent($content);
}, 999);

add_action('plugins_loaded', array(PSDSGVO::getInstance(), 'init'));
add_action('wp', array(PSDSGVO::getInstance(), 'checkSession'));
add_action('save_post', array(PSDSGVO::getInstance(), 'checkIfAccessRequestPage'), 10, 3);

register_activation_hook(__FILE__, array(Action::getInstance(), 'addTagsToFields'));
register_deactivation_hook(__FILE__, array(Action::getInstance(), 'removeTagsFromFields'));

/**
 * Class PSDSGVO
 * @package PSDSGVO
 */
class PSDSGVO {

    /** @var null */
    private static $instance = null;

    public function init() {
        self::handleDatabaseTables();
        self::addConsentVersion();
        self::removeOldConsentCookies();
        
        // Erstelle Standard-Consent für Embeds wenn keine existieren
        Action::getInstance()->createDefaultEmbedConsent();
        
        // Hook: Erstelle Consents automatisch wenn Embed-Settings aktiviert werden
        add_action('update_option_psdsgvo_embed_settings_youtube', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        add_action('update_option_psdsgvo_embed_settings_vimeo', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        add_action('update_option_psdsgvo_embed_settings_google_maps', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        add_action('update_option_psdsgvo_embed_settings_twitter', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        add_action('update_option_psdsgvo_embed_settings_instagram', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        add_action('update_option_psdsgvo_embed_settings_facebook', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        add_action('update_option_psdsgvo_embed_settings_spotify', array(Action::getInstance(), 'handleEmbedSettingChange'), 10, 2);
        
        if (is_admin()) {
            Action::getInstance()->handleRedirects();
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
        }
        $action = (isset($_REQUEST['psdsgvo-action'])) ? esc_html($_REQUEST['psdsgvo-action']) : false;
        Helper::doAction($action);
        add_action('plugins_loaded', function () {
            load_plugin_textdomain(PS_DSGVO_C_SLUG, false, basename(dirname(__FILE__)) . '/languages/');
        });
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'addActionLinksToPluginPage'));
        if (is_admin()) {
            add_action('admin_init', array(Page::getInstance(), 'registerSettings'));
            add_action('admin_menu', array(Page::getInstance(), 'addAdminMenu'));
            add_action('admin_notices', array(Action::getInstance(), 'showAdminNotices'));
        }
        add_action('wp_enqueue_scripts', array($this, 'loadAssets'), 999);
        add_action('admin_enqueue_scripts', array($this, 'loadAdminAssets'), 999);
        add_action('core_version_check_query_args', array(Action::getInstance(), 'onlySendEssentialDataDuringUpdateCheck'));
        add_filter('cron_schedules', array(Cron::getInstance(), 'addCronSchedules'));
        add_action('wp_ajax_psdsgvo_process_settings', array(Ajax::getInstance(), 'processSettings'));
        add_action('wp_ajax_psdsgvo_process_action', array(Ajax::getInstance(), 'processAction'));
        add_action('wp_ajax_nopriv_psdsgvo_process_action', array(Ajax::getInstance(), 'processAction'));
        add_action('update_option_psdsgvo_settings_enable_access_request', array(Action::getInstance(), 'processToggleAccessRequest'));
        Integration::getInstance();
        if (Helper::isEnabled('enable_access_request', 'settings')) {
            add_action('init', array(Action::getInstance(), 'processEnableAccessRequest'));
            add_action('admin_notices', array(Action::getInstance(), 'showNoticesRequestUserData'));
            add_action('psdsgvo_deactivate_access_requests', array(Cron::getInstance(), 'deactivateAccessRequests'));
            add_action('psdsgvo_anonymise_requests', array(Cron::getInstance(), 'anonymiseRequests'));
            add_action('wp_ajax_psdsgvo_process_delete_request', array(Ajax::getInstance(), 'processDeleteRequest'));
            add_shortcode('psdsgvo_access_request_form', array(Shortcode::getInstance(), 'accessRequestForm'));
            if (!wp_next_scheduled('psdsgvo_deactivate_access_requests')) {
                wp_schedule_event(time(), 'hourly', 'psdsgvo_deactivate_access_requests');
            }
            if (!wp_next_scheduled('psdsgvo_anonymise_requests')) {
                wp_schedule_event(time(), 'psdsgvo-monthly', 'psdsgvo_anonymise_requests');
            }
        } else {
            if (wp_next_scheduled('psdsgvo_deactivate_access_requests')) {
                wp_clear_scheduled_hook('psdsgvo_deactivate_access_requests');
            }
            if (wp_next_scheduled('psdsgvo_anonymise_requests')) {
                wp_clear_scheduled_hook('psdsgvo_anonymise_requests');
            }
        }
        if (Consent::databaseTableExists()) {
            add_shortcode('psdsgvo_consents_settings_link', array(Shortcode::getInstance(), 'consentsSettingsLink'));
            if (Consent::isActive()) {
                add_action('wp_footer', array(Action::getInstance(), 'addConsentBar'), 1);
                add_action('wp_footer', array(Action::getInstance(), 'addConsentModal'), 999);
                add_action('wp_ajax_psdsgvo_load_consents', array(Ajax::getInstance(), 'loadConsents'));
                add_action('wp_ajax_nopriv_psdsgvo_load_consents', array(Ajax::getInstance(), 'loadConsents'));
            }
        }
        add_filter('psdsgvo_the_content', 'wptexturize');
        add_filter('psdsgvo_the_content', 'convert_smilies', 20);
        add_filter('psdsgvo_the_content', 'wpautop');
        add_filter('psdsgvo_the_content', 'shortcode_unautop');
        add_filter('psdsgvo_the_content', 'wp_filter_content_tags');
    }

    public static function checkSession() {
        global $post;

        $status = get_option('psdsgvo_data_access_request_status');

        if ($status !== 'done') {
            $ids = get_option('psdsgvo_data_access_request_ids');

            if (empty($ids)) {
                $postIdsToSave = array();

                // WP_Query arguments
                $args = array(
                    'post_type' => 'any',
                    'post_status' => array('publish'),
                    's' => '[psdsgvo_access_request_form]',
                );

                // The Query
                $query = new WP_Query($args);

                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();

                        $shortcode = has_shortcode(get_the_content(), 'psdsgvo_access_request_form');
                        if ($shortcode) {
                            array_push($postIdsToSave, intval(get_the_ID()));
                        }
                    }
                    wp_reset_postdata();
                }
                update_option('psdsgvo_data_access_request_ids', $postIdsToSave);
            }
        }

        $ids = get_option('psdsgvo_data_access_request_ids');
        if (!is_array($ids)) {
            $ids = array();
        }

        if ($post !== NULL) {
            if (in_array($post->ID, $ids)) {
                SessionHelper::startSession();
            }
        }
    }

    public static function checkIfAccessRequestPage($postId, $post, $update) {
        $ids = get_option('psdsgvo_data_access_request_ids');
        if (!is_array($ids)) {
            $ids = array();
        }
        if (!in_array($postId, $ids)) {
            if (has_shortcode($post->post_content, 'psdsgvo_access_request_form')) {
                array_push($ids, $postId);
                update_option('psdsgvo_data_access_request_ids', $ids);
            } else {
                if (in_array($postId, $ids)) {
                    foreach ($ids as $id => $value) {
                        if ($value === $postId) {
                            unset($ids[$id]);
                        }
                    }
                    update_option('psdsgvo_data_access_request_ids', $ids);
                }
            }
        }
    }

    public static function addConsentVersion() {
        $option = get_option('psdsgvo_consent_version');
        if (!$option) {
            update_option('psdsgvo_consent_version', '1');
        }
    }

    /**
     * Checks if cookie numbers is lower than the current
     * consent version. If so, removes the cookie.
     */
    public static function removeOldConsentCookies() {
        $consentVersion = get_option('psdsgvo_consent_version');
        $isMultisite = is_multisite();
        $path = '/';
        $needle = 'psdsgvo-consent-';
        $arrayNumber = 0;
        if ($isMultisite) {
            $blogDetails = get_blog_details();
            if ($blogDetails !== false) {
                $path = $blogDetails->path;
                $blogId = (string)$blogDetails->id;
                $needle = $blogId . '-psdsgvo-consent-';
            }
            $arrayNumber = 1;
        }
        foreach ($_COOKIE as $cookie => $val) {
            if ($isMultisite) {
                if (strpos($cookie, 'psdsgvo-consent-') === 0) {
                    unset($_COOKIE[$cookie]);
                    setcookie($cookie, null, -1, $path);
                }
            }
            if (strpos($cookie, $needle) !== false) {
                /**
                 * Filters cookie name for numbers to find the
                 * cookie version number. [0][0] for normal
                 * sites (psdsgvo-consent-1) or [0][1] for
                 * multisite (1-psdsgvo-consent-1).
                 */
                preg_match_all('!\d+!', $cookie, $matches);
                if (isset($matches[0][$arrayNumber]) && !empty($matches[0][$arrayNumber])) {
                    $cookieVersion = intval($matches[0][$arrayNumber]);
                    if ($cookieVersion < intval($consentVersion)) {
                        unset($_COOKIE[$cookie]);
                        setcookie($cookie, null, -1, $path);
                    } elseif ($cookieVersion > intval($consentVersion)) {
                        unset($_COOKIE[$cookie]);
                        setcookie($cookie, null, -1, $path);
                    }
                }
            }
        }
    }

    public static function handleDatabaseTables() {
        $dbVersion = get_option('psdsgvo_db_version', 0);
        if (version_compare($dbVersion, '1.8', '==')) {
            return;
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charsetCollate = $wpdb->get_charset_collate();

        // Create 'Consents' table
        if (version_compare($dbVersion, '1.0', '<')) {
            $query = "CREATE TABLE IF NOT EXISTS `" . Consent::getDatabaseTableName() . "` (
                `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                `site_id` bigint(20) NOT NULL,
                `title` text NOT NULL,
                `description` longtext NOT NULL,
                `snippet` longtext NOT NULL,
                `placement` varchar(20) NOT NULL,
                `plugins` longtext NOT NULL,
                `active` tinyint(1) DEFAULT '1' NOT NULL,
                `date_modified` timestamp DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
                `date_created` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (`ID`)
            ) $charsetCollate;";
            dbDelta($query);
            update_option('psdsgvo_db_version', '1.0');
        }

        // Add column 'wrap' to 'Consents' table
        if (version_compare($dbVersion, '1.1', '<')) {
            $query = "ALTER TABLE `" . Consent::getDatabaseTableName() . "`
            ADD column `wrap` tinyint(1) DEFAULT '1' NOT NULL AFTER `snippet`;";
            $wpdb->query($query);
            update_option('psdsgvo_db_version', '1.1');
        }

        // Add column 'required' to 'Consents' table
        if (version_compare($dbVersion, '1.2', '<')) {
            $query = "ALTER TABLE `" . Consent::getDatabaseTableName() . "`
            ADD column `required` tinyint(1) DEFAULT '0' NOT NULL AFTER `plugins`;";
            $wpdb->query($query);
            update_option('psdsgvo_db_version', '1.2');
        }

        // Create 'Log' table
        if (version_compare($dbVersion, '1.6', '<')) {
            if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->base_prefix . "psdsgvo_log'") !== $wpdb->base_prefix . 'psdsgvo_log') {
                $query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->base_prefix . "psdsgvo_log` (
		            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
		            `plugin_id` varchar(255) NULL,
		            `form_id` varchar(255) NULL,
		            `user` varchar(255) NULL,
		            `ip_address` varchar(255) NOT NULL,
		            `date_created` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		            `log` varchar(255) NULL,
		            `consent_text` varchar(255) NULL,
		            PRIMARY KEY (`ID`)
                ) $charsetCollate;";
                dbDelta($query);
                update_option('psdsgvo_db_version', '1.6');
            }
        }

        // Add column 'token' to 'Access Requests' table
        if (version_compare($dbVersion, '1.7', '<')) {
            if ($wpdb->get_var("SHOW TABLES LIKE '" . AccessRequest::getDatabaseTableName() . "'") === AccessRequest::getDatabaseTableName()) {
                if (!$wpdb->get_var("SHOW COLUMNS FROM " . AccessRequest::getDatabaseTableName() . " LIKE 'token'")) {
                    $query = "ALTER TABLE `" . AccessRequest::getDatabaseTableName() . "`
		            ADD column `token` text NOT NULL AFTER `ip_address`;";
                    $wpdb->query($query);
                }
                update_option('psdsgvo_db_version', '1.7');
            }
        }

	    // Add column 'siteId' to 'Log' table
	    if (version_compare($dbVersion, '1.8', '<')) {
		    if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->base_prefix . "psdsgvo_log'") === $wpdb->base_prefix . 'psdsgvo_log') {
			    if (!$wpdb->get_var("SHOW COLUMNS FROM " . $wpdb->base_prefix . "psdsgvo_log"  . " LIKE 'site_id'")) {
				    $query = "ALTER TABLE `" . $wpdb->base_prefix . "psdsgvo_log"  . "`
		            ADD column `site_id` bigint(20) NULL AFTER `ID`;";
				    $wpdb->query($query);
			    }
			    update_option('psdsgvo_db_version', '1.8');
		    }
	    }
    }

    /**
     * @param array $links
     * @return array
     */
    public function addActionLinksToPluginPage($links = array()) {
        $actionLinks = array(
            'settings' => '<a href="' . add_query_arg(array('page' => str_replace('-', '_', PS_DSGVO_C_SLUG)), admin_url('tools.php')) . '" aria-label="' . esc_attr__('View WP GDPR Compliance settings', PS_DSGVO_C_SLUG) . '">' . esc_html__('Settings', PS_DSGVO_C_SLUG) . '</a>',
        );
        return array_merge($actionLinks, $links);
    }

    public function loadAssets() {
        wp_register_script('psdsgvo.micromodal.js', PS_DSGVO_C_URI_VENDOR . '/micromodal/micromodal.min.js', array(), filemtime(PS_DSGVO_C_DIR_VENDOR . '/micromodal/micromodal.min.js'));
        wp_register_script('psdsgvo.postscribe.js', PS_DSGVO_C_URI_VENDOR . '/postscribe/postscribe.min.js', array(), filemtime(PS_DSGVO_C_DIR_VENDOR . '/postscribe/postscribe.min.js'));
        wp_enqueue_style('psdsgvo.css', PS_DSGVO_C_URI_CSS . '/front.min.css', array(), filemtime(PS_DSGVO_C_DIR_CSS . '/front.min.css'));
        wp_add_inline_style('psdsgvo.css', "
            div.psdsgvo .psdsgvo-switch .psdsgvo-switch-inner:before { content: '" . __('Ja', PS_DSGVO_C_SLUG) . "'; }
            div.psdsgvo .psdsgvo-switch .psdsgvo-switch-inner:after { content: '" . __('Nein', PS_DSGVO_C_SLUG) . "'; }
        ");
        $dependencies = array();
        $isMultisite = is_multisite();
        $data = array(
            'ajaxURL' => admin_url('admin-ajax.php'),
            'ajaxSecurity' => wp_create_nonce('psdsgvo'),
            'isMultisite' => $isMultisite,
            'path' => '/',
            'blogId' => '',
        );
        if (Consent::isActive()) {
            $dependencies[] = 'psdsgvo.micromodal.js';
            $dependencies[] = 'psdsgvo.postscribe.js';
            $data['consentVersion'] = get_option('psdsgvo_consent_version');
            $data['consents'] = Consent::getInstance()->getListByPlacements();
        }
        if ($isMultisite) {
            $blogDetails = get_blog_details();
            if ($blogDetails !== false) {
                $data['path'] = $blogDetails->path;
            }
            $data['blogId'] = get_current_blog_id();
        }
        if (!empty($_REQUEST['psdsgvo'])) {
            $data['token'] = esc_html($_REQUEST['psdsgvo']);  // WordPress dekodiert automatisch
        }
        wp_enqueue_script('psdsgvo.js', PS_DSGVO_C_URI_JS . '/front.min.js', $dependencies, filemtime(PS_DSGVO_C_DIR_JS . '/front.min.js'), true);
        wp_localize_script('psdsgvo.js', 'psdsgvoData', $data);
    }

    public function loadAdminAssets() {
        wp_register_style('psdsgvo.admin.codemirror.css', PS_DSGVO_C_URI_VENDOR . '/codemirror/codemirror.css', array(), filemtime(PS_DSGVO_C_DIR_VENDOR . '/codemirror/codemirror.css'));
        wp_enqueue_style('psdsgvo.admin.css', PS_DSGVO_C_URI_CSS . '/admin.min.css', array(), filemtime(PS_DSGVO_C_DIR_CSS . '/admin.min.css'));
        wp_add_inline_style('psdsgvo.admin.css', "
            div.psdsgvo .psdsgvo-switch .psdsgvo-switch-inner:before { content: '" . __('Ja', PS_DSGVO_C_SLUG) . "'; }
            div.psdsgvo .psdsgvo-switch .psdsgvo-switch-inner:after { content: '" . __('Nein', PS_DSGVO_C_SLUG) . "'; }
        ");
        wp_register_script('psdsgvo.admin.codemirror.js', PS_DSGVO_C_URI_VENDOR . '/codemirror/codemirror.js', array(), filemtime(PS_DSGVO_C_DIR_VENDOR . '/codemirror/codemirror.js'));
        wp_register_script('psdsgvo.admin.codemirror.additional.js', PS_DSGVO_C_URI_VENDOR . '/codemirror/codemirror.additional.js', array('psdsgvo.admin.codemirror.js'), filemtime(PS_DSGVO_C_DIR_VENDOR . '/codemirror/codemirror.additional.js'), true);
        wp_enqueue_script('psdsgvo.admin.js', PS_DSGVO_C_URI_JS . '/admin.min.js', array(), filemtime(PS_DSGVO_C_DIR_JS . '/admin.min.js'), true);
        wp_localize_script('psdsgvo.admin.js', 'psdsgvoData', array(
            'ajaxURL' => admin_url('admin-ajax.php'),
            'ajaxSecurity' => wp_create_nonce('psdsgvo'),
        ));
    }

    /**
     * @return null|PSDSGVO
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

/**
 * @param string $class
 */
function autoload($class = '') {
    if (!strstr($class, 'PSDSGVO')) {
        return;
    }
    $result = str_replace('PSDSGVO\\', '', $class);
    $result = str_replace('\\', '/', $result);
    require $result . '.php';
}

