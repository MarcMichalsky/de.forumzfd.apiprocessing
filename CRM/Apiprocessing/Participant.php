<?php

class CRM_Apiprocessing_Participant {
	
	/**
	 * Process a registration for an event.
	 */
	public function processRegistration($apiParams) {
		try {
			$config = CRM_Apiprocessing_Config::singleton();
			$activity = new CRM_Apiprocessing_Activity();
			
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
			
			$participantParams = array(
				'event_id' => $apiParams['event_id'],
				'contact_id' => $contactId,
				'status_id' => $this->generateParticipantStatus($apiParams['event_id']),
			);
			
			// Try to find an existing registration. If so create an error activity and do not create an extra participant record.
			$existingParticipant = $this->findCurrentRegistration($apiParams['event_id'], $contactId);
			if ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] == $config->getRegisteredParticipantStatusId()) {
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
				return $this->createApi3SuccessReturnArray($apiParams['event_id'], $contactId, $existingParticipant['participant_id']);
			} elseif ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] == $config->getCancelledParticipantStatusId()) {
				$participantParams['id'] = $existingParticipant['participant_id'];
				$activity = new CRM_Apiprocessing_Activity();
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
			}
			
			$result = civicrm_api3('Participant', 'create', $participantParams);
			return $this->createApi3SuccessReturnArray($apiParams['event_id'], $contactId, $result['id'], $participantParams['status_id']);
			
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
   * Method to determine if participant can be registered or should be waitlisted
   *
   * @param $eventId
   * @return mixed
   * @throws
   */
	private function generateParticipantStatus($eventId) {
	  $config = CRM_Apiprocessing_Config::singleton();
	  $statusId = NULL;
	  // retrieve event data to see if a waitlist is applicable and what the max participants is
    try {
      $event = civicrm_api3('Event', 'getsingle', array('id' => $eventId));
    } catch (CiviCRM_API3_Exception $ex) {
      throw new CiviCRM_API3_Exception('Could not find event with id '.$eventId, 0102);
    }
    $noRegistered = CRM_Apiprocessing_Utils::getNumberOfEventRegistrations($eventId);
    // if no waitlist return registered if no max or error if full
    if ($event['has_waitlist'] == 0) {
      if (!isset($event['max_participants'])) {
        return $config->getRegisteredParticipantStatusId();
      } else {
        if ($noRegistered >= $event['max_participants']) {
          throw new CiviCRM_API3_Exception('Could not register participant, event is full and has no waitlist.', 0101);
        } else {
          return $config->getRegisteredParticipantStatusId();
        }
      }
    } else {
      // if waitlist
      if ($noRegistered < $event['max_participants']) {
        return $config->getRegisteredParticipantStatusId();
      } else {
        return $config->getWaitlistedParticipantStatusId();
      }
    }
  }

	/** 
	 * Process an apply for an event. Apply means I want to come and is used to invest how many people are interested.
	 */
	public function processApply($apiParams) {
		try {
			$config = CRM_Apiprocessing_Config::singleton();
			$activity = new CRM_Apiprocessing_Activity();
			
			$contact = new CRM_Apiprocessing_Contact();
			$contactId = $contact->processIncomingIndividual($apiParams);
			if (isset($apiParams['organization_name']) && !empty($apiParams['organization_name'])) {
        $organizationId = $this->processOrganization($apiParams, $contactId);
      }
			if (empty($contactId)) {
				throw new Exception('Could not find or create a contact for applying for an event');
			}
			
			$this->processCustomFields($apiParams, $contactId);
			$this->processNewsletterSubscribtions($apiParams, $contactId);
			
			$participantParams = array(
				'event_id' => $apiParams['event_id'],
				'contact_id' => $contactId,
				'status_id' => $config->getNeuParticipantStatusId(),
			);
			
			// Try to find an existing registration. If so create an error activity and do not create an extra participant record.
			$existingParticipant = $this->findCurrentRegistration($apiParams['event_id'], $contactId);
			if ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] == $config->getNeuParticipantStatusId()) {
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
				return $this->createApi3SuccessReturnArray($apiParams['event_id'], $contactId, $existingParticipant['participant_id']);
			} elseif ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] == $config->getCancelledParticipantStatusId()) {
				$participantParams['id'] = $existingParticipant['participant_id'];
				$activity = new CRM_Apiprocessing_Activity();
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
			} elseif ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] != $config->getCancelledParticipantStatusId() && $existingParticipant['participant_status_id'] != $config->getNeuParticipantStatusId()) {
				$activity = new CRM_Apiprocessing_Activity();
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
			}
			
			$result = civicrm_api3('Participant', 'create', $participantParams);
			return $this->createApi3SuccessReturnArray($apiParams['event_id'], $contactId, $result['id'], $participantParams['status_id']);
			
		} catch (Exception $ex) {
			return array(
    			'is_error' => 1,
    			'count' => 0,
    			'values' => array(),
    			'error_message' => 'Could not apply for an event in '.__METHOD__.', contact your system administrator. Error: '.$ex->getMessage(), 
				);
		}
	}

	/**
	 * Find a current registration for an event. If no registration is present we return False.
	 */
	private function findCurrentRegistration($event_id, $contact_id) {
		$participantParams = array(
			'event_id' => $event_id,
			'contact_id' => $contact_id,
		);
		$result = civicrm_api3('Participant', 'get', $participantParams);
		if ($result['count'] > 0) {
			// In theory there could be more than one registration we return the first one.
			$participant = reset($result['values']);
			return $participant;
		} 
		return false;
	}

	/**
 	 * Returns an Api3 success array for registering or applying successfully. 
 	 */
	private function createApi3SuccessReturnArray($eventId, $contactId, $participantId, $participantStatusId = NULL) {
		return array(
				'is_error' => 0,
				'count' => 1,
				'values' => array(
					array(
						'event_id' => $eventId,
						'contact_id' => $contactId,
						'participant_id' => $participantId,
            'participant_status_id' => $participantStatusId,
					),
				)
			);
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
      'organization_supplemental_address_1',
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
