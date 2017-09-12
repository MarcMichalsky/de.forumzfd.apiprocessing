<?php

class CRM_Apiprocessing_Participant {
	
	public function processRegistration($apiParams) {
		try {
			$config = CRM_Apiprocessing_Config::singleton();
			$contact = new CRM_Apiprocessing_Contact();
			$contactId = $contact->processIncomingIndividual($apiParams);
			if (isset($apiParams['organization_name']) && !empty($apiParams['organization_name'])) {
        $organizationId = $this->processOrganization($apiParams, $contactId);
      }
			if (empty($contactId)) {
				throw new Exception('Could not find or create a contact for registering for an event');
			}
			
			$this->processCustomFields($apiParams, $contactId);
			$this->processNewsletterSubscribtions($apiParams, $contactId);
			
			// Try to find an existing registration. If so create an error activity and do not create an extra participant record.
			try {
				$existingParticipantParams = array(
					'event_id' => $apiParams['event_id'],
					'contact_id' => $contactId,
					'status_id' => $config->getRegisteredParticipantStatus(),
				);
				$existingParticipant = civicrm_api3('Participant', 'getsingle', $existingParticipantParams);
				$activity = new CRM_Apiprocessing_Activity();
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
				return array(
					'is_error' => 0,
					'count' => 1,
					'values' => array(
						array(
							'event_id' => $apiParams['event_id'],
							'contact_id' => $contactId,
							'participant_id' => $existingParticipant['participant_id'],
						),
					)
				);
			} catch (exception $e) {
				// Do nothing. We did not find an existing registration.
			}
			
			$participantParams = array(
				'event_id' => $apiParams['event_id'],
				'contact_id' => $contactId,
				'status_id' => $config->getRegisteredParticipantStatus(),
			);
			
			// Try to find a cancelled registration
			try {
				$canclledParticipantParams = array(
					'event_id' => $apiParams['event_id'],
					'contact_id' => $contactId,
					'status_id' => $config->getCancelledParticipantStatus(),
				);
				$cancelledParticipant = civicrm_api3('Participant', 'getsingle', $canclledParticipantParams);
				$participantParams['id'] = $cancelledParticipant['participant_id'];
				$activity = new CRM_Apiprocessing_Activity();
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
			} catch (exception $e) {
				// Do nothing. We did not find an existing registration.
			}
			
			$result = civicrm_api3('Participant', 'create', $participantParams);
			return array(
				'is_error' => 0,
				'count' => 1,
				'values' => array(
					array(
						'event_id' => $apiParams['event_id'],
						'contact_id' => $contactId,
						'participant_id' => $result['id'],
					),
				)
			);
			
		} catch (Exception $ex) {
			return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not register for an event in '.__METHOD__.', contact your system administrator. Error: '.$ex->getMessage(), 
				);
		}
	}

	/**
	 * Process the custom fields wishes, experience, employer.
	 */
	public function processCustomFields($apiParams, $contactId) {
		$config = CRM_Apiprocessing_Config::singleton();
		$params = array();
		if (isset($apiParams['wishes'])) {
			$params['custom_'.$config->getWishesCustomFieldId()] = $apiParams['wishes'];
		}
		if (isset($apiParams['experience'])) {
			$params['custom_'.$config->getExperienceCustomFieldId()] = $apiParams['experience'];
		}
		if (isset($apiParams['employer'])) {
			$params['custom_'.$config->getEmployerCustomFieldId()] = $apiParams['employer'];
		}
		if (!empty($params)) {
			$params['id'] = $contactId;
			civicrm_api3('Contact', 'create', $params);
		}
	}

	public function processNewsletterSubscribtions($apiParams, $contactId) {
		$groupContact = new CRM_Apiprocessing_GroupContact();
		if (empty($apiParams['newsletter_ids'])) {
			return;
		}
		// put newsletter ids from string into array
    $subscribeNewsletterIds = CRM_Apiprocessing_Utils::storeNewsletterIds($apiParams['newsletter_ids']);
		// Make sure we only process the group ids which are a child of the newsletter parent group.
		// Ignore non existent group ids or group ids which are not part of the parent group.
		$subscribeNewsletterIds = $groupContact->filterGroupIds($subscribeNewsletterIds);
		if (empty($subscribeNewsletterIds)) {
			return;
		}
		
		$groupContactApiParams['group_id'] = $subscribeNewsletterIds;
		$groupContactApiParams['contact_id'] = $contactId;
		civicrm_api3('GroupContact', 'create', $groupContactApiParams);
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
