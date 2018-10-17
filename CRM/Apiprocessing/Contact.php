<?php

/**
 * Class for ForumZFD Contact API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Contact {

  private $_defaultContactType = NULL;

  /**
   * CRM_Apiprocessing_Contact constructor.
   */
  function __construct()   {
    $this->_defaultContactType = "Individual";
  }
	
	/**
   * Method to find either the contact id with hash if there is a single match or the number of matches found
   *
	 * @param $hash
   * @return array|bool
   */
	public function findIndividualIdWithHash($hash) {
    try {
      $individualCount = civicrm_api3('Contact', 'getcount', array(
        'hash' => $hash,
      ));
      if ($individualCount == 1) {
        $individualId = civicrm_api3('Contact', 'getvalue', array(
          'hash' => $hash,
          'return' => 'id',
        ));
        return array('individual_id' => $individualId,);
      } elseif ($individualCount > 1) {
        return array('count' => $individualCount);
      } else {
      	return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
	}

  /**
   * Method to find either the individual id with email if there is a single match or the number of matches found
   *
   * @param $email
   * @return array|bool
   */
  public function findIndividualIdWithEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return FALSE;
    }
    try {
      $individualCount = civicrm_api3('Contact', 'getcount', array(
        'email' => $email,
      ));
      if ($individualCount == 1) {
        $individualId = civicrm_api3('Contact', 'getvalue', array(
          'email' => $email,
          'return' => 'id',
        ));
        return array('individual_id' => $individualId,);
      } else {
        return array('count' => $individualCount);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to find either the organization id with organization_name if there is a single match or the number of matches found
   *
   * @param $organizationName
   * @return array|bool
   */
  public function findOrganizationIdWithName($organizationName) {
    if (empty($organizationName)) {
      return FALSE;
    }
    try {
      $organizationCount = civicrm_api3('Contact', 'getcount', array(
        'contact_type' => 'Organization',
        'organization_name' => $organizationName,
      ));
      if ($organizationCount == 1) {
        $organizationId = civicrm_api3('Contact', 'getvalue', array(
          'contact_type' => 'Organization',
          'organization_name' => $organizationName,
          'return' => 'id',
        ));
        return array('organization_id' => $organizationId,);
      } else {
        return array('count' => $organizationCount);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to process incoming organization. It will try to find an organization with the incoming organization_name. If
   * no organization is found, a new one will be created. If 1 organization is found, the id will be returned. If more
   * than 1 are found, a new organization will be created as well as an error activity.
   *
   * @param $params
   * @return bool|mixed
   * @throws Exception
   */
  public function processIncomingOrganization($params) {
    if (!isset($params['organization_name']) || empty($params['organization_name'])) {
      return FALSE;
    }
    $find = $this->findOrganizationIdWithName($params['organization_name']);
    if (!$find) {
      return FALSE;
    }
    if (isset($find['organization_id'])) {
      // update organization name if it makes sense
      $this->updateOrganizationNames($find['organization_id'], $params);
      // possibly add or update address
      $addressParams = $this->getOrganizationAddressParams($params);
      if (!empty($addressParams)) {
        $addressParams['contact_id'] = $find['organization_id'];
        $address = new CRM_Apiprocessing_Address();
        $address->createNewAddress($addressParams);
      }
      return $find['organization_id'];
    } else {
      $newOrganizationParams = $this->getNewOrganizationParams($params);
      try {
        $newOrganization = civicrm_api3('Contact', 'create', $newOrganizationParams);
        // create address if applicable
        $addressParams = $this->getOrganizationAddressParams($params);
        if (!empty($addressParams)) {
          $addressParams['contact_id'] = $newOrganization['id'];
          $address = new CRM_Apiprocessing_Address();
          $address->createNewAddress($addressParams);
        }
        // create phone if applicable
        if (isset($params['phone']) && !empty($params['phone'])) {
          $phone = new CRM_Apiprocessing_Phone();
          $phone->createIncomingPhone($params, $newOrganization['id']);
        }

        $this->addNewContactToSettingsGroup($newOrganization['id']);
        // if more than one organization found with name, create error activity
        if (isset($find['count']) && $find['count'] > 1) {
          $errorActivity = new CRM_Apiprocessing_Activity();
          $errorActivity->createNewErrorActivity('forumzfd','More than one organization found with organization name', $params);
        }
        return $newOrganization['id'];
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not create a new organization in '.__METHOD__
          .'contact your system administrator. Error message from API Contact create '.$ex->getMessage());
      }
    }
  }

  /**
   * Method to distill the address params from the incoming params from website
   *
   * @param $params
   * @return array
   */
  private function getOrganizationAddressParams($params) {
    $addressParams = array();
    if (isset($params['organization_street_address']) && !empty($params['organization_street_address'])) {
      $addressParams['street_address'] = $params['organization_street_address'];
    }
		if (isset($params['organization_supplemental_address_1']) && !empty($params['organization_supplemental_address_1'])) {
      $addressParams['supplemental_address_1'] = $params['organization_supplemental_address_1'];
    }
    if (isset($params['organization_postal_code']) && !empty($params['organization_postal_code'])) {
      $addressParams['postal_code'] = $params['organization_postal_code'];
    }
    if (isset($params['organization_city']) && !empty($params['organization_city'])) {
      $addressParams['city'] = $params['organization_city'];
    }
    if (isset($params['organization_country_iso']) && !empty($params['organization_country_iso'])) {
      $addressParams['country_iso'] = $params['organization_country_iso'];
    }
    return $addressParams;
  }

  /**
   * Method to process an incoming individual. This method will determine if the individual can be uniquely identified
   * by the email. If no individual with the email is found, it will create a new individual. If more individuals are
   * found with the email, it will create a new individual but also create an error activity.
   *
   * @param $params
   * @return int|bool
   * @throws Exception when contact not created
   */
  public function processIncomingIndividual($params) {
    if (isset($params['contact_hash'])) {
      $params['contact_hash'] = trim($params['contact_hash']);
    }
		if (isset($params['contact_hash']) && !empty($params['contact_hash'])) {
    	$find = $this->findIndividualIdWithHash($params['contact_hash']);
		} else {
			$find = $this->findIndividualIdWithEmail($params['email']);
		}
    if (!$find) {
      return FALSE;
    }
    if (isset($find['individual_id'])) {
		  // possibly update first and last name of individual or formal title, prefix id
      $this->updateIndividualData($find['individual_id'], $params);
      // possibly add or update address
      if (isset($params['individual_addresses']) && !empty($params['individual_addresses'])) {
        $address = new CRM_Apiprocessing_Address();
        $address->processIncomingAddressArray($params['individual_addresses'], $find['individual_id']);
      }
      return $find['individual_id'];
    } else {
      $newIndividualParams = $this->getNewIndividualParams($params);
      try {
        $newIndividual = civicrm_api3('Contact', 'create', $newIndividualParams);
        // create address if applicable
        if (isset($params['individual_addresses']) && !empty($params['individual_addresses'])) {
          $address = new CRM_Apiprocessing_Address();
          $address->processIncomingAddressArray($params['individual_addresses'], $newIndividual['id']);
        }
        // create phone if applicable
        if (isset($params['phone']) && !empty($params['phone'])) {
          $phone = new CRM_Apiprocessing_Phone();
          $phone->createIncomingPhone($params, $newIndividual['id']);
        }
        // create skype if applicable
        if (isset($params['skype']) && !empty($params['skype'])) {
          $this->createIncomingSkype($newIndividual['id'], $params['skype']);
        }
				$this->addNewContactToSettingsGroup($newIndividual['id']);
        // if more than one individual found with email, create error activity
        if (isset($find['count']) && $find['count'] > 1) {
          $errorActivity = new CRM_Apiprocessing_Activity();
          $errorActivity->createNewErrorActivity('forumzfd','More than one individual found with email', $params);
        }
        return $newIndividual['id'];
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not create a new individual in '.__METHOD__
          .', contact your system administrator. Error message from API Contact create '.$ex->getMessage());
      }
    }
  }

  /**
   * Method to add Skype to contact
   *
   * @param $contactId
   * @param $skypeName
   */
  private function createIncomingSkype($contactId, $skypeName) {
    $config = CRM_Apiprocessing_Config::singleton();
    try {
      civicrm_api3('IM', 'create', array(
        'contact_id' => $contactId,
        'provider_id' => $config->getSkypeProviderId(),
        'location_type_id' => $config->getDefaultLocationTypeId(),
        'name' => $skypeName,
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message(ts('Could not add skype name ') . $skypeName . ts(' to contact ')
        . $contactId . ts(' in ') . __METHOD__ . ts(', error from API IM Create: ' . $ex->getMessage()));
    }
  }

  /**
   * Method to distill individual create params from params array (coming in from website)
   *
   * @param $params
   * @return array
   */
  private function getNewIndividualParams($params) {
  	$config = CRM_Apiprocessing_Config::singleton();
    $newIndividualParams = array();
    if (isset($params['contact_type']) && !empty($params['contact_type'])) {
      $newIndividualParams['contact_type'] = $params['contact_type'];
    } else {
      $newIndividualParams['contact_type'] = $this->_defaultContactType;
    }
    $apiFields = civicrm_api3('Contact', 'getfields', array());
    foreach ($apiFields['values'] as $apiField) {
      // ignore api_key
      if ($apiField['name'] != 'api_key') {
        if (isset($params[$apiField['name']])) {
          $newIndividualParams[$apiField['name']] = $params[$apiField['name']];
        }
      }
    }
    // if gender_id not set, generate from prefix
    if (!isset($newIndividualParams['gender_id'])) {
      if (isset($params['prefix_id'])) {
        $genderId = $this->generateGenderFromPrefix($params);
      } else {
        $genderId = NULL;
      }
      if (!empty($genderId)) {
        $newIndividualParams['gender_id'] = $genderId;
      }
    }
		
		if (isset($params['additional_data'])) {
			$newIndividualParams['custom_'.$config->getAdditionalDataCustomFieldId()] = $params['additional_data'];
		}
		if (isset($params['department'])) {
			$newIndividualParams['custom_'.$config->getDepartmentCustomFieldId()] = $params['department'];
		}
		
    return $newIndividualParams;
  }

  /**
   * Method to distill organization create params from params array (coming in from website)
   *
   * @param $params
   * @return array
   */
  private function getNewOrganizationParams($params) {
    return array(
      'contact_type' => 'Organization',
      'organization_name' => $params['organization_name'],
    );
  }

	/**
	 * Method to add a contact to the group new_contacts which is set by the administrator.
	 * This setting could be empty so a check takes places whether the setting is set. 
	 * If not set the contact will not be added to a group.
   *
   * @param int $contactId
   * @throws
	 */
	private function addNewContactToSettingsGroup($contactId) {
		$settings = CRM_Apiprocessing_Settings::singleton();
    $newContactGroupId = $settings->get('new_contacts_group_id');
		if (!empty($newContactGroupId)) {
			civicrm_api3('GroupContact', 'create', array(
				'group_id' => $newContactGroupId,
				'contact_id' => $contactId,
			));
		}
	}

  /**
   * Method to generate gender id based on prefix
   *
   * @param $prefixId
   * @return mixed|null
   */
  public function generateGenderFromPrefix($prefixId) {
    $genderId = NULL;
    $malePrefix = array(3, 4);
    $femalePrefix = array(1,2);
    if (in_array($prefixId, $femalePrefix)) {
      // female gender id
      $genderId = 1;
    }
    if (in_array($prefixId, $malePrefix)) {
      // male gender id
      $genderId = 2;
    }
    return $genderId;
  }

  /**
   * Method to return the spendengruppe of a contact. Will be called from the website
   *
   * @param $params
   * @return bool|array
   * @throws Exception when hash not in params or empty
   */
  public function getSpendengruppe($params) {
    // returns level one if checksum is invalid
    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($params['contact_id'], $params['checksum'])) {
      $result = array(
        'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_min'),
        'avg_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_avg'),
        'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_max'),
      );
      return $result;
    }
    try {
      $goldCount = civicrm_api3('GroupContact', 'getcount', array(
        'contact_id' => $params['contact_id'],
        'group_id' => CRM_Apiprocessing_Config::singleton()->getGoldGroupId(),
      ));
      if ($goldCount > 0) {
        $result = array(
          'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_three_min'),
          'avg_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_three_avg'),
          'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_three_max'),
        );
        return $result;
      }
      $silverCount = civicrm_api3('GroupContact', 'getcount', array(
        'contact_id' => $params['contact_id'],
        'group_id' => CRM_Apiprocessing_Config::singleton()->getSilverGroupId(),
      ));
      if ($silverCount > 0) {
        $result = array(
          'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_two_min'),
          'avg_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_two_avg'),
          'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_two_max'),
        );
        return $result;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    $result = array(
      'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_min'),
      'avg_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_avg'),
      'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_max'),
    );
    return $result;
  }

  /**
   * Method to process the FzfdPerson Get api
   *
   * @param $apiParams
   * @return array
   */
  public function getFzfdPerson($apiParams) {
    $result = array();
    // only valid if either group_titles are valid groups
    if ($this->validFzfdPersonGet($apiParams['group_titles'])) {
      //get array with contact_ids with group_titles
      $contactIds = $this->getContactIdsWithGroupTitles($apiParams['group_titles']);
      if (!empty($contactIds)) {
      // set contact data
      $result = $this->setFzfdPersonData($contactIds);
      }
    } else {
      $result['error_message'] = 'Parameter group_titles is either empty or contains invalid group titles';
    }
    return $result;
  }

  /**
   * Method to check if the parameters passed to api FzfdPerson get are valid
   *
   * @param $groupTitles
   * @return bool
   */
  private function validFzfdPersonGet($groupTitles) {
    // error if group_titles empty
    if (empty($groupTitles)) {
      return FALSE;
    }
    try {
      // get valid group titles
      $validTitles = array();
      $validGroupIds = CRM_Apiprocessing_Settings::singleton()->get('fzfdperson_groups');
      foreach ($validGroupIds as $validGroupId) {
        $validTitles[] = civicrm_api3('Group', 'getvalue', array(
          'id' => $validGroupId,
          'return' => 'title',
          ));
      }
      if (empty($validTitles)) {
        return FALSE;
      }
      foreach ($groupTitles as $groupTitle) {
        if (!in_array($groupTitle, $validTitles)) {
          return FALSE;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Method to get all contactIds of group members based on group titles
   *
   * @param $groupTitles
   * @return array
   */
  private function getContactIdsWithGroupTitles($groupTitles) {
    $result = array();
    foreach ($groupTitles as $groupTitle) {
      try {
        $groupId = civicrm_api3('Group', 'getvalue', array(
          'title' => $groupTitle,
          'return' => 'id',
        ));
        $groupContacts = civicrm_api3('GroupContact', 'get', array(
          'group_id' => $groupId,
          'status' => 'Added',
          'options' => array('limit' => 0),
        ));
        foreach ($groupContacts['values'] as $groupContact) {
          if (!in_array($groupContact['contact_id'], $result)) {
            $result[] = $groupContact['contact_id'];
          }
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $result;
  }

  /**
   * Method to collect the data for fzfdPerson get
   *
   * @param $contactIds
   * @return array
   */
  private function setFzfdPersonData($contactIds) {
    $result = array();
    $locationTypeId = CRM_Apiprocessing_Settings::singleton()->get('fzfdperson_location_type');
    foreach ($contactIds as $contactId) {
      try {
        $result[$contactId] = civicrm_api3('Contact', 'getsingle', array(
          'id' => $contactId,
          'return' => array("first_name", "last_name", "prefix_id", "formal_title"),
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
      try {
        // add email, phone and im of location type in settings
        $result[$contactId]['email'] = civicrm_api3('Email', 'getvalue', array(
          'contact_id' => $contactId,
          'location_type_id' => $locationTypeId,
          'options' => array('limit' => 1),
          'return' => 'email',
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
      try {
        $phoneData = civicrm_api3('Phone', 'getsingle', array(
          'contact_id' => $contactId,
          'location_type_id' => $locationTypeId,
          'options' => array('limit' => 1),
        ));
        $result[$contactId]['phone'] = $phoneData['phone'];
        if (isset($phoneData['phone_ext']) && !empty($phoneData['phone_ext'])) {
          $result[$contactId]['phone'] = $phoneData['phone'].' '.$phoneData['phone_ext'];
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
      try {
        $imData = civicrm_api3('IM', 'getsingle', array(
          'return' => array("provider_id", "name"),
          'contact_id' => "user_contact_id",
          'options' => array('limit' => 1),
          'location_type_id' => "Arbeit",
        ));
        if (!empty($imData['provider_id'])) {
          $result[$contactId]['instant_messenger']['service'] = civicrm_api3('OptionValue', 'getvalue', array(
            'option_group_id' => 'instant_messenger_service',
            'value' => $imData['provider_id'],
            'return' => 'label',
          ));
        }
        $result[$contactId]['instant_messenger']['id'] = $imData['name'];
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $result;
  }

  /**
   * Method to update:
   * - the first and/or last name of the contact with the values from the params
   *   (only if the contact does not have a first and/or last name yet)
   *   If contact does have a first/last name, if incoming is different -> generate activity
   * - update the formal title and/or prefix id if the contact does not have a prefix id and/or formal title
   *   If contact does have a formal title and/or prefix id, if the incoming is different -> generate activity
   *
   * @param $contactId
   * @param $params
   */
  private function updateIndividualData($contactId, $params) {
    $changeParams = [];
    try {
      $contact = civicrm_api3(' Contact', ' getsingle', ['id' => $contactId]);
      if (!isset($contact['first_name']) || empty($contact['first_name'])) {
        if (isset($params['first_name']) && !empty($params['first_name'])) {
          $changeParams['first_name'] = $params['first_name'];
        }
      }
      if (!isset($contact['last_name']) || empty($contact['last_name'])) {
        if (isset($params['last_name']) && !empty($params['last_name'])) {
          $changeParams['last_name'] = $params['last_name'];
        }
      }
      if (!isset($contact['prefix_id']) || empty($contact['prefix_id'])) {
        if (isset($params['prefix_id']) && !empty($params['prefix_id'])) {
          $changeParams['prefix_id'] = $params['prefix_id'];
        }
      }
      if (!isset($contact['formal_title']) || empty($contact['formal_title'])) {
        if (isset($params['formal_title']) && !empty($params['formal_title'])) {
          $changeParams['formal_title'] = $params['formal_title'];
        }
      }
      if (!empty($changeParams)) {
        $changeParams['id'] = $contactId;
        civicrm_api3('Contact', 'create', $changeParams);
      } else {
        $this->compareIndividualData($contact, $params);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message(ts(' Could not find or update contact with id ' . $contactId . ' in '. __METHOD__
        . ' when trying check if names, prefix or formal title need to be updated'));
    }
  }

  /**
   * Method to update the organization name of the contact with the values from the params
   *
   * @param $contactId
   * @param $params
   */
  private function updateOrganizationNames($contactId, $params) {
    $nameParams = [];
    try {
      $contact = civicrm_api3(' Contact', ' getsingle', ['id' => $contactId]);
      if (isset($contact['organization_name']) && !empty($contact['organization_name'])) {
        if ($contact['organization_name'] != $params['organization_name']) {
          $nameParams['organization_name'] = $params['organization_name'];
        }
      }
      else {
        $nameParams['organization_name'] = $params['organization_name'];
      }
      if (!empty($nameParams)) {
        $nameParams['id'] = $contactId;
        civicrm_api3('Contact', 'create', $nameParams);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message(ts(' Could not find or update contact with id ' . $contactId . ' in '. __METHOD__
        . ' when trying check if organization name needs to be updated'));
    }
  }

  /**
   * Method to compare incoming and current names on a contact and create error activity if different
   *
   * @param $contact
   * @param $params
   */
  private function compareIndividualData($contact, $params) {
    $details = [];
    if ($contact['first_name'] != $params['first_name']) {
      $details[ts('Current first name')] = $contact['first_name'];
      $details[ts('Incoming first name')] = $params['first_name'];
    }
    if ($contact['last_name'] != $params['last_name']) {
      $details[ts('Current last name')] = $contact['last_name'];
      $details[ts('Incoming last name')] = $params['last_name'];
    }
    if ($contact['prefix_id'] != $params['prefix_id']) {
      $details[ts('Current prefix id')] = $contact['prefix_id'];
      $details[ts('Incoming prefix id')] = $params['prefix_id'];
    }
    if ($contact['formal_title'] != $params['formal_title']) {
      $details[ts('Current formal title')] = $contact['formal_title'];
      $details[ts('Incoming formal title')] = $params['formal_title'];
    }
    if (!empty($details)) {
      $errorActivity = new CRM_Apiprocessing_Activity();
      $errorActivity->createNewErrorActivity('forumzfd', 'Different incoming data', $details, $contact['id']);
    }
  }
}