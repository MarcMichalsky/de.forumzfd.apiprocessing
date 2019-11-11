<?php
use CRM_Apiprocessing_ExtensionUtil as E;
/**
 * FzfdFix.Partcolumns API - api to change wishes and experience on participant data custom group
 *                             from String to Memo
 *
 * @author Erik Hommel (erik/hommel@civicoop.org)
 * @date 11 Nov 2019
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_fix_Partcolumns($params) {
  $fixFieldNames = ['fzfd_wishes_new', 'fzfd_experience_new'];
  foreach ($fixFieldNames as $fixFieldName) {
    // first check if field needs changing
    if (doesFieldNeedFixing($fixFieldName)) {
      // find potential index and remove
      $indexQuery = "SHOW INDEXES FROM civicrm_value_fzfd_participant_data_new WHERE Column_name = %1";
      $indexDao = CRM_Core_DAO::executeQuery($indexQuery, [1 => [$fixFieldName, 'String']]);
      if ($indexDao->fetch()) {
        $dropQuery = "ALTER TABLE civicrm_value_fzfd_participant_data_new DROP INDEX INDEX_" . $fixFieldName;
        CRM_Core_DAO::executeQuery($dropQuery);
      }
    }
    // change custom field type and make not searchable
    $updateCustomField = "UPDATE civicrm_custom_field SET data_type = %1, html_type = %2, is_searchable = %3 WHERE name = %4";
    CRM_Core_DAO::executeQuery($updateCustomField, [
      1 => ["Memo", "String"],
      2 => ["TextArea", "String"],
      3 => [0, "Integer"],
      4 => [$fixFieldName, "String"],
    ]);
    // change type in table
    $updateColumn = "ALTER TABLE civicrm_value_fzfd_participant_data_new MODIFY COLUMN " . $fixFieldName . " TEXT NULL";
    CRM_Core_DAO::executeQuery($updateColumn);
  }
  return civicrm_api3_create_success([], $params, 'FzfdFix', 'FixPart');
}

/**
 * Function to check if the field needs changing
 *
 * @param $fixFieldName
 * @return bool
 */
function doesFieldNeedFixing($fixFieldName) {
  // only fix if field is currently varchar
  $query = "SHOW COLUMNS FROM civicrm_value_fzfd_participant_data_new WHERE Field = %1";
  $dao = CRM_Core_DAO::executeQuery($query, [1 => [$fixFieldName, 'String']]);
  if ($dao->fetch()) {
    if (strtolower($dao->Type != 'text')) {
      return TRUE;
    }
  }
  return FALSE;
}
