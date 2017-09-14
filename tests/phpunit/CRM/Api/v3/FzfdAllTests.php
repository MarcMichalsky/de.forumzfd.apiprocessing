<?php

class CRM_api_v3_FzfdAllTests extends CiviTestSuite {
  private static $instance = NULL;

  /**
   * Simple name based constructor.
   *
   * @param string $theClass
   * @param string $name
   */
  public function __construct($theClass = '', $name = '') {
    parent::__construct($theClass, $name);
  }

  /**
   */
  private static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Build test suite dynamically.
   */
  public static function suite() {
    $inst = self::getInstance();
    return $inst->implSuite(__FILE__);
  }

}