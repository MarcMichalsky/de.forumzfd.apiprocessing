<?php

/**
 * FzfdAkademie.Apply API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_akademie_Apply_spec(&$spec) {
  $spec['event_id'] = array(
    'name' => 'event_id',
    'title' => 'event_id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
	);	
  $spec['prefix_id'] = array(
    'name' => 'prefix',
    'title' => 'prefix',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
	);
	$spec['formal_title'] = array(
    'name' => 'formal_title',
    'title' => 'formal_title',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['first_name'] = array(
    'name' => 'first_name',
    'title' => 'first_name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
  $spec['last_name'] = array(
    'name' => 'last_name',
    'title' => 'last_name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
  $spec['email'] = array(
    'name' => 'email',
    'title' => 'email',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
	
	$spec['individual_addresses'] = array(
    'name' => 'individual_addresses',
    'title' => 'individual_addresses',
    'type' => CRM_Utils_Type::T_ENUM,
    'api.required' => 0,
  );
	
	$spec['organization_name'] = array(
    'name' => 'organization_name',
    'title' => 'organization_name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['organization_street_address'] = array(
    'name' => 'organization_street_address',
    'title' => 'organization_street_address',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
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
    'api.required' => 0,
	);
	$spec['organization_city'] = array(
    'name' => 'organization_city',
    'title' => 'organization_city',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['organization_country_iso'] = array(
    'name' => 'organization_country_iso',
    'title' => 'organization_country_iso',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['phone'] = array(
    'name' => 'phone',
    'title' => 'phone',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['phone_type_id'] = array(
    'name' => 'phone_type_id',
    'title' => 'phone_type_id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
	);
	$spec['experience'] = array(
    'name' => 'experience',
    'title' => 'experience',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['employer'] = array(
    'name' => 'employer',
    'title' => 'employer',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['wishes'] = array(
    'name' => 'wishes',
    'title' => 'wishes',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	$spec['newsletter_ids'] = array(
    'name' => 'newsletter_ids',
    'title' => 'newsletter_ids',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  );
}

/**
 * FzfdAkademie.Apply API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_akademie_Apply($params) {
  $participant = new CRM_Apiprocessing_Participant();
	$returnValues = $participant->processApply($params);
  if ($returnValues['is_error'] == 0) {
    return $returnValues;
  } else {
    return civicrm_api3_create_error($returnValues['error_message'], $params);
  }
}
