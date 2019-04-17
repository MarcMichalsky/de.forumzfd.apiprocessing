<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdContribution.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_contribution_create_spec(&$spec) {
  $spec['temp_id'] = [
    'name' => 'temp_id',
    'title' => 'temp_id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * FzfdContribution.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_contribution_create($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdContribution::create($params), $params, "FzfdContribution", "create");
}

/**
 * FzfdContribution.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_fzfd_contribution_get($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_BAO_FzfdContribution::getValues($params), $params, "FzfdContribution", "get");
}
