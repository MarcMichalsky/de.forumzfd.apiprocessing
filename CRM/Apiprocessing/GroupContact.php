<?php

/**
 * Class for ForumZFD GroupContact API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_GroupContact {

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
      return civicrm_api3_create_error(
        'Could not unsubscribe contact to newsletters in '.__METHOD__.', contact your system administrator. No valid groups given.',
        $apiParams
      );
		}

    $contact = new CRM_Apiprocessing_Contact();
    try {
      $newsletterContactId = $contact->processIncomingIndividual($apiParams);
    } catch (CiviCRM_API3_Exception $ex) {
      return civicrm_api3_create_error(
        'Could not unsubscribe contact to newsletters in '.__METHOD__.', contact your system administrator. Error from API GroupContact create: '.$ex->getMessage(),
        $apiParams
      );
    }
    $groupContactApiParams['group_id'] = $subscribeNewsletterIds;
    $groupContactApiParams['contact_id'] = $newsletterContactId;
    $groupContactApiParams['status'] = 'Removed';
    try {
      $result = civicrm_api3('GroupContact', 'create', $groupContactApiParams);
    } catch (CiviCRM_API3_Exception $ex) {
      return civicrm_api3_create_error(
        'Could not unsubscribe contact to newsletters in ' . __METHOD__ . ', contact your system administrator. Error from API GroupContact create: ' . $ex->getMessage(),
        $groupContactApiParams
      );
    }

    return $result;
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
    try {
      $subscribeNewsletterIds = $this->filterGroupIds($subscribeNewsletterIds);
    } catch (CiviCRM_API3_Exception $ex) {
      return array(
        'is_error' => 1,
        'count' => 0,
        'values' => array(),
        'error_message' => 'Could not subscribe contact to newsletters in '.__METHOD__.', contact your system administrator. ' . $ex->getMessage(),
      );
    }
		if (empty($subscribeNewsletterIds)) {
			return array(
  			'is_error' => 1,
  			'count' => 0,
  			'values' => array(),
  			'error_message' => 'Could not subscribe contact to newsletters in '.__METHOD__.', contact your system administrator. No valid groups given',
			);
		}

		$contact = new CRM_Apiprocessing_Contact();
    $newsletterContactId = $contact->processIncomingIndividual($apiParams);
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
   * @throws CiviCRM_API3_Exception
	 */
  public function filterGroupIds($groupIds) {
    $return = [];
    foreach ($groupIds as $groupId) {
      $children = [$groupId];
        while (TRUE) {
          $parents = $this->getParentGroups($children);
          if (in_array($this->_newsletterParentGroupId, $parents)) {
            $return[] = $groupId;
            break;
          }
          elseif (empty($parents)) {
            break;
          } else {
            $children = $parents;
          }
        }
    }
    return $return;
  }

  /**
   * This function returns a single array with all parent groups of the
   * given group IDs.
   * @param $childGroupIds
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getParentGroups($childGroupIds): array {
    $parentGroups = [];
    foreach ($childGroupIds as $groupId) {
      $group = civicrm_api3('Group', 'getsingle', [
        'id'        => $groupId,
        'is_active' => 1
      ]);
      if (isset($group['parents']) && !empty($group['parents'])) {
        $parentGroups = array_merge(
          $parentGroups,
          explode(",", $group['parents'])
        );
      }
    }
    return $parentGroups;
  }

}
