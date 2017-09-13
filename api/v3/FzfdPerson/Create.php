<?php

/**
 * FzfdPerson.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_person_Create_spec(&$spec) {
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
	
	$spec['additional_information'] = array(
    'name' => 'additional_information',
    'title' => 'additional_information',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	
	$spec['department'] = array(
    'name' => 'department',
    'title' => 'department',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
	);
	
	$spec['job_title'] = array(
    'name' => 'job_title',
    'title' => 'job_title',
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
}

/**
 * FzfdPerson.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_person_create($params) {
	$contact = new CRM_Apiprocessing_Contact();
	$contactId = $contact->processIncomingIndividual($params);
  if ($contactId) {
  	$returnValues = array(
			array(
				'contact_id' => $contactId,
			),
		);
    return civicrm_api3_create_success($returnValues);
  } else {
    return civicrm_api3_create_error($returnValues['error_message'], $params);
  }
}
