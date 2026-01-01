<?php

namespace PSDSGVO\Includes;

use PSDSGVO\Includes\Extensions\CF7;

/**
 * Class Helper
 * @package PSDSGVO\Includes
 */
class Helper {
    /** @var null */
    private static $instance = null;

    /**
     * @return array
     */
    public static function getPluginData() {
        return get_plugin_data(PS_DSGVO_C_ROOT_FILE);
    }

    /**
     * @param string $type
     * @param array $additionalArgs
     * @return string
     */
    public static function getPluginAdminUrl($type = '', $additionalArgs = array()) {
        $args = array(
            'page' => str_replace('-', '_', PS_DSGVO_C_SLUG)
        );
        if (!empty($type)) {
            $args['type'] = esc_html($type);
        }
        if (!empty($additionalArgs)) {
            $args = array_merge($args, $additionalArgs);
        }
        $url = add_query_arg($args,
            admin_url('tools.php')
        );
        return $url;
    }

    /**
     * @param string $action
     */
    public static function doAction($action = '') {
        if (!empty($action)) {
            switch ($action) {
                case 'create_request_tables' :
                    Helper::createUserRequestDataTables();
                    wp_safe_redirect(Helper::getPluginAdminUrl());
                    die();
                    break;
            }
        }
    }

    /**
     * @param string $plugin
     * @return mixed
     */
    public static function getAllowedHTMLTags($plugin = '') {
        switch ($plugin) {
            case CF7::ID :
                $output = '';
                break;
            default :
                $output = array(
                    'a' => array(
                        'class' => array(),
                        'href' => array(),
                        'hreflang' => array(),
                        'title' => array(),
                        'target' => array(),
                        'rel' => array(),
                    ),
                    'br' => array(),
                    'em' => array(),
                    'strong' => array(),
                    'u' => array(),
                    'strike' => array(),
                    'span' => array(
                        'class' => array(),
                    ),
                );
                break;
        }
        return apply_filters('psdsgvo_allowed_html_tags', $output, $plugin);
    }

