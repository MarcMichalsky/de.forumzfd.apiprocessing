<?php

/**
 * Class with generic extension helper methods
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Utils {

  /**
   * Method to store newsletter ids from string in array
   *
   * @param string $newsletterIdsString
   * @return array
   */
  public static function storeNewsletterIds($newsletterIdsString) {
    if (is_array($newsletterIdsString)) {
      return $newsletterIdsString;
    }
    $newsletterIds = array();
    $ids = explode(";", $newsletterIdsString);
    foreach ($ids as $key => $value) {
      $newsletterIds[] = trim($value);
    }
    return $newsletterIds;
  }

  /**
   * Method to create label based on name
   *
   * @param $name
   * @return string
   */
  public static function createLabelFromName($name) {
    $parts = explode('_', $name);
    if (isset($parts[1])) {
      foreach ($parts as $key => $value) {
        $parts[$key] = ucfirst(strtolower($value));
      }
      return implode(' ', $parts);
    } else {
      return ucfirst(strtolower($name));
    }
  }

  /**
   * Method uses SMARTY to render a template
   *
   * @param $templatePath
   * @param $vars
   * @return string
   */
  public static function renderTemplate($templatePath, $vars) {
    $smarty = CRM_Core_Smarty::singleton();

    // first backup original variables, since smarty instance is a singleton
    $oldVars = $smarty->get_template_vars();
    $backupFrame = array();
    foreach ($vars as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $backupFrame[$key] = isset($oldVars[$key]) ? $oldVars[$key] : NULL;
    }

    // then assign new variables
    foreach ($vars as $key => $value) {
      $key = str_replace(' ', '_', $key);

      $smarty->assign($key, $value);
    }
		$smarty->assign('data', $vars);
    // create result
    $result =  $smarty->fetch($templatePath);

    // reset smarty variables
    foreach ($backupFrame as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $smarty->assign($key, $value);
    }

    return $result;
  }

  /**
   * Method to get the country id with iso code. Return default country id of installation if not found or empty
   *
   * @param $isoCode
   * @return array
   */
  public static function getCountryIdWithIso($isoCode) {
    if (!empty($isoCode)) {
      try {
        return civicrm_api3('Country', 'getvalue', array(
          'iso_code' => $isoCode,
          'return' => 'id',
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return CRM_Apiprocessing_Config::singleton()->getDefaultCountryId();
  }

  /**
   * Method to check if campaign exists
   *
   * @param $campaignId
   * @return bool
   */
  public static function campaignExists($campaignId) {
    try {
      $count = civicrm_api3('Campaign', 'getcount' , array('id' => $campaignId,));
      if ($count > 0) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to count the number of registrations for an event
   *
   * @param $eventId
   * @return mixed
   */
  public static function getNumberOfEventRegistrations($eventId) {
    // retrieve setting that determines which participant status is considered as registered
    $registeredStatusIds = CRM_Apiprocessing_Settings::singleton()->get('fzfd_participant_status_id');
    if (!is_array($registeredStatusIds)) {
      Civi::log()->error(ts('Expected but did not recognize array of participant status ids to be counted as registered in ') . __METHOD__);
      return 0;
    }
    try {
      return civicrm_api3('Participant', 'getcount', [
        'event_id' => $eventId,
        'status_id' => array('IN' => $registeredStatusIds),
        'options' => array('limit' => 0),
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      return 0;
    }
  }

  /**
   * Method to get option group id with name
   *
   * @param $optionGroupName
   * @return array|bool
   */
  public static function getOptionGroupIdWithName($optionGroupName) {
    try {
      return civicrm_api3('OptionGroup', 'getvalue', [
        'return' => 'id',
        'name' => $optionGroupName,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }
}