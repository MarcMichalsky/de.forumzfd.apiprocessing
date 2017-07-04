<?php

/**
 * FzfdNewsletter.Subscribe API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_fzfd_newsletter_Subscribe_spec(&$spec) {
  $spec['expense_date'] = array(
    'name' => 'expense_date',
    'title' => 'expense_date',
    'type' => CRM_Utils_Type::T_DATE,
    'api.required' => 1,
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
  $spec['newsletter_id'] = array(
    'name' => 'newsletter_id',
    'title' => 'newsletter_id',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  );
}

/**
 * FzfdNewsletter.Subscribe API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_newsletter_Subscribe($params) {
  $groupContact = new CRM_Apiprocessing_GroupContact();
  $returnValues = $groupContact->processApiSubscribe($params);
  if ($returnValues['is_error'] == 0) {
    return civicrm_api3_create_success($returnValues, $params, 'FzfdNewsletter', 'subscribe');
  } else {
    return civicrm_api3_create_error($returnValues['error_message'], $params);
  }
}
