<?php

/**
 * FzfdSpendengruppe.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_spendengruppe_Get_spec(&$spec) {
  $spec['contact_hash'] = array(
    'name' => 'contact_hash',
    'title' => 'contact_hash',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
}

/**
 * FzfdSpendengruppe.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_spendengruppe_Get($params) {
  $contact = new CRM_Apiprocessing_Contact();
  $result[] = $contact->getSpendengruppe($params);
  return civicrm_api3_create_success($result, $params, 'FzfdSpendengruppe', 'Get');
}
