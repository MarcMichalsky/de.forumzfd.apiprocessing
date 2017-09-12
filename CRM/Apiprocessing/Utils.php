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
  	if ($name == 'fzfd_petition_signed') {
  		return 'An Petition teilgenommen';
  	}
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

}