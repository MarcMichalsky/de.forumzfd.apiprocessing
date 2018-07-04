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
 * @param $params
 * @return array
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_fzfd_event_Get($params) {
  $event = new CRM_Apiprocessing_Event();
	return civicrm_api3_create_success($event->get(), $params, 'FzfdEvent', 'get');
}
