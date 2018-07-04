<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdEvent.Getsingle API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_event_Getsingle_spec(&$spec) {
  $spec['event_id'] = array(
    'name' => 'event_id',
    'title' => 'Event ID',
    'description' => 'Unique ID of the event in CiviCRM',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  );
}

/**
 * FzfdEvent.Getsingle API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_event_Getsingle($params) {
  if (empty($params['event_id'])) {
    return civicrm_api3_create_error(E::ts('Parameter eventID is mandatory and can not be empty'), $params);
  }
  $event = new CRM_Apiprocessing_Event();
  $result = $event->getSingle($params['event_id']);
  if (isset($result['error_message'])) {
    return civicrm_api3_create_error(E::ts($result['error_message']), $params);
  }
  else {
    return $result;
  }
}