    /**
     * @param string $plugin
     * @return string
     */
    public static function getAllowedHTMLTagsOutput($plugin = '') {
        $allowedTags = self::getAllowedHTMLTags($plugin);
        $output = '<div class="psdsgvo-information">';
        if (!empty($allowedTags)) {
            $tags = '%privacy_policy%';
            foreach ($allowedTags as $tag => $attributes) {
                $tags .= ' <' . $tag;
                if (!empty($attributes)) {
                    foreach ($attributes as $attribute => $data) {
                        $tags .= ' ' . $attribute . '=""';
                    }
                }
                $tags .= '>';
            }
            $output .= sprintf(
                __('Du kannst verwenden: %s', PS_DSGVO_C_SLUG),
                sprintf('<pre>%s</pre>', esc_html($tags))
            );
        } else {
            $output .= sprintf(
                '<strong>%s:</strong> %s',
                strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                __('Aufgrund von Plugin-Einschränkungen ist kein HTML zulässig.', PS_DSGVO_C_SLUG)
            );
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * @param string $notice
     */
    public static function showAdminNotice($notice = '') {
        if (!empty($notice)) {
            $type = 'success';
            $dismissible = true;
            $message = '';
            switch ($notice) {
                case 'psdsgvo-consent-updated' :
                    $message = __('Die Zustimmung wurde erfolgreich aktualisiert.', PS_DSGVO_C_SLUG);
                    break;
                case 'psdsgvo-consent-added' :
                    $message = __('Die Zustimmung wurde erfolgreich hinzugefügt.', PS_DSGVO_C_SLUG);
                    break;
                case 'psdsgvo-consent-removed' :
                    $message = __('Die Zustimmung wurde erfolgreich entfernt.', PS_DSGVO_C_SLUG);
                    break;
                case 'psdsgvo-consent-not-found' :
                    $type = 'error';
                    $message = __('Diese Zustimmung konnte nicht gefunden werden.', PS_DSGVO_C_SLUG);
                    break;
                case 'psdsgvo-cookie-bar-reset' :
                    $message = __('Die Zustimmungsleiste wurde zurückgesetzt.', PS_DSGVO_C_SLUG);
                    break;
            }
            if (!empty($message)) {
                printf(
                    '<div class="notice notice-%s %s"><p>%s</p></div>',
                    $type,
                    (($dismissible) ? 'is-dismissible' : ''),
                    $message
                );
            }
        }
    }

    /**
     * @param string $plugin
     * @return string
     */
    public static function getNotices($plugin = '') {
        $output = '';
        switch ($plugin) {
            case 'wordpress' :
                if (self::isPluginEnabled('jetpack/jetpack.php')) {
                    $activeModules = (array)get_option('jetpack_active_modules');
                    if (in_array('comments', $activeModules)) {
                        $output .= sprintf(
                            '<strong>%s:</strong> %s',
                            strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                            __('Bitte deaktiviere das benutzerdefinierte Kommentarformular in Jetpack, um Deine GDPR-Konformität mit WordPress-Kommentaren zu gewährleisten.', PS_DSGVO_C_SLUG)
                        );
                    }
                }
                break;
        }
        return $output;
    }

    /**
     * @return array
     */
    public static function getCheckList() {
        return array(
            'contact_form' => array(
                'label' => __('Hast Du ein Kontaktformular?', PS_DSGVO_C_SLUG),
                'description' => __('Stelle sicher, dass Du ein Kontrollkästchen hinzufügst, in dem der Benutzer des Formulars speziell gefragt wird, ob er damit einverstanden ist, dass Du seine persönlichen Daten speicherst und verwendest, um wieder mit ihm in Kontakt zu treten. Das Kontrollkästchen muss standardmäßig deaktiviert sein. Erwähne auch, ob Du die Daten an Dritte sendest oder weitergibst und welche.', PS_DSGVO_C_SLUG),
            ),
            'comments' => array(
                'label' => __('Können Besucher irgendwo auf Deiner Website Kommentare abgeben?', PS_DSGVO_C_SLUG),
                'description' => __('Stelle sicher, dass Du ein Kontrollkästchen hinzufügst, in dem der Benutzer des Kommentarbereichs speziell gefragt wird, ob er damit einverstanden ist, seine an die E-Mail-Adresse angehängte Nachricht zu speichern, die er zum Kommentieren verwendet hat. Das Kontrollkästchen muss standardmäßig deaktiviert sein. Erwähne auch, ob Du die Daten an Dritte sendest oder weitergibst und welche.', PS_DSGVO_C_SLUG),
            ),
            'webshop' => array(
                'label' => __('Ist auf Deiner Webseite oder in Deinem Webshop ein Bestellformular vorhanden?', PS_DSGVO_C_SLUG),
                'description' => __('Stelle sicher, dass Du ein Kontrollkästchen hinzufügst, in dem der Benutzer des Formulars speziell gefragt wird, ob er damit einverstanden ist, dass Du seine persönlichen Daten speicherst und zum Versenden der Bestellung verwendest. Dies kann nicht dasselbe Kontrollkästchen sein wie das Kontrollkästchen Datenschutz-Verantwortlichkeiten, das Du bereits eingerichtet haben solltest. Das Kontrollkästchen muss standardmäßig deaktiviert sein. Erwähne auch, ob Du die Daten an Dritte sendest oder weitergibst und welche.', PS_DSGVO_C_SLUG),
            ),
            'forum' => array(
                'label' => __('Bietest Du ein Forum oder ein Message Board an?', PS_DSGVO_C_SLUG),
                'description' => __('Stelle sicher, dass Du ein Kontrollkästchen hinzufügst, in dem Forum-/Board-Benutzer speziell gefragt werden, ob sie damit einverstanden sind, dass Du ihre persönlichen Informationen und Nachrichten speicherst und verwendest. Das Kontrollkästchen muss standardmäßig deaktiviert sein. Erwähne auch, ob Du die Daten an Dritte sendest oder weitergibst und welche.', PS_DSGVO_C_SLUG),
            ),
            'chat' => array(
                'label' => __('Können Besucher direkt mit Deinem Unternehmen chatten?', PS_DSGVO_C_SLUG),
                'description' => __('Stelle sicher, dass Du ein Kontrollkästchen hinzufügst, in dem Chat-Benutzer speziell gefragt werden, ob sie damit einverstanden sind, dass Du ihre persönlichen Informationen und Nachrichten speicherst und verwendest. Das Kontrollkästchen muss standardmäßig deaktiviert sein. Wir empfehlen außerdem anzugeben, wie lange Du Chat-Nachrichten speicherst oder alle innerhalb von 24 Stunden löschen. Erwähne auch, ob Du die Daten an Dritte sendest oder weitergibst und welche.', PS_DSGVO_C_SLUG),
            ),
        );
    }

    /**
     * @param string $plugin
     * @return bool
     */
    public static function isPluginEnabled($plugin = '') {
        $activatePlugins = (array)self::getActivePlugins();
        return (in_array($plugin, $activatePlugins));
    }

    /**
     * @param string $option
     * @param string $type
     * @return bool
     */
    public static function isEnabled($option = '', $type = 'integrations') {
        return filter_var(get_option(PS_DSGVO_C_PREFIX . '_' . $type . '_' . $option, false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param bool $showPluginData
     * @return array
     */
    public static function getActivePlugins($showPluginData = false) {
        $activePlugins = (array)get_option('active_plugins', array());
        $activeNetworkPlugins = (is_multisite()) ? (array)get_site_option('active_sitewide_plugins', array()) : array();
        if (!empty($activeNetworkPlugins)) {
            foreach ($activeNetworkPlugins as $file => $timestamp) {
                if (!in_array($file, $activePlugins)) {
                    $activePlugins[] = $file;
                }
            }
        }

        // Remove this plugin from array
        $key = array_search(PS_DSGVO_C_BASENAME, $activePlugins);
        if ($key !== false) {
            unset($activePlugins[$key]);
        }

        if ($showPluginData) {
            foreach ($activePlugins as $key => $file) {
                $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/' . $file);
                $data = array(
                    'basename' => plugin_basename($file)
                );
                if (isset($pluginData['Name'])) {
                    $data['slug'] = sanitize_title($pluginData['Name']);
                    $data['name'] = $pluginData['Name'];
                }
                if (isset($pluginData['Description'])) {
                    $data['description'] = $pluginData['Description'];
                }
                $activePlugins[$key] = $data;
            }
        }

        return $activePlugins;
    }

    /**
     * @return array
     */
    public static function getActivatedPlugins() {
        $output = array();
        $activePlugins = self::getActivePlugins();
        // Loop through supported plugins
        foreach (Integration::getSupportedPlugins() as $plugin) {
            if (in_array($plugin['file'], $activePlugins)) {
                if (is_admin()) {
                    $plugin['supported'] = true;
                    if (isset($plugin['supported_version'])) {
                        $pluginData = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin['file']);
                        if (!empty($pluginData['Version']) && $pluginData['Version'] < $plugin['supported_version']) {
                            $plugin['supported'] = false;
                        }
                    }
                }
                $output[] = $plugin;
            }
        }

        // Loop through supported WordPress functionality
        foreach (Integration::getSupportedWordPressFunctionality() as $wp) {
            $wp['supported'] = true;
            $output[] = $wp;
        }

        return $output;
    }

    /**
     * @return array
     */
    public static function getEnabledPlugins() {
        $output = array();
        foreach (self::getActivatedPlugins() as $plugin) {
            if (self::isEnabled($plugin['id'])) {
                $output[] = $plugin;
            }
        }
        return $output;
    }

    /**
     * @return bool
     */
    public static function hasMailPluginInstalled() {
        foreach (self::getActivePlugins() as $activePlugin) {
            if (strpos(strtolower($activePlugin), 'mail') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $data
     * @return string
     */
    public function sanitizeData($data) {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = sanitize_text_field($value);
            }
        } else {
            $data = sanitize_text_field($data);
        }
        return $data;
    }

    /**
     * @param int $timestamp
     * @return \DateTime
     */
    public static function localDateTime($timestamp = 0) {
        $gmtOffset = get_option('gmt_offset', '');
        if ($gmtOffset !== '') {
            $negative = ($gmtOffset < 0);
            $gmtOffset = str_replace('-', '', $gmtOffset);
            $hour = floor($gmtOffset);
            $minutes = ($gmtOffset - $hour) * 60;
            if ($negative) {
                $hour = '-' . $hour;
                $minutes = '-' . $minutes;
            }
            $date = new \DateTime(null, new \DateTimeZone('UTC'));
            $date->setTimestamp($timestamp);
            $date->modify($hour . ' hour');
            $date->modify($minutes . ' minutes');
        } else {
            $date = new \DateTime(null, new \DateTimeZone(get_option('timezone_string', 'UTC')));
            $date->setTimestamp($timestamp);
        }
        return new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
    }

    /**
     * @param string $format
     * @param int $timestamp
     * @return string
     */
    public static function localDateFormat($format = '', $timestamp = 0) {
        $date = self::localDateTime($timestamp);
        return date_i18n($format, $date->getTimestamp(), true);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $more
     * @return string
     */
    public static function shortenStringByWords($string = '', $length = 20, $more = '...') {
        $words = preg_split("/[\n\r\t ]+/", $string, $length + 1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > $length) {
            array_pop($words);
            $output = implode(' ', $words) . $more;
        } else {
            $output = implode(' ', $words);
        }
        return $output;
    }

    /**
     * Ensures an ip address is both a valid IP and does not fall within
     * a private network range.
     *
     * @param string $ipAddress
     * @return bool
     */
    public static function validateIpAddress($ipAddress = '') {
        if (strtolower($ipAddress) === 'unknown') {
            return false;
        }
        // Generate ipv4 network address
        $ipAddress = ip2long($ipAddress);
        // If the ip is set and not equivalent to 255.255.255.255
        if ($ipAddress !== false && $ipAddress !== -1) {
            /**
             * Make sure to get unsigned long representation of ip
             * due to discrepancies between 32 and 64 bit OSes and
             * signed numbers (ints default to signed in PHP)
             */
            $ipAddress = sprintf('%u', $ipAddress);
            // Do private network range checking
            if ($ipAddress >= 0 && $ipAddress <= 50331647) return false;
            if ($ipAddress >= 167772160 && $ipAddress <= 184549375) return false;
            if ($ipAddress >= 2130706432 && $ipAddress <= 2147483647) return false;
            if ($ipAddress >= 2851995648 && $ipAddress <= 2852061183) return false;
            if ($ipAddress >= 2886729728 && $ipAddress <= 2887778303) return false;
            if ($ipAddress >= 3221225984 && $ipAddress <= 3221226239) return false;
            if ($ipAddress >= 3232235520 && $ipAddress <= 3232301055) return false;
            if ($ipAddress >= 4294967040) return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public static function getClientIpAddress() {
        // Check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && self::validateIpAddress($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check if multiple ips exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $listOfIpAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($listOfIpAddresses as $ipAddress) {
                    $ipAddress = trim($ipAddress);
                    if (self::validateIpAddress($ipAddress)) {
                        return $ipAddress;
                    }
                }
            } else {
                if (self::validateIpAddress($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED']) && self::validateIpAddress($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && self::validateIpAddress($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && self::validateIpAddress($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if (!empty($_SERVER['HTTP_FORWARDED']) && self::validateIpAddress($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        // Return unreliable ip since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    public static function checkIpAddress($ipAddress = '') {
        return self::getClientIpAddress() === $ipAddress;
    }

    /**
     * @param string $type
     * @param int $siteId
     * @return string
     */
    public static function getSiteData($type = '', $siteId = 0) {
        $output = '';
        if (!empty($type)) {
            $output = (!empty($siteId) && is_multisite()) ? get_blog_option($siteId, $type) : get_option($type);
        }
        return $output;
    }

    /**
     * This function returns all available options used by the PSDSGVO plugin.
     * NOTE: Keep this list updated in case of newly added/updated options.
     *
     * @return array
     */
    public static function getAvailableOptions() {
        $output = array();

        // Settings for activated plugins
        $activatedPlugins = Helper::getActivatedPlugins();
        foreach ($activatedPlugins as $plugin) {
            $output[] = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'];
            $output[] = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_form_text';
            $output[] = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_error_message';
            switch ($plugin['id']) {
                case 'gravity-forms' :
                case 'contact-form-7' :
                    $output[] = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_forms';
                    break;
            }
            switch ($plugin['id']) {
                case 'gravity-forms' :
                case 'woocommerce' :
                case 'wordpress' :
                    $output[] = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_required_message';
                    break;
            }
        }

        // Settings for the checklist
        foreach (Helper::getCheckList() as $id => $check) {
            $output[] = PS_DSGVO_C_PREFIX . '_general_' . $id;
        }

        // Settings for embed content blocking
        $embedSettings = \PSDSGVO\Includes\Extensions\ContentEmbeds::getInstance();
        foreach ($embedSettings->getEmbedTypes() as $typeId => $config) {
            $output[] = PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId;
            $output[] = PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId . '_message';
        }

        // Settings for the general things
        $output[] = PS_DSGVO_C_PREFIX . '_settings_privacy_policy_page';
        $output[] = PS_DSGVO_C_PREFIX . '_settings_privacy_policy_text';
        $output[] = PS_DSGVO_C_PREFIX . '_settings_enable_access_request';
        if (Helper::isEnabled('enable_access_request', 'settings')) {
            $output[] = PS_DSGVO_C_PREFIX . '_settings_access_request_page';
            $output[] = PS_DSGVO_C_PREFIX . '_settings_access_request_form_checkbox_text';
            $output[] = PS_DSGVO_C_PREFIX . '_settings_delete_request_form_explanation_text';
        }
        $output[] = PS_DSGVO_C_PREFIX . '_settings_consents_modal_title';
        $output[] = PS_DSGVO_C_PREFIX . '_settings_consents_modal_explanation_text';
        $output[] = PS_DSGVO_C_PREFIX . '_settings_consents_bar_explanation_text';

        return $output;
    }

    /**
     * @return bool|\WP_Post
     */
    public static function getAccessRequestPage() {
        $output = false;
        $option = get_option(PS_DSGVO_C_PREFIX . '_settings_access_request_page', 0);
        if (!empty($option)) {
            $output = get_post($option);
        } else {
            $page = get_pages(array(
                'post_type' => 'page',
                'post_status' => 'publish,private,draft',
                'number' => 1,
                'meta_key' => '_psdsgvo_access_request',
                'meta_value' => '1'
            ));
            if (!empty($page)) {
                /** @var \WP_Post $output */
                $output = $page[0];
            }
        }
        return $output;
    }

    /**
     * Function resets the cookie bar for all users, this will happen on button trigger & when new Consent has been added.
     */
    public static function resetCookieBar() {
        $consentVersion = get_option('psdsgvo_consent_version');
        $consentVersion += 1;
        update_option('psdsgvo_consent_version', $consentVersion);
    }

    /**
     * @return array
     */
    public static function getRequiredConsentIds() {
        $output = array();
        $requiredConsents = Consent::getInstance()->getList(array(
            'required' => array(
                'value' => 1,
            ),
            'active' => array(
                'value' => 1
            ),
        ));
        if (!empty($requiredConsents)) {
            foreach ($requiredConsents as $requiredConsent) {
                $output[] = intval($requiredConsent->getId());
            }
        }
        return $output;
    }

    /**
     * @return array|bool
     */
    public static function getConsentIdsByCookie() {
        $output = array();
        $requiredConsents = Consent::getInstance()->getList(array(
            'required' => array(
                'value' => 1
            ),
            'active' => array(
                'value' => 1
            )
        ));
        $consentVersion = get_option('psdsgvo_consent_version');
        $multiSite = is_multisite();
        if ($multiSite) {
            $blogId = get_current_blog_id();
            $consents = (!empty($_COOKIE[$blogId . '-psdsgvo-consent-' . $consentVersion])) ? esc_html($_COOKIE[$blogId . '-psdsgvo-consent-' . $consentVersion]) : '';
        } else {
            $consents = (!empty($_COOKIE['psdsgvo-consent-' . $consentVersion])) ? esc_html($_COOKIE['psdsgvo-consent-' . $consentVersion]) : '';
        }
        if (!empty($requiredConsents)) {
            foreach ($requiredConsents as $requiredConsent) {
                $output[] = intval($requiredConsent->getId());
            }
        }
        if (!empty($consents)) {
            switch ($consents) {
                case 'decline' :
                    break;
                case 'accept' :
                    $consents = Consent::getInstance()->getList(array(
                        'required' => array(
                            'value' => 0
                        ),
                        'active' => array(
                            'value' => 1
                        )
                    ));
                    foreach ($consents as $consent) {
                        $output[] = intval($consent->getId());
                    }
                    break;
                default :
                    $consents = explode(',', $consents);
                    foreach ($consents as $id) {
                        if (is_numeric($id) && Consent::getInstance()->exists($id)) {
                            $output[] = intval($id);
                        }
                    }
                    break;
            }
        }
        return $output;
    }

    public static function createUserRequestDataTables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charsetCollate = $wpdb->get_charset_collate();
        $query = "CREATE TABLE IF NOT EXISTS `" . AccessRequest::getDatabaseTableName() . "` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `site_id` bigint(20) NOT NULL,
            `email_address` varchar(100) NOT NULL,
            `session_id` varchar(255) NOT NULL,
            `ip_address` varchar(100) NOT NULL,
            `token` text NOT NULL,
            `expired` tinyint(1) DEFAULT '0' NOT NULL,
            `date_created` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (`ID`)
        ) $charsetCollate;";
        dbDelta($query);
        $query = "CREATE TABLE IF NOT EXISTS `" . DeleteRequest::getDatabaseTableName() . "` (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `site_id` bigint(20) NOT NULL,
            `access_request_id` bigint(20) NOT NULL,
            `session_id` varchar(255) NOT NULL,
            `ip_address` varchar(100) NOT NULL,
            `data_id` bigint(20) NOT NULL,
            `type` varchar(255) NOT NULL,
            `processed` tinyint(1) DEFAULT '0' NOT NULL,
            `date_created` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (`ID`)
        ) $charsetCollate;";
        dbDelta($query);
    }

    /**
     * @param array $filters
     * @param bool $grouped
     * @return string
     */
    public static function getQueryByFilters($filters = array(), $grouped = false) {
        $output = '';
        if (!empty($filters)) {
            $count = 0;
            foreach ($filters as $column => $filter) {
                if (isset($filter['columns'])) {
                    $output .= " AND ( ";
                    $output .= trim(self::getQueryByFilters($filter['columns'], true));
                    $output .= " )";
                } else {
                    $value = (isset($filter['value'])) ? $filter['value'] : false;
                    if ($value !== false) {
                        $or = (isset($filter['or']) && filter_var($filter['or'], FILTER_VALIDATE_BOOLEAN)) ? 'OR' : 'AND';
                        $or = ($grouped === true && $count === 0) ? '' : $or;
                        $compare = (isset($filter['compare'])) ? $filter['compare'] : '=';
                        $wildcard = (isset($filter['wildcard']) && filter_var($filter['wildcard'], FILTER_VALIDATE_BOOLEAN)) ? '%' : '';
                        if (($compare === 'IN' || $compare === 'NOT IN') && is_array($value)) {
                            $in = '';
                            foreach ($value as $key => $data) {
                                $in .= ($key !== 0) ? ', ' : '';
                                $in .= (is_numeric($data)) ? $data : "'" . $data . "'";
                            }
                            $value = '(' . $in . ')';
                            $output .= " $or `$column` $compare $wildcard$value$wildcard";
                        } else {
                            $output .= " $or `$column` $compare '$wildcard$value$wildcard'";
                        }
                    }
                }
                $count++;
            }
        }
        return $output;
    }

    /**
     * @param $email
     *
     * @return null|string
     */
    public static function anonymizeEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailParts = explode('@', $email);
            $localPart = $emailParts[0];
            if (strlen($localPart) > 1 && strlen($localPart) < 4) {
                $localPart = substr_replace($localPart, '*', strlen($localPart) - 1);
            } else if (strlen($localPart) > 3 && strlen($localPart) < 6) {
                $localPart = substr_replace($localPart, '**', strlen($localPart) - 2);
            } else if (strlen($localPart) > 5) {
                $localPart = substr_replace($localPart, '***', strlen($localPart) - 3);
            } else {
                $domain = $emailParts[1];
                $domainName = explode('.', $domain);
                $anonymisedDomain = str_replace($domainName[0], '***', $domainName[0]);
            }

            if (isset($domainName) && isset($anonymisedDomain)) {
                return $localPart . '@' . $anonymisedDomain . '.' . $domainName[1];
            } else {
                return $localPart . '@' . $emailParts[1];
            }
        } else {
            return NULL;
        }
    }

    /**
     * @param $ip
     *
     * @return null|string
     */
    public static function anonymizeIP($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $lastDot = strrpos($ip, '.') + 1;
                return substr($ip, 0, $lastDot)
                    . str_repeat('*', strlen($ip) - $lastDot);
            } else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $lastColon = strrpos($ip, ':') + 1;
                return substr($ip, 0, $lastColon)
                    . str_repeat('*', strlen($ip) - $lastColon);
            } else {
                return NULL;
            }
        } else {
            return NULL;
        }
    }

    /**
     * @return null|Helper
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get Privacy Policy URL
     * @return string
     */
    public static function getPrivacyPolicyUrl() {
        $enableExternalLink = Helper::isEnabled('enable_privacy_policy_extern', 'settings');
        
        if ($enableExternalLink) {
            $externalLink = get_option(PS_DSGVO_C_PREFIX . '_settings_privacy_policy_link', '');
            if (!empty($externalLink)) {
                return $externalLink;
            }
        }
        
        $pageId = get_option(PS_DSGVO_C_PREFIX . '_settings_privacy_policy_page', 0);
        if (!empty($pageId)) {
            $url = get_permalink($pageId);
            if ($url) {
                return $url;
            }
        }
        
        // Fallback to WordPress privacy policy page
        if (function_exists('get_privacy_policy_url')) {
            $wpPrivacyUrl = get_privacy_policy_url();
            if ($wpPrivacyUrl) {
                return $wpPrivacyUrl;
            }
        }
        
        return '#';
    }
}
