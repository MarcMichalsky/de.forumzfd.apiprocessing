<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdDonation.Gettemp API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_donation_Gettemp_spec(&$spec) {
  $spec['temp_id'] = array(
    'name' => 'temp_id',
    'title' => 'Temporary ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  );
}

/**
 * FzfdDonation.Gettemp API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_donation_Gettemp($params) {
  $contribution = new CRM_Apiprocessing_Contribution();
  return civicrm_api3_create_success($contribution->getTempData($params['temp_id']), $params, 'FzfdDonation', 'gettemp');
}
