<?php

namespace PSDSGVO\Includes;

use PSDSGVO\Includes\Extensions\CF7;
use PSDSGVO\Includes\Extensions\GForms;

/**
 * Class Action
 * @package PSDSGVO\Includes
 */
class Action {
    /** @var null */
    private static $instance = null;

    public function handleRedirects() {
        global $pagenow;
        if ($pagenow === 'tools.php' && isset($_REQUEST['page']) && $_REQUEST['page'] === str_replace('-', '_', PS_DSGVO_C_SLUG)) {
            $type = (isset($_REQUEST['type'])) ? esc_html($_REQUEST['type']) : false;
            if ($type !== false) {
                switch ($type) {
                    case 'consents' :
                        $action = (isset($_REQUEST['action'])) ? esc_html($_REQUEST['action']) : false;
                        switch ($action) {
                            case 'manage' :
                                $id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
                                if (!empty($id) && !Consent::getInstance()->exists($id)) {
                                    wp_safe_redirect(Helper::getPluginAdminUrl('consents', array('notice' => 'psdsgvo-consent-not-found')));
                                    exit;
                                }
                                break;
                            case 'create' :
                                $consent = new Consent();
                                $consent->setSiteId(get_current_blog_id());
                                $id = $consent->save();
                                if (!empty($id)) {
                                    wp_safe_redirect(add_query_arg(
                                        array('notice' => 'psdsgvo-consent-added'),
                                        Consent::getActionUrl($id)
                                    ));
                                    exit;
                                }
                                break;
                            case 'delete' :
                                $id = (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
                                if (!empty($id) && Consent::getInstance()->exists($id)) {
                                    $result = Consent::getInstance()->delete($id);
                                    if ($result !== false) {
                                        wp_safe_redirect(Helper::getPluginAdminUrl('consents', array('notice' => 'psdsgvo-consent-removed')));
                                        exit;
                                    }
                                }
                                break;
                        }
                        break;
                }
            }
        }
    }

    public function showAdminNotices() {
        if (!empty($_REQUEST['notice'])) {
            Helper::showAdminNotice(esc_html($_REQUEST['notice']));
        }
    }

    /**
     * Stop WordPress from sending anything but essential data during the update check
     * @param array $query
     * @return array
     */
    public function onlySendEssentialDataDuringUpdateCheck($query = array()) {
        unset($query['php']);
        unset($query['mysql']);
        unset($query['local_package']);
        unset($query['blogs']);
        unset($query['users']);
        unset($query['multisite_enabled']);
        unset($query['initial_db_version']);
        return $query;
    }

    public function processEnableAccessRequest() {
        $enabled = Helper::isEnabled('enable_access_request', 'settings');
        if ($enabled) {
            $accessRequest = AccessRequest::databaseTableExists();
            $deleteRequest = DeleteRequest::databaseTableExists();
            if (!$accessRequest || !$deleteRequest) {
                Helper::createUserRequestDataTables();
                $result = wp_insert_post(array(
                    'post_type' => 'page',
                    'post_status' => 'private',
                    'post_title' => __('Datenzugriffsanforderung', PS_DSGVO_C_SLUG),
                    'post_content' => '[psdsgvo_access_request_form]',
                    'meta_input' => array(
                        '_psdsgvo_access_request' => 1,
                    ),
                ), true);
                if (!is_wp_error($result)) {
                    update_option(PS_DSGVO_C_PREFIX . '_settings_access_request_page', $result);
                }
            }
        }
    }

    public function processToggleAccessRequest() {
        $page = Helper::getAccessRequestPage();
        if (!empty($page)) {
            $enabled = Helper::isEnabled('enable_access_request', 'settings');
            $status = ($enabled) ? 'private' : 'draft';
            wp_update_post(array(
                'ID' => $page->ID,
                'post_status' => $status
            ));
        }
    }

    public function showNoticesRequestUserData() {
        $enabled = Helper::isEnabled('enable_access_request', 'settings');
        if ($enabled) {
            $accessRequest = AccessRequest::databaseTableExists();
            $deleteRequest = DeleteRequest::databaseTableExists();
            if (!$accessRequest || !$deleteRequest) {
                $pluginData = Helper::getPluginData();
                printf(
                    '<div class="%s"><p><strong>%s:</strong> %s %s</p></div>',
                    'notice notice-error',
                    $pluginData['Name'],
                    __('Die erforderlichen Datenbanktabellen konnten nicht erstellt werden.', PS_DSGVO_C_SLUG),
                    sprintf(
                        '<a class="button" href="%s">%s</a>',
                        Helper::getPluginAdminUrl('', array('psdsgvo-action' => 'create_request_tables')),
                        __('Wiederholen', PS_DSGVO_C_SLUG)
                    )
                );
            }
        }
    }

    public function addConsentBar() {
    	$consentRequiredStatus = Action::checkAllConsentsRequired();
	    $consentLeisteFarbe = get_option(PS_DSGVO_C_PREFIX . '_settings_consents_bar_color');
    	$consentBarTextColor = get_option(PS_DSGVO_C_PREFIX . '_settings_consents_bar_text_color');
    	$consentBarButtonColor = get_option(PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_primary');
    	$consentBarButtonTextColor = get_option(PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_secondary');
    	$consentBarStyling = array(
    		'display: none;'
	    );
    	if (!empty($consentLeisteFarbe)) {
    		$consentBarStyling[] = 'background: ' . $consentLeisteFarbe .';';
	    }
	    $consentBarTextStyling = array();
	    if (!empty($consentBarTextColor)) {
    		$consentBarTextStyling[] = 'color: ' . $consentBarTextColor . ';';
	    }

	    $output = '<div class="psdsgvo psdsgvo-consent-bar" style="' . implode('', $consentBarStyling) . '">';
        $output .= '<div class="psdsgvo-consent-bar__container">';
	    $output .= '<div class="psdsgvo-consent-bar__content" ' .  (!empty($consentBarTextColor) ? 'style="' . implode('', $consentBarTextStyling) . '"' : '') . '>';
        $output .= '<div class="psdsgvo-consent-bar__column">';
        $output .= '<div class="psdsgvo-consent-bar__notice">';
        $output .= apply_filters('psdsgvo_the_content', Consent::getBarExplanationText());
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<div class="psdsgvo-consent-bar__column">';
	    $output .= sprintf(
		    '<a class="psdsgvo-consent-bar__settings" href="javascript:void(0);" data-micromodal-trigger="psdsgvo-consent-modal" aria-expanded="false" aria-haspopup="true">%s</a>',
		    Consent::getBarMoreInformationText()
	    );
        $output .= '</div>';
        $output .= '<div class="psdsgvo-consent-bar__column">';
	    $buttonStyling = array();
	    if (!empty($consentBarButtonColor)) {
		    $buttonStyling[] = 'background: ' . $consentBarButtonColor . ';';
            $buttonStyling[] = 'border-color: ' . $consentBarButtonColor . ';';
	    }
	    if (!empty($consentBarButtonTextColor)) {
		    $buttonStyling[] = 'color: ' . $consentBarButtonTextColor . ';';
	    }
	    $output .= sprintf(
		    '<button class="psdsgvo-button psdsgvo-consent-bar__button" ' .  (!empty($buttonStyling) ? 'style="' . implode('', $buttonStyling) . '"' : '') .'>%s</button>',
		    Consent::getBarButtonText()
	    );
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        echo apply_filters('psdsgvo_consent_bar', $output);
    }

	/**
	 * Checks if all the consents are required.
	 * @return bool
	 */
	public static function checkAllConsentsRequired() {
		$totalRequiredConsents = Consent::getInstance()->getList(array(
			'active' => array('value' => 1),
			'required' => array('value' => 1)
		));
		$totalActiveConsents = Consent::getInstance()->getList(array(
			'active' => array('value' => 1),
		));
		return sizeof($totalRequiredConsents) === sizeof($totalActiveConsents);
    }

    public function addConsentModal() {
    	$consentRequiredStatus = Action::checkAllConsentsRequired();
	    $consentModalButtonColor = get_option(PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_primary');
	    $consentModalButtonTextColor = get_option(PS_DSGVO_C_PREFIX . '_settings_consents_bar_button_color_secondary');
        $consentIds = (array)Helper::getConsentIdsByCookie();
        $consents = Consent::getInstance()->getList(array(
            'active' => array('value' => 1)
        ));
	    $text = ($consentRequiredStatus) ? Consent::getBarButtonText() : __('Speicher meine Einstellungen', PS_DSGVO_C_SLUG);
        $output = '<div class="psdsgvo psdsgvo-consent-modal" id="psdsgvo-consent-modal" aria-hidden="true">';
        $output .= '<div class="psdsgvo-consent-modal__overlay" tabindex="-1" data-micromodal-close>';
        $output .= '<div class="psdsgvo-consent-modal__container" role="dialog" aria-modal="true">';
        if (!empty($consents)) {
            $output .= '<nav class="psdsgvo-consent-modal__navigation">';
            $output .= sprintf(
                '<a class="psdsgvo-button psdsgvo-button--active" href="javascript:void(0);" data-target="description">%s</a>',
                Consent::getModalTitle()
            );
            /** @var Consent $consent */
            foreach ($consents as $consent) {
                $title = $consent->getTitle();
                $output .= sprintf(
                    '<a class="psdsgvo-button" href="javascript:void(0);" data-target="%d">%s</a>',
                    $consent->getId(),
                    ((!empty($title)) ? $title : __('(kein Titel)', PS_DSGVO_C_SLUG))
                );
            }
            $output .= '</nav>'; // .psdsgvo-consent-modal__navigation
            $output .= '<div class="psdsgvo-consent-modal__information">';
            $output .= '<div class="psdsgvo-consent-modal__description" data-target="description">';
            $output .= sprintf(
                '<p class="psdsgvo-consent-modal__title">%s</p>',
                Consent::getModalTitle()
            );
            $output .= apply_filters('psdsgvo_the_content', Consent::getModalExplanationText());
            $output .= apply_filters('psdsgvo_the_content', sprintf(
                '<strong>%s:</strong> %s',
                strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                __('Diese Einstellungen gelten nur für den Browser und das Gerät, die Du gerade verwendest.', PS_DSGVO_C_SLUG)
            ));
            $output .= '</div>'; // .psdsgvo-consent-modal__description
            /** @var Consent $consent */
            foreach ($consents as $consent) {
                $output .= sprintf(
                    '<div class="psdsgvo-consent-modal__description" style="display: none;" data-target="%d">',
                    $consent->getId()
                );
                $output .= sprintf('<p class="psdsgvo-consent-modal__title">%s</p>', $consent->getTitle());
                $output .= apply_filters('psdsgvo_the_content', $consent->getDescription());
                if (!$consent->getRequired()) {
                    $output .= '<div class="psdsgvo-checkbox">';
                    $output .= '<label>';
                    $output .= sprintf(
                        '<input type="checkbox" value="%d" %s />',
                        $consent->getId(),
                        checked(true, in_array($consent->getId(), $consentIds), false)
                    );
                    $output .= '<span class="psdsgvo-switch" aria-hidden="true">';
                    $output .= '<span class="psdsgvo-switch-label">';
                    $output .= '<span class="psdsgvo-switch-inner"></span>';
                    $output .= '<span class="psdsgvo-switch-switch"></span>';
                    $output .= '</span>';
                    $output .= '</span>';
                    $output .= __('Enable', PS_DSGVO_C_SLUG);
                    $output .= '</label>';
                    $output .= '</div>';
                }
                $output .= '</div>'; // .psdsgvo-consent-modal__description
            }
            $output .= '<footer class="psdsgvo-consent-modal__footer">';
	        $buttonStyling = array();
	        if (!empty($consentModalButtonColor)) {
		        $buttonStyling[] = 'background: ' . $consentModalButtonColor . ';';
                $buttonStyling[] = 'border-color: ' . $consentModalButtonColor . ';';
	        }
	        if (!empty($consentModalButtonTextColor)) {
		        $buttonStyling[] = 'color: ' . $consentModalButtonTextColor . ';';
	        }
	        $output .= sprintf(
		        '<a class="psdsgvo-button psdsgvo-button--secondary" href="javascript:void(0);" %s>%s</a>',
		        (!empty($buttonStyling) ? 'style="' . implode('', $buttonStyling) . '"' : ''),
		        $text
	        );
            $output .= '</footer>'; // .psdsgvo-consent-modal__footer
            $output .= '</div>'; // .psdsgvo-consent-modal__information
        }
        $output .= sprintf(
            '<button class="psdsgvo-consent-modal__close" aria-label="%s" data-micromodal-close>&#x2715;</button>',
            esc_attr__('Modal schließen', PS_DSGVO_C_SLUG)
        );
        $output .= '</div>'; // .psdsgvo-consent-modal__container
        $output .= '</div>'; // .psdsgvo-consent-modal__overlay
        $output .= '</div>'; // #psdsgvo-consent-modal
        echo $output;
    }

    public function addTagsToFields() {
        // Contact Form 7
        if (Helper::isEnabled(CF7::ID)) {
            CF7::getInstance()->addFormTagToForms();
            CF7::getInstance()->addAcceptedDateToForms();
        }

        // Gravity Forms
        if (Helper::isEnabled(GForms::ID)) {
            foreach (GForms::getInstance()->getForms() as $form) {
                if (in_array($form['id'], GForms::getInstance()->getEnabledForms())) {
                    GForms::getInstance()->addField($form);
                }
            }
        }
    }

    public function removeTagsFromFields() {
        // Contact Form 7
        if (Helper::isEnabled(CF7::ID)) {
            CF7::getInstance()->removeFormTagFromForms();
            CF7::getInstance()->removeAcceptedDateFromForms();
        }

        // Gravity Forms
        if (Helper::isEnabled(GForms::ID)) {
            foreach (GForms::getInstance()->getForms() as $form) {
                if (in_array($form['id'], GForms::getInstance()->getEnabledForms())) {
                    GForms::getInstance()->removeField($form);
                }
            }
        }
    }

    /**
     * Erstelle automatisch ein YouTube Embed Consent für Anfänger
     */
    public function createDefaultEmbedConsent() {
        // Prüfe ob die Consent-Tabelle existiert
        if (!Consent::databaseTableExists()) {
            return;
        }
        
        // Hole alle aktivierten Embed-Typen
        $embedSettings = new \PSDSGVO\Includes\Extensions\ContentEmbeds();
        $embedTypes = $embedSettings->getEmbedTypes();
        
        // Erstelle Consents für aktivierte Embed-Typen
        foreach ($embedTypes as $typeId => $config) {
            $optionName = PS_DSGVO_C_PREFIX . '_embed_settings_' . $typeId;
            $isEnabled = (bool)get_option($optionName, false);
            
            if ($isEnabled) {
                $this->ensureEmbedConsentExists($typeId, $config);
            }
        }
    }

    /**
     * Stellt sicher, dass für einen Embed-Typ ein Consent existiert
     * @param string $typeId
     * @param array $config
     */
    private function ensureEmbedConsentExists($typeId, $config) {
        // Prüfe ob bereits ein Consent für diesen Typ existiert
        $consents = Consent::getInstance()->getList();
        
        foreach ($consents as $consent) {
            if (strpos(strtolower($consent->getTitle()), strtolower($config['label'])) !== false) {
                // Consent existiert bereits
                return;
            }
        }
        
        // Erstelle neuen Consent
        $consent = new Consent();
        $consent->setTitle($config['label'] . ' Embeds');
        $consent->setDescription(sprintf(
            'Externe %s-Inhalte werden von externen Servern geladen und können Daten über Sie sammeln. Bitte akzeptieren Sie, um die Inhalte zu sehen.',
            $config['label']
        ) . ' ' . $config['cookie_info']);
        $consent->setActive(1);
        $consent->setSiteId(get_current_blog_id());
        $consent->save();
    }

    /**
     * Hook-Handler: Erstellt Consent wenn eine Embed-Option aktiviert wird
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string $optionName (wird von do_action nicht übergeben, muss in handleEmbedSettingChange abgeleitet werden)
     */
    public function handleEmbedSettingChange($oldValue = null, $newValue = null) {
        // Dieser Handler wird durch update_option_* Hooks aufgerufen
        // Der Hook-Name ist verfügbar über current_filter()
        $currentHook = current_filter();
        
        // Extrahiere den Optionsnamen vom Hook
        // Hook hat Format: update_option_psdsgvo_embed_settings_TYPE
        if (strpos($currentHook, 'update_option_psdsgvo_embed_settings_') !== 0) {
            return;
        }
        
        $optionName = str_replace('update_option_', '', $currentHook);
        $typeId = str_replace('psdsgvo_embed_settings_', '', $optionName);
        
        // Entferne '_message' Suffix wenn vorhanden
        if (strpos($typeId, '_message') !== false) {
            return; // Das ist die Message-Option, nicht die Enable/Disable
        }
        
        // Wenn der neue Wert TRUE ist, erstelle den Consent
        if ($newValue) {
            $embedSettings = new \PSDSGVO\Includes\Extensions\ContentEmbeds();
            $embedTypes = $embedSettings->getEmbedTypes();
            
            if (isset($embedTypes[$typeId])) {
                $this->ensureEmbedConsentExists($typeId, $embedTypes[$typeId]);
            }
        }
    }

    /**
     * @return null|Action
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
