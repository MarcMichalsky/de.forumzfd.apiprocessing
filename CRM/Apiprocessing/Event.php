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
    $startDate = new DateTime('now');
    $startDate->modify('-1 year');
    $this->_defaultEventParams = array(
      'is_active' => 1,
      'is_template' => '0',
      'start_date' => array('>=' => $startDate->format('Ymd')),
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
      'event_title' => $event['title'],
      'event_type_id' => $event['event_type_id'],
      'event_type_name' => '',
      'registration_is_online' => $event['is_online_registration'],
      'has_waitlist' => $event['has_waitlist'],
      'trainer_id' => array(),
      'teilnahme_fuer_organisation' => '',
      'ansprech_organisation_id' => array(),
      'ansprech_inhalt_id' => array(),
      'start_date' => $event['start_date'],
      'end_date' => $event['end_date'],
      'event_language' => '',
      'meeting_venue' => '',
      'price' => array(),
      'maximum_participants' => $event['max_participants'],
      'registration_count' => 0,
      'registered_count' => 0,
      'partially_paid_count' => 0,
      'rechnung_zugesandt_count' => 0,
      'incomplete_count' => 0,
      'komplette_zahlung_count' => 0,
      'zertifikat_count' => 0,
      'zertifikat_nicht_count' => 0,
    );
    if (isset($event['registration_start_date'])) {
      $result['registration_start_date'] = $event['registration_start_date'];
    }
    else {
      $result['registration_start_date'] = "";
    }
    if (isset($event['registration_end_date'])) {
      $result['registration_end_date'] = $event['registration_end_date'];
    }
    else {
      $result['registration_end_date'] = "";
    }
    $this->getParticipantStatusCount($event['id'], $result);
    $this->addFzfdCustomFields($event, $result);
    $this->addPriceData($event, $result);
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
   * Method to count participants of various statusses
   *
   * @param $eventId
   * @param $result
   */
  private function getParticipantStatusCount($eventId, &$result) {
    // get statusIds from config
    $registered = CRM_Apiprocessing_Config::singleton()->getRegisteredParticipantStatusId();
    $rechnungZugestandt = CRM_Apiprocessing_Config::singleton()->getRechnungZuParticipantStatusId();
    $partiallyPaid = CRM_Apiprocessing_Config::singleton()->getPartiallyPaidParticipantStatusId();
    $incomplete = CRM_Apiprocessing_Config::singleton()->getIncompleteParticipantStatusId();
    $kompletteZahlung = CRM_Apiprocessing_Config::singleton()->getKompletteZahlungParticipantStatusId();
    $zertifikat = CRM_Apiprocessing_Config::singleton()->getZertifikatParticipantStatusId();
    $zertifikatNicht = CRM_Apiprocessing_Config::singleton()->getZertifikatNichtParticipantStatusId();
    // retrieve all registered participants
    $query = "SELECT status_id FROM civicrm_participant WHERE event_id = %1 AND is_test = %2";
    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$eventId, 'Integer'],
      2 => [0, 'Integer'],
    ]);
    // count each status
    while ($dao->fetch()) {
      switch ($dao->status_id) {
        case $registered:
          $result['registration_count']++;
          $result['registered_count']++;
          break;
        case $rechnungZugestandt:
          $result['registration_count']++;
          $result['rechnung_zugesandt_count']++;
          break;
        case $partiallyPaid:
          $result['registration_count']++;
          $result['partially_paid_count']++;
          break;
        case $incomplete:
          $result['registration_count']++;
          $result['incomplete_count']++;
          break;
        case $kompletteZahlung:
          $result['registration_count']++;
          $result['komplette_zahlung_count']++;
          break;
        case $zertifikat:
          $result['registration_count']++;
          $result['zertifikat_count']++;
          break;
        case $zertifikatNicht:
          $result['registration_count']++;
          $result['zertifikat_nicht_count']++;
          break;
      }
    }
  }

  /**
   * Method to collect the fees and discounts for the event
   *
   * @param $event
   * @param $result
   */
  private function addPriceData($event, &$result) {
    // only if there is a price set or discount for the event
    $queryParams = array(
      1 => array($event['id'], 'Integer'),
      2 => array('civicrm_event', 'String'),
    );
    $query = 'SELECT COUNT(*) FROM civicrm_price_set_entity WHERE entity_id = %1 AND entity_table = %2';
    $countPriceSet = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    $query = 'SELECT COUNT(*) FROM civicrm_discount WHERE entity_id = %1 AND entity_table = %2';
    $countDiscount = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($countPriceSet != 0 || $countDiscount != 0) {
      $result['price'] = $this->getPriceSetData($event['id']);
    }
  }

  /**
   * Method to get prices
   *
   * @param $eventId
   * @return array
   */
  private function getPriceSetData($eventId) {
    $prices = array();
    // first get the base prices
    $query = "SELECT pse.price_set_id AS price_set_id FROM civicrm_price_set_entity AS pse
      JOIN civicrm_price_set AS ps ON pse.price_set_id = ps.id
      WHERE pse.entity_table = %1 AND pse.entity_id = %2 AND ps.is_active = %3";
    $daoPriceSet = CRM_Core_DAO::executeQuery($query, array(
      1 => array('civicrm_event', 'String'),
      2 => array($eventId, 'Integer'),
      3 => array(1, 'Integer'),
    ));
    while ($daoPriceSet->fetch()) {
      $this->getPriceFieldData($daoPriceSet->price_set_id, 0, "", "", $prices);
    }
    // now add discounts
    $query = "SELECT dis.* FROM civicrm_discount AS dis
      JOIN civicrm_price_set AS ps ON dis.price_set_id = ps.id
      WHERE dis.entity_table = %1 AND dis.entity_id = %2";
    $daoDisSet = CRM_Core_DAO::executeQuery($query, array(
      1 => array('civicrm_event', 'String'),
      2 => array($eventId, 'Integer'),
    ));
    while ($daoDisSet->fetch()) {
      $this->getPriceFieldData($daoDisSet->price_set_id, 1, $daoDisSet->start_date, $daoDisSet->end_date, $prices);
    }
    return $prices;
  }

  /**
   * Method to get price field values
   *
   * @param $priceSetId
   * @param $discount
   * @param $startDate
   * @param $endDate
   * @param $result
   */
  private function getPriceFieldData($priceSetId, $discount, $startDate, $endDate, &$result) {
    // first get relevant price fields
    $queryFields = "SELECT id AS price_field_id FROM civicrm_price_field WHERE price_set_id = %1";
    $daoPriceFields = CRM_Core_DAO::executeQuery($queryFields, array(1 => array($priceSetId, 'Integer')));
    while ($daoPriceFields->fetch()) {
      // now get all the related price field values
      $queryValues = "SELECT label, amount FROM civicrm_price_field_value WHERE price_field_id = %1";
      $daoPriceValues = CRM_Core_DAO::executeQuery($queryValues, array(1 => array($daoPriceFields->price_field_id, 'Integer')));
      while ($daoPriceValues->fetch()) {
        $result[] = array(
          'price_set_id' => $priceSetId,
          'price_field_id' => $daoPriceFields->price_field_id,
          'price_field_label' => $daoPriceValues->label,
          'amount' => $daoPriceValues->amount,
          'discount' => $discount,
          'start_date' => $startDate,
          'end_date' => $endDate,
        );
      }
    }
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
    $this->getAnsprechOrganisation($event, $result);
    $this->getTrainers($event, $result);
    $this->getAnsprechInhalt($event, $result);
    if (isset($event['custom_' . $config->getNewEventVenueCustomFieldId()])) {
      $result['meeting_venue'] = $event['custom_' . $config->getNewEventVenueCustomFieldId()];
    }
    if (isset($event['custom_' . $config->getNewEventLanguageCustomFieldId()])) {
      try {
        $result['event_language'] = civicrm_api3('OptionValue', 'getvalue', array(
          'return' => 'label',
          'value' => $event['custom_' . $config->getNewEventLanguageCustomFieldId()],
          'option_group_id' => 'fzfd_sprache',
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
        $result['event_language'] = "no language label found";
      }
    }
  }

  /**
   * Method to get the ansprech organisation contactIds
   * @param $event
   * @param $result
   */
  private function getAnsprechOrganisation($event, &$result) {
    $config = CRM_Apiprocessing_Config::singleton();
    $contactIds = array();
    if (isset($event['custom_' . $config->getNewAnsprechOrg1CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewAnsprechOrg1CustomFieldId() . '_id'];
    }
    if (isset($event['custom_' . $config->getNewAnsprechOrg2CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewAnsprechOrg2CustomFieldId() . '_id'];
    }
    $result['ansprech_organisation_id'] = $contactIds;
  }

  /**
   * Method to get the ansprech inhalt contactIds
   * @param $event
   * @param $result
   */
  private function getAnsprechInhalt($event, &$result) {
    $config = CRM_Apiprocessing_Config::singleton();
    $contactIds = array();
    if (isset($event['custom_' . $config->getNewAnsprechInhalt1CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewAnsprechInhalt1CustomFieldId() . '_id'];
    }
    if (isset($event['custom_' . $config->getNewAnsprechInhalt2CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewAnsprechInhalt2CustomFieldId() . '_id'];
    }
    $result['ansprech_inhalt_id'] = $contactIds;
  }

  /**
   * Method to get the trainer contactIds
   * @param $event
   * @param $result
   */
  private function getTrainers($event, &$result) {
    $config = CRM_Apiprocessing_Config::singleton();
    $contactIds = array();
    if (isset($event['custom_' . $config->getNewTrainer1CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewTrainer1CustomFieldId() . '_id'];
    }
    if (isset($event['custom_' . $config->getNewTrainer2CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewTrainer2CustomFieldId() . '_id'];
    }
    if (isset($event['custom_' . $config->getNewTrainer3CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewTrainer3CustomFieldId() . '_id'];
    }
    if (isset($event['custom_' . $config->getNewTrainer4CustomFieldId() . '_id'])) {
      $contactIds[] = $event['custom_' . $config->getNewTrainer4CustomFieldId() . '_id'];
    }
    $result['trainer_id'] = $contactIds;
  }
}