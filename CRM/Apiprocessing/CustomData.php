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

}