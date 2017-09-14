<?php

/**
 * Class for ForumZFD Activity API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Activity {

  /**
   * Method to create a new API Problem Error Activity
   *
   * @param $problemSource
   * @param $errorMessage
   * @param $params
   */
  public function createNewErrorActivity($problemSource = 'forumzfd', $errorMessage, $params, $contactId = false) {
    // determine activity type id based on problem source
    $apiSettings = CRM_Apiprocessing_Settings::singleton();
    $activitySettingsKey = $problemSource.'_error_activity_type_id';
    $assigneeSettingsKey = $problemSource.'_error_activity_assignee_id';
    $activityTypeId = $apiSettings->get($activitySettingsKey);
    $assigneeId = $apiSettings->get($assigneeSettingsKey);
    $sourceContactId = CRM_Core_Session::singleton()->get('userID');
    $activityParams = array(
      'activity_type_id' => $activityTypeId,
      'source_contact_id' => $sourceContactId,
      'assignee_id' =>  $assigneeId,
      'status_id' => CRM_Apiprocessing_Config::singleton()->getScheduledActivityStatusId(),
      'subject' => ts('Error message from API: '.$errorMessage),
      'details' => CRM_Apiprocessing_Utils::renderTemplate('activities/ApiProblem.tpl', $params),
    );
		if ($contactId) {
			$activityParams['target_contact_id'] = $contactId;
		}
    civicrm_api3('Activity', 'create', $activityParams);
  }
	
	/**
	 * Process the api FzfdPetition.sign. Creates a contact and an activity for signing the petition.
	 */
	public function processApiPetitionSign($apiParams) {
		try {
			$contact = new CRM_Apiprocessing_Contact();
			$signerId = $contact->processIncomingIndividual($apiParams);
			if (isset($apiParams['organization_name']) && !empty($apiParams['organization_name'])) {
        $organizationId = $this->processOrganization($apiParams, $signerId);
				if ($organizationId) {
					$signerId = $organizationId;
				}
      }
			if (empty($signerId)) {
				throw new Exception('Could not find or create a contact for signing a petition');
			}
			
			$apiSettings = CRM_Apiprocessing_Settings::singleton();
			$activityTypeId = $apiSettings->get('fzfd_petition_signed_activity_type_id');
			$activityParams = array(
      	'activity_type_id' => $activityTypeId,
      	'source_contact_id' => $signerId,
      	'target_contact_id' => $signerId,
      	'status_id' => CRM_Apiprocessing_Config::singleton()->getCompletedActivityStatusId(),
    	);
			if (isset($apiParams['source'])) {
				$activityParams['subject'] = $apiParams['source'];
			}
			if (isset($apiParams['campaign_id'])) {
				$activityParams['campaign_id'] = $apiParams['campaign_id'];
			}
			$activity = civicrm_api3('Activity', 'create', $activityParams);
			
			return array(
				'is_error' => 0,
				'count' => 1,
				'values' => array(
					array(
						'contact_id' => $signerId,
						'activity_id' => $activity['id'],
					)
				)
			);
			
		} catch (Exception $ex) {
			return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not sign a petition in '.__METHOD__.', contact your system administrator. Error: '.$ex->getMessage(), 
				);
		}
	}

	/**
   * Method to create or find organization
   *
   * @param $params
   * @param $individualId
   * @return bool|int
   */
  public function processOrganization($params, $individualId) {
    // return FALSE if no organization name in params
    if (!isset($params['organization_name']) || empty($params['organization_name'])) {
      return FALSE;
    }
    $organizationParams = array(
      'organization_name' => $params['organization_name'],
      'contact_type' => 'Organization',
    );
    $possibles = array(
      'organization_street_address',
      'organization_postal_code',
      'organization_city',
      'organization_country_iso',
    );
    foreach ($possibles as $possible) {
      if (isset($params[$possible]) && !empty($params[$possible])) {
        $organizationParams[$possible] = $params[$possible];
      }
    }
    $organization = new CRM_Apiprocessing_Contact();
    $organizationId = $organization->processIncomingOrganization($organizationParams);
    // now process relationship between organization and individual
    $relationship = new CRM_Apiprocessing_Relationship();
    $relationship->processEmployerRelationship($organizationId, $individualId);
    return $organizationId;
  }
}