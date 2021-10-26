<?php

class CRM_Apiprocessing_Participant {

	/**
	 * Process a registration for an event.
	 */
	public function processRegistration($apiParams) {
		try {
			$config = CRM_Apiprocessing_Config::singleton();
			$contact = new CRM_Apiprocessing_Contact();
			$contactId = $contact->processIncomingIndividual($apiParams);
			if (isset($apiParams['organization_name']) && !empty($apiParams['organization_name'])) {
        $organization = new CRM_Apiprocessing_Contact();
        $organization->processOrganization($apiParams, $contactId);
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
      $activity = new CRM_Apiprocessing_Activity();
			$existingParticipant = $this->findCurrentRegistration($apiParams['event_id'], $contactId);
			if ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] == $config->getRegisteredParticipantStatusId()) {
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
				return $this->createApi3SuccessReturnArray($apiParams['event_id'], $contactId, $existingParticipant['participant_id']);
			} elseif ($existingParticipant && isset($existingParticipant['participant_status_id']) && $existingParticipant['participant_status_id'] == $config->getCancelledParticipantStatusId()) {
				$participantParams['id'] = $existingParticipant['participant_id'];
				$activity = new CRM_Apiprocessing_Activity();
				$activity->createNewErrorActivity('akademie', ts('Request to check the data'), $apiParams, $contactId);
			}
			// add custom fields if required
      if (isset($apiParams['how_did_you_hear_about_us'])) {
        $participantParams['custom_'.$config->getNewHowDidCustomFieldId()] = $apiParams['how_did_you_hear_about_us'];
      }
      if (isset($apiParams['wishes'])) {
        $participantParams['custom_'.$config->getWishesCustomFieldId()] = $apiParams['wishes'];
      }
      if (isset($apiParams['experience'])) {
        $participantParams['custom_'.$config->getExperienceCustomFieldId()] = $apiParams['experience'];
      }
      if (isset($apiParams['employer'])) {
        $participantParams['custom_'.$config->getEmployerCustomFieldId()] = $apiParams['employer'];
      }
      // always use role teilnehmer
      $participantParams['role_id'] = CRM_Apiprocessing_Config::singleton()->getAttendeeParticipantRoleId();
			$result = civicrm_api3('Participant', 'create', $participantParams);
      // if files for lebenslauf or bewerbungsschreiben added, upload attachments to CiviCRM
      if (isset($apiParams['bewerbungsschreiben']) || isset($apiParams['lebenslauf'])) {
        $this->addAttachment((int) $result['id'], $apiParams, $activity);
      }
      // process invoice @todo uncomment once billing extension is complete
      //$invoice = new CRM_Apiprocessing_Invoice();
      //$invoice->processParticipantInvoice((int) $result['id'], (int) $contactId, $activity);
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
   * Method to add attachments to participant
   *
   * @param int $participantId
   * @param array $apiParams
   * @param CRM_Apiprocessing_Activity $activity
   */
  private function addAttachment(int $participantId, array $apiParams, CRM_Apiprocessing_Activity $activity) {
    $possibleAttachments = ['bewerbungsschreiben', 'lebenslauf'];
    foreach ($possibleAttachments as $possibleAttachment) {
      if (isset($apiParams[$possibleAttachment]) && !empty($possibleAttachment)) {
        if (is_array($apiParams[$possibleAttachment])) {
          $columnMethod = "get" . ucfirst($possibleAttachment) . "CustomFieldId";
          $customField = "custom_" . CRM_Apiprocessing_Config::singleton()->$columnMethod();
          if ($customField) {
            $attachment = new CRM_Apiprocessing_Attachment($apiParams[$possibleAttachment]);
            $fileId = $attachment->addToCivi($participantId, $customField);
            if (!$fileId) {
              $activity->createNewErrorActivity('akademie', ts('Attachment for ') . $possibleAttachment . ts(' could not be loaded, check CiviCRM logs.'), $apiParams);
            }
          }
        }
        else {
          $activity->createNewErrorActivity('akademie', ts('Attachment for ') . $possibleAttachment . ts(' is not an array.'), $apiParams);
        }
      }
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
	  // retrieve eventType
    $query = "SELECT event_type_id FROM civicrm_event WHERE id = %1";
    $eventTypeId = CRM_Core_DAO::singleValueQuery($query, array(1 => array($eventId, 'Integer')));
	  // use status neu for weiterbildung
    if ($eventTypeId == $config->getWeiterBerufsEventTypeId() || $eventTypeId == $config->getWeiterVollzeitEventTypeId()) {
      return $config->getNeuParticipantStatusId();
    }
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
	  // set status based on event type
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

  /**
   * Method to process the subscriptions to newsletters
   *
   * @param $apiParams
   * @param $contactId
   * @throws CiviCRM_API3_Exception
   */
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

}
