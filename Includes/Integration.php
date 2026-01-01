<?php

namespace PSDSGVO\Includes;

use PSDSGVO\Includes\Extensions\CF7;
use PSDSGVO\Includes\Extensions\GForms;
use PSDSGVO\Includes\Extensions\WC;
use PSDSGVO\Includes\Extensions\WP;
use PSDSGVO\Includes\Extensions\WPRegistration;

/**
 * Class Integration
 * @package PSDSGVO\Includes
 */
class Integration {
    /** @var null */
    private static $instance = null;

    /**
     * Integration constructor.
     */
    public function __construct() {
        add_action('admin_init', array($this, 'registerSettings'));
        foreach (Helper::getEnabledPlugins() as $plugin) {
            switch ($plugin['id']) {
                case WP::ID :
                    if (current_user_can('administrator')) {
                        add_filter('comment_form_submit_field', array(WP::getInstance(), 'addFieldForAdmin'), 999);
                    } else {
                        add_filter('comment_form_submit_field', array(WP::getInstance(), 'addField'), 999);
                    }
                    add_action('pre_comment_on_post', array(WP::getInstance(), 'checkPost'));
                    add_action('comment_post', array(WP::getInstance(), 'addAcceptedDateToCommentMeta'));
                    add_filter('manage_edit-comments_columns', array(WP::getInstance(), 'displayAcceptedDateColumnInCommentOverview'));
                    add_action('manage_comments_custom_column', array(WP::getInstance(), 'displayAcceptedDateInCommentOverview'), 10, 2);
                    break;
                case CF7::ID :
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . CF7::ID . '_forms', array(CF7::getInstance(), 'processIntegration'));
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . CF7::ID . '_form_text', array(CF7::getInstance(), 'processIntegration'));
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . CF7::ID . '_error_message', array(CF7::getInstance(), 'processIntegration'));
                    add_action('wpcf7_init', array(CF7::getInstance(), 'addFormTagSupport'));
                    add_filter('wpcf7_before_send_mail', array(CF7::getInstance(), 'changeMailBodyOutput'), 999);
                    add_filter('wpcf7_validate_psdsgvo', array(CF7::getInstance(), 'validateField'), 10, 2);
                    break;
                case WPRegistration::ID :
                    $users_can_register = get_option('users_can_register');
                    if ($users_can_register) {
	                    $addFieldAction = (is_multisite() ? 'signup_extra_fields' : 'register_form');
	                    $addFieldFunction = (is_multisite() ? 'addFieldMultiSite' : 'addField');
	                    $validationAction = (is_multisite() ? 'wpmu_validate_user_signup' : 'registration_errors');
	                    $registerUserAction = (is_multisite() ? 'wpmu_new_user' : 'user_register');
	                    $validateFunction = (is_multisite() ? 'validateGDPRCheckboxMultisite' : 'validateGDPRCheckbox' );
	                    $logFunction = 'logGivenGDPRConsent';
	                    $validationArguments  = (is_multisite() ? 1 : 3);
                        add_action($addFieldAction, array(WPRegistration::getInstance(), $addFieldFunction), 10, 1);
                        add_filter( $validationAction, array(WPRegistration::getInstance(), $validateFunction), 10, $validationArguments );
                        add_action($registerUserAction, array(WPRegistration::getInstance(), $logFunction), 10, 1);
                    }
                    break;
                case WC::ID :
                    add_action('woocommerce_checkout_process', array(WC::getInstance(), 'checkPostCheckoutForm'));
                    add_action('woocommerce_register_post', array(WC::getInstance(), 'checkPostRegisterForm'), 10, 3);
                    add_action('woocommerce_review_order_before_submit', array(WC::getInstance(), 'addField'), 999);
                    add_action('woocommerce_register_form', array(WC::getInstance(), 'addField'), 999);
                    add_action('woocommerce_checkout_update_order_meta', array(WC::getInstance(), 'addAcceptedDateToOrderMeta'));
                    add_action('woocommerce_admin_order_data_after_order_details', array(WC::getInstance(), 'displayAcceptedDateInOrderData'));
                    add_filter('manage_edit-shop_order_columns', array(WC::getInstance(), 'displayAcceptedDateColumnInOrderOverview'));
                    add_action('manage_shop_order_posts_custom_column', array(WC::getInstance(), 'displayAcceptedDateInOrderOverview'), 10, 2);
                    break;
                case GForms::ID :
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . GForms::ID . '_forms', array(GForms::getInstance(), 'processIntegration'));
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . GForms::ID . '_form_text', array(GForms::getInstance(), 'processIntegration'));
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . GForms::ID . '_error_message', array(GForms::getInstance(), 'processIntegration'));
                    add_filter('gform_entries_field_value', array(GForms::getInstance(), 'displayAcceptedDateInEntryOverview'), 10, 4);
                    add_filter('gform_get_field_value', array(GForms::getInstance(), 'displayAcceptedDateInEntry'), 10, 2);
                    foreach (GForms::getInstance()->getEnabledForms() as $formId) {
                        add_filter('gform_entry_list_columns_' . $formId, array(GForms::getInstance(), 'displayAcceptedDateColumnInEntryOverview'), 10, 2);
                        add_filter('gform_save_field_value_' . $formId, array(GForms::getInstance(), 'addAcceptedDateToEntry'), 10, 3);
                        add_action('gform_validation_' . $formId, array(GForms::getInstance(), 'overwriteValidationMessage'));
                    }
                    break;
            }
        }
    }

    public function registerSettings() {
        foreach (self::getSupportedIntegrations() as $plugin) {
            register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'], 'intval');
            switch ($plugin['id']) {
                case CF7::ID :
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'], array(CF7::getInstance(), 'processIntegration'));
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_forms');
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_form_text', array('sanitize_callback' => array(Helper::getInstance(), 'sanitizeData')));
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_error_message', array('sanitize_callback' => array(Helper::getInstance(), 'sanitizeData')));
                    break;
                case GForms::ID :
                    add_action('update_option_' . PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'], array(GForms::getInstance(), 'processIntegration'));
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_forms');
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_form_text');
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_error_message');
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_required_message');
                    break;
                default :
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_text');
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_error_message');
                    register_setting(PS_DSGVO_C_SLUG . '_integrations', PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'] . '_required_message');
                    break;
            }
        }
    }

    /**
     * @param string $plugin
     * @return string
     */
    public static function getSupportedPluginOptions($plugin = '') {
        $output = '';
        switch ($plugin) {
            case CF7::ID :
                $forms = CF7::getInstance()->getForms();
                if (!empty($forms)) {
                    $optionNameForms = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_forms';
                    $optionNameFormText = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_form_text';
                    $optionNameErrorMessage = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_error_message';
                    $enabledForms = CF7::getInstance()->getEnabledForms();
                    $output .= '<ul class="psdsgvo-checklist-options">';
                    foreach ($forms as $form) {
                        $formSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_form_' . $form;
                        $textSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_form_text_' . $form;
                        $errorSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_error_message_' . $form;
                        $enabled = in_array($form, $enabledForms);
                        $text = CF7::getInstance()->getCheckboxText($form, false);
                        $errorMessage = CF7::getInstance()->getErrorMessage($form);
                        $output .= '<li class="psdsgvo-clearfix">';
                        $output .= '<div class="psdsgvo-checkbox">';
                        $output .= '<input type="checkbox" name="' . $optionNameForms . '[]" id="' . $formSettingId . '" value="' . $form . '" tabindex="1" data-option="' . $optionNameForms . '" data-append="1" ' . checked(true, $enabled, false) . ' />';
                        $output .= '<label for="' . $formSettingId . '"><strong>' . sprintf(__('Formular: %s', PS_DSGVO_C_SLUG), get_the_title($form)) . '</strong></label>';
                        $output .= '<span class="psdsgvo-instructions">' . __('Für dieses Formular aktivieren:', PS_DSGVO_C_SLUG) . '</span>';
                        $output .= '</div>';
                        $output .= '<div class="psdsgvo-setting">';
                        $output .= '<label for="' . $textSettingId . '">' . __('Checkboxtext', PS_DSGVO_C_SLUG) . '</label>';
                        $output .= '<div class="psdsgvo-options">';
                        $output .= '<textarea name="' . $optionNameFormText . '[' . $form . ']' . '" class="regular-text" id="' . $textSettingId . '" placeholder="' . $text . '">' . $text . '</textarea>';
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= '<div class="psdsgvo-setting">';
                        $output .= '<label for="' . $errorSettingId . '">' . __('Fehlermeldung', PS_DSGVO_C_SLUG) . '</label>';
                        $output .= '<div class="psdsgvo-options">';
                        $output .= '<input type="text" name="' . $optionNameErrorMessage . '[' . $form . ']' . '" class="regular-text" id="' . $errorSettingId . '" placeholder="' . $errorMessage . '" value="' . $errorMessage . '" />';
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= Helper::getAllowedHTMLTagsOutput($plugin);
                        $output .= '</li>';
                    }
                    $output .= '</ul>';
                } else {
                    $output = '<p>' . __('Keine Formulare gefunden.', PS_DSGVO_C_SLUG) . '</p>';
                }
                break;
            case GForms::ID :
                $forms = GForms::getInstance()->getForms();
                if (!empty($forms)) {
                    $optionNameForms = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_forms';
                    $optionNameFormText = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_form_text';
                    $optionNameErrorMessage = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_error_message';
                    $optionNameRequiredMessage = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_required_message';
                    $enabledForms = GForms::getInstance()->getEnabledForms();
                    $output .= '<ul class="psdsgvo-checklist-options">';
                    foreach ($forms as $form) {
                        $formSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_form_' . $form['id'];
                        $textSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_form_text_' . $form['id'];
                        $errorSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_error_message_' . $form['id'];
                        $requiredSettingId = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_required_message_' . $form['id'];
                        $enabled = in_array($form['id'], $enabledForms);
                        $text = esc_html(GForms::getInstance()->getCheckboxText($form['id'], false));
                        $errorMessage = esc_html(GForms::getInstance()->getErrorMessage($form['id']));
                        $requiredMessage = esc_html(GForms::getInstance()->getRequiredMessage($form['id']));
                        $output .= '<li class="psdsgvo-clearfix">';
                        $output .= '<div class="psdsgvo-checkbox">';
                        $output .= '<input type="checkbox" name="' . $optionNameForms . '[]" id="' . $formSettingId . '" value="' . $form['id'] . '" tabindex="1" data-option="' . $optionNameForms . '" data-append="1" ' . checked(true, $enabled, false) . ' />';
                        $output .= '<label for="' . $formSettingId . '"><strong>' . sprintf(__('Formular: %s', PS_DSGVO_C_SLUG), $form['title']) . '</strong></label>';
                        $output .= '<span class="psdsgvo-instructions">' . __('Aktiviere für dieses Formular:', PS_DSGVO_C_SLUG) . '</span>';
                        $output .= '</div>';
                        $output .= '<div class="psdsgvo-setting">';
                        $output .= '<label for="' . $textSettingId . '">' . __('Kontrollkästchentext', PS_DSGVO_C_SLUG) . '</label>';
                        $output .= '<div class="psdsgvo-options">';
                        $output .= '<textarea name="' . $optionNameFormText . '[' . $form['id'] . ']' . '" class="regular-text" id="' . $textSettingId . '" placeholder="' . $text . '">' . $text . '</textarea>';
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= '<div class="psdsgvo-setting">';
                        $output .= '<label for="' . $errorSettingId . '">' . __('Fehlermeldung', PS_DSGVO_C_SLUG) . '</label>';
                        $output .= '<div class="psdsgvo-options">';
                        $output .= '<input type="text" name="' . $optionNameErrorMessage . '[' . $form['id'] . ']' . '" class="regular-text" id="' . $errorSettingId . '" placeholder="' . $errorMessage . '" value="' . $errorMessage . '" />';
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= '<div class="psdsgvo-setting">';
                        $output .= '<label for="' . $requiredSettingId . '">' . __('Erforderlich Nachricht', PS_DSGVO_C_SLUG) . '</label>';
                        $output .= '<div class="psdsgvo-options">';
                        $output .= '<input type="text" name="' . $optionNameRequiredMessage . '[' . $form['id'] . ']' . '" class="regular-text" id="' . $requiredSettingId . '" placeholder="' . $requiredMessage . '" value="' . $requiredMessage . '" />';
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= Helper::getAllowedHTMLTagsOutput($plugin);
                        $output .= '</li>';
                    }
                    $output .= '</ul>';
                } else {
                    $output = '<p>' . __('Keine Formulare gefunden.', PS_DSGVO_C_SLUG) . '</p>';
                }
                break;
            default :
                $optionNameText = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_text';
                $optionNameErrorMessage = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_error_message';
                $text = esc_html(self::getCheckboxText($plugin, false));
                $errorMessage = esc_html(self::getErrorMessage($plugin));
                $optionNameRequiredMessage = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_required_message';
                $requiredMessage = esc_html(self::getRequiredMessage($plugin));
                $output .= '<ul class="psdsgvo-checklist-options">';
                $output .= '<li class="psdsgvo-clearfix">';
                $output .= '<div class="psdsgvo-setting">';
                $output .= '<label for="' . $optionNameText . '">' . __('Kontrollkästchentext', PS_DSGVO_C_SLUG) . '</label>';
                $output .= '<div class="psdsgvo-options">';
                $output .= '<textarea name="' . $optionNameText . '" class="regular-text" id="' . $optionNameText . '" placeholder="' . $text . '">' . $text . '</textarea>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="psdsgvo-setting">';
                $output .= '<label for="' . $optionNameErrorMessage . '">' . __('Fehlermeldung', PS_DSGVO_C_SLUG) . '</label>';
                $output .= '<div class="psdsgvo-options">';
                $output .= '<input type="text" name="' . $optionNameErrorMessage . '" class="regular-text" id="' . $optionNameErrorMessage . '" placeholder="' . $errorMessage . '" value="' . $errorMessage . '" />';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="psdsgvo-setting">';
                $output .= '<label for="' . $optionNameRequiredMessage . '">' . __('Erforderlich Nachricht', PS_DSGVO_C_SLUG) . '</label>';
                $output .= '<div class="psdsgvo-options">';
                $output .= '<input type="text" name="' . $optionNameRequiredMessage . '" class="regular-text" id="' . $optionNameRequiredMessage . '" placeholder="' . $requiredMessage . '" value="' . $requiredMessage . '" />';
                $output .= '</div>';
                $output .= '</div>';
                $output .= Helper::getAllowedHTMLTagsOutput($plugin);
                $output .= '</li>';
                $output .= '</ul>';
                break;
        }
        return $output;
    }

    /**
     * @param string $plugin
     * @param bool $insertPrivacyPolicyLink
     * @return string
     */
    public static function getCheckboxText($plugin = '', $insertPrivacyPolicyLink = true) {
        $output = '';
        if (!empty($plugin)) {
            $output = get_option(PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_text');
            $output = ($insertPrivacyPolicyLink === true) ? self::insertPrivacyPolicyLink($output) : $output;
            $output = apply_filters('psdsgvo_' . $plugin . '_checkbox_text', $output);
        }
        if (empty($output)) {
            $output = __('Durch die Verwendung dieses Formulars stimmst Du der Speicherung und Verarbeitung Deiner Daten durch diese Webseite zu.', PS_DSGVO_C_SLUG);
        }
        $output = wp_kses($output, Helper::getAllowedHTMLTags($plugin));
        return apply_filters('psdsgvo_checkbox_text', $output);
    }

    /**
     * @param string $plugin
     * @return mixed
     */
    public static function getErrorMessage($plugin = '') {
        $output = '';
        if (!empty($plugin)) {
            $output = get_option(PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_error_message');
            $output = apply_filters('psdsgvo_' . $plugin . '_error_message', $output);
        }
        if (empty($output)) {
            $output = __('Bitte akzeptiere das Kontrollkästchen Datenschutz.', PS_DSGVO_C_SLUG);
        }
        return apply_filters('psdsgvo_error_message', wp_kses($output, Helper::getAllowedHTMLTags($plugin)));
    }

    /**
     * @param string $plugin
     * @return mixed
     */
    public static function getRequiredMessage($plugin = '') {
        $output = '';
        if (!empty($plugin)) {
            $output = get_option(PS_DSGVO_C_PREFIX . '_integrations_' . $plugin . '_required_message');
            $output = apply_filters('psdsgvo_' . $plugin . '_required_message', $output);
        }
        if (empty($output)) {
            $output = __('Du musst dieses Kontrollkästchen akzeptieren.', PS_DSGVO_C_SLUG);
        }
        return apply_filters('psdsgvo_required_message', esc_attr($output));
    }

    /**
     * @return mixed
     */
    public static function getPrivacyPolicyText() {
        $output = get_option(PS_DSGVO_C_PREFIX . '_settings_privacy_policy_text');
        if (empty($output)) {
            $output = __('Datenschutz-Bestimmungen', PS_DSGVO_C_SLUG);
        }
        return apply_filters('psdsgvo_privacy_policy_text', $output);
    }

    public static function getPrivacyPolicyLink() {
        $output = get_option(PS_DSGVO_C_PREFIX . '_settings_privacy_policy_link');
        if (empty($output)) {
            $output = __('http://www.example.com', PS_DSGVO_C_SLUG);
        }
        return apply_filters('psdsgvo_privacy_policy_link', $output);
    }

    /**
     * @param bool $insertPrivacyPolicyLink
     * @return mixed
     */
    public static function getAccessRequestFormCheckboxText($insertPrivacyPolicyLink = true) {
        $output = get_option(PS_DSGVO_C_PREFIX . '_settings_access_request_form_checkbox_text');
        if (empty($output)) {
            $output = __('Durch die Verwendung dieses Formulars stimmst Du der Speicherung und Verarbeitung Deiner Daten durch diese Webseite zu.', PS_DSGVO_C_SLUG);
        }
        $output = ($insertPrivacyPolicyLink === true) ? self::insertPrivacyPolicyLink($output) : $output;
        return apply_filters('psdsgvo_access_request_form_checkbox_text', wp_kses($output, Helper::getAllowedHTMLTags()));
    }

    /**
     * @param bool $insertPrivacyPolicyLink
     * @return mixed
     */
    public static function getDeleteRequestFormExplanationText($insertPrivacyPolicyLink = true) {
        $output = get_option(PS_DSGVO_C_PREFIX . '_settings_delete_request_form_explanation_text');
        if (empty($output)) {
            $output = sprintf(
                __('Nachfolgend zeigen wir Dir alle Daten, die von %s auf %s gespeichert wurden. Wähle die Daten aus, die der Webseitenbesitzer anonymisieren soll, damit sie nicht mehr mit Deiner Email-Adresse verknüpft werden können. Es liegt in der Verantwortung des Eigentümers der Webseite, auf Deine Anfrage zu reagieren. Wenn Deine Daten anonymisiert sind, erhältst Du eine E-Mail-Bestätigung.', PS_DSGVO_C_SLUG),
                get_option('blogname'),
                get_option('siteurl')
            );
        }
        $output = ($insertPrivacyPolicyLink === true) ? self::insertPrivacyPolicyLink($output) : $output;
        return apply_filters('psdsgvo_delete_request_form_explanation_text', wp_kses($output, Helper::getAllowedHTMLTags()));
    }

    /**
     * @param string $content
     * @return mixed|string
     */
    public static function insertPrivacyPolicyLink($content = '') {
        if (!Helper::isEnabled('enable_privacy_policy_extern', 'settings')) {
            $page = get_option(PS_DSGVO_C_PREFIX . '_settings_privacy_policy_page');
        } else {
            $url = get_option(PS_DSGVO_C_PREFIX . '_settings_privacy_policy_link');
        }
        $text = Integration::getPrivacyPolicyText();
        if ((!empty($page) || !empty($url)) && !empty($text)) {
            $link = apply_filters(
                'psdsgvo_privacy_policy_link',
                sprintf(
                    '<a target="_blank" href="%s" rel="noopener noreferrer">%s</a>',
                    (Helper::isEnabled('enable_privacy_policy_extern', 'settings')) ? $url : get_page_link($page),
                    esc_html($text)
                ),
                (Helper::isEnabled('enable_privacy_policy_extern', 'settings')) ? $url : $page,
                $text
            );
            $content = str_replace('%privacy_policy%', $link, $content);
        }
        return $content;
    }

    /**
     * @return array
     */
    public static function getSupportedWordPressFunctionality() {
        return array(
            array(
                'id' => 'wordpress',
                'name' => __('WordPress Kommentare', PS_DSGVO_C_SLUG),
                'description' => __('Bei Aktivierung wird das Kontrollkästchen DSGVO automatisch direkt über der Schaltfläche "Senden" hinzugefügt.', PS_DSGVO_C_SLUG),
            ),
            array(
                'id' => WPRegistration::ID,
                'name' => __('Wordpress Registrierung', PS_DSGVO_C_SLUG),
                'description' => __('Bei Aktivierung wird das Kontrollkästchen DSGVO automatisch direkt über der Registrierungsschaltfläche hinzugefügt.', PS_DSGVO_C_SLUG),
            )
        );
    }

    /**
     * @return array
     */
    public static function getSupportedPlugins() {
        return array(
            array(
                'id' => CF7::ID,
                'supported_version' => CF7::SUPPORTED_VERSION,
                'file' => 'contact-form-7/wp-contact-form-7.php',
                'name' => __('Contact Form 7', PS_DSGVO_C_SLUG),
                'description' => __('Ein DSGVO-Formular-Tag wird automatisch zu jedem von Dir aktivierten Formular hinzugefügt.', PS_DSGVO_C_SLUG),
            ),
            array(
                'id' => GForms::ID,
                'supported_version' => GForms::SUPPORTED_VERSION,
                'file' => 'gravityforms/gravityforms.php',
                'name' => __('Gravity Forms', PS_DSGVO_C_SLUG),
                'description' => __('Ein DSGVO-Formular-Tag wird automatisch zu jedem von Dir aktivierten Formular hinzugefügt.', PS_DSGVO_C_SLUG),
            ),
            array(
                'id' => WC::ID,
                'supported_version' => WC::SUPPORTED_VERSION,
                'file' => 'woocommerce/woocommerce.php',
                'name' => __('WooCommerce', PS_DSGVO_C_SLUG),
                'description' => __('Das Kontrollkästchen DSGVO wird am Ende Deiner Checkout-Seite automatisch hinzugefügt.', PS_DSGVO_C_SLUG),
            )
        );
    }

    /**
     * @return array
     */
    public static function getSupportedIntegrations() {
        return array_merge(self::getSupportedPlugins(), self::getSupportedWordPressFunctionality());
    }

    /**
     * @return array
     */
    public static function getSupportedIntegrationsLabels() {
        $output = array();
        $supportedIntegrations = self::getSupportedIntegrations();
        foreach ($supportedIntegrations as $supportedIntegration) {
            $output[] = $supportedIntegration['name'];
        }
        return $output;
    }

    /**
     * @return null|Integration
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}