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
   * Method to process the api request to unsubscribe
   *
   * @param array $apiParams
   * @return array
   */
  public function processApiUnsubscribe($apiParams) {
    // put newsletter ids from string into array
    $subscribeNewsletterIds = CRM_Apiprocessing_Utils::storeNewsletterIds($apiParams['newsletter_ids']);
		// Make sure we only process the group ids which are a child of the newsletter parent group.
		// Ignore non existent group ids or group ids which are not part of the parent group.
		$subscribeNewsletterIds = $this->filterGroupIds($subscribeNewsletterIds);
		if (empty($subscribeNewsletterIds)) {
			return array(
  			'is_error' => 1,
  			'count' => 0,
  			'values' => array(),
  			'error_message' => 'Could not unsubscribe contact to newsletters in '.__METHOD__.', contact your system administrator. No valid groups given', 
			);
		}
		
		$contact = new CRM_Apiprocessing_Contact();
		try {
			if (isset($apiParams['contact_hash'])) {
				$newsletterContactId = $contact->findIndividualIdWithHash($apiParams['contact_hash']);
			} else {
				$newsletterContactId = $contact->findIndividualIdWithEmail($apiParams['email']);
			}
		} catch (Exception $ex) {
			return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not unsubscribe contact to newsletters in '.__METHOD__.', contact your system administrator. Error from API GroupContact create: '.$ex->getMessage(), 
				);
		}
		
		if (isset($newsletterContactId['individual_id'])) {
			$groupContactApiParams['group_id'] = $subscribeNewsletterIds;
			$groupContactApiParams['contact_id'] = $newsletterContactId['individual_id'];
			$groupContactApiParams['status'] = 'Removed';
			try {
				$result = civicrm_api3('GroupContact', 'create', $groupContactApiParams);
			} catch (CiviCRM_API3_Exception $ex) {
				return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not unsubscribe contact to newsletters in '.__METHOD__.', contact your system administrator. Error from API GroupContact create: '.$ex->getMessage(), 
				);
			}
		} else {
			return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not unsubscribe contact to newsletters in because we could not match it to a contact in '.__METHOD__.', contact your system administrator. Error from API GroupContact create: '.$ex->getMessage(), 
				);
		}
				
    return array(
    	'is_error' => 0,
    	'count' => count($subscribeNewsletterIds),
    	'values' => array(),
		);
  }

  /**
   * Method to process the api request to subscribe
   *
   * @param array $apiParams
   * @return array
   */
  public function processApiSubscribe($apiParams) {
    // put newsletter ids from string into array
    $subscribeNewsletterIds = CRM_Apiprocessing_Utils::storeNewsletterIds($apiParams['newsletter_ids']);
		// Make sure we only process the group ids which are a child of the newsletter parent group.
		// Ignore non existent group ids or group ids which are not part of the parent group.
		$subscribeNewsletterIds = $this->filterGroupIds($subscribeNewsletterIds);
		if (empty($subscribeNewsletterIds)) {
			return array(
  			'is_error' => 1,
  			'count' => 0,
  			'values' => array(),
  			'error_message' => 'Could not subscribe contact to newsletters in '.__METHOD__.', contact your system administrator. No valid groups given', 
			);
		} 
		
		$contact = new CRM_Apiprocessing_Contact();
		try {
			$newsletterContactId = $contact->processIncomingIndividual($apiParams);
		} catch (Exception $ex) {
			throw $ex;
			return array(
  			'is_error' => 1,
  			'count' => 0,
  			'values' => array(),
  			'error_message' => 'Could not subscribe contact to newsletters in '.__METHOD__.', contact your system administrator. Error from API process incoming contact: '.$ex->getMessage(), 
			);
		}
		
		if ($newsletterContactId) {
			$groupContactApiParams['group_id'] = $subscribeNewsletterIds;
			$groupContactApiParams['contact_id'] = $newsletterContactId;
			try {
				$result = civicrm_api3('GroupContact', 'create', $groupContactApiParams);
			} catch (CiviCRM_API3_Exception $ex) {
				return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not subscribe contact to newsletters in '.__METHOD__.', contact your system administrator. Error from API GroupContact create: '.$ex->getMessage(), 
				);
			}
		}
				
    return array(
    	'is_error' => 0,
    	'count' => count($subscribeNewsletterIds),
    	'values' => array(),
		);
  }

/**
 * Function to remove the group ids which don't exist or are not a child of the newsletter parent group.
 */
private function filterGroupIds($groupIds) {
	$return = array();
	foreach($groupIds as $groupId) {
		if (in_array($groupId, $this->_newsletterGroupIds)) {
			$return[] = $groupId;
		}
	}
	return $return;
}

}