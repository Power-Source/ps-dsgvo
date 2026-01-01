<?php

namespace PSDSGVO\Includes;

/**
 * Class Shortcode
 * @package PSDSGVO\Includes
 */
class Shortcode {
    /** @var null */
    private static $instance = null;

    /**
     * @return string
     */
    private static function getAccessRequestData() {
        $output = '';
        $token = (isset($_REQUEST['psdsgvo'])) ? esc_html(urldecode($_REQUEST['psdsgvo'])) : false;
        $request = ($token !== false) ? AccessRequest::getInstance()->getByToken($token) : false;
        if ($request !== false) {
            if (
                SessionHelper::checkSession($request->getSessionId()) &&
                Helper::checkIpAddress($request->getIpAddress())
            ) {
                $data = new Data($request->getEmailAddress());
                $users = Data::getOutput($data->getUsers(), 'user', $request->getId());
                $comments = Data::getOutput($data->getComments(), 'comment', $request->getId());

                $output .= sprintf(
                    '<div class="psdsgvo-message psdsgvo-message--notice">%s</div>',
                    apply_filters('psdsgvo_the_content', Integration::getDeleteRequestFormExplanationText())
                );

                // WordPress Users
                $output .= sprintf('<h2 class="psdsgvo-title">%s</h2>', __('Benutzer', PS_DSGVO_C_SLUG));
                if (!empty($users)) {
                    $output .= $users;
                } else {
                    $output .= sprintf(
                        '<div class="psdsgvo-message psdsgvo-message--notice">%s</div>',
                        sprintf(
                            __('Keine Benutzer mit E-Mail-Adresse %s gefunden.', PS_DSGVO_C_SLUG),
                            sprintf('<strong>%s</strong>', $request->getEmailAddress())
                        )
                    );
                }

                // WordPress Comments
                $output .= sprintf('<h2 class="psdsgvo-title">%s</h2>', __('Kommentare', PS_DSGVO_C_SLUG));
                if (!empty($comments)) {
                    $output .= $comments;
                } else {
                    $output .= sprintf(
                        '<div class="psdsgvo-message psdsgvo-message--notice">%s</div>',
                        sprintf(
                            __('Keine Kommentare mit E-Mail-Adresse %s gefunden.', PS_DSGVO_C_SLUG),
                            sprintf('<strong>%s</strong>', $request->getEmailAddress())
                        )
                    );
                }

                // WooCommerce Orders
                if (in_array('woocommerce/woocommerce.php', Helper::getActivePlugins())) {
                    $woocommerceOrders = Data::getOutput($data->getWooCommerceOrders(), 'woocommerce_order', $request->getId());
                    $output .= sprintf('<h2 class="psdsgvo-title">%s</h2>', __('WooCommerce Bestellungen', PS_DSGVO_C_SLUG));
                    if (!empty($woocommerceOrders)) {
                        $output .= $woocommerceOrders;
                    } else {
                        $output .= sprintf(
                            '<div class="psdsgvo-message psdsgvo-message--notice">%s</div>',
                            sprintf(
                                __('Es wurden keine WooCommerce-Bestellungen mit der E-Mail-Adresse %s gefunden.', PS_DSGVO_C_SLUG),
                                sprintf('<strong>%s</strong>', $request->getEmailAddress())
                            )
                        );
                    }
                }

                $output = apply_filters('psdsgvo_request_data', $output, $data, $request);
            } else {
                $accessRequestPage = Helper::getAccessRequestPage();
                $output .= sprintf(
                    '<div class="psdsgvo-message psdsgvo-message--error"><p>%s</p></div>',
                    sprintf(
                        __('<strong>FEHLER</strong>: %s', PS_DSGVO_C_SLUG),
                        sprintf(
                            '%s<br /><br />%s',
                            __('Du kannst Deine Daten nur anzeigen, wenn Du diese Seite auf demselben Gerät mit derselben IP-Adresse und in derselben Browsersitzung wie bei der Ausführung Deiner Anforderung besuchst. Dies ist eine zusätzliche Sicherheitsmaßnahme, um Deine Daten zu schützen.', PS_DSGVO_C_SLUG),
                            sprintf(
                                __('Bei Bedarf kannst Du hier nach 24 Stunden eine neue Anfrage stellen: %s.', PS_DSGVO_C_SLUG),
                                sprintf(
                                    '<a target="_blank" href="%s">%s</a>',
                                    get_permalink($accessRequestPage),
                                    get_the_title($accessRequestPage)
                                )
                            )
                        )
                    )
                );
            }
        } else {
            $output .= sprintf(
                '<div class="psdsgvo-message psdsgvo-message--error"><p>%s</p></div>',
                sprintf(
                    __('<strong>FEHLER</strong>: %s', PS_DSGVO_C_SLUG),
                    __('Diese Anfrage ist abgelaufen oder existiert nicht.', PS_DSGVO_C_SLUG)
                )
            );
        }
        return $output;
    }

