<?php

/**
 * FzfdEvent.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_event_Get_spec(&$spec) {
  
}

/**
 * FzfdEvent.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_event_Get($params) {
	$config = CRM_Apiprocessing_Config::singleton();
	$eventParams['is_online_registration'] = '1';
	$eventParams['is_template'] = '0';
	$eventParams['options']['limit'] = 0;
	$events = civicrm_api3('Event', 'get', $eventParams);
	$returnValues = array();
	foreach($events['values'] as $event) {
		$returnValue = array(
			'event_id' => $event['id'],
			'event_title' => $event['title'],
			'trainer' => array(),
			'teilnahme_organisation_id' => null,
			'teilnahme_organisation_name' => null,		
			'ansprech_inhalt' => array(),
		);
		
		if (isset($event['custom_'.$config->getTrainerCustomFieldId()])) {
			$trainers = explode(';', $event['custom_'.$config->getTrainerCustomFieldId()]);
			foreach($trainers as $trainer_id) {
				$trainer_name = civicrm_api3('Contact', 'getvalue', array('return' => 'display_name', 'id' => $trainer_id));
				$returnValue['trainer'][] = array(
					'contact_id' => $trainer_id,
					'contact_name' => $trainer_name
				);
			}
		}
		
		if (isset($event['custom_'.$config->getTeilnahmeOrganisationCustomFieldId().'_id'])) {
			$returnValue['teilnahme_organisation_id'] = $event['custom_'.$config->getTeilnahmeOrganisationCustomFieldId().'_id'];
			$returnValue['teilnahme_organisation_name'] = $event['custom_'.$config->getTeilnahmeOrganisationCustomFieldId()];
		}
		
		if (isset($event['custom_'.$config->getAnsprechInhaltCustomFieldId()])) {
			$ansprechers = explode(';', $event['custom_'.$config->getAnsprechInhaltCustomFieldId()]);
			foreach($ansprechers as $ansprecher_id) {
				$ansprecher_name = civicrm_api3('Contact', 'getvalue', array('return' => 'display_name', 'id' => $ansprecher_id));
				$returnValue['ansprech_inhalt'][] = array(
					'ansprech_inhalt_id' => $ansprecher_id,
					'ansprech_inhalt_name' => $ansprecher_name
				);
			}
		}

		$returnValues[] = $returnValue;
	}
	return civicrm_api3_create_success($returnValues, $params, 'FzfdEvent', 'get');
}
