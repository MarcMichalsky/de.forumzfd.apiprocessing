<?php

/**
 * Class for ForumZFD Contact API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Contact {

	/**
   * Method to find either the contact id with hash if there is a single match or the number of matches found
   *
	 * @param $hash
   * @return array|bool
   */
	public function findIndividualIdWithHash($hash) {
    try {
      $individualId = civicrm_api3('Contact', 'getvalue', array(
        'hash' => $hash,
        'return' => 'id',
        'options' => ['limit' => 1],
      ));
      return $individualId;
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
	}

  /**
   * Method to find either the individual id with email if there is a single match or the number of matches found
   *
   * @param array $params
   * @return int|bool
   */
  public function findIndividualId(array $params) {
    if (!empty($params)) {
      $params['contact_type'] = "Individual";
      try {
        $individual = civicrm_api3('Contact', 'getorcreate', $params);
        if (isset($individual['id'])) {
          return $individual['id'];
        }
      } catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to find either the organization id
   *
   * @param array $params
   * @return array|bool
   */
  public function findOrganizationId($params) {
    if (!empty($params)) {
      $orgaParams['contact_type'] = "Organization";
      $orgaParams["organization_name"] = $params["organization_name"];
      $orgaParams["xcm_profile"] = CRM_Apiprocessing_Settings::singleton()
        ->get('fzfd_xcm_organization_profile');
      foreach ($params as $key => $value) {
        $keySegments = explode("_", $key);
        if ($keySegments[0] == "organization" && $keySegments[1] != "name") {
          array_shift($keySegments);
          $key = implode("_", $keySegments);
          $orgaParams[$key] = $value;
        }
      }
      try {
        $organization = civicrm_api3('Contact', 'getorcreate', $orgaParams);
        if (isset($organization['id'])) {
          return (int) $organization['id'];
        }
      } catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to create or find organization
   *
   * @param array $params
   * @param int $individualId
   * @return bool|int
   */
  public function processOrganization(array $params, int $individualId) {
    $organizationId = $this->findOrganizationId($params);
    if ($organizationId) {
      if (isset($params['rechnungsadresse']) && !empty($params['rechnungsadresse'])) {
        $address = new CRM_Apiprocessing_Address();
        $address->processBillingAddress($params['rechnungsadresse'], (int) $organizationId);
      }
      // now process relationship between organization and individual
      $relationship = new CRM_Apiprocessing_Relationship();
      $relationship->processEmployerRelationship((int) $organizationId, $individualId);
      return (int) $organizationId;
    }
    return FALSE;
  }

  /**
   * Method to process an incoming individual. This method will determine if the individual can be uniquely identified
   * by the email. If no individual with the email is found, it will create a new individual.
   *
   * @param array $params
   * @return int
   * @throws CiviCRM_API3_Exception when contact not created
   */
  public function processIncomingIndividual(array $params) {
    $findId = FALSE;
    $address = new CRM_Apiprocessing_Address();
    // if only 1 address XCM can deal with it
    if (isset($params['individual_addresses']) && !empty($params['individual_addresses'])) {
      $address->processIncomingAddressArray($params);
    }
    if (isset($params['contact_hash'])) {
      $params['contact_hash'] = trim($params['contact_hash']);
    }
		if (isset($params['contact_hash']) && !empty($params['contact_hash'])) {
      $findId = $this->findIndividualIdWithHash($params['contact_hash']);
    }
    if (!$findId) {
      $findId = $this->findIndividualId($params);
      if ($findId) {
        if (isset($params['individual_addresses']) && !empty($params['individual_addresses'])) {
          $address->processIncomingAddressArray($params, (int) $findId);
        }
        if (isset($params['rechnungsadresse']) && !empty($params['rechnungsadresse'])) {
          $address->processBillingAddress($params['rechnungsadresse'], (int) $findId);
        }
      }
      else {
        throw new CiviCRM_API3_Exception('Could not find or create contact.');
      }
    }
    unset ($address);
    return $findId;
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
   * Method to remove the unwanted temporary tags
   */
  public function removeUnwantedTemporaryTags() {
    $query = "SELECT entity_id
      FROM civicrm_entity_tag
      WHERE entity_table = %1 AND tag_id = %2
      AND entity_id NOT IN (SELECT distinct(contact_id) FROM civicrm_fzfd_temp)";
    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => ['civicrm_contact', 'String'],
      2 => [CRM_Apiprocessing_Config::singleton()->getTemporaryTagId(), 'Integer'],
    ]);
    while ($dao->fetch()) {
      try {
        civicrm_api3('EntityTag', 'delete', [
          'tag_id' => CRM_Apiprocessing_Config::singleton()->getTemporaryTagId(),
          'contact_id' => $dao->entity_id,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
  }


}
