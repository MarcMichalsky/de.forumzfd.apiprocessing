<?php

require_once 'apiprocessing.civix.php';

/**
 * Implementation of hook_civicrm_navigationMenu
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_navigationMenu/
 */
function apiprocessing_civicrm_navigationMenu(&$menu) {
  _apiprocessing_insert_navigation_menu($menu, "Administer", array(
    'label' => ts('Settings for ForumZFD API Processing'),
    'name' => 'fzfd_apiprocessing_setttings',
    'url' => 'civicrm/forumzfd/apiprocessing/form/settings',
    'permission' => 'administer CiviCRM',
    'operator' => 'AND',
    'separator' => 0,
  ));
  _apiprocessing_civix_navigationMenu($menu);
}

/**
 * Function to insert navigation menu
 *
 * @param $menu
 * @param $path
 * @param $item
 * @return bool
 */
function _apiprocessing_insert_navigation_menu(&$menu, $path, $item) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    $menu[] = array(
      'attributes' => array_merge(array(
        'label'      => CRM_Utils_Array::value('name', $item),
        'active'     => 1,
      ), $item),
    );
    return TRUE;
  }
  else {
    // Find an recurse into the next level down
    $found = FALSE;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!$entry['child']) {
          $entry['child'] = array();
        }
        $newPath = implode('/', $path);
        $found = _apiprocessing_insert_navigation_menu($entry['child'], $newPath, $item);
      }
    }
    return $found;
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function apiprocessing_civicrm_config(&$config) {
  _apiprocessing_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function apiprocessing_civicrm_install() {
  _apiprocessing_required_extensions_installed();
  _apiprocessing_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function apiprocessing_civicrm_enable() {
  _apiprocessing_required_extensions_installed();
  _apiprocessing_civix_civicrm_enable();
}

/**
 * Function to check if the required extensions are installed
 *
 * @throws Exception
 */
function _apiprocessing_required_extensions_installed() {
  $required = array(
    'org.project60.sepa' => FALSE,
    'org.civicoop.groupprotect' => FALSE,
  );
  $installedExtensions = civicrm_api3('Extension', 'get', array(
    'option' => array('limit' => 0,),));
  foreach ($installedExtensions['values'] as $installedExtension) {
    if (isset($required[$installedExtension['key']]) && $installedExtension['status'] =! 'installed') {
      throw new Exception('Required extension ' . $installedExtension['key'] . ' is not installed, can not install or enable 
      de.forumzfd.apiprocessing. Please install the extension and then retry installing or enabling 
      de.forumzfd.apiprocessing');
    }
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function apiprocessing_civicrm_navigationMenu(&$menu) {
  _apiprocessing_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'de.forumzfd.apiprocessing')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _apiprocessing_civix_navigationMenu($menu);
} // */
