<?php

namespace PSDSGVO\Includes\Extensions;

use PSDSGVO\Includes\Helper;
use PSDSGVO\Includes\Integration;

/**
 * Class WPRegistration
 * @package PSDSGVO\Includes\Extensions
 */
class WPRegistration {
    const ID = 'wp_registration';
    /** @var null */
    private static $instance = null;

    public function addFieldMultiSite( $errors ) {
        ?>
        <p>
            <label><input type="checkbox" name="psdsgvo_consent" value="1" /> <?php echo Integration::getCheckboxText(self::ID); ?><abbr class="psdsgvo-required" title=" <?php echo Integration::getRequiredMessage(self::ID); ?> ">*</abbr></label>
        </p><br>
        <?php

            if ($errorMessage = $errors->get_error_message( 'psdsgvo_consent' )) : ?>
                <p class="error"><?php echo $errorMessage; ?></p>
        <?php endif;
    }

	public function addField() {
		// Nur anzeigen wenn nicht im Admin-Bereich (Frontend-Registrierung)
		if (is_admin()) {
			return;
		}
		?>
        <p>
            <label><input type="checkbox" name="psdsgvo_consent" value="1" /> <?php echo Integration::getCheckboxText(self::ID); ?><abbr class="psdsgvo-required" title=" <?php echo Integration::getRequiredMessage(self::ID); ?> ">*</abbr></label>
        </p><br>
        <?php
	}

    /**
     * @param $errors
     * @param $sanitized_user_login
     * @param $user_email
     *
     * @return mixed
     */
    public function validateGDPRCheckbox($errors, $sanitized_user_login, $user_email) {
            // Nur validieren wenn nicht im Admin-Bereich (Frontend-Registrierung)
            if (!is_admin() && !isset($_POST['psdsgvo_consent'])) {
                $errors->add('gdpr_consent_error', '<strong>ERROR</strong>: ' . Integration::getErrorMessage(self::ID));
            }
        return $errors;
    }

	/**
	 * @param $result
	 *
	 * @return mixed
	 */
	public function validateGDPRCheckboxMultisite( $result ) {
	    $psdsgvoConsent = '';
	    if( !empty( $_POST['psdsgvo_consent'] ) ) {
		    $psdsgvoConsent = sanitize_text_field( $_POST['psdsgvo_consent'] );
	    } elseif (empty($_POST['psdsgvo_consent'])) {
		    $result['errors']->add( 'psdsgvo_consent', Integration::getErrorMessage(self::ID), PS_DSGVO_C_SLUG );
	    }
	    $result['psdsgvo_consent'] = $psdsgvoConsent;
	    return $result;
    }

    /**
     * @param $user
     */
    public function logGivenGDPRConsent($user) {
        global $wpdb;
        
        // Nur loggen wenn Consent gegeben wurde (Frontend-Registrierung)
        if (is_admin() || !isset($_POST['psdsgvo_consent'])) {
            return;
        }
        
        if (is_multisite()) {
            $user = get_userdata($user);
            $userEmail = $user->user_email;
            $siteId = get_current_blog_id();
        } else {
	        $userEmail  = $_POST['user_email'];
            $siteId = null;
        }
        $wpdb->insert($wpdb->base_prefix . 'psdsgvo_log', array(
            'site_id' => $siteId,
            'plugin_id' => self::ID,
            'user' => Helper::anonymizeEmail($userEmail),
            'ip_address' => Helper::anonymizeIP(Helper::getClientIpAddress()),
            'date_created' => Helper::localDateTime(time())->format('Y-m-d H:i:s'),
            'log' => __('user has given consent when registering', PS_DSGVO_C_SLUG),
            'consent_text' => Integration::getCheckboxText(self::ID)
        ));
	}

    /**
     * @return null|WPRegistration
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}