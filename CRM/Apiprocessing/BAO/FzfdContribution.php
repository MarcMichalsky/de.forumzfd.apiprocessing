<?php
use CRM_Apiprocessing_ExtensionUtil as E;

class CRM_Apiprocessing_BAO_FzfdContribution extends CRM_Apiprocessing_DAO_FzfdContribution {

  /**
   * Create a new FzfdContribution based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Apiprocessing_DAO_FzfdContribution|NULL
   */
  public static function create($params) {
    $className = 'CRM_Apiprocessing_DAO_FzfdContribution';
    $entityName = 'FzfdContribution';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Function to get values
   *
   * @return array $result found rows with data
   * @access public
   * @static
   */
  public static function getValues($params) {
    $className = 'CRM_Apiprocessing_DAO_FzfdContribution';
    $result = [];
    $instance = new $className();
    if (!empty($params)) {
      $fields = self::fields();
      foreach ($params as $key => $value) {
        if (isset($fields[$key])) {
          $instance->$key = $value;
        }
      }
    }
    $instance->find();
    while ($instance->fetch()) {
      $row = [];
      self::storeValues($instance, $row);
      $result[$row['id']] = $row;
    }
    return $result;
  }

}
