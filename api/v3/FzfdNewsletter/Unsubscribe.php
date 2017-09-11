<?php

/**
 * FzfdNewsletter.Unsubscribe API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_newsletter_Unsubscribe_spec(&$spec) {
  $spec['email'] = array(
    'name' => 'email',
    'title' => 'email',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
	$spec['contact_hash'] = array(
    'name' => 'contact_hash',
    'title' => 'contact_hash',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  );
  $spec['newsletter_ids'] = array(
    'name' => 'newsletter_ids',
    'title' => 'newsletter_ids',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
}

/**
 * FzfdNewsletter.Unsubscribe API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_newsletter_Unsubscribe($params) {
	$groupContact = new CRM_Apiprocessing_GroupContact();
	$returnValues = $groupContact->processApiUnsubscribe($params);
  if ($returnValues['is_error'] == 0) {
    return $returnValues;
  } else {
    return civicrm_api3_create_error($returnValues['error_message'], $params);
  }
}
