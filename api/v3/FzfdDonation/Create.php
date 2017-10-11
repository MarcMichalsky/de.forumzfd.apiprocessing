<?php

/**
 * FzfdDonation.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_donation_Create_spec(&$spec) {
  $spec['email'] = array(
    'name' => 'email',
    'title' => 'email',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
  $spec['payment_instrument_id'] = array(
    'name' => 'payment_instrument_id',
    'title' => 'payment_instrument_id',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['amount'] = array(
    'name' => 'amount',
    'title' => 'amount',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_MONEY,
  );
  $spec['donation_date'] = array(
    'name' => 'donation_date',
    'title' => 'donation_date',
    'type' => CRM_Utils_Type::T_DATE,
  );
  $spec['campaign_id'] = array(
    'name' => 'campaign_id',
    'title' => 'campaign_id',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['source'] = array(
    'name' => 'source',
    'title' => 'source',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['first_name'] = array(
    'name' => 'first_name',
    'title' => 'first_name',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['last_name'] = array(
    'name' => 'last_name',
    'title' => 'last_name',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['prefix_id'] = array(
    'name' => 'prefix_id',
    'title' => 'prefix_id',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['formal_title'] = array(
    'name' => 'formal_title',
    'title' => 'formal_title',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['phone'] = array(
    'name' => 'phone',
    'title' => 'phone',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['iban'] = array(
    'name' => 'iban',
    'title' => 'iban',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['bic'] = array(
    'name' => 'bic',
    'title' => 'bic',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['start_date'] = array(
    'name' => 'start_date',
    'title' => 'start_date',
    'type' => CRM_Utils_Type::T_DATE,
  );
  $spec['frequency_unit'] = array(
    'name' => 'frequency_unit',
    'title' => 'frequency_unit',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['frequency_interval'] = array(
    'name' => 'frequency_interval',
    'title' => 'frequency_interval',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['cycle_day'] = array(
    'name' => 'cycle_day',
    'title' => 'cycle_day',
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['organization_name'] = array(
    'name' => 'organization_name',
    'title' => 'organization_name',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['organization_street_address'] = array(
    'name' => 'organization_street_address',
    'title' => 'organization_street_address',
    'type' => CRM_Utils_Type::T_STRING,
  );
	$spec['organization_supplemental_address_1'] = array(
    'name' => 'organization_supplemental_address_1',
    'title' => 'organization_supplemental_address_1',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
  $spec['organization_postal_code'] = array(
    'name' => 'organization_postal_code',
    'title' => 'organization_postal_code',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['organization_city'] = array(
    'name' => 'organization_city',
    'title' => 'organization_city',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['organization_country_iso'] = array(
    'name' => 'organization_country_iso',
    'title' => 'organization_country_iso',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['individual_addresses'] = array(
    'name' => 'individual_addresses',
    'title' => 'individual_addresses',
  );

}

/**
 * FzfdDonation.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_donation_Create($params) {
  $contribution = new CRM_Apiprocessing_Contribution();
  $contribution->processIncomingData($params);
  $returnValues = array(
    'is_error' => '0',
    'version' => '3',
    'count' => 1,
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
