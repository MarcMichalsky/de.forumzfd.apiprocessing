<?php

/**
 * Class for ForumZFD Phone API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 12 September 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Phone {

  /**
   * Method to create a phone coming in from the website
   * (assumes check on $params['phone'] is before calling this method)
   *
   * @param $params
   * @param $contactId
   */
  public function createIncomingPhone($params, $contactId) {
    if (!empty($params) && !empty($contactId)) {
      // only if contact exists
      $contactCount = civicrm_api3('Contact', 'getcount', array('id' => $contactId,));
      if ($contactCount != 0) {
        $phoneParams = array(
          'contact_id' => $contactId,
          'phone' => $params['phone'],
        );
        // use default phone type id if no phone type in params
        if (!isset($params['phone_type_id']) || empty($params['phone_type_id'])) {
          $phoneParams['phone_type_id'] = CRM_Apiprocessing_Config::singleton()->getDefaultPhoneTypeId();
        } else {
          $phoneParams['phone_type_id'] = $params['phone_type_id'];
        }
        // use default location type id
        $phoneParams['location_type_id'] = CRM_Apiprocessing_Config::singleton()->getDefaultLocationTypeId();
        // create phone if it does not exist yet
        if ($this->alreadyExists($phoneParams) == FALSE) {
          try {
            civicrm_api3('Phone', 'create', $phoneParams);
          }
          catch (CiviCRM_API3_Exception $ex) {
            $errorMessage = 'Could not create phone for contact with id '.$contactId.' in '.__METHOD__
              .', error from API Phone Create: '.$ex->getMessage();
            $activity = new CRM_Apiprocessing_Activity();
            $activity->createNewErrorActivity('forumzfd', $errorMessage, $phoneParams);
          }
        }

      } else {
        $errorMessage = 'Contact with id '.$contactId.' not found when trying to add a phone for the contact.';
        $activity = new CRM_Apiprocessing_Activity();
        $activity->createNewErrorActivity('forumzfd', $errorMessage, $params);
      }
    }
  }

  /**
   * Method to check if phone already exists
   *
   * @param $phoneParams
   * @return bool
   */
  private function alreadyExists($phoneParams) {
    try {
      $phoneCount = civicrm_api3('Phone', 'getcount', $phoneParams);
      if ($phoneCount > 0) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

}