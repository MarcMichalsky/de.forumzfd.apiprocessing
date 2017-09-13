<?php

require_once 'apiprocessing.civix.php';

/**
 * Implementation of hook_civicrm_navigationMenu
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
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
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function apiprocessing_civicrm_xmlMenu(&$files) {
  _apiprocessing_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function apiprocessing_civicrm_postInstall() {
  _apiprocessing_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function apiprocessing_civicrm_uninstall() {
  _apiprocessing_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function apiprocessing_civicrm_disable() {
  _apiprocessing_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function apiprocessing_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _apiprocessing_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function apiprocessing_civicrm_managed(&$entities) {
  _apiprocessing_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function apiprocessing_civicrm_caseTypes(&$caseTypes) {
  _apiprocessing_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function apiprocessing_civicrm_angularModules(&$angularModules) {
  _apiprocessing_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function apiprocessing_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _apiprocessing_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Function to check if the required extensions are installed
 *
 * @throws Exception
 */
function _apiprocessing_required_extensions_installed() {
  $required = array(
    'org.project60.sepa' => FALSE,
  );
  $installedExtensions = civicrm_api3('Extension', 'get', array(
    'option' => array('limit' => 0,),));
  foreach ($installedExtensions['values'] as $installedExtension) {
    if (isset($required[$installedExtension['key']]) && $installedExtension['status'] == 'installed') {
      $required[$installedExtension['key']] = TRUE;
    }
  }
  foreach ($required as $requiredExtension => $installed) {
    if (!$installed) {
      throw new Exception('Required extension '.$requiredExtension.' is not installed, can not install or enable 
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
function apiprocessing_civicrm_preProcess($formName, &$form) {

} // */

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
