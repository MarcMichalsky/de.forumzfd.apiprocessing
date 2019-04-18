<?php

/**
 * Class for ForumZFD Address API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Address {

  public function createNewAddress($params) {
    $result = array();
    $addressFields = civicrm_api3('Address', 'getfields', array());
    foreach ($addressFields['values'] as $addressField) {
      if (isset($params[$addressField['name']])) {
        $addressParams[$addressField['name']] = $params[$addressField['name']];
      }
    }
    // replace country iso_code with id
    if (isset($params['country_iso'])) {
      $addressParams['country_id'] = CRM_Apiprocessing_Utils::getCountryIdWithIso($params['country_iso']);
    }
    // only create a new address if the address does not exist yet for the contact
    if (!$this->addressAlreadyExists($addressParams)) {
      // use location type API Eingabe if new address is not the only one, otherwise Privat
      $addressParams['location_type_id'] = $this->getAddressLocationTypeId($addressParams['contact_id']);
      // if supplemental_address, set as supplemental_address_1
      if (isset($params['supplemental_address'])) {
        $addressParams['supplemental_address_1'] = $params['supplemental_address'];
      }

      if (!empty($addressParams) && isset($addressParams['contact_id'])) {
        try {
          $newAddress = civicrm_api3('Address', 'create', $addressParams);
          $result = $newAddress['values'];
          // always create API activity for new address
          $activity = new CRM_Apiprocessing_Activity();
          $errorMessage = ts('New address created in API');
          $activity->createNewErrorActivity('forumzfd', $errorMessage, array(
            'contact_id' => $addressParams['contact_id'],
            'addressArray' => $addressParams
          ), $addressParams['contact_id']);
        }
        catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::debug_log_message('Could not create new address in '.__METHOD__.', error message from API Address create: '.$ex->getMessage());
        }
      }
    }
    return $result;
  }

  /**
   * Method to get the relevant location type id
   *
   * @param $contactId
   * @return mixed
   */
  private function getAddressLocationTypeId($contactId) {
    // check if the contact already has addresses. If not, use Privat otherwise API Eingabe
    try {
      $count = civicrm_api3('Address', 'getcount', ['contact_id' => $contactId]);
      if ($count > 0) {
        return CRM_Apiprocessing_Config::singleton()->getApiEingabeLocationTypeId();
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return CRM_Apiprocessing_Config::singleton()->getDefaultLocationTypeId();
  }

  /**
   * Method to check if the address is indeed a change compared to the current addresses
   *
   * @param $newAddressParams
   * @return bool
   */
  private function addressAlreadyExists($newAddressParams) {
    // get all addresses for contact
    try {
      $currentAddresses = civicrm_api3('Address', 'get', [
        'contact_id' => $newAddressParams['contact_id'],
        'options' => ['limit' => 0],
        'sequential' => 1,
      ]);
      foreach ($currentAddresses['values'] as $currentAddress) {
        // check if any of the values are diffent (ignore country is one of them is empty)
        $sameAddress = TRUE;
        foreach ($newAddressParams as $paramKey => $paramValue) {
          if ($paramKey == "country_id") {
            if (!empty($currentAddress[$paramKey]) && !empty($newAddressParams[$paramKey])) {
              if ($currentAddress[$paramKey] != $newAddressParams[$paramKey]) {
                $sameAddress = FALSE;
              }
            }
          }
          else {
            if ($currentAddress[$paramKey] != $newAddressParams[$paramKey]) {
              $sameAddress = FALSE;
            }
          }
        }
        if ($sameAddress) {
          return TRUE;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to process an array of addresses
   *
   * @param $addressArray
   * @param $contactId
   */
  public function processIncomingAddressArray($addressArray, $contactId) {
    // log error if addressArray is not an array
    if (!is_array($addressArray)) {
      $activity = new CRM_Apiprocessing_Activity();
      $errorMessage = 'Incoming parameter addressArray is not an array in '.__METHOD__.', no address(es) created';
      $activity->createNewErrorActivity('forumzfd', $errorMessage, array(
        'contact_id' => $contactId,
        'addressArray' => $addressArray,));
    } else {
      foreach ($addressArray as $addressKey => $newAddress) {
        // if newAddress is not an array, assume the addressArray contains just 1 address
        if (!is_array($newAddress)) {
          $newAddress = $addressArray;
          $newAddress['contact_id'] = $contactId;
          $this->createNewAddress($newAddress);
          break;
        }
        else {
          $newAddress['contact_id'] = $contactId;
          $this->createNewAddress($newAddress);
        }
      }
    }
  }

}