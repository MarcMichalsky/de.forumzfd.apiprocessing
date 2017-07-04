<?php

/**
 * Class for ForumZFD Api Processing Configuration
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 4 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Config {

  // property for singleton pattern (caching the config)
  static private $_singleton = NULL;

  // configuration properties
  private $_employeeRelationshipTypeId = NULL;
  private $_problemActivityTypeId = NULL;

  /**
   * CRM_Mafsepa_Config constructor.
   */
  function __construct() {
    $this->setActivityTypes();
    try {
      $this->_employeeRelationshipTypeId = civicrm_api3('RelationshipType', 'getvalue', array(
        'name_a_b' => 'Employee of',
        'name_b_a' => 'Employer of',
        'return' => 'id'
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Getter for employee relationship type id
   *
   * @return mixed
   */
  public function getEmployeeRelationshipTypeId() {
    return $this->_employeeRelationshipTypeId;
  }

  /**
   * Method to set and if required create the activity types
   */
  private function setActivityTypes() {
    try {
      $this->_problemActivityTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'activity_type',
        'name' => 'forumzfd_api_problem',
        'return' => 'value',

      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      // create activity type if not found
      $activityType = civicrm_api3('OptionValue', 'create', array(
        'option_group_id' => 'activity_type',
        'label' => 'ForumZFD API Problem',
        'name' => 'forumzfd_api_problem',
        'description' => 'ForumZFD API Problem in traffic between website(s) and CiviCRM',
        'is_active' => 1,
        'is_reserved' => 1,
      ));
      foreach ($activityType['values'] as $values) {
        $this->_problemActivityTypeId = $values['value'];
      }
    }
  }

  /**
   * Function to return singleton object
   *
   * @return object $_singleton
   * @access public
   * @static
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Apiprocessing_Config();
    }
    return self::$_singleton;
  }
}