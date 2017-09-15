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
    if (!isset($params['email']) || empty($params['email'])) {
      return FALSE;
    }
		if (isset($params['contact_hash'])) {
    	$find = $this->findIndividualIdWithHash($params['contact_hash']);
		} else {
			$find = $this->findIndividualIdWithEmail($params['email']);
		}
    if (!$find) {
      return FALSE;
    }
    if (isset($find['individual_id'])) {
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
   * with the only allowed parameter contact_hash
   *
   * @param $params
   * @return bool/array
   * @throws Exception when hash not in params or empty
   */
  public function getSpendengruppe($params) {
    $result = array(
      'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_min'),
      'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_one_max'),
    );
    if (!isset($params['contact_hash']) || empty($params['contact_hash'])) {
      throw new Exception('Contact Hash has to be set and can not be empty for API Spendengruppe Get');
    }
    // retrieve contact id with contact hash
    try {
      $contactId = civicrm_api3('Contact', 'getvalue', array(
        'hash' => $params['contact_hash'],
        'return' => 'id',
      ));
      // retrieve all groups that the contact is member of
      try {
        $goldCount = civicrm_api3('GroupContact', 'getcount', array(
          'contact_id' => $contactId,
          'group_id' => CRM_Apiprocessing_Config::singleton()->getGoldGroupId(),
        ));
        if ($goldCount > 0) {
          $result = array(
            'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_three_min'),
            'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_three_max'),
          );
        }
        $silverCount = civicrm_api3('GroupContact', 'getcount', array(
          'contact_id' => $contactId,
          'group_id' => CRM_Apiprocessing_Config::singleton()->getSilverGroupId(),
        ));
        if ($silverCount > 0) {
          $result = array(
            'min_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_two_min'),
            'max_value' => CRM_Apiprocessing_Settings::singleton()->get('fzfd_donation_level_two_max'),
          );
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('No contact found with the contact hash');
    }
    return $result;
  }
}