    /**
     * @return string
     */
    public function accessRequestForm() {
        $output = '<div class="psdsgvo">';
        if (isset($_REQUEST['psdsgvo'])) {
            $output .= self::getAccessRequestData();
        } else {
            $output .= '<form class="psdsgvo-form psdsgvo-form--access-request" name="psdsgvo_form" method="POST">';
            $output .= apply_filters(
                'psdsgvo_request_form_email_field',
                sprintf(
                    '<p><input type="email" name="psdsgvo_email" id="psdsgvo-form__email" placeholder="%s" erforderlich /></p>',
                    apply_filters('psdsgvo_request_form_email_label', esc_attr__('Deine Emailadresse', PS_DSGVO_C_SLUG))
                )
            );
            $output .= apply_filters(
                'psdsgvo_request_form_consent_field',
                sprintf(
                    '<p><label><input type="checkbox" name="psdsgvo_consent" id="psdsgvo-form__consent" value="1" erforderlich /> %s</label></p>',
                    Integration::getAccessRequestFormCheckboxText()
                )
            );
            $output .= apply_filters(
                'psdsgvo_request_form_submit_field',
                sprintf(
                    '<p><input type="submit" name="psdsgvo_submit" value="%s" /></p>',
                    apply_filters('psdsgvo_request_form_submit_label', esc_attr__('Abfragen', PS_DSGVO_C_SLUG))
                )
            );
            $output .= '<div class="psdsgvo-message" style="display: none;"></div>';
            $output .= '</form>';
            $output = apply_filters('psdsgvo_request_form', $output);
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * @param $attributes
     * @param string $label
     * @return string
     */
    public function consentsSettingsLink($attributes, $label = '') {
        $attributes = shortcode_atts(array(
            'class' => '',
        ), $attributes, 'psdsgvo_consents_settings_link');
        $output = '';
        if (current_user_can('administrator')) {
            $output = sprintf(
                '<p style="color: red;"><strong>%s:</strong> %s</p>',
                strtoupper(__('Hinweis', PS_DSGVO_C_SLUG)),
                __('Du musst sicherstellen, dass Du mindestens eine (1)aktive Einwilligung hinzugefügt hast.', PS_DSGVO_C_SLUG)
            );
        }
        if (Consent::isActive()) {
            $label = (!empty($label)) ? esc_html($label) : __('Meine Einstellungen', PS_DSGVO_C_SLUG);
            $classes = explode(',', $attributes['class']);
            $classes[] = 'psdsgvo-consents-settings-link';
            $classes = implode(' ', $classes);
            $output = sprintf(
                '<a class="%s" href="javascript:void(0);" data-micromodal-trigger="psdsgvo-consent-modal">%s</a>',
                esc_attr($classes),
                $label
            );
        }
        return $output;
    }

    /**
     * @return null|Shortcode
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}