<?php

namespace PSDSGVO\Includes;

use PSDSGVO\Includes\Data\Comment;
use PSDSGVO\Includes\Data\User;
use PSDSGVO\Includes\Data\WooCommerceOrder;

/**
 * Class Data
 * @package PSDSGVO\Includes
 */
class Data {
    /** @var null */
    private static $instance = null;
    /** @var string */
    protected $emailAddress = '';

    /**
     * Data constructor.
     * @param string $emailAddress
     */
    public function __construct($emailAddress = '') {
        if (empty($emailAddress)) {
            wp_die(
                '<p>' . sprintf(
                    __('<strong>FEHLER</strong>: %s', 'psource-dsgvo'),
                    __('E-Mailadresse wird benötigt.', 'psource-dsgvo')
                ) . '</p>'
            );
            exit;
        }
        $this->setEmailAddress($emailAddress);
    }

    /**
     * @return array
     */
    public static function getPossibleDataTypes() {
        return array('user', 'comment', 'woocommerce_order');
    }

    /**
     * @param string $type
     * @return array
     */
    private static function getOutputColumns($type = '') {
        $output = array();
        switch ($type) {
            case 'user' :
                $output = array(
                    __('Benutzername', 'psource-dsgvo'),
                    __('Anzeigename', 'psource-dsgvo'),
                    __('Email-Addresse', 'psource-dsgvo'),
                    __('Webseite', 'psource-dsgvo'),
                    __('Registriert am', 'psource-dsgvo')
                );
                break;
            case 'comment' :
                $output = array(
                    __('Autor', 'psource-dsgvo'),
                    __('Inhalt', 'psource-dsgvo'),
                    __('Email-Addresse', 'psource-dsgvo'),
                    __('IP Addresse', 'psource-dsgvo')
                );
                break;
            case 'woocommerce_order' :
                $output = array(
                    __('Bestellung', 'psource-dsgvo'),
                    __('Email-Addresse', 'psource-dsgvo'),
                    __('Name', 'psource-dsgvo'),
                    __('Addresse', 'psource-dsgvo'),
                    __('Postleitzahl', 'psource-dsgvo'),
                    __('Stadt', 'psource-dsgvo')
                );
                break;
        }
        $output['checkbox'] = '<input type="checkbox" class="psdsgvo-select-all" />';
        return $output;
    }

    /**
     * @param array $data
     * @param string $type
     * @param int $requestId
     * @return array
     */
    private static function getOutputData($data = array(), $type = '', $requestId = 0) {
        $output = array();
        $action = '<input type="checkbox" name="' . PS_DSGVO_C_PREFIX . '_remove[]" class="psdsgvo-checkbox" value="%d" tabindex="1" />';
        switch ($type) {
            case 'user' :
                /** @var User $user */
                foreach ($data as $user) {
                    $request = DeleteRequest::getInstance()->getByTypeAndDataIdAndAccessRequestId($type, $user->getId(), $requestId);
                    $output[$user->getId()] = array(
                        $user->getUsername(),
                        $user->getDisplayName(),
                        $user->getEmailAddress(),
                        $user->getWebsite(),
                        $user->getRegisteredDate(),
                        (($request === false) ? sprintf($action, $user->getId()) : '&nbsp;')
                    );
                }
                break;
            case 'comment' :
                /** @var Comment $comment */
                foreach ($data as $comment) {
                    $request = DeleteRequest::getInstance()->getByTypeAndDataIdAndAccessRequestId($type, $comment->getId(), $requestId);
                    $output[$comment->getId()] = array(
                        $comment->getAuthorName(),
                        Helper::shortenStringByWords(wp_strip_all_tags($comment->getContent(), true), 5),
                        $comment->getEmailAddress(),
                        $comment->getIpAddress(),
                        (($request === false) ? sprintf($action, $comment->getId()) : '&nbsp;')
                    );
                }
                break;
            case 'woocommerce_order' :
                /** @var WooCommerceOrder $woocommerceOrder */
                foreach ($data as $woocommerceOrder) {
                    $request = DeleteRequest::getInstance()->getByTypeAndDataIdAndAccessRequestId($type, $woocommerceOrder->getOrderId(), $requestId);
                    $billingAddressTwo = $woocommerceOrder->getBillingAddressTwo();
                    $address = (!empty($billingAddressTwo)) ? sprintf('%s,<br />%s', $woocommerceOrder->getBillingAddressOne(), $billingAddressTwo) : $woocommerceOrder->getBillingAddressOne();
                    $output[$woocommerceOrder->getOrderId()] = array(
                        sprintf('#%d', $woocommerceOrder->getOrderId()),
                        $woocommerceOrder->getBillingEmailAddress(),
                        sprintf('%s %s', $woocommerceOrder->getBillingFirstName(), $woocommerceOrder->getBillingLastName()),
                        $address,
                        $woocommerceOrder->getBillingPostCode(),
                        $woocommerceOrder->getBillingCity(),
                        (($request === false) ? sprintf($action, $woocommerceOrder->getOrderId()) : '&nbsp;')
                    );
                }
                break;
        }
        return $output;
    }

