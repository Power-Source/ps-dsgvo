<?php

namespace PSDSGVO\Includes;

/**
 * Class Ajax
 * @package PSDSGVO\Includes
 */
class Ajax {
    /** @var null */
    private static $instance = null;

    public function processSettings() {
        check_ajax_referer('psdsgvo', 'security');

        $output = array(
            'message' => '',
            'error' => '',
        );
        $data = (isset($_POST['data']) && (is_array($_POST['data']) || is_string($_POST['data']))) ? $_POST['data'] : false;
        if (is_string($data)) {
            $data = json_decode(stripslashes($data), true);
        }

        if (!$data) {
            $output['error'] = __('Fehlende Daten.', PS_DSGVO_C_SLUG);
        }

        if (empty($output['error'])) {
            $option = (isset($data['option']) && is_string($data['option'])) ? esc_html($data['option']) : false;
            $value = (isset($data['value'])) ? self::sanitizeValue($data['value']) : false;
            $enabled = (isset($data['enabled'])) ? filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) : false;
            $append = (isset($data['append'])) ? filter_var($data['append'], FILTER_VALIDATE_BOOLEAN) : false;

            if (!$option) {
                $output['error'] = __('Fehlender Optionsname.', PS_DSGVO_C_SLUG);
            }

            if (!current_user_can('manage_options')) {
                $output['error'] = __('Du darfst keine Einstellungen verwalten.', PS_DSGVO_C_SLUG);
            }

            if (!in_array($option, Helper::getAvailableOptions())) {
                $output['error'] = __('Du darfst diese Einstellung nicht verwalten.', PS_DSGVO_C_SLUG);
            }

            if (!isset($data['value'])) {
                $output['error'] = __('Fehlender Wert.', PS_DSGVO_C_SLUG);
            }

            // Let's do this!
            if (empty($output['error'])) {
                if ($append) {
                    $values = (array)get_option($option, array());
                    if ($enabled) {
                        if (!in_array($value, $values)) {
                            $values[] = $value;
                        }
                    } else {
                        $index = array_search($value, $values);
                        if ($index !== false) {
                            unset($values[$index]);
                        }
                    }
                    $value = $values;
                } else {
                    if (isset($data['enabled'])) {
                        $value = $enabled;
                    }
                }
                update_option($option, $value);
                do_action($option, $value);
            }
        }

