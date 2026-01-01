<?php

namespace PSDSGVO\Includes;

/**
 * Class Page
 * @package PSDSGVO\Includes
 */
class Page {
    /** @var null */
    private static $instance = null;

    public function registerSettings() {
        foreach (Helper::getCheckList() as $id => $check) {
            register_setting(PS_DSGVO_C_SLUG . '_general', PS_DSGVO_C_PREFIX . '_general_' . $id, 'intval');
        }
        
        // Embed Settings registrieren
        $embedSettings = \PSDSGVO\Includes\Extensions\ContentEmbeds::getInstance();
        foreach ($embedSettings->getEmbedTypes() as $typeId => $config) {
            register_setting(PS_DSGVO_C_SLUG . '_embed_settings', PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId, 'intval');
            register_setting(PS_DSGVO_C_SLUG . '_embed_settings', PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId . '_message', array('sanitize_callback' => array(Helper::getInstance(), 'sanitizeData')));
        }
        
        if (!Helper::isEnabled('enable_privacy_policy_extern', 'settings')) {
            register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_privacy_policy_page', 'intval');
        }
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_privacy_policy_text', array('sanitize_callback' => array(Helper::getInstance(), 'sanitizeData')));
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_enable_privacy_policy_extern', 'intval');
        if (Helper::isEnabled('enable_privacy_policy_extern', 'settings')) {
            register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_privacy_policy_link', array('sanitize_callback' => array(Helper::getInstance(), 'sanitizeData')));
        }
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_enable_access_request', 'intval');
        if (Helper::isEnabled('enable_access_request', 'settings')) {
            register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_access_request_page', 'intval');
            register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_access_request_form_checkbox_text');
            register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_delete_request_form_explanation_text');
        }
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_modal_title');
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_modal_explanation_text');
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_explanation_text');
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_more_information_text');
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_text');
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_color', array('default' => '#000000'));
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_text_color', array('default' => '#FFFFFF'));
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_primary', array('default' => '#FFFFFF'));
        register_setting(PS_DSGVO_C_SLUG . '_settings', PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_secondary', array('default' => '#000000'));
    }

    public function addAdminMenu() {
        $pluginData = Helper::getPluginData();
        add_submenu_page(
            'tools.php',
            $pluginData['Name'],
            $pluginData['Name'],
            'manage_options',
            str_replace('-', '_', PS_DSGVO_C_SLUG),
            array($this, 'generatePage')
        );
    }

    public function generatePage() {
        $type = (isset($_REQUEST['type'])) ? esc_html($_REQUEST['type']) : false;
        $pluginData = Helper::getPluginData();
        $enableAccessRequest = Helper::isEnabled('enable_access_request', 'settings');
        $adminUrl = Helper::getPluginAdminUrl();
        ?>
        <div class="wrap">
            <div class="psdsgvo">
                <div class="psdsgvo-contents">
                    <h1 class="psdsgvo-title"><?php echo $pluginData['Name']; ?>
                        <span><?php printf('v%s', $pluginData['Version']); ?></span></h1>

                    <?php settings_errors(); ?>

                    <div class="psdsgvo-navigation psdsgvo-clearfix">
                        <a class="<?php echo (empty($type)) ? 'psdsgvo-active' : ''; ?>"
                           href="<?php echo $adminUrl; ?>"><?php _e('Integrationen', PS_DSGVO_C_SLUG); ?></a>
                        <a class="<?php echo checked('consents', $type, false) ? 'psdsgvo-active' : ''; ?>"
                           href="<?php echo $adminUrl; ?>&type=consents"><?php _e('Einwilligungen', PS_DSGVO_C_SLUG); ?></a>
                        <?php
                        if ($enableAccessRequest) :
                            $totalDeleteRequests = DeleteRequest::getInstance()->getTotal(array(
                                'ip_address' => array(
                                    'value' => '127.0.0.1',
                                    'compare' => '!='
                                ),
                                'processed' => array(
                                    'value' => 0
                                )
                            ));
                            ?>
                            <a class="<?php echo checked('requests', $type, false) ? 'psdsgvo-active' : ''; ?>"
                               href="<?php echo $adminUrl; ?>&type=requests">
                                <?php _e('Anfragen', PS_DSGVO_C_SLUG); ?>
                                <?php
                                if ($totalDeleteRequests > 1) {
                                    printf('<span class="psdsgvo-badge">%d</span>', $totalDeleteRequests);
                                }
                                ?>
                            </a>
                        <?php
                        endif;
                        ?>
                        <a class="<?php echo checked('embed-settings', $type, false) ? 'psdsgvo-active' : ''; ?>"
                           href="<?php echo $adminUrl; ?>&type=embed-settings"><?php _e('Externe Inhalte', PS_DSGVO_C_SLUG); ?></a>
                        <a class="<?php echo checked('checklist', $type, false) ? 'psdsgvo-active' : ''; ?>"
                           href="<?php echo $adminUrl; ?>&type=checklist"><?php _e('Checkliste', PS_DSGVO_C_SLUG); ?></a>
                        <a class="<?php echo checked('settings', $type, false) ? 'psdsgvo-active' : ''; ?>"
                           href="<?php echo $adminUrl; ?>&type=settings"><?php _e('Einstellungen', PS_DSGVO_C_SLUG); ?></a>
                    </div>

                    <div class="psdsgvo-content psdsgvo-clearfix">
                        <?php
                        switch ($type) {
                            case 'consents' :
                                $action = (isset($_REQUEST['action'])) ? esc_html($_REQUEST['action']) : false;
                                switch ($action) {
                                    case 'manage' :
                                        $id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
                                        self::renderManageConsentPage($id);
                                        break;
                                    default :
                                        self::renderConsentsPage();
                                        break;
                                }
                                break;
                            case 'requests' :
                                $id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
                                if (!empty($id) && AccessRequest::getInstance()->exists($id)) {
                                    self::renderManageRequestPage($id);
                                } else {
                                    self::renderRequestsPage();
                                }
                                break;
                            case 'embed-settings' :
                                self::renderEmbedSettingsPage();
                                break;
                            case 'checklist' :
                                self::renderChecklistPage();
                                break;
                            case 'settings' :
                                self::renderSettingsPage();
                                break;
                            default :
                                self::renderIntegrationsPage();
                                break;
                        }
                        ?>
                    </div>

                    <div class="psdsgvo-description">
                        <p><?php _e('Dieses Plugin unterstützt Webseiten- und Webshop-Besitzer bei der Einhaltung der europäischen Datenschutzbestimmungen, die als DSGVO bekannt sind. Ab dem 25. Mai 2018 muss Deine Webseite oder Dein Geschäft den Anforderungen entsprechen.', PS_DSGVO_C_SLUG); ?></p>
                        <p><?php
                            printf(
                                __('%s unterstützt derzeit %s. Bitte besuche %s für häufig gestellte Fragen und unsere Entwicklungs-Roadmap.', PS_DSGVO_C_SLUG),
                                $pluginData['Name'],
                                implode(', ', Integration::getSupportedIntegrationsLabels()),
                                sprintf('<a target="_blank" href="%s">%s</a>', '//n3rds.work/', 'WebMasterService N3rds@Work')
                            );
                            ?></p>
                        <p class="psdsgvo-disclaimer"><?php _e('Haftungsausschluss: Die Ersteller dieses Plugins haben keinen rechtlichen Hintergrund. Bitte wende Dich an eine Anwaltskanzlei, um eine solide Rechtsberatung zu erhalten.', PS_DSGVO_C_SLUG); ?></p>
                    </div>
                </div>

                <div class="psdsgvo-background"><?php include(PS_DSGVO_C_DIR_SVG . '/inline-waves.svg.php'); ?></div>
            </div>
        </div>
        <?php
    }

    private static function renderIntegrationsPage() {
        $pluginData = Helper::getPluginData();
        $activatedPlugins = Helper::getActivatedPlugins();
        ?>
        <form method="post" action="<?php echo admin_url('options.php'); ?>" novalidate="novalidate">
            <?php settings_fields(PS_DSGVO_C_SLUG . '_integrations'); ?>
            <?php if (!empty($activatedPlugins)) : ?>
                <ul class="psdsgvo-list">
                    <?php
                    foreach ($activatedPlugins as $key => $plugin) :
                        $optionName = PS_DSGVO_C_PREFIX . '_integrations_' . $plugin['id'];
                        $checked = Helper::isEnabled($plugin['id']);
                        $description = (!empty($plugin['description'])) ? apply_filters('psdsgvo_the_content', $plugin['description']) : '';
                        $notices = Helper::getNotices($plugin['id']);
                        $options = Integration::getSupportedPluginOptions($plugin['id']);
                        ?>
                        <li class="psdsgvo-clearfix">
                            <?php if ($plugin['supported']) : ?>
                                <?php if (empty($notices)) : ?>
                                    <div class="psdsgvo-checkbox">
                                        <input type="checkbox" name="<?php echo $optionName; ?>"
                                               id="<?php echo $optionName; ?>" value="1" tabindex="1"
                                               data-option="<?php echo $optionName; ?>" <?php checked(true, $checked); ?> />
                                        <label for="<?php echo $optionName; ?>"><?php echo $plugin['name']; ?></label>
                                        <span class="psdsgvo-instructions"><?php _e('Aktivieren:', PS_DSGVO_C_SLUG); ?></span>
                                        <div class="psdsgvo-switch" aria-hidden="true">
                                            <div class="psdsgvo-switch-label">
                                                <div class="psdsgvo-switch-inner"></div>
                                                <div class="psdsgvo-switch-switch"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="psdsgvo-checkbox-data"
                                         <?php if (!$checked) : ?>style="display: none;"<?php endif; ?>>
                                        <?php if (!empty($description)) : ?>
                                            <div class="psdsgvo-checklist-description">
                                                <?php echo $description; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php echo $options; ?>
                                    </div>
                                <?php else : ?>
                                    <div class="psdsgvo-message psdsgvo-message--notice">
                                        <strong><?php echo $plugin['name']; ?></strong><br/>
                                        <?php echo $notices; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else : ?>
                                <div class="psdsgvo-message psdsgvo-message--error">
                                    <strong><?php echo $plugin['name']; ?></strong><br/>
                                    <?php printf(__('Dieses Plugin ist veraltet. %s unterstützt Version %s und höher.', PS_DSGVO_C_SLUG), $pluginData['Name'], '<strong>' . $plugin['supported_version'] . '</strong>'); ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php
                    endforeach;
                    ?>
                </ul>
            <?php else : ?>
                <p><strong><?php _e('Es wurden keine unterstützten Plugins gefunden.', PS_DSGVO_C_SLUG); ?></strong></p>
                <p><?php _e('Folgende Plugins werden ab sofort unterstützt:', PS_DSGVO_C_SLUG); ?></p>
                <ul class="ul-square">
                    <?php foreach (Integration::getSupportedPlugins() as $plugin) : ?>
                        <li><?php echo $plugin['name']; ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><?php _e('Weitere Plugins werden in Zukunft hinzugefügt.', PS_DSGVO_C_SLUG); ?></p>
            <?php endif; ?>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Page: Checklist
     */
    private static function renderChecklistPage() {
        ?>
        <?php if (Helper::hasMailPluginInstalled()) : ?>
            <div class="psdsgvo-message psdsgvo-message--notice">
                <?php
                printf(
                    '<p><strong>%s:</strong> %s</p>',
                    strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                    __('Wir glauben, dass Du möglicherweise ein Mail-Plugin installiert hast.', PS_DSGVO_C_SLUG)
                );
                ?>
                <p><?php _e('Weißt DU, woher Du Deine E-Mail-Datenbank hast? Hast Du alle Personen in Deinen Newslettern gefragt, ob sie dem Erhalt zustimmen? GDPR erfordert, dass alle Personen in Deiner E-Mail-Software Dir die ausdrückliche Erlaubnis erteilt haben, sie zu versenden.', PS_DSGVO_C_SLUG); ?></p>
            </div>
        <?php endif; ?>
        <p><?php _e('Im Folgenden fragen wir Dich, welche privaten Daten Du derzeit sammelst, und geben Dir Tipps zur Einhaltung.', PS_DSGVO_C_SLUG); ?></p>
        <ul class="psdsgvo-list">
            <?php
            foreach (Helper::getCheckList() as $id => $check) :
                $optionName = PS_DSGVO_C_PREFIX . '_general_' . $id;
                $checked = Helper::isEnabled($id, 'general');
                $description = (!empty($check['description'])) ? esc_html($check['description']) : '';
                ?>
                <li class="psdsgvo-clearfix">
                    <div class="psdsgvo-checkbox">
                        <input type="checkbox" name="<?php echo $optionName; ?>" id="<?php echo $id; ?>" value="1"
                               tabindex="1"
                               data-option="<?php echo $optionName; ?>" <?php checked(true, $checked); ?> />
                        <label for="<?php echo $id; ?>"><?php echo $check['label']; ?></label>
                        <div class="psdsgvo-switch psdsgvo-switch--reverse" aria-hidden="true">
                            <div class="psdsgvo-switch-label">
                                <div class="psdsgvo-switch-inner"></div>
                                <div class="psdsgvo-switch-switch"></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($description)) : ?>
                        <div class="psdsgvo-checkbox-data"
                             <?php if (!$checked) : ?>style="display: none;"<?php endif; ?>>
                            <div class="psdsgvo-checklist-description">
                                <?php echo $description; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </li>
            <?php
            endforeach;
            ?>
        </ul>
        <?php
    }

    /**
     * Page: Settings
     */
    private static function renderSettingsPage() {
        $optionNamePrivacyPolicyPage = PS_DSGVO_C_PREFIX . '_settings_privacy_policy_page';
        $optionNamePrivacyPolicyText = PS_DSGVO_C_PREFIX . '_settings_privacy_policy_text';
        $optionNameEnablePrivacyPolicyExternal = PS_DSGVO_C_PREFIX . '_settings_enable_privacy_policy_extern';
        $optionNamePrivacyPolicyLink = PS_DSGVO_C_PREFIX . '_settings_privacy_policy_link';
        $optionNameEnableAccessRequest = PS_DSGVO_C_PREFIX . '_settings_enable_access_request';
        $optionNameAccessRequestPage = PS_DSGVO_C_PREFIX . '_settings_access_request_page';
        $optionNameAccessRequestFormCheckboxText = PS_DSGVO_C_PREFIX . '_settings_access_request_form_checkbox_text';
        $optionNameDeleteRequestFormExplanationText = PS_DSGVO_C_PREFIX . '_settings_delete_request_form_explanation_text';
        $optionNameConsentsBarExplanationText = PS_DSGVO_C_PREFIX . '_settings_consents_bar_explanation_text';
        $optionNameConsentsBarMoreInformationText = PS_DSGVO_C_PREFIX . '_settings_consents_bar_more_information_text';
        $optionNameConsentsBarButtonText = PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_text';
        $optionNameConsentsModalTitle = PS_DSGVO_C_PREFIX . '_settings_consents_modal_title';
        $optionNameConsentsModalExplanationText = PS_DSGVO_C_PREFIX . '_settings_consents_modal_explanation_text';
        $optionNameLeisteFarbe = PS_DSGVO_C_PREFIX . '_settings_consents_bar_color';
        $optionNameBarTextColor = PS_DSGVO_C_PREFIX . '_settings_consents_bar_text_color';
        $optionNameBarButtonColorPrimary = PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_primary';
        $optionNameBarButtonColorSecondary = PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_secondary';
        $barColor = get_option($optionNameLeisteFarbe);
        $barTextColor = get_option($optionNameBarTextColor);
        $barButtonColorPrimary = get_option($optionNameBarButtonColorPrimary);
        $barButtonColorSecondary = get_option($optionNameBarButtonColorSecondary);
        $privacyPolicyPage = get_option($optionNamePrivacyPolicyPage);
        $privacyPolicyText = esc_html(Integration::getPrivacyPolicyText());
        $enablePrivacyPolicyExternal = Helper::isEnabled('enable_privacy_policy_extern', 'settings');
        $privacyPolicyLink = esc_html(Integration::getPrivacyPolicyLink());
        $enableAccessRequest = Helper::isEnabled('enable_access_request', 'settings');
        $accessRequestPage = get_option($optionNameAccessRequestPage);
        $accessRequestFormCheckboxText = Integration::getAccessRequestFormCheckboxText(false);
        $deleteRequestFormExplanationText = Integration::getDeleteRequestFormExplanationText(false);
        $consentsBarExplanationText = Consent::getBarExplanationText();
        $consentsBarMoreInformationText = Consent::getBarMoreInformationText();
        $consentsBarButtonText = Consent::getBarButtonText();
        $consentsModalTitle = Consent::getModalTitle();
        $consentsModalExplanationText = Consent::getModalExplanationText();
        ?>
        <form method="post" action="<?php echo admin_url('options.php'); ?>" novalidate="novalidate">
            <?php settings_fields(PS_DSGVO_C_SLUG . '_settings'); ?>
            <p><strong><?php _e('Datenschutz-Bestimmungen', PS_DSGVO_C_SLUG); ?></strong></p>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNameEnablePrivacyPolicyExternal; ?>"><?php _e('Aktivieren', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <label><input type="checkbox" name="<?php echo $optionNameEnablePrivacyPolicyExternal; ?>"
                                  id="<?php echo $optionNameEnablePrivacyPolicyExternal; ?>" value="1"
                                  tabindex="1" <?php checked(true, $enablePrivacyPolicyExternal); ?> /> <?php _e('Aktiviere externe Links', PS_DSGVO_C_SLUG); ?>
                    </label>
                    <div class="psdsgvo-information">
                        <div class="psdsgvo-message psdsgvo-message--notice">
                            <?php
                            printf(
                                '<p><strong>%s:</strong> %s</p>',
                                strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                                sprintf(
                                    __('Wenn Du dies aktivierst, kannst Du externe Datenschutzrichtlinieninstanzen verwenden', PS_DSGVO_C_SLUG)
                                )
                            );
                            ?>
                        </div>
                        <?php
                        if ($enablePrivacyPolicyExternal !== true) {
                            if (empty($privacyPolicyPage) || $privacyPolicyPage === '' || $privacyPolicyPage < 1) { ?>
                                <br>
                                <div class="psdsgvo-message psdsgvo-message--notice">
                                    <?php
                                    printf(
                                        '<p><strong>%s:</strong> %s</p>',
                                        strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                                        sprintf(
                                            __('Derzeit ist keine Seite mit Datenschutzrichtlinien ausgewählt', PS_DSGVO_C_SLUG)
                                        )
                                    );
                                    ?>
                                </div>
                            <?php }
                        } ?>
                    </div>
                </div>
            </div>
            <?php if ($enablePrivacyPolicyExternal) : ?>
                <div class="psdsgvo-setting">
                    <label for="<?php echo $optionNamePrivacyPolicyLink; ?>"><?php _e('Link zur externen Datenschutzrichtlinie', PS_DSGVO_C_SLUG); ?></label>
                    <div class="psdsgvo-options">
                        <input type="url" name="<?php echo $optionNamePrivacyPolicyLink; ?>" class="regular-text"
                               id="<?php echo $optionNamePrivacyPolicyLink; ?>"
                               placeholder="<?php echo $privacyPolicyLink; ?>"
                               value="<?php echo $privacyPolicyLink; ?>"/>
                    </div>
                </div>
            <?php else: ?>
                <div class="psdsgvo-setting">
                    <label for="<?php echo $optionNamePrivacyPolicyPage; ?>"><?php _e('Datenschutz-Bestimmungen', PS_DSGVO_C_SLUG); ?></label>
                    <div class="psdsgvo-options">
                        <?php
                        wp_dropdown_pages(array(
                            'post_status' => 'publish,private,draft',
                            'show_option_none' => __('Wähle eine Option', PS_DSGVO_C_SLUG),
                            'name' => $optionNamePrivacyPolicyPage,
                            'selected' => $privacyPolicyPage
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNamePrivacyPolicyText; ?>"><?php _e('Link Text', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="text" name="<?php echo $optionNamePrivacyPolicyText; ?>" class="regular-text"
                           id="<?php echo $optionNamePrivacyPolicyText; ?>"
                           placeholder="<?php echo $privacyPolicyText; ?>" value="<?php echo $privacyPolicyText; ?>"/>
                </div>
            </div>
            <p><strong><?php _e('Benutzerdaten anfordern', PS_DSGVO_C_SLUG); ?></strong></p>
            <div class="psdsgvo-information">
                <p><?php _e('Ermögliche den Besuchern Deiner Webseite, ihre in der WordPress-Datenbank gespeicherten Daten anzufordern (Kommentare, WooCommerce-Bestellungen usw.). Die gefundenen Daten werden an ihre E-Mail-Adresse gesendet und ermöglichen es ihnen, eine zusätzliche Anfrage zur Anonymisierung der Daten zu stellen.', PS_DSGVO_C_SLUG); ?></p>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNameEnableAccessRequest; ?>"><?php _e('Aktivieren', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <label><input type="checkbox" name="<?php echo $optionNameEnableAccessRequest; ?>"
                                  id="<?php echo $optionNameEnableAccessRequest; ?>" value="1"
                                  tabindex="1" <?php checked(true, $enableAccessRequest); ?> /> <?php _e('Seite aktivieren', PS_DSGVO_C_SLUG); ?>
                    </label>
                    <div class="psdsgvo-information">
                        <div class="psdsgvo-message psdsgvo-message--notice">
                            <?php
                            printf(
                                '<p><strong>%s:</strong> %s</p>',
                                strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                                sprintf(
                                    __('Wenn Du dies aktivierst, wird eine private Seite erstellt, die den erforderlichen Shortcode enthält: %s. Du kannst selbst bestimmen, wann und wie diese Seite veröffentlicht werden soll.', PS_DSGVO_C_SLUG),
                                    '<span class="psdsgvo-pre"><strong>[psdsgvo_access_request_form]</strong></span>'
                                )
                            );
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($enableAccessRequest) : ?>
                <div class="psdsgvo-setting">
                    <label for="<?php echo $optionNameAccessRequestPage; ?>"><?php _e('Seite', PS_DSGVO_C_SLUG); ?></label>
                    <div class="psdsgvo-options">
                        <?php
                        wp_dropdown_pages(array(
                            'post_status' => 'publish,private,draft',
                            'show_option_none' => __('Wähle eine Option', PS_DSGVO_C_SLUG),
                            'name' => $optionNameAccessRequestPage,
                            'selected' => $accessRequestPage
                        ));
                        ?>
                        <?php if (!empty($accessRequestPage)) : ?>
                            <div class="psdsgvo-information">
                                <?php printf('<p><a href="%s">%s</a></p>', get_edit_post_link($accessRequestPage), __('Klicke hier, um diese Seite zu bearbeiten', PS_DSGVO_C_SLUG)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="psdsgvo-setting">
                    <label for="<?php echo $optionNameAccessRequestFormCheckboxText; ?>"><?php _e('Kontrollkästchentext', PS_DSGVO_C_SLUG); ?></label>
                    <div class="psdsgvo-options">
                        <input type="text" name="<?php echo $optionNameAccessRequestFormCheckboxText; ?>"
                               class="regular-text" id="<?php echo $optionNameAccessRequestFormCheckboxText; ?>"
                               placeholder="<?php echo $accessRequestFormCheckboxText; ?>"
                               value="<?php echo $accessRequestFormCheckboxText; ?>"/>
                    </div>
                </div>
                <div class="psdsgvo-setting">
                    <label for="<?php echo $optionNameDeleteRequestFormExplanationText; ?>"><?php _e('Erklärung der Anonymisierungsanfrage', PS_DSGVO_C_SLUG); ?></label>
                    <div class="psdsgvo-options">
                        <textarea name="<?php echo $optionNameDeleteRequestFormExplanationText; ?>" rows="5"
                                  id="<?php echo $optionNameAccessRequestFormCheckboxText; ?>"
                                  placeholder="<?php echo $deleteRequestFormExplanationText; ?>"><?php echo $deleteRequestFormExplanationText; ?></textarea>
                        <?php echo Helper::getAllowedHTMLTagsOutput(); ?>
                    </div>
                </div>
            <?php endif; ?>
            <p><strong><?php _e('Zustimmung', PS_DSGVO_C_SLUG); ?></strong></p>
            <div class="psdsgvo-information">
                <p><?php _e('Deine Besucher können über eine Zustimmungsleiste am unteren Bildschirmrand allen erstellten Zustimmungen (Skripten) die Berechtigung erteilen. Dort können sie auch auf ihre persönlichen Einstellungen zugreifen, um einzelnen Zustimmungen die Erlaubnis zu erteilen oder zu verweigern. Sobald ihre Einstellungen gespeichert sind, verschwindet die Leiste für 365 Tage.', PS_DSGVO_C_SLUG); ?></p>
                <div class="psdsgvo-message psdsgvo-message--notice">
                    <?php
                    printf(
                        '<p><strong>%s:</strong> %s</p>',
                        strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                        sprintf(
                            __('Lasse Deine Besucher erneut auf ihre Einstellungen zugreifen, indem Du einen Link zum Modal mit dem Shortcode %s platzierst oder die Klasse "%s" zu einem Menüelement hinzufügst.', PS_DSGVO_C_SLUG),
                            sprintf(
                                '<span class="psdsgvo-pre"><strong>[psdsgvo_consents_settings_link]<em>%s</em>[/psdsgvo_consents_settings_link]</strong></span>',
                                __('Meine Einstellungen', PS_DSGVO_C_SLUG)
                            ),
                            '<span class="psdsgvo-pre"><strong>psdsgvo-consents-settings-link</strong></span>'
                        )
                    );
                    ?>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo htmlspecialchars($optionNameConsentsBarExplanationText); ?>"><?php _e('Leiste: Erläuterung', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <textarea name="<?php echo htmlspecialchars($optionNameConsentsBarExplanationText); ?>" rows="2"
                              id="<?php echo htmlspecialchars($optionNameConsentsBarExplanationText); ?>"
                              placeholder="<?php echo htmlspecialchars($consentsBarExplanationText); ?>"><?php echo htmlspecialchars($consentsBarExplanationText); ?></textarea>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo htmlspecialchars($optionNameConsentsBarMoreInformationText); ?>"><?php _e('Leiste: Weitere Informationen Text', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="text" name="<?php echo htmlspecialchars($optionNameConsentsBarMoreInformationText); ?>"
                            class="regular-text" id="<?php echo htmlspecialchars($optionNameConsentsBarMoreInformationText); ?>"
                            placeholder="<?php echo htmlspecialchars($consentsBarMoreInformationText); ?>"
                            value="<?php echo htmlspecialchars($consentsBarMoreInformationText); ?>"/>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo htmlspecialchars($optionNameConsentsBarButtonText); ?>"><?php _e('Leiste: Schaltflächentext akzeptieren', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="text" name="<?php echo htmlspecialchars($optionNameConsentsBarButtonText); ?>"
                            class="regular-text" id="<?php echo htmlspecialchars($optionNameConsentsBarButtonText); ?>"
                            placeholder="<?php echo htmlspecialchars($consentsBarButtonText); ?>"
                            value="<?php echo htmlspecialchars($consentsBarButtonText); ?>"/>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNameLeisteFarbe; ?>"><?php _e('Leiste Farbe', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="color" name="<?php echo $optionNameLeisteFarbe; ?>"
                           id="<?php echo $optionNameLeisteFarbe; ?>" value="<?php echo $barColor; ?>">
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNameBarTextColor; ?>"><?php _e('Leiste Textfarbe', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="color" name="<?php echo $optionNameBarTextColor; ?>"
                           id="<?php echo $optionNameBarTextColor; ?>" value="<?php echo $barTextColor; ?>">
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNameBarButtonColorPrimary; ?>"><?php _e('Button Background Color', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="color" name="<?php echo $optionNameBarButtonColorPrimary; ?>"
                           id="<?php echo $optionNameBarButtonColorPrimary; ?>"
                           value="<?php echo $barButtonColorPrimary; ?>">
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo $optionNameBarButtonColorSecondary; ?>"><?php _e('Button Text Color', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="color" name="<?php echo $optionNameBarButtonColorSecondary; ?>"
                           id="<?php echo $optionNameBarButtonColorSecondary; ?>"
                           value="<?php echo $barButtonColorSecondary; ?>">
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo htmlspecialchars($optionNameConsentsModalTitle); ?>"><?php _e('Modal: Title', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="text" name="<?php echo htmlspecialchars($optionNameConsentsModalTitle); ?>"
                           class="regular-text" id="<?php echo htmlspecialchars($optionNameConsentsModalTitle); ?>"
                           placeholder="<?php echo htmlspecialchars($consentsModalTitle); ?>"
                           value="<?php echo htmlspecialchars($consentsModalTitle); ?>"/>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="<?php echo htmlspecialchars($optionNameConsentsModalExplanationText); ?>"><?php _e('Modal: Erklärung', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <textarea name="<?php echo htmlspecialchars($optionNameConsentsModalExplanationText); ?>" rows="5"
                              id="<?php echo htmlspecialchars($optionNameConsentsModalExplanationText); ?>"
                              placeholder="<?php echo htmlspecialchars($consentsModalExplanationText); ?>"><?php echo htmlspecialchars($consentsModalExplanationText); ?></textarea>
                    <?php echo Helper::getAllowedHTMLTagsOutput(); ?>
                </div>
            </div>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * @param int $consentId
     */
    private static function renderManageConsentPage($consentId = 0) {
        wp_enqueue_style('psdsgvo.admin.codemirror.css');
        wp_enqueue_script('psdsgvo.admin.codemirror.additional.js');
        $consent = new Consent($consentId);
        if (isset($_POST['submit']) && check_admin_referer('consent_create_or_update', 'consent_nonce')) {
            $active = (isset($_POST['active'])) ? 1 : 0;
            $title = (isset($_POST['title'])) ? stripslashes(esc_html($_POST['title'])) : $consent->getTitle();
            $description = (isset($_POST['description'])) ? stripslashes(wp_kses($_POST['description'], Helper::getAllowedHTMLTags(''))) : $consent->getDescription();
            $snippet = (isset($_POST['snippet'])) ? stripslashes($_POST['snippet']) : $consent->getSnippet();
            $wrap = (isset($_POST['wrap']) && array_key_exists($_POST['wrap'], Consent::getPossibleCodeWraps())) ? esc_html($_POST['wrap']) : $consent->getWrap();
            $placement = (isset($_POST['placement']) && array_key_exists($_POST['placement'], Consent::getPossiblePlacements())) ? esc_html($_POST['placement']) : $consent->getPlacement();
            $required = (isset($_POST['required'])) ? 1 : 0;
            $consent->setTitle($title);
            $consent->setDescription($description);
            $consent->setSnippet($snippet);
            $consent->setWrap($wrap);
            $consent->setPlacement($placement);
            $consent->setRequired($required);
            $consent->setActive($active);
            $id = $consent->save();
            if (!empty($id)) {
                Helper::showAdminNotice('psdsgvo-consent-updated');
                Helper::resetCookieBar();
            }
        }
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('consent_create_or_update', 'consent_nonce'); ?>
            <p><strong><?php _e('Neue Zustimmung hinzufügen', PS_DSGVO_C_SLUG); ?></strong></p>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_active"><?php _e('Aktiv', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <label><input type="checkbox" name="active" id="psdsgvo_active"
                                  value="1" <?php checked(1, $consent->getActive()); ?> /> <?php _e('Ja', PS_DSGVO_C_SLUG); ?>
                    </label>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_title"><?php _e('Titel', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <input type="text" name="title" class="regular-text" id="psdsgvo_title"
                           value="<?php echo $consent->getTitle(); ?>" required="required"/>
                    <div class="psdsgvo-information">
                        <p><?php _e('z.B. "Google Analytics" oder "Werbung""', PS_DSGVO_C_SLUG); ?></p>
                    </div>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_description"><?php _e('Beschreibung', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <textarea name="description" id="psdsgvo_description" rows="5" autocomplete="false"
                              autocorrect="false" autocapitalize="false"
                              spellcheck="false"><?php echo $consent->getDescription(); ?></textarea>
                    <div class="psdsgvo-information">
                        <p><?php _e('Beschreibe Dein Einwilligungsskript so gründlich wie möglich. %privacy_policy% funktioniert nicht.', PS_DSGVO_C_SLUG); ?></p>
                    </div>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_snippet"><?php _e('Code-Auszug', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <textarea name="snippet" id="psdsgvo_snippet" rows="10" autocomplete="false" autocorrect="false"
                              autocapitalize="false"
                              spellcheck="false"><?php echo htmlspecialchars($consent->getSnippet(), ENT_QUOTES, get_option('blog_charset')); ?></textarea>
                    <div class="psdsgvo-information">
                        <p><?php _e('Codefragmente für Google Analytics, Facebook Pixel usw..', PS_DSGVO_C_SLUG); ?></p>
                    </div>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_code_wrap"><?php _e('Code Wrapper', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <select name="wrap" id="psdsgvo_code_wrap">
                        <?php
                        foreach (Consent::getPossibleCodeWraps() as $value => $label) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                $value,
                                selected($value, $consent->getWrap(), false),
                                $label
                            );
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_placement"><?php _e('Platzierung', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <select name="placement" id="psdsgvo_placement">
                        <?php
                        foreach (Consent::getPossiblePlacements() as $value => $label) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                $value,
                                selected($value, $consent->getPlacement(), false),
                                $label
                            );
                        }
                        ?>
                    </select>
                    <div class="psdsgvo-information">
                        <?php
                        printf(
                            '<strong>%s:</strong> %s<br />',
                            strtoupper(__('Header', PS_DSGVO_C_SLUG)),
                            __('Das Snippet wird am Ende des HEAD-Tags hinzugefügt.', PS_DSGVO_C_SLUG)
                        );
                        printf(
                            '<strong>%s:</strong> %s<br />',
                            strtoupper(__('Body', PS_DSGVO_C_SLUG)),
                            __('Das Snippet wird direkt nach dem BODY-Tag hinzugefügt.', PS_DSGVO_C_SLUG)
                        );
                        printf(
                            '<strong>%s:</strong> %s',
                            strtoupper(__('Footer', PS_DSGVO_C_SLUG)),
                            __('Das Snippet wird am Ende des BODY-Tags hinzugefügt.', PS_DSGVO_C_SLUG)
                        );
                        ?>
                    </div>
                </div>
            </div>
            <div class="psdsgvo-setting">
                <label for="psdsgvo_active"><?php _e('Erforderlich', PS_DSGVO_C_SLUG); ?></label>
                <div class="psdsgvo-options">
                    <label><input type="checkbox" name="required" id="psdsgvo-required"
                                  value="1" <?php checked(1, $consent->getRequired()); ?> /> <?php _e('Ja', PS_DSGVO_C_SLUG); ?>
                    </label>
                    <div class="psdsgvo-information">
                        <p><?php _e('Wenn Du dieses Kontrollkästchen aktivierst, wird diese Zustimmung immer ausgelöst, sodass Benutzer sich nicht anmelden oder abmelden können.', PS_DSGVO_C_SLUG); ?></p>
                    </div>
                </div>
            </div>
            <p class="submit">
                <?php submit_button((!empty($consentId) ? __('Aktualisieren', PS_DSGVO_C_SLUG) : __('Hinzufügen', PS_DSGVO_C_SLUG)), 'primary', 'submit', false); ?>
                <a class="button button-secondary"
                   href="<?php echo Helper::getPluginAdminUrl('consents'); ?>"><?php _e('Zurück zur Übersicht', PS_DSGVO_C_SLUG); ?></a>
            </p>
        </form>
        <?php
    }

    private static function renderConsentsPage() {
        if (isset($_POST['reset-cookie-bar'])) {
            Helper::resetCookieBar();
            Helper::showAdminNotice('psdsgvo-cookie-bar-reset');
        }
        $paged = (isset($_REQUEST['paged'])) ? absint($_REQUEST['paged']) : 1;
        $limit = 20;
        $offset = ($paged - 1) * $limit;
        $total = Consent::getInstance()->getTotal();
        $numberOfPages = ceil($total / $limit);
        $consents = Consent::getInstance()->getList(array(), $limit, $offset);
        ?>
        <div class="psdsgvo-message psdsgvo-message--notice">
            <p><?php _e('Bitte Deine Besucher um Erlaubnis, bestimmte Skripte für Tracking- oder Werbezwecke zu aktivieren. Füge für jeden Skripttyp, für den Du eine Berechtigung anforderst, eine Zustimmung hinzu. Skripte werden nur aktiviert, wenn die Erlaubnis erteilt wurde.', PS_DSGVO_C_SLUG); ?></p>
            <p><a class="button button-primary"
                  href="<?php echo Helper::getPluginAdminUrl('consents', array('action' => 'create')); ?>"><?php _ex('Neue hinzufügen', 'consent', PS_DSGVO_C_SLUG); ?></a>
            </p>
        </div>
        <div class="psdsgvo-message psdsgvo-message--notice">
            <p><?php _e('Klicke auf diese Schaltfläche, wenn Du die Zustimmungsleiste zurücksetzen möchtest. Dies bedeutet, dass die Zustimmungsleiste für alle Benutzer erneut angezeigt wird.', PS_DSGVO_C_SLUG); ?></p>
            <form method="post">
                <button type="submit" class="button button-primary" name="reset-cookie-bar">Zustimmungsleiste zurücksetzen</button>
            </form>
        </div>
        <?php if (!empty($consents)) : ?>
            <table class="psdsgvo-table">
                <thead>
                <tr>
                    <th scope="col" width="10%"><?php _e('Zustimmung', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="16%"><?php _e('Titel', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="12%"><?php _e('Erforderlich', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="20%"><?php _e('Geändert am', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="20%"><?php _e('Erstellt am', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="14%"><?php _e('Aktion', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="8%"><?php _e('Aktiv', PS_DSGVO_C_SLUG); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($consents as $consent) :
                    $title = $consent->getTitle();
                    ?>
                    <tr class="psdsgvo-table__row <?php echo (!$consent->getActive()) ? 'psdsgvo-table__row--expired' : ''; ?>">
                        <td><?php printf('#%d', $consent->getId()); ?></td>
                        <td>
                            <?php
                            printf(
                                '<a href="%s">%s</a>',
                                Consent::getActionUrl($consent->getId()),
                                ((!empty($title)) ? $title : __('(kein Titel)', PS_DSGVO_C_SLUG))
                            );
                            ?>
                        </td>
                        <td><?php echo ($consent->getRequired()) ? __('Ja', PS_DSGVO_C_SLUG) : __('Nein', PS_DSGVO_C_SLUG); ?></td>
                        <td><?php echo $consent->getDateModified(); ?></td>
                        <td><?php echo $consent->getDateCreated(); ?></td>
                        <td>
                            <?php
                            printf(
                                '%s | %s',
                                sprintf(
                                    '<a href="%s">%s</a>',
                                    Consent::getActionUrl($consent->getId()),
                                    __('Bearbeiten', PS_DSGVO_C_SLUG)
                                ),
                                sprintf(
                                    '<a href="%s">%s</a>',
                                    Consent::getActionUrl($consent->getId(), 'delete'),
                                    __('Entfernen', PS_DSGVO_C_SLUG)
                                )
                            );
                            ?>
                        </td>
                        <td><?php echo ($consent->getActive()) ? __('Ja', PS_DSGVO_C_SLUG) : __('Nein', PS_DSGVO_C_SLUG); ?></td>
                    </tr>
                <?php
                endforeach;
                ?>
                </tbody>
            </table>
            <div class="psdsgvo-pagination">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(
                        999999999,
                        '%#%',
                        Helper::getPluginAdminUrl('consents', array('paged' => 999999999))
                    ),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $numberOfPages,
                    'prev_text' => '&lsaquo;',
                    'next_text' => '&rsaquo;',
                    'before_page_number' => '<span>',
                    'after_page_number' => '</span>'
                ));
                printf('<span class="psdsgvo-pagination__results">%s</span>', sprintf(__('%d von %d Ergebnisse gefunden', PS_DSGVO_C_SLUG), count($consents), $total));
                ?>
            </div>
        <?php else : ?>
            <p><strong><?php _e('Keine Einwilligungen gefunden.', PS_DSGVO_C_SLUG); ?></strong></p>
        <?php endif; ?>
        <?php
    }

    /**
     * @param int $requestId
     */
    private static function renderManageRequestPage($requestId = 0) {
        $accessRequest = new AccessRequest($requestId);
        $filters = array(
            'access_request_id' => array(
                'value' => $accessRequest->getId(),
            ),
        );
        $paged = (isset($_REQUEST['paged'])) ? absint($_REQUEST['paged']) : 1;
        $limit = 20;
        $offset = ($paged - 1) * $limit;
        $total = DeleteRequest::getInstance()->getTotal($filters);
        $numberOfPages = ceil($total / $limit);
        $requests = DeleteRequest::getInstance()->getList($filters, $limit, $offset);
        if (!empty($requests)) :
            ?>
            <div class="psdsgvo-message psdsgvo-message--notice">
                <p><?php _e('Anonymisiere eine Anfrage, indem Du das Kontrollkästchen aktivierst und unten auf die grüne Schaltfläche zum Anonymisieren klickst.', PS_DSGVO_C_SLUG); ?></p>
                <p>
                    <?php printf('<strong>%s:</strong> %s', __('WordPress Benutzer', PS_DSGVO_C_SLUG), 'Anonymisiert Vor- und Nachnamen, Anzeigenamen, Spitznamen und E-Mail-Adresse.', PS_DSGVO_C_SLUG); ?>
                    <br/>
                    <?php printf('<strong>%s:</strong> %s', __('WordPress Kommentare', PS_DSGVO_C_SLUG), 'Anonymisiert Autorennamen, E-Mail-Adresse und IP-Adresse.', PS_DSGVO_C_SLUG); ?>
                    <br/>
                    <?php printf('<strong>%s:</strong> %s', __('WooCommerce', PS_DSGVO_C_SLUG), 'Anonymisiert Rechnungs- und Versanddetails pro Bestellung.', PS_DSGVO_C_SLUG); ?>
                </p>
                <?php
                printf(
                    '<p><strong>%s:</strong> %s</p>',
                    strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                    sprintf(__('Anfragen werden nach %d Tagen automatisch anonymisiert.', PS_DSGVO_C_SLUG), 30)
                );
                ?>
            </div>

            <form class="psdsgvo-form psdsgvo-form--process-delete-requests" method="POST" novalidate="novalidate">
                <div class="psdsgvo-message" style="display: none;"></div>
                <table class="psdsgvo-table">
                    <thead>
                    <tr>
                        <th scope="col" width="10%"><?php _e('Anfrage', PS_DSGVO_C_SLUG); ?></th>
                        <th scope="col" width="22%"><?php _e('Typ', PS_DSGVO_C_SLUG); ?></th>
                        <th scope="col" width="18%"><?php _e('IP Addresse', PS_DSGVO_C_SLUG); ?></th>
                        <th scope="col" width="22%"><?php _e('Datum', PS_DSGVO_C_SLUG); ?></th>
                        <th scope="col" width="12%"><?php _e('Verarbeitet', PS_DSGVO_C_SLUG); ?></th>
                        <th scope="col" width="10%"><?php _e('Aktion', PS_DSGVO_C_SLUG); ?></th>
                        <th scope="col" width="6%"><input type="checkbox" class="psdsgvo-select-all"/></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    /** @var DeleteRequest $request */
                    foreach ($requests as $request) :
                        ?>
                        <tr class="psdsgvo-table__row <?php echo ($request->isAnonymised()) ? 'psdsgvo-table__row--expired' : ''; ?>"
                            data-id="<?php echo $request->getId(); ?>">
                            <td><?php printf('#%d', $request->getId()); ?></td>
                            <td><?php echo $request->getNiceTypeLabel(); ?></td>
                            <td><?php echo $request->getIpAddress(); ?></td>
                            <td><?php echo $request->getDateCreated(); ?></td>
                            <td>
                                <span class="dashicons dashicons-<?php echo ($request->getProcessed()) ? 'yes' : 'no'; ?>"></span>
                            </td>
                            <td>
                                <?php
                                if ($request->getDataId() !== 0 && !$request->isAnonymised()) {
                                    printf('<a target="_blank" href="%s">%s</a>', $request->getManageUrl(), __('Ansicht', PS_DSGVO_C_SLUG));
                                } else {
                                    _e('N/A', PS_DSGVO_C_SLUG);
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (!$request->getProcessed() && !$request->isAnonymised()) {
                                    printf('<input type="checkbox" class="psdsgvo-checkbox" value="%d" />', $request->getId());
                                } else {
                                    echo '&nbsp;';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php
                    endforeach;
                    ?>
                    </tbody>
                </table>
                <?php submit_button(__('Ausgewählte Anfrage(n) anonymisieren', PS_DSGVO_C_SLUG), 'primary psdsgvo-remove'); ?>
            </form>

            <div class="psdsgvo-pagination">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(
                        999999999,
                        '%#%',
                        Helper::getPluginAdminUrl('requests', array('paged' => 999999999))
                    ),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $numberOfPages,
                    'prev_text' => '&lsaquo;',
                    'next_text' => '&rsaquo;',
                    'before_page_number' => '<span>',
                    'after_page_number' => '</span>'
                ));
                printf('<span class="psdsgvo-pagination__results">%s</span>', sprintf(__('%d von %d Ergebnisse gefunden', PS_DSGVO_C_SLUG), count($requests), $total));
                ?>
            </div>
        <?php
        else :
            ?>
            <p><strong><?php _e('Keine Anfragen gefunden.', PS_DSGVO_C_SLUG); ?></strong></p>
        <?php
        endif;
        ?>
        <?php
    }

    /**
     * Page: Requests
     */
    private static function renderRequestsPage() {
        $paged = (isset($_REQUEST['paged'])) ? absint($_REQUEST['paged']) : 1;
        $limit = 20;
        $offset = ($paged - 1) * $limit;
        $total = AccessRequest::getInstance()->getTotal();
        $numberOfPages = ceil($total / $limit);
        $requests = AccessRequest::getInstance()->getList(array(), $limit, $offset);
        if (!empty($requests)) :
            ?>
            <div class="psdsgvo-message psdsgvo-message--notice">
                <?php
                printf(
                    '<p><strong>%s:</strong> %s</p>',
                    strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                    sprintf(__('Anfragen werden nach %d Tagen automatisch anonymisiert.', PS_DSGVO_C_SLUG), 30)
                );
                ?>
            </div>
            <table class="psdsgvo-table">
                <thead>
                <tr>
                    <th scope="col" width="10%"><?php _e('ID', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="20%"><?php _e('Anfragen zu bearbeiten', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="22%"><?php _e('Email Addresse', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="18%"><?php _e('IP Addresse', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="22%"><?php _e('Datum', PS_DSGVO_C_SLUG); ?></th>
                    <th scope="col" width="8%"><?php _e('Status', PS_DSGVO_C_SLUG); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                /** @var AccessRequest $request */
                foreach ($requests as $request) :
                    $amountOfNonAnonymisedDeleteRequests = DeleteRequest::getInstance()->getAmountByAccessRequestId($request->getId(), false);
                    $amountOfDeleteRequests = DeleteRequest::getInstance()->getAmountByAccessRequestId($request->getId());
                    ?>
                    <tr class="psdsgvo-table__row <?php echo ($request->getExpired() || $request->isAnonymised()) ? 'psdsgvo-table__row--expired' : ''; ?>">
                        <td><?php printf('#%d', $request->getId()); ?></td>
                        <td>
                            <?php printf('%d', $amountOfNonAnonymisedDeleteRequests); ?>
                            <?php
                            if ($amountOfDeleteRequests > 0) {
                                printf(
                                    '<a href="%s">%s</a>',
                                    Helper::getPluginAdminUrl('requests', array('id' => $request->getId())),
                                    __('Verwalten', PS_DSGVO_C_SLUG)
                                );
                            }
                            ?>
                        </td>
                        <td><?php echo $request->getEmailAddress(); ?></td>
                        <td><?php echo $request->getIpAddress(); ?></td>
                        <td><?php echo $request->getDateCreated(); ?></td>
                        <td><?php echo ($request->getExpired()) ? __('Abgelaufen', PS_DSGVO_C_SLUG) : __('Aktiv', PS_DSGVO_C_SLUG); ?></td>
                    </tr>
                <?php
                endforeach;
                ?>
                </tbody>
            </table>
            <div class="psdsgvo-pagination">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(
                        999999999,
                        '%#%',
                        Helper::getPluginAdminUrl('requests', array('paged' => 999999999))
                    ),
                    'format' => '?paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $numberOfPages,
                    'prev_text' => '&lsaquo;',
                    'next_text' => '&rsaquo;',
                    'before_page_number' => '<span>',
                    'after_page_number' => '</span>'
                ));
                printf('<span class="psdsgvo-pagination__results">%s</span>', sprintf(__('%d von %d Ergebnisse gefunden', PS_DSGVO_C_SLUG), count($requests), $total));
                ?>
            </div>
        <?php
        else :
            ?>
            <p><strong><?php _e('Keine Anfragen gefunden.', PS_DSGVO_C_SLUG); ?></strong></p>
        <?php
        endif;
    }

    /**
     * Page: Embed Settings (Externe Inhalte)
     */
    private static function renderEmbedSettingsPage() {
        $embedSettings = \PSDSGVO\Includes\Extensions\ContentEmbeds::getInstance();
        $embedTypes = $embedSettings->getEmbedTypes();
        ?>
        <p><?php _e('Verwalte externe Inhalte wie YouTube-Videos, Vimeo, Google Maps und andere Embeds. Wenn aktiviert, werden diese Inhalte durch Platzhalter ersetzt, bis der Benutzer seine Zustimmung gibt.', PS_DSGVO_C_SLUG); ?></p>
        
        <div class="psdsgvo-message psdsgvo-message--notice">
            <?php
            printf(
                '<p><strong>%s:</strong> %s</p>',
                strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                __('Externe Inhalte werden standardmäßig blockiert und erst nach Zustimmung des Besuchers geladen. Dies hilft, die DSGVO-Konformität Ihrer Website zu verbessern.', PS_DSGVO_C_SLUG)
            );
            ?>
        </div>

        <form method="post" action="<?php echo admin_url('options.php'); ?>" novalidate="novalidate">
            <?php settings_fields(PS_DSGVO_C_SLUG . '_embed_settings'); ?>
            
            <ul class="psdsgvo-list">
                <?php foreach ($embedTypes as $typeId => $config) : 
                    $optionName = PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId;
                    $optionNameMessage = PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId . '_message';
                    $checked = $embedSettings->isEmbedTypeEnabled($typeId);
                    $customMessage = get_option($optionNameMessage, '');
                    ?>
                    <li class="psdsgvo-clearfix">
                        <div class="psdsgvo-checkbox">
                            <input type="checkbox" name="<?php echo $optionName; ?>"
                                   id="<?php echo $optionName; ?>" value="1" tabindex="1"
                                   data-option="<?php echo $optionName; ?>" <?php checked(true, $checked); ?> />
                            <label for="<?php echo $optionName; ?>">
                                <strong><?php echo esc_html($config['label']); ?></strong>
                            </label>
                            <span class="psdsgvo-instructions"><?php _e('Aktivieren:', PS_DSGVO_C_SLUG); ?></span>
                            <div class="psdsgvo-switch" aria-hidden="true">
                                <div class="psdsgvo-switch-label">
                                    <div class="psdsgvo-switch-inner"></div>
                                    <div class="psdsgvo-switch-switch"></div>
                                </div>
                            </div>
                        </div>

                        <div class="psdsgvo-checkbox-data" <?php if (!$checked) : ?>style="display: none;"<?php endif; ?>>
                            <div class="psdsgvo-checklist-description">
                                <p><?php echo esc_html($config['description']); ?></p>
                                <p><em><?php echo esc_html($config['cookie_info']); ?></em></p>
                            </div>
                            
                            <div class="psdsgvo-setting">
                                <label for="<?php echo $optionNameMessage; ?>"><?php _e('Platzhalter-Nachricht', PS_DSGVO_C_SLUG); ?></label>
                                <div class="psdsgvo-options">
                                    <textarea name="<?php echo $optionNameMessage; ?>" rows="3"
                                              class="regular-text" id="<?php echo $optionNameMessage; ?>"
                                              placeholder="<?php echo esc_attr($embedSettings->getPlaceholderMessage($typeId)); ?>"><?php echo esc_textarea($customMessage); ?></textarea>
                                    <p class="description">
                                        <?php _e('Diese Nachricht wird angezeigt, wenn der Inhalt blockiert ist.', PS_DSGVO_C_SLUG); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * @return null|Page
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
