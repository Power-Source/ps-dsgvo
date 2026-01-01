<?php

namespace PSDSGVO\Includes\Extensions;

use PSDSGVO\Includes\Helper;
use PSDSGVO\Includes\Integration;

/**
 * Class WP
 * @package PSDSGVO\Includes\Extensions
 */
class WP {
    const ID = 'wordpress';
    /** @var null */
    private static $instance = null;

    /**
     * @param string $submitField
     * @return string
     */
    public function addField($submitField = '') {
        $field = apply_filters(
            'psdsgvo_wordpress_field',
            '<p class="psdsgvo-checkbox"><input type="checkbox" name="psdsgvo" id="psdsgvo" value="1" /><label for="psdsgvo">' . Integration::getCheckboxText(self::ID) . ' <abbr class="psdsgvo-required" title="' . Integration::getRequiredMessage(self::ID) . '">*</abbr></label></p>',
            $submitField
        );
        return $field . $submitField;
    }

    public function addFieldForAdmin($submitField = '') {
        $field = apply_filters(
            'psdsgvo_wordpress_field',
            '<label style="font-size: 14px;"><i>' . __('This checkbox is checked because you are an admin', PS_DSGVO_C_SLUG) . '</i></label>' .
            '<p class="psdsgvo-checkbox"><input type="checkbox" name="psdsgvo" id="psdsgvo" value="1" checked="checked" /><label for="psdsgvo">' . Integration::getCheckboxText(self::ID) . ' <abbr class="required" title="' . esc_attr__('required', PS_DSGVO_C_SLUG) . '">*</abbr></label></p>',
            $submitField
        );
        return $field . $submitField;
    }

    public function checkPost() {
        if (!isset($_POST['psdsgvo'])) {
            wp_die(
                '<p>' . sprintf(
                    __('<strong>ERROR</strong>: %s', PS_DSGVO_C_SLUG),
                    Integration::getErrorMessage(self::ID)
                ) . '</p>',
                __('Comment Submission Failure'),
                array('back_link' => true)
            );
        }
    }

    /**
     * @param int $commentId
     */
    public function addAcceptedDateToCommentMeta($commentId = 0) {
        if (isset($_POST['psdsgvo']) && !empty($commentId)) {
            add_comment_meta($commentId, '_psdsgvo', time());
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    public function displayAcceptedDateColumnInCommentOverview($columns = array()) {
        $columns['psdsgvo-date'] = apply_filters('psdsgvo_accepted_date_column_in_comment_overview', __('GDPR Accepted On', PS_DSGVO_C_SLUG));
        return $columns;
    }

    /**
     * @param string $column
     * @param int $commentId
     * @return string
     */
    public function displayAcceptedDateInCommentOverview($column = '', $commentId = 0) {
        if ($column === 'psdsgvo-date') {
            $date = get_comment_meta($commentId, '_psdsgvo', true);
            $value = (!empty($date)) ? Helper::localDateFormat(get_option('date_format') . ' ' . get_option('time_format'), $date) : __('Not accepted.', PS_DSGVO_C_SLUG);
            echo apply_filters('psdsgvo_accepted_date_in_comment_overview', $value, $commentId);
        }
        return $column;
    }

    /**
     * @return null|WP
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}