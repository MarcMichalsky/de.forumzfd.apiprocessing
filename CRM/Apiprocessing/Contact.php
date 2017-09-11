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
      $contactCount = civicrm_api3('Contact', 'getcount', array(
        'email' => $email,
      ));
      if ($contactCount == 1) {
        $contactId = civicrm_api3('Contact', 'getvalue', array(
          'email' => $email,
          'return' => 'id',
        ));
        return array('contact_id' => $contactId,);
      } else {
        return array('count' => $contactCount);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to process an incoming contact. This method will determine if the contact can be uniquely identified by the
   * email. If no contact with the email is found, it will create a new contact. If more contacts are found with the email,
   * it will create a new contact but also create an error activity.
   *
   * @param $params
   * @return int|bool
   * @throws Exception when contact not created
   */
  public function processIncomingIndividual($params) {
    if (!isset($params['email']) || empty($params['email'])) {
      return FALSE;
    }
    $find = $this->findIndividualIdWithEmail($params['email']);
    if (!$find) {
      return FALSE;
    }
    if (isset($find['contact_id'])) {
      return $find['contact_id'];
    } else {
      $newIndividualParams = $this->getNewIndividualParams($params);
      try {
        $newIndividual = civicrm_api3('Contact', 'create', $newIndividualParams);
        // create address if applicable
        $params['contact_id'] = $newIndividual['id'];
        $address = new CRM_Apiprocessing_Address();
        $address->createNewAddress($params);
        // if more than one contact found with email, create error activity
        if (isset($find['count']) && $find['count'] > 1) {
          $errorActivity = new CRM_Apiprocessing_Activity();
          $errorActivity->createNewErrorActivity('forumzfd','More than one individual found with email', $params);
        }
        return $newIndividual['id'];
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not create a new individual in '.__METHOD__
          .'contact your system administrator. Error message from API Contact create '.$ex->getMessage());
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
    // address fields are useless *don't ask*
    $addressParams = array('street_address', 'postal_code', 'city',  'country_id');
    foreach ($addressParams as $addressParam) {
      if (isset($newIndividualParams[$addressParam])) {
        unset($newIndividualParams[$addressParam]);
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
    return $newIndividualParams;
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

}