<?php

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Apiprocessing_Form_Settings extends CRM_Core_Form {

  private $_activityTypesList = array();
  private $_employeesList = array();
  private $_groupList = array();

  /**
   * Method to set the list of activity types
   */
  private function setActivityTypeList() {
    try {
      $activityTypes = civicrm_api3('OptionValue', 'get', array(
        'option_group_id' => 'activity_type',
        'is_active' => 1,
        'options' => array('limit' => 0)));
      foreach ($activityTypes['values'] as $activityType) {
        $this->_activityTypesList[$activityType['value']] = $activityType['label'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    asort($this->_activityTypesList);
  }

  /**
   * Method to set the list of ForumZfd employees
   */
  private function setEmployeeList() {
    // get default organization contact id
    try {
      $defaultOrgId = civicrm_api3('Domain', 'getvalue', array(
        'return' => 'contact_id',
      ));
      // now get all employees
      $config = CRM_Apiprocessing_Config::singleton();
      $relationships = civicrm_api3('Relationship', 'get', array(
        'contact_id_b' => $defaultOrgId,
        'relationship_type_id' => $config->getEmployeeRelationshipTypeId(),
        'is_active' => 1,
        'options' => array('limit' => 0),
      ));
      foreach ($relationships['values'] as $relationship) {
        $contactName = civicrm_api3('Contact', 'getvalue', array(
          'id' => $relationship['contact_id_a'],
          'return' => 'display_name',
        ));
        $this->_employeesList[$relationship['contact_id_a']] = $contactName;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    asort($this->_employeesList);
  }

  /**
   * Method to set the list of groups
   */
  private function setGroupList() {
    $groupList = array();
    try {
      $groups = civicrm_api3('Group', 'get', array(
        'is_active' => 1,
        'options' => array('limit' => 0),
      ));
      foreach ($groups['values'] as $group) {
        $groupList[$group['id']] = $group['title'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    asort($groupList);
    $this->_groupList = array(0 => '- select - ') + $groupList;
  }

  /**
   * Overridden parent method to initiate form
   *
   * @access public
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('Settings for ForumZFD API processing between website and CiviCRM'));
    $this->setActivityTypeList();
    $this->setEmployeeList();
    $this->setGroupList();
  }

  /**
   * Overridden parent method to build form
   */
  public function buildQuickForm() {
    $this->add('select', 'forumzfd_error_activity_type_id', ts('Activity Type for ForumZFD Errors'), $this->_activityTypesList, TRUE);
    $this->add('select', 'forumzfd_error_activity_assignee_id', ts('Assign ForumZFD Error Activities to'), $this->_employeesList, TRUE);
    $this->add('select', 'akademie_error_activity_type_id', ts('Activity Type for Akademie Errors'), $this->_activityTypesList, TRUE);
    $this->add('select', 'akademie_error_activity_assignee_id', ts('Assign Akademie Error Activities to'), $this->_employeesList, TRUE);
    $this->add('select', 'new_contacts_group_id', ts('Add New Contacts to Group'), $this->_groupList, FALSE);

    // add buttons
    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => true,),
      array('type' => 'cancel', 'name' => ts('Cancel')),
    ));
    parent::buildQuickForm();
  }

  /**
   * Overridden parent method to process submitted form
   */
  public function postProcess() {
    $this->saveSettings($this->_submitValues);
    CRM_Core_Session::setStatus(ts('Settings for API Processing saved to JSON file settings.json in extension folder').' resources.',
      'API Processing Settings saved', 'success');
    parent::postProcess();
  }

  /**
   * Method to save json file
   *
   * @param $formValues
   * @throws Exception when file can not be opened for write
   */
  private function saveSettings($formValues) {
    if (!empty($formValues)) {
      $data = array(
        'forumzfd_error_activity_type_id' => $formValues['forumzfd_error_activity_type_id'],
        'forumzfd_error_activity_assignee_id' => $formValues['forumzfd_error_activity_assignee_id'],
        'akademie_error_activity_type_id' => $formValues['akademie_error_activity_type_id'],
        'akademie_error_activity_assignee_id' => $formValues['akademie_error_activity_assignee_id'],
      );
      if (!empty($formValues['new_contacts_group_id'])) {
        $data['new_contacts_group_id'] = $formValues['new_contacts_group_id'];
      } else {
        $data['new_contacts_group_id'] = "";
      }
      $container = CRM_Extension_System::singleton()->getFullContainer();
      $fileName = $container->getPath('de.forumzfd.apiprocessing').'/resources/settings.json';
      try {
        $fh = fopen($fileName, 'w');
        fwrite($fh, json_encode($data, JSON_PRETTY_PRINT));
        fclose($fh);
      } catch (Exception $ex) {
        throw new Exception('Could not open '.$fileName.', contact your system administrator. Error reported: '
          . $ex->getMessage());
      }
    }

  }

  /**
   * Overridden parent method to set default values
   *
   * @return array $defaults
   * @access public
   */
  public function setDefaultValues() {
    $defaults = array();
    $settings = new CRM_Apiprocessing_Settings();
    $apiSettings = $settings->get();
    foreach ($apiSettings as $key => $value) {
      $defaults[$key] = $value;
    }
    return $defaults;
  }
}
