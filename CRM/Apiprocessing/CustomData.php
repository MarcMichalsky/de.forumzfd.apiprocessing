<?php

/**
 * Class with generic function to add custom data
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 22 Feb 2019
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_CustomData {
  /**
   * Method to create the privacy options custom group
   *
   * @return array|bool
   */
  public static function createPrivacyCustomGroup() {
    try {
      $group = civicrm_api3('CustomGroup', 'create', [
        'name' => 'fzfd_privacy_options',
        'title' => ts('Privacy options for website'),
        'table_name' => 'civicrm_value_contact_privacy_options',
        'extends' => 'Contact',
        'style' => 'Inline',
        'collapse_display' => 0,
        'is_active' => 1,
        'is_multiple' => 0,
        'sequential' => 1,
      ]);
      return $group['values'];
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Could not create custom group for privacy options in ') . __METHOD__
        . ts(', error from API CustomGroup create :') . $ex->getMessage());
      return FALSE;
    }
  }

  /**
   * Method to create the website data consent custom field
   *
   * @return array|bool
   */
  public static function createWebsiteDataConsentCustomField() {
    try {
      $field = civicrm_api3('CustomField', 'create', [
        'custom_group_id' => 'fzfd_privacy_options',
        'label' => ts('Can we send first name, last name and address to website'),
        'name' => 'fzfd_website_consent',
        'column_name' => 'fzfd_website_consent',
        'data_type' => 'Boolean',
        'html_type' => 'Radio',
        'is_active' => 1,
        'default_value' => 1,
        'sequential' => 1,
      ]);
      return $field['values'];
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Could not create custom field for website consent in group for privacy options in ') . __METHOD__
        . ts(', error from API CustomField create :') . $ex->getMessage());
      return FALSE;
    }
  }

  /**
   * Method to create the new wishes and experience custom fields (if they do not exist already)
   *
   * @return bool
   */
  public static function createExpAndWishesParticipantCustomFields() {
    $fieldNames = ['fzfd_wishes_new' => 'Wishes', 'fzfd_experience_new' => 'Experience'];
    $weight = 20;
    foreach ($fieldNames as $fieldName => $fieldLabel) {
      // first check if the field does not exist yet
      try {
        $count = civicrm_api3('CustomField', 'getcount', [
          'custom_group_id' => 'fzfd_participant_data_new',
          'name' => $fieldName,
        ]);
        if ($count == 0) {
          try {
            civicrm_api3('CustomField', 'create', [
              'custom_group_id' => 'fzfd_participant_data_new',
              'label' => ts($fieldLabel),
              'name' => $fieldName,
              'column_name' => $fieldName,
              'data_type' => 'String',
              'html_type' => 'Text',
              'is_active' => 1,
              'is_searchable' => 1,
              'weight' => $weight,
              'sequential' => 1,
            ]);
            $weight++;
          }
          catch (CiviCRM_API3_Exception $ex) {
            Civi::log()->error(ts('Could not create custom field') . $fieldLabel . ts('for participant custom group in ') . __METHOD__
              . ts(', error from API CustomField create :') . $ex->getMessage());
            return FALSE;
          }
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return TRUE;
  }

  /**
   * Method to migrate wishes and experience to the new custom group
   */
  public static function migrateOldExperienceAndWishes() {
    if (CRM_Core_DAO::checkTableExists('civicrm_value_fzfd_akademie_data')) {
      $dao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_value_fzfd_akademie_data");
      while ($dao->fetch()) {
        // check if there is already a record for the contact
        $query = "SELECT COUNT(*) FROM civicrm_value_fzfd_participant_data_new WHERE entity_id = %1";
        $count = CRM_Core_DAO::singleValueQuery($query, [1 => [$dao->entity_id, 'Integer']]);
        if ($count == 0) {
          // if not, insert if there is data
          if (!empty($dao->fzfd_wishes) || !empty($dao->fzfd_experience)) {
            $insert = "INSERT INTO civicrm_value_fzfd_participant_data_new (entity_id, fzfd_wishes_new, fzfd_experience_new) VALUES(%1, %2, %3)";
            CRM_Core_DAO::executeQuery($insert, [
              1 => [$dao->entity_id, 'Integer'],
              2 => [$dao->fzfd_wishes, 'String'],
              3 => [$dao->fzfd_experience, 'String'],
            ]);
          }
        }
        else {
          // if not, update if there is data
          if (!empty($dao->fzfd_wishes) || !empty($dao->fzfd_experience)) {
            $update = "UPDATE civicrm_value_fzfd_participant_data_new SET fzfd_wishes_new = %1, fzfd_experience_new = %2 WHERE entity_id = %3";
            CRM_Core_DAO::executeQuery($update, [
              1 => [$dao->fzfd_wishes, 'String'],
              2 => [$dao->fzfd_experience, 'String'],
              3 => [$dao->entity_id, 'Integer'],
            ]);
          }
        }
      }
    }
  }
}