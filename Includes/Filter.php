<?php

namespace PSDSGVO\Includes;

/**
 * Class Filter
 * @package PSDSGVO\Includes
 */
class Filter {
    /** @var null */
    private static $instance = null;

    /**
     * @return null|Filter
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}