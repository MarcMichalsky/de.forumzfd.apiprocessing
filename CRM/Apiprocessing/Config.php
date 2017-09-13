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
	private $_completedContributionStatusId = NULL;
  private $_defaultLocationTypeId = NULL;
  private $_defaultPhoneTypeId = NULL;
  private $_defaultCountryId = NULL;
  private $_defaultCurrency = NULL;
  private $_sepaFrstPaymentInstrumentId = NULL;
  private $_sepaOoffPaymentInstrumentId = NULL;
  private $_sepaRcurPaymentInstrumentId = NULL;
  private $_contributionFinancialTypeId = NULL;
  private $_sepaOoffFinancialTypeId = NULL;
  private $_sepaRcurFinancialTypeId = NULL;
  private $_sepaOoffMandateStatus = NULL;
  private $_sepaFrstMandateStatus = NULL;
  private $_sepaOoffMandateType = NULL;
  private $_sepaRcurMandateType = NULL;
	private $_akademieCustomGroup = NULL;
	private $_experienceCustomFieldId = NULL;
	private $_employerCustomFieldId = NULL;
	private $_wishesCustomFieldId = NULL;
	private $_registeredParticipantStatusId = NULL;
	private $_cancelledParticipantStatusId = NULL;
	private $_eventCustomGroup = NULL;
	private $_trainerCustomFieldId = NULL;
	private $_teilnahmeOrganisationCustomFieldId = NULL;
	private $_ansprechInhaltCustomFieldId = NULL;

  /**
   * CRM_Mafsepa_Config constructor.
   */
  function __construct() {
    $this->setActivityTypes();
    $this->_sepaOoffMandateStatus = "OOFF";
    $this->_sepaOoffMandateType = "OOFF";
    $this->_sepaFrstMandateStatus = "FRST";
    $this->_sepaRcurMandateType = "RCUR";
    $this->_defaultCurrency = "EUR";

    $this->setSepaPaymentInstrumentIds();
    $this->setFinancialTypeIds();
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
      $this->_completedContributionStatusId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'contribution_status',
        'name' => 'Completed',
        'return' => 'value',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard completed contribution status in '.__METHOD__
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
      $this->_defaultPhoneTypeId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'phone_type',
        'name' => 'Phone',
        'return' => 'value'));
    }
    catch (CiviCRM_API3_Exception $ex) {
      // if Phone not found, just take the first one found and log error
      try {
        $this->_defaultPhoneTypeId = civicrm_api3('OptionValue', 'getvalue', array(
          'option_group_id' => 'phone_type',
          'return' => 'value',
          'options' => array('limit' => 1),
        ));
        CRM_Core_Error::debug_log_message('No phone type with name Phone found in '.__METHOD__
          .', first phone type found used as default phone type for ForumZFD api processing');
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find any phone type in ' . __METHOD__
          . ', contact your system administrator. Error from API LocationType getvalue: ' . $ex->getMessage());
      }
    }
    try {
      $this->_defaultCountryId = civicrm_api3('Setting', 'getvalue', array(
        'name' => "defaultContactCountry",
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
		
		try {
			$this->_akademieCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'akademie'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom data set Akademie in '.__METHOD__
			.' contact your system administrator. Error from API CustomGroup getsingle: '.$ex->getMessage());
		}
		try {
			$this->_experienceCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'experience', 'custom_group_id' => $this->_akademieCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Experience in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_wishesCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'wishes', 'custom_group_id' => $this->_akademieCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Wishes in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_employerCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'employer', 'custom_group_id' => $this->_akademieCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Employer in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		
		try {
			$this->_eventCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'event'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom data set Event in '.__METHOD__
			.' contact your system administrator. Error from API CustomGroup getsingle: '.$ex->getMessage());
		}
		try {
			$this->_trainerCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'trainer', 'custom_group_id' => $this->_eventCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Trainer in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_teilnahmeOrganisationCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'teilnahme_organisation', 'custom_group_id' => $this->_eventCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Teilnahme für Organisation in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_ansprechInhaltCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'ansprech_inhalt', 'custom_group_id' => $this->_eventCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Ansprech Inhalt in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		
		try {
      $this->_registeredParticipantStatusId = civicrm_api3('ParticipantStatusType', 'getvalue', array(
        'name' => 'Registered',
        'return' => 'id',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard registered participant status in '.__METHOD__
        .', contact your system administrator. Error from API OptionValue Type getvalue: '.$ex->getMessage());
    }
		try {
      $this->_cancelledParticipantStatusId = civicrm_api3('ParticipantStatusType', 'getvalue', array(
        'name' => 'Cancelled',
        'return' => 'id',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard cancelled participant status in '.__METHOD__
        .', contact your system administrator. Error from API OptionValue Type getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Getter for sepa first mandate status
   *
   * @return null
   */
  public function getSepaFrstMandateStatus() {
    return $this->_sepaFrstMandateStatus;
  }

  /**
   * Getter for sepa one off mandate status
   *
   * @return null
   */
  public function getSepaOoffMandateStatus() {
    return $this->_sepaOoffMandateStatus;
  }

  /**
   * Getter for sepa one off mandate type
   *
   * @return null
   */
  public function getSepaOoffMandateType() {
    return $this->_sepaOoffMandateType;
  }

  /**
   * Getter for sepa recurring mandate type
   *
   * @return null
   */
  public function getSepaRcurMandateType() {
    return $this->_sepaRcurMandateType;
  }

  /**
   * Getter for sepa recurring financial type id
   *
   * @return null
   */
  public function getSepaRcurFinancialTypeId() {
    return $this->_sepaRcurFinancialTypeId;
  }

  /**
   * Getter for contribution default financial type id
   *
   * @return null
   */
  public function getContributionFinancialTypeId() {
    return $this->_contributionFinancialTypeId;
  }

  /**
   * Getter for sepa one off financial type id
   *
   * @return null
   */
  public function getSepaOoffFinancialTypeId() {
    return $this->_sepaOoffFinancialTypeId;
  }

  public function getDefaultOoffMandateStatus() {

  }

  /**
   * Getter for sepa one off payment instrument id
   *
   * @return null
   */
  public function getSepaOoffPaymentInstrumentId() {
    return $this->_sepaOoffPaymentInstrumentId;
  }

  /**
   * Getter for sepa recurring payment instrument id
   *
   * @return null
   */
  public function getSepaRcurPaymentInstrumentId() {
    return $this->_sepaRcurPaymentInstrumentId;
  }

  /**
   * Getter for sepa first payment instrument id
   *
   * @return null
   */
  public function getSepaFrstPaymentInstrumentId() {
    return $this->_sepaFrstPaymentInstrumentId;
  }

  /**
   * Getter for default currency
   *
   * @return null
   */
  public function getDefaultCurrency() {
    return $this->_defaultCurrency;
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
   * Getter for default phone type id
   *
   * @return null
   */
  public function getDefaultPhoneTypeId() {
    return $this->_defaultPhoneTypeId;
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
   * Getter for completed contribution status id
   *
   * @return null
   */
  public function getCompletedContributionStatusId() {
    return $this->_completedContributionStatusId;
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
	 * Getter for employer custom field id
	 */
	public function getEmployerCustomFieldId() {
		return $this->_employerCustomFieldId;
	}
	
	/**
	 * Getter for experience custom field id
	 */
	public function getExperienceCustomFieldId() {
		return $this->_experienceCustomFieldId;
	}
	
	/**
	 * Getter for wishes custom field id
	 */
	public function getWishesCustomFieldId() {
		return $this->_wishesCustomFieldId;
	}
	
	/**
	 * Getter for trainer custom field id
	 */
	 public function getTrainerCustomFieldId() {
	 	return $this->_trainerCustomFieldId;
	 }
	 
	 /**
	 * Getter for Teilnahme für Organisation custom field id
	 */
	 public function getTeilnahmeOrganisationCustomFieldId() {
	 	return $this->_teilnahmeOrganisationCustomFieldId;
	 }
	 
	 /**
	 * Getter for Ansprech Inhalt custom field id
	 */
	 public function getAnsprechInhaltCustomFieldId() {
	 	return $this->_ansprechInhaltCustomFieldId;
	 }
	
	/**
	 * Getter for participant status Registered
	 */
	public function getRegisteredParticipantStatus() {
		return $this->_registeredParticipantStatusId;
	}
	
	/**
	 * Getter for participant status Cancelled
	 */
	public function getCancelledParticipantStatus() {
		return $this->_cancelledParticipantStatusId;
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

  /**
   * Method to set the financial types for SEPA
   *
   * @throws Exception
   */
  private function setFinancialTypeIds() {
    $rcurFinancialTypeName = 'Förderbeitrag';
    $ooffFinancialTypeName = 'Spende';
    $contributionFinancialTypeName = 'Spende';
    try {
      $this->_sepaRcurFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', array(
        'name' => $rcurFinancialTypeName,
        'return' => 'id',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find SEPA financial type '.$rcurFinancialTypeName.' in '.__METHOD__
        .', contact your system administrator!');
    }
    try {
      $this->_sepaOoffFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', array(
        'name' => $ooffFinancialTypeName,
        'return' => 'id',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find SEPA financial type '.$ooffFinancialTypeName.' in '.__METHOD__
        .', contact your system administrator!');
    }
    try {
      $this->_contributionFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', array(
        'name' => $contributionFinancialTypeName,
        'return' => 'id',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find contribution default financial type '.$contributionFinancialTypeName.' in '.__METHOD__
        .', contact your system administrator!');
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