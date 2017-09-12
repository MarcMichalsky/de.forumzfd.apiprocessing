<?php

/**
 * Class for ForumZFD Relationship API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 12 September 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Relationship {

  /**
   * Method to create an employer relationship between organization and individual if it does not exist yet
   *
   * @param $organizationId
   * @param $individualId
   */
  public function processEmployerRelationship($organizationId, $individualId) {
    // create if relationship does not already exists as an active one
    $employerRelationshipTypeId = CRM_Apiprocessing_Config::singleton()->getEmployeeRelationshipTypeId();
    if ($this->isActive($employerRelationshipTypeId, $individualId, $organizationId) == FALSE) {
      try {
        civicrm_api3('Relationship', 'create', array(
          'relationship_type_id' => $employerRelationshipTypeId,
          'is_active' => 1,
          'start_date' => date('d-m-Y'),
          'contact_id_a' => $individualId,
          'contact_id_b' => $organizationId,
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
        CRM_Core_Error::debug_log_message('Could not create an employee/employer relationship between individual '.
          $individualId.' and organization '.$organizationId.' in '.__METHOD__);
      }
    }
  }

  /**
   * Method to check if relationship is active
   *
   * @param $relationshipTypeId
   * @param $contactIdA
   * @param $contactIdB
   * @return bool
   */
  public function isActive($relationshipTypeId, $contactIdA, $contactIdB) {
    try {
      $count = civicrm_api3('Relationship', 'getcount', array(
        'relationship_type_id' => $relationshipTypeId,
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'is_active' => 1,
      ));
      if ($count > 0) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

}