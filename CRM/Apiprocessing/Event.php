<?php

/**
 * Class for ForumZFD Event API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Event {
  private $_defaultEventParams = array();

  /**
   * CRM_Apiprocessing_Event constructor.
   */
  public function __construct() {
    $this->_defaultEventParams = array(
      'is_online_registration' => 1,
      'is_template' => '0',
      'options' => array('limit' => 0),
    );
  }

  /**
   * Method to get all public events and add the required data
   *
   * @throws CiviCRM_API3_Exception
   */
  public function get() {
    $eventParams = $this->_defaultEventParams;
    $events = civicrm_api3('Event', 'get', $eventParams);
    $returnValues = array();
    foreach($events['values'] as $event) {
      $returnValue = $this->prepareFzfdEvent($event);
      $returnValues[] = $returnValue;
    }
    return $returnValues;
  }

  /**
   * Method to get a single event with event_id
   *
   * @param $eventId
   * @return array
   */
  public function getSingle($eventId) {
    try {
      $event = civicrm_api3('Event', 'getsingle', array('id' => $eventId));
      return $this->prepareFzfdEvent($event);

    }
    catch (CiviCRM_API3_Exception $ex) {
      return array(
        'error_message' => $ex->getMessage(),
      );
    }
  }

  /**
   * Method to set all data for a single Fzfd Event
   *
   * @param $event
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  private function prepareFzfdEvent($event) {
    $result = array(
      'event_id' => $event['id'],
      'event_type_id' => $event['event_type_id'],
      'event_type_name' => '',
      'event_title' => $event['title'],
      'registration_is_online' => $event['is_online_registration'],
      'maximum_participants' => $event['max_participants'],
      'registration_count' => 0,
      'start_date' => $event['start_date'],
      'end_date' => $event['end_date'],
      'registration_start_date' => $event['registration_start_date'],
      'registration_end_date' => $event['registration_end_date'],
      'trainer' => array(),
      'teilnahme_organisation_id' => '',
      'teilnahme_organisation_name' => '',
      'ansprech_inhalt' => array(),
      'ansprech_organisation' => array(),
      'bewerbung' => '',
    );
    $result['registration_count'] = CRM_Apiprocessing_Utils::getNumberOfEventRegistrations($event['id']);
    $this->addFzfdCustomFields($event, $result);

    if (!empty($event['event_type_id'])) {
      try {
        $result['event_type_name'] = civicrm_api3('OptionValue', 'getvalue', array(
          'option_group_id' => 'event_type',
          'value' => $event['event_type_id'],
          'return' => 'label',
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $result;
  }

  /**
   * Method to add the forumZfd Custom Fields to the event result
   *
   * @param $event
   * @param $result
   * @throws CiviCRM_API3_Exception
   */
  private function addFzfdCustomFields($event, &$result) {
    $config = CRM_Apiprocessing_Config::singleton();
    if (isset($event['custom_'.$config->getAnsprechOrganisationCustomFieldId()])) {
      $result['ansprech_organisation'] = $event['custom_' . $config->getAnsprechOrganisationCustomFieldId()];
    }
    if (isset($event['custom_'.$config->getBewerbungCustomFieldId()])) {
      $result['bewerbung'] = $event['custom_' . $config->getBewerbungCustomFieldId()];
    }
    if (isset($event['custom_' . $config->getTrainerCustomFieldId()])) {
      $trainers = explode(';', $event['custom_' . $config->getTrainerCustomFieldId()]);
      foreach($trainers as $trainerId) {
        try {
          $trainerName = civicrm_api3('Contact', 'getvalue', array(
            'return' => 'display_name',
            'id' => $trainerId,
            ));
          $result['trainer'][] = array(
            'contact_id' => $trainerId,
            'contact_name' => $trainerName,
          );
        }
        catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::createError('Could not find a display name for contact ' . $trainerId . ' in '. __METHOD__);
        }
      }
    }
    if (isset($event['custom_' . $config->getTeilnahmeOrganisationCustomFieldId() . '_id'])) {
      $result['teilnahme_organisation_id'] = $event['custom_' . $config->getTeilnahmeOrganisationCustomFieldId() . '_id'];
      $result['teilnahme_organisation_name'] = $event['custom_' . $config->getTeilnahmeOrganisationCustomFieldId()];
    }
    if (isset($event['custom_' . $config->getAnsprechInhaltCustomFieldId()])) {
      $ansprechers = explode(';', $event['custom_' . $config->getAnsprechInhaltCustomFieldId()]);
      foreach($ansprechers as $ansprecherId) {
        try {
          $ansprecherName = civicrm_api3('Contact', 'getvalue', array(
            'return' => 'display_name',
            'id' => $ansprecherId,
            ));
          $result['ansprech_inhalt'][] = array(
            'ansprech_inhalt_id' => $ansprecherId,
            'ansprech_inhalt_name' => $ansprecherName,
          );
        }
        catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::createError('Could not find a display name for contact ' . $ansprecherId . ' in '. __METHOD__);
        }
      }
    }
  }
}