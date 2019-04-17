<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdMandate.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_mandate_create_spec(&$spec) {
  $spec['temp_id'] = [
    'name' => 'temp_id',
    'title' => 'temp_id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * FzfdMandate.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_mandate_create($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdMandate::create($params), $params, "FzfdMandate", "create");
}

/**
 * FzfdMandate.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_mandate_get($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdMandate::getValues($params), $params, "FzfdMandate", "get");
}
