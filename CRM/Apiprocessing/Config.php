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
  private $_bankAccountReferenceType = NULL;
  private $_weiterBerufsEventTypeId = NULL;
  private $_weiterVollzeitEventTypeId = NULL;
  private $_seminarEventTypeId = NULL;
  private $_onlineSeminarEventTypeId = NULL;
  private $_skypeProviderId = NULL;
  private $_employeeRelationshipTypeId = NULL;
  private $_forumzfdApiProblemActivityTypeId = NULL;
  private $_akademieApiProblemActivityTypeId = NULL;
  private $_scheduledActivityStatusId = NULL;
	private $_completedActivityStatusId = NULL;
	private $_completedContributionStatusId = NULL;
	private $_pendingContributionStatusId = NULL;
	private $_apiEingabeLocationTypeId = NULL;
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
	private $_experienceCustomFieldId = NULL;
	private $_employerCustomFieldId = NULL;
	private $_wishesCustomFieldId = NULL;
  private $_machineCustomFieldId = NULL;
  private $_browserCustomFieldId = NULL;
  private $_pingCustomFieldId = NULL;
  private $_downloadCustomFieldId = NULL;
  private $_uploadCustomFieldId = NULL;
	private $_additionalDataCustomGroup = NULL;
	private $_additionalDataCustomFieldId = NULL;
	private $_departmentDataCustomFieldId = NULL;
	private $_registeredParticipantStatusId = NULL;
	private $_waitlistedParticipantStatusId = NULL;
	private $_cancelledParticipantStatusId = NULL;
	private $_rechnungZuParticipantStatusId = NULL;
	private $_partiallyPaidParticipantStatusId = NULL;
	private $_incompleteParticipantStatusId = NULL;
	private $_kompletteZahlungParticipantStatusId = NULL;
	private $_zertifikatParticipantStatusId = NULL;
	private $_zertifikatNichtParticipantStatusId = NULL;
	private $_neuParticipantStatusTypeId = NULL;
	private $_attendeeParticipantRoleId = NULL;
	private $_weiterBildungCustomGroup = NULL;
	private $_campaignOnLineCustomFieldId = NULL;
	private $_protectedGroupCustomFieldId = NULL;
	private $_goldGroupId = NULL;
	private $_silverGroupId = NULL;
	private $_allGroupId = NULL;
	private $_privacyOptionsCustomGroup = [];
	private $_websiteConsentCustomFieldId = NULL;
	private $_temporaryTagId = NULL;
	private $_payDirektPaymentInstrumentId = NULL;
  private $_bewerbungCustomFieldId = NULL;
  private $_bewerbungsschreibenCustomFieldId = NULL;
  private $_lebenslaufCustomFieldId = NULL;

	// new event data
  private $_newEventCustomGroup = NULL;
  private $_newTrainer1CustomFieldId = NULL;
  private $_newTrainer2CustomFieldId = NULL;
  private $_newTrainer3CustomFieldId = NULL;
  private $_newTrainer4CustomFieldId = NULL;
  private $_newAnsprechOrg1CustomFieldId = NULL;
  private $_newAnsprechOrg2CustomFieldId = NULL;
  private $_newAnsprechInhalt1CustomFieldId = NULL;
  private $_newAnsprechInhalt2CustomFieldId = NULL;
  private $_newEventLanguageCustomFieldId  = NULL;
  private $_newEventVenueCustomFieldId = NULL;

  //new participant data
  private $_newParticipantCustomGroup = NULL;
  private $_newHowDidCustomFieldId = NULL;

  /**
   * CRM_Apiprocessing_Config constructor.
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  function __construct() {
    new CRM_Apiprocessing_Initialize();


    $this->setActivityTypes();
    $this->_sepaOoffMandateStatus = "OOFF";
    $this->_sepaOoffMandateType = "OOFF";
    $this->_sepaFrstMandateStatus = "FRST";
    $this->_sepaRcurMandateType = "RCUR";
    $this->_defaultCurrency = "EUR";

    $this->createApiEingabeLocationType();
    $this->setBankAccountReferenceType();
    $this->setEventTypes();
    $this->setPaymentInstrumentIds();
    $this->setFinancialTypeIds();
    $this->setParticipantStatusIds();
    $this->setParticipantRoleIds();
    $this->setCustomGroupsAndFields();
    $this->setContributionStatusIds();
    // careful, the groups have to be done after the custom groups and fields
    // because it uses one custom field property (protectGroupCustomFieldId)!
    $this->setGroups();
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
      $this->_apiEingabeLocationTypeId = civicrm_api3('LocationType', 'getvalue', array(
        'name' => 'fzfd_api_eingabe',
        'return' => 'id'));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find a default or API-Eingabe location type id in '.__METHOD__
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
      $this->_skypeProviderId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'instant_messenger_service',
        'name' => 'Skype',
        'return' => 'label',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find an instant messenger service with the name Skype in ' . __METHOD__
        .', contact your system administrator. Error from API OptionValue Type getvalue: '.$ex->getMessage());
    }
    try {
      $this->_temporaryTagId = civicrm_api3('Tag', 'getvalue', [
        'return' => 'id',
        'name' => 'Temporär Zahlung angekündigt',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Could not find a tag Temporär Zahlung angekündigt in ') . __METHOD__);
    }
  }

  /**
   * Getter for bank account reference type
   *
   * @return null
   */
  public function getBankAccountReferenceType() {
    return $this->_bankAccountReferenceType;
  }

  /**
   * Getter for weiterbildung vollzeit event type id
   *
   * @return null
   */
  public function getWeiterVollzeitEventTypeId() {
    return $this->_weiterVollzeitEventTypeId;
  }

  /**
   * Getter for weiterbildung berufsbegleitend event type id
   *
   * @return null
   */
  public function getWeiterBerufsEventTypeId() {
    return $this->_weiterBerufsEventTypeId;
  }

  /**
   * Getter for attendee participant role id
   *
   * @return null
   */
  public function getAttendeeParticipantRoleId() {
    return $this->_attendeeParticipantRoleId;
  }

  /**
   * Getter for online seminar event type id
   *
   * @return null
   */
  public function getOnlineSeminarEventTypeId() {
    return $this->_onlineSeminarEventTypeId;
  }

  /**
   * Getter for seminar event type id
   *
   * @return null
   */
  public function getSeminarEventTypeId() {
    return $this->_seminarEventTypeId;
  }

  /**
   * Getter for skype provider id
   *
   * @return null
   */
  public function getSkypeProviderId() {
    return $this->_skypeProviderId;
  }

  /**
   * Getter for all group id
   *
   * @return null
   */
  public function getAllGroupId() {
    return $this->_allGroupId;
  }

  /**
   * Getter for gold group id
   *
   * @return null
   */
  public function getGoldGroupId() {
    return $this->_goldGroupId;
  }

  /**
   * Getter for silver group id
   *
   * @return null
   */
  public function getSilverGroupId() {
    return $this->_silverGroupId;
  }

  /**
   * Getter for custom field id protect group
   *
   * @return null
   */
  public function getProtectGroupCustomFieldId() {
    return $this->_protectedGroupCustomFieldId;
  }

  /**
   * Getter for custom field id campaign on line
   *
   * @return null
   */
  public function getCampaignOnLineCustomFieldId() {
    return $this->_campaignOnLineCustomFieldId;
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
   * Getter for pay direkt payment instrument id
   *
   * @return null
   */
  public function getPayDirektPaymentInstrumentId() {
    return $this->_payDirektPaymentInstrumentId;
  }

  /**
   * Getter for temporary tag
   *
   *@return null
   */
  public function getTemporaryTagId() {
    return $this->_temporaryTagId;
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
   * Getter for api eingabe location type id
   *
   * @return null
   */
  public function getApiEingabeLocationTypeId() {
    return $this->_apiEingabeLocationTypeId;
  }

  /**
   * Getter for new participant custom group id
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function getNewParticipantCustomGroupId() {
    if (self::$_singleton === NULL) {
      try {
        $newParticipantCustomGroup =
          civicrm_api3(
            'CustomGroup',
            'getsingle',
            ['name' => 'fzfd_participant_data_new']
          );
        return $newParticipantCustomGroup['id'];
      } catch (\CiviCRM_API3_Exception $ex) {
        throw new \CiviCRM_API3_Exception('Could not find custom data '
          . 'set ParticipantNew in ' . __METHOD__ . ' contact your system '
          . 'administrator. Error from API CustomGroup getsingle: '
          . $ex->getMessage()
        );
      }
    }
    else {
      $instance = CRM_Apiprocessing_Config::$_singleton;
      if (isset($instance->_newParticipantCustomGroup['id'])) {
        return $instance->_newParticipantCustomGroup['id'];
      }
      return $instance->_newParticipantCustomGroup;
    }
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
   * Getter for pending contribution status id
   *
   * @return null
   */
  public function getPendingContributionStatusId() {
    return $this->_pendingContributionStatusId;
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
	 * Getter for i will use this machine custom field id
	 */
	public function getMachineCustomFieldId() {
		return $this->_machineCustomFieldId;
	}

	/**
	 * Getter for browser version custom field id
	 */
	public function getBrowserCustomFieldId() {
		return $this->_browserCustomFieldId;
	}

	/**
	 * Getter for ping custom field id
	 */
	public function getPingCustomFieldId() {
		return $this->_pingCustomFieldId;
	}

	/**
	 * Getter for download custom field id
	 */
	public function getDownloadCustomFieldId() {
		return $this->_downloadCustomFieldId;
	}

	/**
	 * Getter for upload custom field id
	 */
	public function getUploadCustomFieldId() {
		return $this->_uploadCustomFieldId;
	}

	/**
	 * Getter for bewerbungsschreiben custom field id
	 */
	public function getBewerbungsschreibenCustomFieldId() {
		return $this->_bewerbungsschreibenCustomFieldId;
	}

	/**
	 * Getter for lebenslauf custom field id
	 */
	public function getLebenslaufCustomFieldId() {
		return $this->_lebenslaufCustomFieldId;
	}

	/**
	 * Getter for addition data custom field id.
	 */
	public function getAdditionalDataCustomFieldId() {
		return $this->_additionalDataCustomFieldId;
	}

	/**
	 * Getter for department custom field id.
	 */
	public function getDepartmentCustomFieldId() {
		return $this->_departmentDataCustomFieldId;
	}

    /**
     * Getter for participant status Registered
     */
    public function getRegisteredParticipantStatusId() {
        return $this->_registeredParticipantStatusId;
    }

    /**
     * Getter for counted participant status IDs
     * @throws CRM_Apiprocessing_Exceptions_BaseException
     */
	public function getCountedParticipantStatusIds(): array {
        $counted_participant_status_type_ids = [];
        $counted_participant_status_types = civicrm_api3('ParticipantStatusType', 'get', [
            'sequential' => 1,
            'return' => ["id"],
            'is_counted' => 1,
            'options' => ['limit' => 0],
        ]);
        if (!isset($counted_participant_status_types['is_error']) || $counted_participant_status_types['is_error'] != 0) {
            $message = $counted_participant_status_types['error_message'] ?? 'Something went wrong on the attempt to retrieve participant status IDs';
            throw new CRM_Apiprocessing_Exceptions_BaseException($message);
        }
        foreach ($counted_participant_status_types['values'] as $status_type) {
            $counted_participant_status_type_ids[] = $status_type['id'];
        }
		return $counted_participant_status_type_ids;
	}

	/**
	 * Getter for participant status On waitlist
	 */
	public function getWaitlistedParticipantStatusId() {
		return $this->_waitlistedParticipantStatusId;
	}

	/**
	 * Getter for participant status Cancelled
	 */
	public function getCancelledParticipantStatusId() {
		return $this->_cancelledParticipantStatusId;
	}

	/**
	 * Getter for participant status Partially paid
	 */
	public function getPartiallyPaidParticipantStatusId() {
		return $this->_partiallyPaidParticipantStatusId;
	}

	/**
	 * Getter for participant status Pending from incomplete transaction
	 */
	public function getIncompleteParticipantStatusId() {
		return $this->_incompleteParticipantStatusId;
	}

	/**
	 * Getter for participant status Komplette Zahlung Eingegangen
	 */
	public function getKompletteZahlungParticipantStatusId() {
		return $this->_kompletteZahlungParticipantStatusId;
	}

	/**
	 * Getter for participant status Zertifikat Ausgehändigt
	 */
	public function getZertifikatParticipantStatusId() {
		return $this->_zertifikatParticipantStatusId;
	}

	/**
	 * Getter for participant status Zertifikat Nicht Ausgehändigt
	 */
	public function getZertifikatNichtParticipantStatusId() {
		return $this->_zertifikatNichtParticipantStatusId;
	}

	/**
	 * Getter for participant status Rechnung Zugesandt
	 */
	public function getRechnungZuParticipantStatusId() {
		return $this->_rechnungZuParticipantStatusId;
	}

	/**
	 * Getter for participant status Neu
	 */
	public function getNeuParticipantStatusId() {
	  return $this->_neuParticipantStatusTypeId;
	}

  /**
   * Getter for trainer 1 custom field id
   */
	public function getNewTrainer1CustomFieldId() {
	  return $this->_newTrainer1CustomFieldId;
  }

  /**
   * Getter for trainer 2 custom field id
   */
	public function getNewTrainer2CustomFieldId() {
	  return $this->_newTrainer2CustomFieldId;
  }

  /**
   * Getter for trainer 3 custom field id
   */
	public function getNewTrainer3CustomFieldId() {
	  return $this->_newTrainer3CustomFieldId;
  }

  /**
   * Getter for trainer 4 custom field id
   */
	public function getNewTrainer4CustomFieldId() {
	  return $this->_newTrainer4CustomFieldId;
  }

  /**
   * Getter for ansprech organisation 1 custom field id
   */
	public function getNewAnsprechOrg1CustomFieldId() {
	  return $this->_newAnsprechOrg1CustomFieldId;
  }

  /**
   * Getter for ansprech organisation 2 custom field id
   */
	public function getNewAnsprechOrg2CustomFieldId() {
	  return $this->_newAnsprechOrg2CustomFieldId;
  }

  /**
   * Getter for ansprech inhalt 1 custom field id
   */
  public function getNewAnsprechInhalt1CustomFieldId() {
    return $this->_newAnsprechInhalt1CustomFieldId;
  }

  /**
   * Getter for ansprech inhalt 2 custom field id
   */
  public function getNewAnsprechInhalt2CustomFieldId() {
    return $this->_newAnsprechInhalt2CustomFieldId;
  }

  /**
   * Getter for event sprache custom field id
   */
  public function getNewEventLanguageCustomFieldId() {
    return $this->_newEventLanguageCustomFieldId;
  }

  /**
   * Getter for event venue custom field id
   */
  public function getNewEventVenueCustomFieldId() {
    return $this->_newEventVenueCustomFieldId;
  }

  /**
   * Getter for participant how did you hear about us custom field id
   */
  public function getNewHowDidCustomFieldId() {
    return $this->_newHowDidCustomFieldId;
  }

  /**
   * Method to set and if required create the activity types

   * @throws CiviCRM_API3_Exception
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
  private function setPaymentInstrumentIds() {
    try {
      $this->_sepaFrstPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "FRST",
      ));
      $this->_sepaOoffPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "OOFF",
      ));
      $this->_sepaRcurPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "RCUR",
      ));
      $this->_payDirektPaymentInstrumentId = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => "value",
        'option_group_id' => "payment_instrument",
        'name' => "fzfd_pay_direkt",
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
   * Method to set all the custom groups and fields required
   *
   * @throws Exception
   */
  private function setCustomGroupsAndFields() {
    try {
      $this->_weiterBildungCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'fzfd_weiterbildung_data'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom data set Weiterbildung in '.__METHOD__
        .' contact your system administrator. Error from API CustomGroup getsingle: '.$ex->getMessage());
    }
    // new event custom group and custom fields
    try {
      $this->_newEventCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'fzfd_event_data_new'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom data set EventNew in '.__METHOD__
        .' contact your system administrator. Error from API CustomGroup getsingle: '.$ex->getMessage());
    }
    try {
      $this->_newTrainer1CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_trainers_1_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Trainer 1 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newTrainer2CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_trainers_2_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Trainer 2 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newTrainer3CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_trainers_3_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Trainer 3 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newTrainer4CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_trainers_4_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Trainer 4 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newAnsprechOrg1CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_contacts_org_1_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Ansprech Organisation 1 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newAnsprechOrg2CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_contacts_org_2_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Ansprech Organisation 2 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newAnsprechInhalt1CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_contacts_content_1_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Ansprech Inhalt 1 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newAnsprechInhalt2CustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_contacts_content_2_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Ansprech Inhalt 2 in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newEventLanguageCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_sprache_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Event Sprache in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_newEventVenueCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_event_venue_new', 'custom_group_id' => $this->_newEventCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Event Venue in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    // new participant custom group and custom fields
    try {
      $this->_newParticipantCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'fzfd_participant_data_new'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom data set ParticipantNew in '.__METHOD__
        .' contact your system administrator. Error from API CustomGroup getsingle: '.$ex->getMessage());
    }
    try {
      $this->_newHowDidCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_where_did_you_hear', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id'));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field How did you hear about us in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_wishesCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'fzfd_wishes_new', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Wishes in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_experienceCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'fzfd_experience_new', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Experience in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_bewerbungsschreibenCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'fzfd_bewerbungsschreiben', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Bewerbungsschreiben in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_lebenslaufCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'fzfd_lebenslauf', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Lebenslauf in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_employerCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'fzfd_employer_new', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Employer in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_machineCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'i_will_use_this_machine', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field I will use this machine in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_browserCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'browser_version', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Browser version in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_pingCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'ping', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Ping in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_downloadCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'download', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Download in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_uploadCustomFieldId = civicrm_api3('CustomField', 'getvalue', ['name' => 'upload', 'custom_group_id' => $this->_newParticipantCustomGroup['id'],'return' => 'id']);
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Upload in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }

    try {
      $this->_campaignOnLineCustomFieldId = civicrm_api3('CustomField', 'getvalue', array(
        'name' => 'fzfd_campaign_on_line',
        'custom_group_id' => 'fzfd_campaign_data',
        'return' => 'id',
        ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field Kampagnen On Line Verfügabar (fzfd_campaign_on_line) in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
    try {
      $this->_protectedGroupCustomFieldId = civicrm_api3('CustomField', 'getvalue', array(
        'name' => 'group_protect',
        'custom_group_id' => 'group_protect',
        'return' => 'id',
        ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find custom field group_protect in '.__METHOD__
        .' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
    }
		try {
			$this->_additionalDataCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'fzfd_additional_data'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom data set Additional Data in '.__METHOD__
			.' contact your system administrator. Error from API CustomGroup getsingle: '.$ex->getMessage());
		}
		try {
			$this->_additionalDataCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_additional_data', 'custom_group_id' => $this->_additionalDataCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Additional Data in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_departmentDataCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_department', 'custom_group_id' => $this->_additionalDataCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Department in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_bewerbungCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_bewerbung', 'custom_group_id' => $this->_weiterBildungCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
			throw new Exception('Could not find custom field Bewerbung in '.__METHOD__
			.' contact your system administrator. Error from API CustomField getvalue: '.$ex->getMessage());
		}
		try {
			$this->_privacyOptionsCustomGroup = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'fzfd_privacy_options'));
		} catch (CiviCRM_API3_Exception $ex) {
      $customGroup = CRM_Apiprocessing_CustomData::createPrivacyCustomGroup();
      if ($customGroup) {
        $this->_privacyOptionsCustomGroup = $customGroup;
      }
		}
		try {
			$this->_websiteConsentCustomFieldId = civicrm_api3('CustomField', 'getvalue', array('name' => 'fzfd_website_consent', 'custom_group_id' => $this->_privacyOptionsCustomGroup['id'],'return' => 'id'));
		} catch (CiviCRM_API3_Exception $ex) {
      $customField = CRM_Apiprocessing_CustomData::createWebsiteDataConsentCustomField();
      if ($customField) {
        $this->_websiteConsentCustomFieldId = $customField['id'];
      }
		}
  }

  /**
   * Method to set the groups
   * @throws CiviCRM_API3_Exception
   */
  private function setGroups() {
    $groups = array(
      'fzfd_all_donors' => array(
        'title' => 'Alle Spender',
        'description' => 'Gruppe für Alle Spender (Silver und Gold sind Kinder Gruppen)',
        'parent_name' => NULL,
        'property' => '_allGroupId',
      ),
      'fzfd_silver_donors' => array(
        'title' => 'Silver Spender',
        'description' => 'Gruppe für Alle Spender niveau Silver',
        'parent_name' => 'fzfd_all_donors',
        'property' => '_silverGroupId',
      ),
      'fzfd_gold_donors' => array(
        'title' => 'Gold Spender',
        'description' => 'Gruppe für Alle Spender niveau Gold',
        'parent_name' => 'fzfd_all_donors',
        'property' => '_goldGroupId',
      ),
    );
    foreach ($groups as $groupName => $groupData) {
      $this->createGroupIfNotExists($groupName, $groupData);
    }
  }

  /**
   *  Method to create a group if required
   *
   * @param $groupName
   * @param $groupData
   * @throws CiviCRM_API3_Exception
   */
  private function createGroupIfNotExists($groupName, $groupData) {
    $groupCount = civicrm_api3('Group', 'getcount', array(
      'name' => $groupName,
    ));
    if ($groupCount == 0) {
      $createdGroup = civicrm_api3('Group', 'create', array(
        'sequential' => 1,
        'name' => $groupName,
        'title' => $groupData['title'],
        'description' => $groupData['description'],
        'group_type' => 'Mailing List',
        'is_active' => 1,
        'is_reserved' => 1,
        'custom_'.$this->_protectedGroupCustomFieldId => 1,
      ));
      // fix issue with api modifying group name
      $query = 'UPDATE civicrm_group SET name = %1, title = %2 WHERE id = %3';
      CRM_Core_DAO::executeQuery($query, array(
        1 => array($groupName, 'String'),
        2 => array($groupData['title'], 'String'),
        3 => array($createdGroup['id'], 'Integer'),
      ));
      // set parent if applicable
      if (!empty($groupData['parent_name'])) {
        $this->createGroupNesting($createdGroup['id'], $groupData['parent_name']);
      }
      // set property
      $propertyName = $groupData['property'];
      $this->$propertyName = $createdGroup['id'];
    } else {
      $propertyName = $groupData['property'];
      $this->$propertyName = civicrm_api3('Group', 'getvalue', array(
        'name' => $groupName,
        'return' => 'id',
      ));
    }
  }

  /**
   * Method to create group nesting
   *
   * @param $childId
   * @param $parentName
   */
  private function createGroupNesting($childId, $parentName) {
    try {
      $parentId = civicrm_api3('Group', 'getvalue', array(
        'name' => $parentName,
        'return' => 'id',
      ));
      civicrm_api3('GroupNesting', 'create', array(
        'child_group_id' => $childId,
        'parent_group_id' => $parentId,
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message('Could not create group nesting between child group with id '
        .$childId.' and parent group with name '.$parentName.', error from API GroupNesting Create: '.$ex->getMessage());
    }
  }

  /**
   * Method to create location type for API Eingabe
   */
  private function createApiEingabeLocationType() {
    $apiEingabeName = 'fzfd_api_eingabe';
    try {
      $count = civicrm_api3('LocationType', 'getcount', array('name' => $apiEingabeName));
      if ($count == 0) {
        try {
          civicrm_api3('LocationType', 'create', array(
            'name' => $apiEingabeName,
            'display_name' => 'API-Eingabe',
            'is_active' => 1,
            'is_reserved' => 1,
          ));
        }
        catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::debug_log_message(ts('Could not create location type API-Eingabe, error message from API LocationType create: ' . $ex->getMessage()));
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message(ts('Unexpected problem using API LocationType getcount, error message: ' . $ex->getMessage()));
    }
  }

  /**
   * Method to set the event types
   */
  private function setEventTypes() {
    try {
      $apiResult = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'event_type',
        'options' => array('limit' => 0),
      ));
      foreach ($apiResult['values'] as $apiOptionValue) {
        switch ($apiOptionValue['name']) {
          case 'Berufsbegleitende Weiterbildung Friedens- und Konfliktarbeit':
            $this->_weiterBerufsEventTypeId = $apiOptionValue['value'];
            break;
          case 'Weiterbildung Friedens- und Konfliktarbeit in Vollzeit':
            $this->_weiterVollzeitEventTypeId = $apiOptionValue['value'];
            break;
          case 'Seminar':
            $this->_seminarEventTypeId = $apiOptionValue['value'];
            break;
          case 'Online-Seminar':
            $this->_onlineSeminarEventTypeId = $apiOptionValue['value'];
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message(ts('Unexpected problem using API OptionValue get for event types, error message: ' . $ex->getMessage()));
    }
  }

  /**
   * Method to set the participant status ids
   *
   * @throws CiviCRM_API3_Exception
   */
  private function setParticipantStatusIds() {
    // create new if required
    try {
      $count = civicrm_api3('ParticipantStatusType', 'getcount', ['name' => 'neu']);
      if ($count == 0) {
        civicrm_api3('ParticipantStatusType', 'create', [
          'sequential' => 1,
          'class' => "Positive",
          'label' => "Neu",
          'is_active' => 1,
          'is_reserved' => 1,
          'is_counted' => 0,
          'name' => "neu",
          'weight' => 0,
          ]);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      civicrm_api3('ParticipantStatusType', 'create', [
        'sequential' => 1,
        'class' => "Positive",
        'label' => "Neu",
        'is_active' => 1,
        'is_reserved' => 1,
        'is_counted' => 0,
        'name' => "neu",
        'weight' => 0,
          ]);
    }
    try {
      $apiResults = civicrm_api3('ParticipantStatusType', 'get', [
        'options' => ['limit' => 0],
      ]);
      foreach ($apiResults['values'] as $participantStatusId => $participantStatus) {
        switch ($participantStatus['name']) {
          case "neu":
            $this->_neuParticipantStatusTypeId = $participantStatusId;
            break;
          case "Registered":
            $this->_registeredParticipantStatusId = $participantStatusId;
            break;
          case "On waitlist":
            $this->_waitlistedParticipantStatusId = $participantStatusId;
            break;
          case "Cancelled":
            $this->_cancelledParticipantStatusId = $participantStatusId;
            break;
          case "2.1_Rechnung_zugesandt":
            $this->_rechnungZuParticipantStatusId = $participantStatusId;
            break;
          case "Partially paid":
            $this->_partiallyPaidParticipantStatusId = $participantStatusId;
            break;
          case "Pending from incomplete transaction":
            $this->_incompleteParticipantStatusId = $participantStatusId;
            break;
          case "3 Komplette Zahlung eingegangen":
            $this->_kompletteZahlungParticipantStatusId = $participantStatusId;
            break;
          case "6 Zertifikat ausgehändigt":
            $this->_zertifikatParticipantStatusId = $participantStatusId;
            break;
          case "6.1 Zertifikat nicht ausgestellt":
            $this->_zertifikatNichtParticipantStatusId = $participantStatusId;
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Could not find any participant statusses using API ParticipantStatusType get in ' . __METHOD__));
    }
  }

  /**
   * Method to set the participant role ids
   *
   * @throws CiviCRM_API3_Exception
   */
  private function setParticipantRoleIds() {
    try {
      $this->_attendeeParticipantRoleId = civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'participant_role',
        'name' => 'Attendee',
        'return' => 'value',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Could not find participant role with name Attendee using API OptionValue getvalue in ')
        . __METHOD__ . ts(', error message from API: ') . $ex->getMessage());
    }
  }

  /**
   * Method to set the bank account
   */
  private function setBankAccountReferenceType() {
    try {
      $this->_bankAccountReferenceType = civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'civicrm_banking.reference_types',
        'name' => 'IBAN',
        'return' => 'id',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Could not find IBAN banking account reference type in ') .__METHOD__
        . ts(', error from API OptionValue getvalue: ') . $ex->getMessage());
    }
  }
  private function setContributionStatusIds() {
    try {
      $this->_completedContributionStatusId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'contribution_status',
        'name' => 'Completed',
        'return' => 'value',
      ));
      $this->_pendingContributionStatusId = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'contribution_status',
        'name' => 'Pending',
        'return' => 'value',
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find the standard completed or pending contribution status in '.__METHOD__
        .', contact your system administrator. Error from API OptionValue Type getvalue: '.$ex->getMessage());
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