    /**
     * @param array $data
     * @param string $type
     * @param int $requestId
     * @return string
     */
    public static function getOutput($data = array(), $type = '', $requestId = 0) {
        $output = '';
        if (!empty($data)) {
            $output .= sprintf(
                '<form class="psdsgvo-form psdsgvo-form--delete-request" data-psdsgvo=\'%s\' method="POST" novalidate="novalidate">',
                json_encode(array(
                    'type' => $type
                ))
            );
            $output .= '<div class="psdsgvo-message" style="display: none;"></div>';
            $output .= '<table class="psdsgvo-table">';
            $output .= '<thead>';
            $output .= '<tr>';
            foreach (self::getOutputColumns($type) as $key => $column) {
                $class = (is_string($key)) ? $key : sanitize_title($column);
                $output .= sprintf('<th class="psdsgvo-table__head psdsgvo-table__head--%s" scope="col">%s</th>', $class, $column);
            }
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';
            foreach (self::getOutputData($data, $type, $requestId) as $id => $row) {
                $output .= sprintf('<tr data-id="%d">', $id);
                foreach ($row as $value) {
                    $output .= sprintf('<td>%s</td>', $value);
                }
                $output .= '</tr>';
            }
            $output .= '</tbody>';
            $output .= '</table>';
            $output .= sprintf(
                '<p><input type="submit" class="psdsgvo-remove" value="%s" /></p>',
                sprintf(
                    __('Anonymisiere ausgewählte %s', 'psource-dsgvo'),
                    str_replace('_', ' ', $type)
                )
            );
            $output .= '</form>';
        }
        return $output;
    }

    /**
     * @return User[]
     */
    public function getUsers() {
        global $wpdb;
        $output = array();
        $query = "SELECT * FROM `" . $wpdb->users . "` WHERE `user_email` = %s";
        $results = $wpdb->get_results($wpdb->prepare($query, $this->getEmailAddress()));
        if ($results !== null) {
            foreach ($results as $row) {
                $object = new User($row->ID);
                $output[] = $object;
            }
        }
        return $output;
    }

    /**
     * @return Comment[]
     */
    public function getComments() {
        global $wpdb;
        $output = array();
        $query = "SELECT * FROM " . $wpdb->comments . " WHERE `comment_author_email` = %s";
        $results = $wpdb->get_results($wpdb->prepare($query, $this->getEmailAddress()));
        if ($results !== null) {
            foreach ($results as $row) {
                $object = new Comment();
                $object->loadByRow($row);
                $output[] = $object;
            }
        }
        return $output;
    }

    /**
     * @return WooCommerceOrder[]
     */
    public function getWooCommerceOrders() {
        global $wpdb;
        $output = array();
        $query = "SELECT * FROM " . $wpdb->postmeta . " WHERE `meta_key` = '_billing_email' AND `meta_value` = %s";
        $results = $wpdb->get_results($wpdb->prepare($query, $this->getEmailAddress()));
        if ($results !== null) {
            foreach ($results as $row) {
                $output[] = new WooCommerceOrder($row->post_id);
            }
        }
        return $output;
    }

    /**
     * @return string
     */
    public function getEmailAddress() {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress) {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return null|Data
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}