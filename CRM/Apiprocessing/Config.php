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
  private $_forumzfdApiProblemActivityTypeId = NULL;
  private $_akademieApiProblemActivityTypeId = NULL;
  private $_scheduledActivityStatusId = NULL;
	private $_completedActivityStatusId = NULL;
  private $_defaultLocationTypeId = NULL;
  private $_defaultCountryId = NULL;
  private $_sepaFirstPaymentInstrumentId = NULL;
  private $_sepaOneOffPaymentInstrumentId = NULL;
  private $_sepaRecurringPaymentInstrumentId = NULL;

  /**
   * CRM_Mafsepa_Config constructor.
   */
  function __construct() {
    $this->setActivityTypes();
    $this->setAssigneeIds();
    $this->setSepaPaymentInstrumentIds();
    try {
      $this->_employeeRelationshipTypeId = civicrm_api3('RelationshipType', 'getvalue', array(
        'name_a_b' => 'Employee of',
        'name_b_a' => 'Employer of',
        'return' => 'id'
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard employer/employee relationship type in '.__METHOD__
        .', contact your system administrator. Error from API Relationship Type getvalue: '.$ex->getMessage());
    }
    try {
      $this->_scheduledActivityStatusId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'activity_status',
        'name' => 'Scheduled',
        'return' => 'value',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard scheduled activity status in '.__METHOD__
        .', contact your system administrator. Error from API OptionValue Type getvalue: '.$ex->getMessage());
    }
		try {
      $this->_completedActivityStatusId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'activity_status',
        'name' => 'Completed',
        'return' => 'value',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard completed activity status in '.__METHOD__
        .', contact your system administrator. Error from API OptionValue Type getvalue: '.$ex->getMessage());
    }
    try {
      $this->_defaultLocationTypeId = civicrm_api3('LocationType', 'getvalue', array(
        'is_default' => 1,
        'return' => 'id'));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find a default location type id in '.__METHOD__
        .', contact your system administrator. Error from API LocationType getvalue: '.$ex->getMessage());
    }
    try {
      $this->_defaultCountryId = civicrm_api3('Setting', 'getvalue', array(
        'name' => "defaultContactCountry",
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Getter for sepa one off payment instrument id
   *
   * @return null
   */
  public function getSepaOneOffPaymentInstrumentId() {
    return $this->_sepaOneOffPaymentInstrumentId;
  }

  /**
   * Getter for sepa recurring payment instrument id
   *
   * @return null
   */
  public function getSepaRecurringPaymentInstrumentId() {
    return $this->_sepaRecurringPaymentInstrumentId;
  }

  /**
   * Getter for sepa first payment instrument id
   *
   * @return null
   */
  public function getSepaFirstPaymentInstrumentId() {
    return $this->_sepaFirstPaymentInstrumentId;
  }

  /**
   * Getter for default country id
   *
   * @return null
   */
  public function getDefaultCountryId() {
    return $this->_defaultCountryId;
  }

  /**
   * Getter for default location type id
   *
   * @return null
   */
  public function getDefaultLocationTypeId() {
    return $this->_defaultLocationTypeId;
  }

  /**
   * Getter for scheduled activity status id
   *
   * @return null
   */
  public function getScheduledActivityStatusId() {
    return $this->_scheduledActivityStatusId;
  }
	
	/**
   * Getter for completed activity status id
   *
   * @return null
   */
  public function getCompletedActivityStatusId() {
    return $this->_completedActivityStatusId;
  }

  /**
   * Getter for akademieApiProblemActivityTypeId
   *
   * @return null
   */
  public function getAkademieApiProblemActivityTypeId() {
    return $this->_akademieApiProblemActivityTypeId;
  }

  /**
   * Getter for forumzfdApiProblemActivityTypeId
   *
   * @return null
   */
  public function getForumzfdApiProblemActivityTypeId() {
    return $this->_forumzfdApiProblemActivityTypeId;
  }
	
	public function getFzfdPetitionSignedActivityTypeId() {
		return $this->_fzfdPetitionSignedActivityTypeId;
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
    $activityTypesToFetch = array(
      'forumzfd_api_problem',
      'akademie_api_problem',
      'fzfd_petition_signed',
      );
    foreach ($activityTypesToFetch as $activityTypeName) {
      $nameParts = explode('_', $activityTypeName);
      foreach($nameParts as $partKey => $namePart) {
        if ($partKey != 0) {
          $nameParts[$partKey] = ucfirst($namePart);
        }
      }
      $property = '_'.implode('', $nameParts).'ActivityTypeId';

      try {
        $this->$property = civicrm_api3('OptionValue', 'getvalue', array(
          'option_group_id' => 'activity_type',
          'name' => $activityTypeName,
          'return' => 'value',
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
        // create activity type if not found
        if ($activityTypeName == 'fzfd_petition_signed') {
  				$activityTypeLabel = 'An Petition teilgenommen';
  			} else {
  				$activityTypeLabel = CRM_Apiprocessing_Utils::createLabelFromName($activityTypeName);
  			}
         
        $newActivityType = civicrm_api3('OptionValue', 'create', array(
          'option_group_id' => 'activity_type',
          'label' => $activityTypeLabel,
          'name' => $activityTypeName,
          'description' => CRM_Apiprocessing_Utils::createLabelFromName($activityTypeName)
            .' in traffic between website(s) and CiviCRM',
          'is_active' => 1,
          'is_reserved' => 1,
        ));
				$newActivityType = reset($newActivityType['values']);
        $this->$property = $newActivityType['value'];
      }
    }
  }

  /**
   * Method to get the SEPA payment instruments for First and One Off
   *
   * @throws Exception when error from api
   */
  private function setSepaPaymentInstrumentIds() {
    try {
      $this->_sepaFirstPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "FRST",
      ));
      $this->_sepaOneOffPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "OOFF",
      ));
      $this->_sepaRecurringPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "RCUR",
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find one of the required SEPA payment instruments (FIRST and ONE OFF) in '
        .__METHOD__.', contact your system administrator');
    }
  }
  private function setAssigneeIds() {

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