<?php
use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * FzfdDonation.Confirm API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_donation_Confirm_spec(&$spec) {
  $spec['temp_id'] = array(
    'name' => 'temp_id',
    'title' => 'Temporary ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  );
}

/**
 * FzfdDonation.Confirm API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_donation_Confirm($params) {
  $contribution = new CRM_Apiprocessing_Contribution();
  $tempId = $contribution->processConfirm($params['temp_id']);
  $returnValues = array(
    'is_error' => '0',
    'version' => '3',
    'count' => 1,
    'temp_id' => $tempId,
  );
  // return doi_id and doi_token if in params
  if (isset($params['doi_id'])) {
    $returnValues['doi_id'] = $params['doi_id'];
  }
  if (isset($params['doi_token'])) {
    $returnValues['doi_token'] = $params['doi_token'];
  }
  return $returnValues;
}
