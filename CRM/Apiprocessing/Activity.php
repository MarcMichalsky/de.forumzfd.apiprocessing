<?php

/**
 * Class for ForumZFD Activity API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Activity {

  /**
   * Method to create a new API Problem Error Activity
   *
   * @param $problemSource
   * @param $errorMessage
   * @param $params
   */
  public function createNewErrorActivity($problemSource = 'forumzfd', $errorMessage, $params) {
    // determine activity type id based on problem source
    $activityFunctionName = 'get'.$problemSource.'ActivityTypeId';
    $assigneeFunctionName = 'get'.$problemSource.'AssigneeId';
    $activityTypeId = CRM_Apiprocessing_Config::singleton()->$activityFunctionName();
    $assigneeId = CRM_Apiprocessing_Config::singleton()->$assigneeFunctionName();
    $sourceContactId = CRM_Core_Session::singleton()->get('userID');
    $activityParams = array(
      'activity_type_id' => $activityTypeId,
      'source_contact_id' => $sourceContactId,
      'assignee_id' =>  $assigneeId,
      'status_id' => CRM_Apiprocessing_Config::singleton()->getScheduledActivityStatusId(),
      'subject' => ts('Error message from API: '.$errorMessage),
      'details' => CRM_Apiprocessing_Utils::renderTemplate('activities/ApiProblem.tpl', $params),
    );
    civicrm_api3('Activity', 'create', $activityParams);
  }
}