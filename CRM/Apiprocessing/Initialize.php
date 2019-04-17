<?php

/**
 * Class with extension initialization
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 17 Apr 2019
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Initialize {

  /**
   * CRM_Apiprocessing_Initialize constructor.
   */
  public function __construct() {
    $this->createTemporaryTag();
    $this->createPayDirektInstrument();
  }

  /**
   * Method to create the pay direkt payment instrument if it does not exist
   */
  private function createPayDirektInstrument() {
    try {
      $count = civicrm_api3('OptionValue', 'getcount', [
        'option_group_id' => 'payment_instrument',
        'name' => 'fzfd_pay_direkt',
      ]);
      if ($count == 0) {
        try {
          civicrm_api3('OptionValue', 'create', [
            'option_group_id' => 'payment_instrument',
            'name' => 'fzfd_pay_direkt',
            'label' => 'PayDirekt',
            'is_active' => 1,
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(ts('Could not create a payment instrument for PayDirekt in ') . __METHOD__);
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(ts('Unexpected error with API OptionValue getcount in ') . __METHOD__ . ts(', error message from API: ') . $ex->getMessage());
    }
  }

  /**
   * Method to create temporary tag if it does not exist yet
   */
  private function createTemporaryTag() {
    $tagName = "Temporär Zahlung angekündigt";
    try {
      $count = civicrm_api3('Tag', 'getcount', [
        'name' => $tagName,
      ]);
      if ($count == 0) {
        try {
          civicrm_api3('Tag', 'create', [
            'name' => $tagName,
            'description' => 'Tag für Temporär Zahlung - nicht ändern bitte',
            'is_reserved' => 1,
            'is_selectable' => 1,
            'used_for' => 'civicrm_contact',
            'color' => '#29a7da',
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(ts('Could not create tag with name ') . $tagName . ts(' in ') . __METHOD__);
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(ts('Unexpected error with API Tag getcount in ') . __METHOD__ . ts(', error message from API: ') . $ex->getMessage());
    }
  }

}