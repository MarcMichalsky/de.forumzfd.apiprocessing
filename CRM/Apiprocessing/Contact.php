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
   * Method to find either the contact id with email if there is a single match or the number of matches found
   *
   * @param $email
   * @return array|bool
   */
  public function findContactIdWithEmail($email) {
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

}