<?php
use CRM_Apiprocessing_ExtensionUtil as E;

class CRM_Apiprocessing_BAO_FzfdTemp extends CRM_Apiprocessing_DAO_FzfdTemp {

  /**
   * Create a new FzfdTemp based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Apiprocessing_DAO_FzfdTemp|NULL
   */
  public static function create($params) {
    $className = 'CRM_Apiprocessing_DAO_FzfdTemp';
    $entityName = 'FzfdTemp';
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
    $className = 'CRM_Apiprocessing_DAO_FzfdTemp';
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

  /**
   * Function to delete a record with id
   *
   * @param int $ruleId
   * @throws Exception when ruleId is empty
   * @access public
   * @static
   */
  public static function deleteWithId($id) {
    $className = 'CRM_Apiprocessing_DAO_FzfdTemp';
    if (empty($id)) {
      throw new Exception(ts('id can not be empty when attempting to delete'));
    }
    $instance = new $className();
    $instance->id = $id;
    $instance->delete();
    return;
  }


}
