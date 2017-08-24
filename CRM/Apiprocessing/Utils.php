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
   * uses SMARTY to render a template
   *
   * @return string
   */
  public static function renderTemplate($template_path, $vars) {
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

    // create result
    $result =  $smarty->fetch($template_path);

    // reset smarty variables
    foreach ($backupFrame as $key => $value) {
      $key = str_replace(' ', '_', $key);
      $smarty->assign($key, $value);
    }

    return $result;
  }

}