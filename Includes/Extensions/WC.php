<?php

namespace PSDSGVO\Includes\Extensions;

use PSDSGVO\Includes\Helper;
use PSDSGVO\Includes\Integration;

/**
 * Class WC
 * @package PSDSGVO\Includes\Extensions
 */
class WC {
    const ID = 'woocommerce';
    const SUPPORTED_VERSION = '2.5.0';
    /** @var null */
    private static $instance = null;

    /**
     * Add WP GDPR field before submit button
     */
    public function addField() {
        $args = array(
            'type' => 'checkbox',
            'class' => array('psdsgvo-checkbox'),
            'label' => Integration::getCheckboxText(self::ID) . ' <abbr class="psdsgvo-required required" title="' . Integration::getRequiredMessage(self::ID) . '">*</abbr>',
            'required' => true
        );
        woocommerce_form_field('psdsgvo', apply_filters('psdsgvo_woocommerce_field_args', $args));
    }

    /**
     * Check if WP GDPR checkbox is checked
     */
    public function checkPostCheckoutForm() {
        if (!isset($_POST['psdsgvo'])) {
            wc_add_notice(Integration::getErrorMessage(self::ID), 'error');
        }
    }

    /**
     * Check if WP GDPR checkbox is checked on register
     *
     * @param string $username
     * @param string $emailAddress
     * @param \WP_Error $errors
     */
    public function checkPostRegisterForm(\WP_Error $errors, $username = '', $emailAddress = '') {
        if (!isset($_POST['psdsgvo'])) {
            $errors->add('psdsgvo_error', Integration::getErrorMessage(self::ID));
        }
    }

    /**
     * @param int $orderId
     */
    public function addAcceptedDateToOrderMeta($orderId = 0) {
        if (isset($_POST['psdsgvo']) && !empty($orderId)) {
            update_post_meta($orderId, '_psdsgvo', time());
        }
    }

    /**
     * @param \WC_Order $order
     */
    public function displayAcceptedDateInOrderData(\WC_Order $order) {
        $orderId = (method_exists($order, 'get_id')) ? $order->get_id() : $order->id;
        $label = __('GDPR accepted on:', PS_DSGVO_C_SLUG);
        $date = get_post_meta($orderId, '_psdsgvo', true);
        $value = (!empty($date)) ? Helper::localDateFormat(get_option('date_format') . ' ' . get_option('time_format'), $date) : __('Not accepted.', PS_DSGVO_C_SLUG);
        echo apply_filters(
            'psdsgvo_woocommerce_accepted_date_in_order_data',
            sprintf('<p class="form-field form-field-wide psdsgvo-accepted-date"><strong>%s</strong><br />%s</p>', $label, $value),
            $label,
            $value,
            $order
        );
    }

    /**
     * @param array $columns
     * @return array
     */
    public function displayAcceptedDateColumnInOrderOverview($columns = array()) {
        $columns['psdsgvo-privacy'] = apply_filters('psdsgvo_accepted_date_column_in_woocommerce_order_overview', __('Privacy', PS_DSGVO_C_SLUG));
        return $columns;
    }

    /**
     * @param string $column
     * @param int $orderId
     * @return string
     */
    public function displayAcceptedDateInOrderOverview($column = '', $orderId = 0) {
        if ($column === 'psdsgvo-privacy') {
            $date = get_post_meta($orderId, '_psdsgvo', true);
            $value = (!empty($date)) ? Helper::localDateFormat(get_option('date_format') . ' ' . get_option('time_format'), $date) : __('Not accepted.', PS_DSGVO_C_SLUG);
            echo apply_filters('psdsgvo_accepted_date_in_woocommerce_order_overview', $value, $orderId);
        }
        return $column;
    }

    /**
     * @return null|WC
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
