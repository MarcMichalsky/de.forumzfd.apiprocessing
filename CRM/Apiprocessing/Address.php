<?php

use CRM_ActionProvider_ExtensionUtil as E;

/**
 * Class for ForumZFD Address API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Address
{

  public function createNewAddress($params)
  {
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
          $errorMessage = E::ts('New address created in API');
          $activity->createNewErrorActivity('forumzfd', $errorMessage, array(
            'contact_id' => $addressParams['contact_id'],
            'addressArray' => $addressParams
          ), $addressParams['contact_id']);
        } catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::debug_log_message('Could not create new address in ' . __METHOD__ . ', error message from API Address create: ' . $ex->getMessage());
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
  private function getAddressLocationTypeId($contactId)
  {
    // check if the contact already has addresses. If not, use Privat otherwise API Eingabe
    try {
      $count = civicrm_api3('Address', 'getcount', ['contact_id' => $contactId]);
      if ($count > 0) {
        return CRM_Apiprocessing_Config::singleton()->getApiEingabeLocationTypeId();
      }
    } catch (CiviCRM_API3_Exception $ex) {
    }
    return CRM_Apiprocessing_Config::singleton()->getDefaultLocationTypeId();
  }

  /**
   * Method to check if the address is indeed a change compared to the current addresses
   *
   * @param $newAddressParams
   * @return bool
   */
  private function addressAlreadyExists($newAddressParams)
  {
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
          } else {
            if ($currentAddress[$paramKey] != $newAddressParams[$paramKey]) {
              $sameAddress = FALSE;
            }
          }
        }
        if ($sameAddress) {
          return TRUE;
        }
      }
    } catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to process an array of addresses
   *
   * @param array $params
   * @param int $contactId
   */
  public function processIncomingAddressArray(array &$params, int $contactId = 0)
  {
    if (isset($params['individual_addresses']) && !empty($params['individual_addresses'])) {
      // correct array structure
      $addressesArray = [];
      foreach ($params['individual_addresses'] as $key => $values) {
        if (!is_array($values)) {
          $addressesArray[] = $params['individual_addresses'];
          break;
        } else {
          $addressesArray[] = $values;
        }
      }
      // if we only have 1 address, XCM can deal with it
      if (count($addressesArray) == 1) {
        $params = $params + $addressesArray[0];
        unset($params['individual_addresses']);
      } else {
        // if more than 1 address, deal with it in this class
        if ($contactId) {
          foreach ($addressesArray as $address) {
            $address['contact_id'] = $contactId;
            $this->createNewAddress($address);
          }
        }

      }
    }
  }

  /**
   * Method to add billing address
   *
   * @param array $address
   * @param int $contactId
   * @return bool
   */
  public function processBillingAddress(array $address, int $contactId) {
    $address['contact_id'] = $contactId;
    $locationTypeId = CRM_Apiprocessing_Settings::singleton()->get('fzfd_billing_location_type');
    if ($locationTypeId) {
      $address['location_type_id'] = $locationTypeId;
      try {
        civicrm_api3('Address', 'create', $address);
        // always create API activity for new address
        $activity = new CRM_Apiprocessing_Activity();
        $errorMessage = E::ts('New billing address created in API');
        $activity->createNewErrorActivity('forumzfd', $errorMessage, array(
          'contact_id' => $address['contact_id'],
          'addressArray' => $address
        ), $address['contact_id']);
        return TRUE;
      } catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->error(E::ts('Could not create new billing address in ') . __METHOD__ . E::ts(', error message from API Address create: ') . $ex->getMessage());
      }
    } else {
      Civi::log()->error(E::ts('Could not create new billing address in ') . __METHOD__ . E::ts(', no billing location type found in settings'));
    }
    return FALSE;
  }

  /**
   * Method to get address of contact using API4 or API3
   *
   * @param int $contactId
   * @param bool $isPrimary
   * @param int $locationTypeId
   * @return false|void
   */
  public function getAddress(int $contactId, bool $isPrimary, int $locationTypeId = 0) {
    $activity = new CRM_Apiprocessing_Activity();
    if (function_exists('civicrm_api4')) {
      try {
        $addresses = \Civi\Api4\Address::get()
          ->addSelect('street_address', 'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3', 'postal_code', 'city', 'country_id', 'country_id:name')
          ->addWhere('contact_id', '=', $contactId)
          ->setLimit(1);
        if ($isPrimary) {
          $addresses->addWhere('is_primary', TRUE);
        }
        if ($locationTypeId) {
          $addresses->addWhere('location_type_id', $locationTypeId);
        }
        $addresses->execute();
        foreach ($addresses as $address) {
          return $address;
        }
      }
      catch (API_Exception $ex) {
        $activity->createNewErrorActivity('akademie', E::ts('Could not find address for contact with API4 Address get'), ['contact_id' => $contactId]);
        return FALSE;
      }
    }
    else {
      try {
        $addressParams = [
          'contact_id' => $contactId,
          'options' => ['limit' => 1],
          'return' => ["street_address", "supplemental_address_1", "supplemental_address_2", "supplemental_address_3", "city", "postal_code", "country_id"]
          ];
        if ($isPrimary) {
          $addressParams['is_primary'] = 1;
        }
        if ($locationTypeId) {
          $addressParams['location_type_id'] = $locationTypeId;
        }
        $address = civicrm_api3('Address', 'getsingle', $addressParams);
        if ($address) {
          return $address;
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
        $activity->createNewErrorActivity('akademie', E::ts('Could not find address for contact with API3 Address get'), ['contact_id' => $contactId]);
        return FALSE;
      }
    }
  }

}
