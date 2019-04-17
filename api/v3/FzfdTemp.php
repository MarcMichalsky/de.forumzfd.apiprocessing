<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdTemp.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_temp_create_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'contact_id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['date_created'] = [
    'name' => 'date_created',
    'title' => 'contact_id',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
}
/**
 * FzfdTemp.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_temp_delete_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * FzfdTemp.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_temp_create($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdTemp::create($params), $params, "FzfdTemp", "create");
}

/**
 * FzfdTemp.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_temp_delete($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdTemp::deleteWithId($params['id']), $params, "FzfdTemp", "delete");
}

/**
 * FzfdTemp.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_temp_get($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdTemp::getValues($params), $params, "FzfdTemp", "get");
}
