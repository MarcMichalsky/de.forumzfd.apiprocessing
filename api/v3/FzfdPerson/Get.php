<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdPerson.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_person_Get_spec(&$spec) {
  $spec['group_titles'] = array(
    'name' => 'group_titles',
    'title' => 'group_titles',
    'api.required' => 1,
  );
}

/**
 * FzfdPerson.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_person_Get($params) {
  if (!is_array($params['group_titles'])) {
    $params['group_titles'] = array($params['group_titles']);
  }
  $contact = new CRM_Apiprocessing_Contact();
  $result = $contact->getFzfdPerson($params);
  if ($result) {
    return civicrm_api3_create_success($result, $params, 'FzfdPerson', 'Get');
  } else {
    return civicrm_api3_create_error($result['error_message'], $params);
  }
}
