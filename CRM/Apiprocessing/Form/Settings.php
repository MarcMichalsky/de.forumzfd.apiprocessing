<?php

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Apiprocessing_Form_Settings extends CRM_Core_Form {

	private $apiSettings;
  private $_activityTypesList = array();
  private $_employeesList = array();
  private $_groupList = array();
  private $_cycleDaysList = array();
  private $_locationTypeList = array();

  /**
   * Method to set the location type list
   */
  private function setLocationTypeList() {
    try {
      $locationTypes = civicrm_api3('LocationType', 'get', array(
        'is_active' => 1,
        ));
      foreach ($locationTypes['values'] as $locationType) {
        $this->_locationTypeList[$locationType['id']] = $locationType['display_name'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to set the cycle days list
   */
  private function setCycleDaysList() {
    try {
      $cycleDays = civicrm_api3('Setting', 'getvalue', array(
        'name' => 'cycledays',
        ));
      if (!empty($cycleDays)) {
        $values = explode(',', $cycleDays);
        foreach ($values as $value) {
          $this->_cycleDaysList[$value] = $value;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

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
    // get all organizations that might be ForumZFD (duplicates present in database....)
    try {
      $forumZfds = civicrm_api3('Contact', 'get', array(
        'contact_type' => 'Organization',
        'organization_name' => 'Forum Ziviler Friedensdienst e.V.',
        'city' => 'Köln',
        'email' => 'kontakt@forumzfd.de',
        'options' => array('limit' => 0,),
        'return' => array('id',),
      ));
      $config = CRM_Apiprocessing_Config::singleton();
      // foreach of those, find relationship employee
      foreach ($forumZfds['values'] as $forumZfd) {
        $relationships = civicrm_api3('Relationship', 'get', array(
          'contact_id_b' => $forumZfd['id'],
          'relationship_type_id' => $config->getEmployeeRelationshipTypeId(),
          'is_active' => 1,
          'options' => array('limit' => 0),
        ));
        foreach ($relationships['values'] as $relationship) {
          if (!isset($this->_employeesList[$relationship['contact_id_a']])) {
            $contactName = civicrm_api3('Contact', 'getvalue', array(
              'id' => $relationship['contact_id_a'],
              'return' => 'display_name',
            ));
            $this->_employeesList[$relationship['contact_id_a']] = $contactName;
          }
        }
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
  	$this->apiSettings = CRM_Apiprocessing_Settings::singleton();
    CRM_Utils_System::setTitle(ts('Settings for ForumZFD API processing between website and CiviCRM'));
    $this->setActivityTypeList();
    $this->setEmployeeList();
    $this->setGroupList();
    $this->setCycleDaysList();
    $this->setLocationTypeList();
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
		$this->add('select', 'fzfd_petition_signed_activity_type_id', ts('Activity Type for ForumZFD Petition Sign'), $this->_activityTypesList, TRUE);
		$this->add('select', 'default_cycle_day_sepa', ts('Default Cycle Day for SEPA Recurring Mandate'), $this->_cycleDaysList, TRUE);
		$this->add('text', 'fzfd_donation_level_one_min', ts('Mimimum amount donation level 1'), array(), true );
		$this->add('text', 'fzfd_donation_level_one_max', ts('Maximum amount donation level 1'), array(), true );
		$this->add('text', 'fzfd_donation_level_two_min', ts('Mimimum amount donation level 2'), array(), true );
		$this->add('text', 'fzfd_donation_level_two_max', ts('Maximum amount donation level 2'), array(), true );
		$this->add('text', 'fzfd_donation_level_three_min', ts('Mimimum amount donation level 3'), array(), true );
		$this->add('text', 'fzfd_donation_level_three_max', ts('Maximum amount donation level 3'), array(), true );
    $this->add('select', 'fzfdperson_groups', ts('Valid groups for API FzfdPerson Get'), $this->_groupList, TRUE,
      array('id' => 'fzfdperson_groups', 'multiple' => 'multiple', 'class' => 'crm-select2'));
    $this->add('select', 'fzfdperson_location_type', ts('Location Type for API FzfdPerson Get'), $this->_locationTypeList, TRUE);

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
        'fzfd_petition_signed_activity_type_id' => $formValues['fzfd_petition_signed_activity_type_id'],
        'fzfd_donation_level_one_min' => $formValues['fzfd_donation_level_one_min'],
        'fzfd_donation_level_one_max' => $formValues['fzfd_donation_level_one_max'],
        'fzfd_donation_level_two_min' => $formValues['fzfd_donation_level_two_min'],
        'fzfd_donation_level_two_max' => $formValues['fzfd_donation_level_two_max'],
        'fzfd_donation_level_three_min' => $formValues['fzfd_donation_level_three_min'],
        'fzfd_donation_level_three_max' => $formValues['fzfd_donation_level_three_max'],
        'fzfdperson_groups' => $formValues['fzfdperson_groups'],
        'fzfdperson_location_type' => $formValues['fzfdperson_location_type'],
      );
      if (!empty($formValues['default_cycle_day_sepa'])) {
        $data['default_cycle_day_sepa'] = $formValues['default_cycle_day_sepa'];
      } else {
        $data['default_cycle_day_sepa'] = "";
      }
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
    $apiSettings = $this->apiSettings->get();
    foreach ($apiSettings as $key => $value) {
      $defaults[$key] = $value;
    }
    return $defaults;
  }

  /**
   * Overridden parent method to add validation rules
   *
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Apiprocessing_Form_Settings', 'validateDonationLevels'));
  }

  /**
   * Method to validate number of participants
   *
   * @param $fields
   * @return bool|array
   */
  public static function validateDonationLevels($fields) {
    // validate all donation levels are numeric
    $numericFields = array(
      'fzfd_donation_level_one_min',
      'fzfd_donation_level_one_max',
      'fzfd_donation_level_two_min',
      'fzfd_donation_level_two_max',
      'fzfd_donation_level_three_min',
      'fzfd_donation_level_three_max',
    );
    foreach ($numericFields as $numericField) {
      if (!is_numeric($fields[$numericField])) {
        $errors[$numericField] = ts('Minimum or maximum level can only contain numbers!');
      }
    }
    if (!empty($errors)) {
      return $errors;
    }
    // validate for each level if min and max are correct
    if ($fields['fzfd_donation_level_one_min'] > $fields['fzfd_donation_level_one_max']) {
      $errors['fzfd_donation_level_one_min'] = ts('Minimum can not be bigger than maximum!');
    }
    if ($fields['fzfd_donation_level_two_min'] > $fields['fzfd_donation_level_two_max']) {
      $errors['fzfd_donation_level_two_min'] = ts('Minimum can not be bigger than maximum!');
    }
    if ($fields['fzfd_donation_level_three_min'] > $fields['fzfd_donation_level_three_max']) {
      $errors['fzfd_donation_level_three_min'] = ts('Minimum can not be bigger than maximum!');
    }
    // validate if levels do not overlap
    if ($fields['fzfd_donation_level_two_min'] <= $fields['fzfd_donation_level_one_max']) {
      $errors['fzfd_donation_level_two_min'] = ts('Minimum of level 2 overlaps maximum of level 1!');
    }
    if ($fields['fzfd_donation_level_two_min'] <= $fields['fzfd_donation_level_one_max']) {
      $errors['fzfd_donation_level_two_min'] = ts('Minimum of level 2 overlaps maximum of level 1!');
    }
    if (!empty($errors)) {
      return $errors;
    } else {
      return TRUE;
    }
  }

}
