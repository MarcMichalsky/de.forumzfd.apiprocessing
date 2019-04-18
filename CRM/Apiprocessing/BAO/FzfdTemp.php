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

  /**
   * Cleanup temporary donation requests and potentially associated contacts
   *
   * @param $numberOfMonths
   * @throws Exception
   */
  public static function cleanUp($numberOfMonths) {
    // select all temp donation requests that are older than number of months
    $testDate = new DateTime();
    $testDate->modify('first day of this month');
    $modifyMonths = '- ' . $numberOfMonths . 'month';
    $testDate->modify($modifyMonths);
    $query = "SELECT * FROM civicrm_fzfd_temp WHERE date_created < %1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$testDate->format('Y-m-d'), 'String']]);
    while ($dao->fetch()) {
      // check if the contact of the temp donation still has the temp tag, and if so trash the contact
      self::trashContactIfTemp($dao->contact_id);
      // delete all references to the temp_id in temporary mandates and contributions
      $deleteParams = [1 => [$dao->id, 'Integer']];
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_fzfd_sdd_mandate WHERE temp_id = %1", $deleteParams);
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_fzfd_contribution WHERE temp_id = %1", $deleteParams);
      // delete temp donation request
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_fzfd_temp WHERE id = %1", $deleteParams);
    }
    // remove temp tag from all contacts that do not have a temp donation request
    $contact = new CRM_Apiprocessing_Contact();
    $contact->removeUnwantedTemporaryTags();
  }

  /**
   * Method to trash temporary contact if it still has the tag
   *
   * @param $contactId
   */
  private static function trashContactIfTemp($contactId) {
    try {
      $count = civicrm_api3('EntityTag', 'getcount', [
        'entity_table' => "civicrm_contact",
        'entity_id' => $contactId,
        'tag_id' => CRM_Apiprocessing_Config::singleton()->getTemporaryTagId(),
      ]);
      if ($count == 1) {
        $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactId]);
        if ($contact) {
          $contact['is_deleted'] = 1;
          civicrm_api3('Contact', 'create', $contact);
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }


}
