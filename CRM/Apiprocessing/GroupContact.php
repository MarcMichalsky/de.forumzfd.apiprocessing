<?php

/**
 * Class for ForumZFD GroupContact API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_GroupContact {

  private $_newsletterGroupIds = array();
  private $_newsletterParentGroupId = NULL;

  /**
   * CRM_Apiprocessing_GroupContact constructor.
   *
   * @throws Exception when error retrieving parent group for newsletters
   */
  function __construct() {
    // retrieve newsletter parent group
    try {
      $this->_newsletterParentGroupId = civicrm_api3('Group',  'getvalue', array(
        'name' => 'forumzfd_newsletters',
        'return' => 'id'
      ));
      // then retrieve all newsletter and job alert groups that are active
      try {
        $groups = civicrm_api3('Group', 'get', array(
          'parents' => $this->_newsletterParentGroupId,
          'is_active' => 1,
          'options' => array('limit' => 0,),
        ));
        foreach ($groups['values'] as $group) {
          $this->_newsletterGroupIds[] = $group['id'];
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find a parent group for ForumZFD newsletters in '.__METHOD__
        .', contact your system administrator. Error from API Group getvalue: '.$ex->getMessage());
    }
  }

  /**
   * Method to process the api request to subscribe
   *
   * @param array $apiParams
   * @return array
   */
  public function processApiSubscribe($apiParams) {
    // put newsletter ids from string into array
    $subscribeNewsletterIds = CRM_Apiprocessing_Utils::storeNewsletterIds($apiParams['newsletter_id']);
    return array();
  }

}