        header('Content-type: application/json');
        echo json_encode($output);
        die();
    }

    public function processAction() {
        check_ajax_referer('psdsgvo', 'security');

        $output = array(
            'message' => '',
            'error' => '',
        );
        $data = (isset($_POST['data']) && (is_array($_POST['data']) || is_string($_POST['data']))) ? $_POST['data'] : false;
        if (is_string($data)) {
            $data = json_decode(stripslashes($data), true);
        }
        $type = (isset($data['type']) && is_string($data['type'])) ? esc_html($data['type']) : false;

        if (!$data) {
            $output['error'] = __('Fehlende Daten.', PS_DSGVO_C_SLUG);
        }

        if (!$type) {
            $output['error'] = __('Fehlender Typ.', PS_DSGVO_C_SLUG);
        }

        if (empty($output['error'])) {
            switch ($type) {
                case 'access_request' :
                    if (Helper::isEnabled('enable_access_request', 'settings')) {
                        $emailAddress = (isset($data['email']) && is_email($data['email'])) ? $data['email'] : false;
                        $consent = (isset($data['consent'])) ? filter_var($data['consent'], FILTER_VALIDATE_BOOLEAN) : false;

                        if (!$emailAddress) {
                            $output['error'] = __('Fehlende oder falsche E-Mail-Adresse.', PS_DSGVO_C_SLUG);
                        }

                        if (!$consent) {
                            $output['error'] = __('Du musst das Kontrollkästchen Datenschutz akzeptieren.', PS_DSGVO_C_SLUG);
                        }

                        // Let's do this!
                        if (empty($output['error'])) {
                            if (!AccessRequest::getInstance()->existsByEmailAddress($emailAddress, true)) {
                                $request = new AccessRequest();
                                $request->setSiteId(get_current_blog_id());
                                $request->setEmailAddress($emailAddress);
                                $request->setSessionId(SessionHelper::getSessionId());
                                $request->setIpAddress(Helper::getClientIpAddress());
                                $request->setToken(substr(md5(openssl_random_pseudo_bytes(20)), -32));
                                $request->setExpired(0);
                                $id = $request->save();
                                if ($id !== false) {
                                    $page = Helper::getAccessRequestPage();
                                    if (!empty($page)) {
                                        $deleteRequestPage = sprintf(
                                            '<a target="_blank" href="%s">%s</a>',
                                            add_query_arg(
                                                array(
                                                    'psdsgvo' => urlencode($request->getToken())
                                                ),
                                                get_permalink($page)
                                            ),
                                            __('Seite', PS_DSGVO_C_SLUG)
                                        );
                                        $siteName = Helper::getSiteData('blogname', $request->getSiteId());
                                        $siteEmail = Helper::getSiteData('admin_email', $request->getSiteId());
                                        $siteUrl = Helper::getSiteData('siteurl', $request->getSiteId());
                                        $subject = apply_filters(
                                            'psdsgvo_access_request_mail_subject',
                                            sprintf(__('%s - Deine Datenanfrage', PS_DSGVO_C_SLUG), $siteName),
                                            $request,
                                            $siteName
                                        );

                                        $message = sprintf(
                                                __('Du hast auf %s angefordert, auf Deine Daten zuzugreifen.', PS_DSGVO_C_SLUG),
                                                sprintf('<a target="_blank" href="%s">%s</a>', $siteUrl, $siteName)
                                            ) . '<br /><br />';
                                        $message .= sprintf(
                                                __('Bitte besuche diese %s, um die mit der E-Mail-Adresse %s verknüpften Daten anzuzeigen.', PS_DSGVO_C_SLUG),
                                                $deleteRequestPage,
                                                $emailAddress
                                            ) . '<br /><br />';
                                        $message .= __('Diese Seite ist 24 Stunden lang verfügbar und kann nur von demselben Gerät, derselben IP-Adresse und derselben Browsersitzung aus erreicht werden, von denen aus sie angefordert wurde.', PS_DSGVO_C_SLUG) . '<br /><br />';
                                        $message .= sprintf(
                                            __('Wenn Dein Link ungültig ist, kannst Du nach 24 Stunden eine neue Anfrage ausfüllen: %s.', PS_DSGVO_C_SLUG),
                                            sprintf(
                                                '<a target="_blank" href="%s">%s</a>',
                                                get_permalink($page),
                                                get_the_title($page)
                                            )
                                        );
                                        $message = apply_filters('psdsgvo_access_request_mail_content', $message, $request, $deleteRequestPage);
                                        $headers = array(
                                            'Content-Type: text/html; charset=UTF-8',
                                            "From: $siteName <$siteEmail>"
                                        );
                                        $response = wp_mail($emailAddress, $subject, $message, $headers);
                                        if ($response !== false) {
                                            $output['message'] = __('Erfolg. Du erhältst in Kürze eine E-Mail mit Deinen Daten.', PS_DSGVO_C_SLUG);
                                        }
                                    }
                                } else {
                                    $output['error'] = __('Beim Speichern der Anfrage ist ein Fehler aufgetreten. Bitte versuche es erneut.', PS_DSGVO_C_SLUG);
                                }
                            } else {
                                $output['error'] = __('Du hast Deine Daten bereits angefordert. Bitte überprüfe Deine Mailbox. Nach 24 Stunden kannst Du eine neue Anfrage stellen.', PS_DSGVO_C_SLUG);
                            }
                        }
                    }
                    break;
                case 'delete_request' :
                    if (Helper::isEnabled('enable_access_request', 'settings')) {
                        $token = (isset($data['token'])) ? esc_html(urldecode($data['token'])) : false;
                        $settings = (isset($data['settings']) && is_array($data['settings'])) ? $data['settings'] : array();
                        $type = (isset($settings['type']) && in_array($settings['type'], Data::getPossibleDataTypes())) ? $settings['type'] : '';
                        $value = (isset($data['value']) && is_numeric($data['value'])) ? (int)$data['value'] : 0;

                        if (empty($token)) {
                            $output['error'] = __('Fehlender Token.', PS_DSGVO_C_SLUG);
                        }

                        if (empty($type)) {
                            $output['error'] = __('Fehlender oder ungültiger Typ.', PS_DSGVO_C_SLUG);
                        }

                        if ($value === 0) {
                            $output['error'] = __('Kein Wert ausgewählt.', PS_DSGVO_C_SLUG);
                        }

                        // Let's do this!
                        if (empty($output['error'])) {
                            $accessRequest = ($token !== false) ? AccessRequest::getInstance()->getByToken($token) : false;
                            if ($accessRequest !== false) {
                                if (
                                    SessionHelper::checkSession($accessRequest->getSessionId()) &&
                                    Helper::checkIpAddress($accessRequest->getIpAddress())
                                ) {
                                    $request = new DeleteRequest();
                                    $request->setSiteId(get_current_blog_id());
                                    $request->setAccessRequestId($accessRequest->getId());
                                    $request->setSessionId($accessRequest->getSessionId());
                                    $request->setIpAddress($accessRequest->getIpAddress());
                                    $request->setDataId($value);
                                    $request->setType($type);
                                    $id = $request->save();
                                    if ($id === false) {
                                        $output['error'] = __('Beim Speichern dieser Anfrage ist ein Fehler aufgetreten. Bitte versuche es erneut.', PS_DSGVO_C_SLUG);
                                    } else {
                                        $siteName = Helper::getSiteData('blogname', $request->getSiteId());
                                        $siteEmail = Helper::getSiteData('admin_email', $request->getSiteId());
                                        $siteUrl = Helper::getSiteData('siteurl', $request->getSiteId());
                                        $adminPage = sprintf(
                                            '<a target="_blank" href="%s">%s</a>',
                                            Helper::getPluginAdminUrl('requests'),
                                            __('Anfragen', PS_DSGVO_C_SLUG)
                                        );
                                        $subject = apply_filters(
                                            'psdsgvo_delete_request_admin_mail_subject',
                                            sprintf(__('%s - Neue Anonymisierungsanfrage', PS_DSGVO_C_SLUG), $siteName),
                                            $request,
                                            $siteName
                                        );
                                        $message = sprintf(
                                                __('Du hast eine neue Anonymisierungsanfrage für %s erhalten.', PS_DSGVO_C_SLUG),
                                                sprintf('<a target="_blank" href="%s">%s</a>', $siteUrl, $siteName)
                                            ) . '<br /><br />';
                                        $message .= sprintf(
                                            __('Du kannst diese Anforderung im Admin-Bereich verwalten: %s', PS_DSGVO_C_SLUG),
                                            $adminPage
                                        );
                                        $message = apply_filters('psdsgvo_delete_request_admin_mail_content', $message, $request, $adminPage);
                                        $headers = array(
                                            'Content-Type: text/html; charset=UTF-8',
                                            "Von: $siteName <$siteEmail>"
                                        );
                                        wp_mail($siteEmail, $subject, $message, $headers);
                                    }
                                } else {
                                    $output['error'] = __('Sitzung stimmt nicht überein.', PS_DSGVO_C_SLUG);
                                }
                            } else {
                                $output['error'] = __('Keine Sitzung gefunden.', PS_DSGVO_C_SLUG);
                            }
                        }
                    }
                    break;
            }
        }

        header('Content-type: application/json');
        echo json_encode($output);
        die();
    }

    public function processDeleteRequest() {
        check_ajax_referer('psdsgvo', 'security');

        $output = array(
            'message' => '',
            'error' => '',
        );

        if (!Helper::isEnabled('enable_access_request', 'settings')) {
            $output['error'] = __('Die Zugriffsanforderungsfunktion ist nicht aktiviert.', PS_DSGVO_C_SLUG);
        }

        $data = (isset($_POST['data']) && (is_array($_POST['data']) || is_string($_POST['data']))) ? $_POST['data'] : false;
        if (is_string($data)) {
            $data = json_decode(stripslashes($data), true);
        }
        $id = (isset($data['id']) && is_numeric($data['id'])) ? absint($data['id']) : 0;

        if (!$data) {
            $output['error'] = __('Fehlende Daten.', PS_DSGVO_C_SLUG);
        }

        if ($id === 0 || !DeleteRequest::getInstance()->exists($id)) {
            $output['error'] = __('Diese Anfrage existiert nicht.', PS_DSGVO_C_SLUG);
        }

        // Let's do this!
        if (empty($output['error'])) {
            $request = new DeleteRequest($id);
            if (!$request->getProcessed()) {
                switch ($request->getType()) {
                    case 'user' :
                        global $wpdb;
                        if (current_user_can('edit_users')) {
                            $date = Helper::localDateTime(time());
                            $result = wp_update_user(array(
                                'ID' => $request->getDataId(),
                                'user_pass' => wp_generate_password(30),
                                'display_name' => 'DISPLAY_NAME',
                                'user_nicename' => 'NICENAME' . $request->getDataId(),
                                'first_name' => 'FIRST_NAME',
                                'last_name' => 'LAST_NAME',
                                'user_email' => $request->getDataId() . '.' . $date->format('Ymd.His') . '@example.org'
                            ));
                            if (is_wp_error($result)) {
                                $output['error'] = __('Dieser Benutzer existiert nicht.', PS_DSGVO_C_SLUG);
                            } else {
                                $wpdb->update($wpdb->users, array('user_login' => 'USERNAME_' . $date->format('Ymd.His')), array('ID' => $request->getDataId()));
                                $request->setProcessed(1);
                                $request->save();
                            }
                        } else {
                            $output['error'] = __('Du darfst keine Benutzer bearbeiten.', PS_DSGVO_C_SLUG);
                        }
                        break;
                    case 'comment' :
                        if (current_user_can('edit_posts')) {
                            $date = Helper::localDateTime(time());
                            $result = wp_update_comment(array(
                                'comment_ID' => $request->getDataId(),
                                'comment_author' => 'NAME',
                                'comment_author_email' => $request->getDataId() . '.' . $date->format('Ymd.His') . '@example.org',
                                'comment_author_IP' => '127.0.0.1'
                            ));
                            if ($result === 0) {
                                $output['error'] = __('Dieser Kommentar existiert nicht.', PS_DSGVO_C_SLUG);
                            } else {
                                $request->setProcessed(1);
                                $request->save();
                            }
                        } else {
                            $output['error'] = __('Du darfst keine Kommentare bearbeiten.', PS_DSGVO_C_SLUG);
                        }
                        break;
                    case 'woocommerce_order' :
                        if (current_user_can('edit_shop_orders')) {
                            $date = Helper::localDateTime(time());
                            $userId = get_post_meta($request->getDataId(), '_customer_user', true);
                            update_post_meta($request->getDataId(), '_billing_first_name', 'FIRST_NAME');
                            update_post_meta($request->getDataId(), '_billing_last_name', 'LAST_NAME');
                            update_post_meta($request->getDataId(), '_billing_company', 'COMPANY_NAME');
                            update_post_meta($request->getDataId(), '_billing_address_1', 'ADDRESS_1');
                            update_post_meta($request->getDataId(), '_billing_address_2', 'ADDRESS_2');
                            update_post_meta($request->getDataId(), '_billing_postcode', 'ZIP_CODE');
                            update_post_meta($request->getDataId(), '_billing_city', 'CITY');
                            update_post_meta($request->getDataId(), '_billing_phone', 'PHONE_NUMBER');
                            update_post_meta($request->getDataId(), '_billing_email', $request->getDataId() . '.' . $date->format('Ymd') . '.' . $date->format('His') . '@example.org');
                            update_post_meta($request->getDataId(), '_shipping_first_name', 'FIRST_NAME');
                            update_post_meta($request->getDataId(), '_shipping_last_name', 'LAST_NAME');
                            update_post_meta($request->getDataId(), '_shipping_company', 'COMPANY_NAME');
                            update_post_meta($request->getDataId(), '_shipping_address_1', 'ADDRESS_1');
                            update_post_meta($request->getDataId(), '_shipping_address_2', 'ADDRESS_2');
                            update_post_meta($request->getDataId(), '_shipping_postcode', 'ZIP_CODE');
                            update_post_meta($request->getDataId(), '_shipping_city', 'CITY');
                            if (!empty($userId) && get_user_by('id', $userId) !== false) {
                                update_user_meta($userId, 'billing_first_name', 'FIRST_NAME');
                                update_user_meta($userId, 'billing_last_name', 'LAST_NAME');
                                update_user_meta($userId, 'billing_company', 'COMPANY_NAME');
                                update_user_meta($userId, 'billing_address_1', 'ADDRESS_1');
                                update_user_meta($userId, 'billing_address_2', 'ADDRESS_2');
                                update_user_meta($userId, 'billing_postcode', 'ZIP_CODE');
                                update_user_meta($userId, 'billing_city', 'CITY');
                                update_user_meta($userId, 'billing_phone', 'PHONE_NUMBER');
                                update_user_meta($userId, 'billing_email', $request->getDataId() . '.' . $date->format('Ymd') . '.' . $date->format('His') . '@example.org');
                                update_user_meta($userId, 'shipping_first_name', 'FIRST_NAME');
                                update_user_meta($userId, 'shipping_last_name', 'LAST_NAME');
                                update_user_meta($userId, 'shipping_company', 'COMPANY_NAME');
                                update_user_meta($userId, 'shipping_address_1', 'ADDRESS_1');
                                update_user_meta($userId, 'shipping_address_2', 'ADDRESS_2');
                                update_user_meta($userId, 'shipping_postcode', 'ZIP_CODE');
                                update_user_meta($userId, 'shipping_city', 'CITY');
                            }
                            $request->setProcessed(1);
                            $request->save();
                        } else {
                            $output['error'] = __('Du darfst WooCommerce-Bestellungen nicht bearbeiten.', PS_DSGVO_C_SLUG);
                        }
                        break;
                }

                if (empty($output['error']) && $request->getProcessed()) {
                    $accessRequest = new AccessRequest($request->getAccessRequestId());
                    $siteName = Helper::getSiteData('blogname', $request->getSiteId());
                    $siteEmail = Helper::getSiteData('admin_email', $request->getSiteId());
                    $siteUrl = Helper::getSiteData('siteurl', $request->getSiteId());
                    $subject = apply_filters(
                        'psdsgvo_delete_request_mail_subject',
                        sprintf(__('%s - Deine Anfrage', PS_DSGVO_C_SLUG), $siteName),
                        $request,
                        $accessRequest
                    );
                    $message = sprintf(
                            __('Wir haben Deine Anfrage erfolgreich bearbeitet und Deine Daten wurden auf %s anonymisiert.', PS_DSGVO_C_SLUG),
                            sprintf('<a target="_blank" href="%s">%s</a>', $siteUrl, $siteName)
                        ) . '<br /><br />';
                    $message .= __('Folgendes wurde verarbeitet:', PS_DSGVO_C_SLUG) . '<br />';
                    $message .= sprintf('%s #%d mit Email-Adresse %s.', $request->getNiceTypeLabel(), $request->getDataId(), $accessRequest->getEmailAddress());
                    $message = apply_filters('psdsgvo_delete_request_mail_content', $message, $request, $accessRequest);
                    $headers = array(
                        'Content-Type: text/html; charset=UTF-8',
                        "From: $siteName <$siteEmail>"
                    );
                    $response = wp_mail($accessRequest->getEmailAddress(), $subject, $message, $headers);
                    if ($response !== false) {
                        $output['message'] = __('Erfolgreich eine Bestätigungsmail an den Benutzer gesendet.', PS_DSGVO_C_SLUG);
                    }
                }
            } else {
                $output['error'] = __('Diese Anfrage wurde bereits bearbeitet.', PS_DSGVO_C_SLUG);
            }
        }

        header('Content-type: application/json');
        echo json_encode($output);
        die();
    }

    /**
     * @param $value
     * @return mixed
     */
    private static function sanitizeValue($value) {
        if (is_numeric($value)) {
            $value = intval($value);
        }
        if (is_string($value)) {
            $value = esc_html($value);
        }
        return $value;
    }

    /**
     * @return null|Ajax
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}