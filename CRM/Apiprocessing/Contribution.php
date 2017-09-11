<?php

/**
 * Class for ForumZFD Contribution API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 7 Sept 2-017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Contribution {

  private function validPaymentInstrument($paymentInstrumentId) {

  }
  private function validFrequencyUnit($frequencyData) {

  }
  public function createNonSepa($contributionData) {

  }
  public function createSepaOneOff($sepaData) {

  }
  public function createSepaFirst($sepaData) {

  }

  /**
   * Method to process the data coming in from the website
   *
   * @param $params
   */
  public function processIncomingData($params) {
    if ($this->validIncomingParams($params) == TRUE) {
      // process donor (find contact id or create if required)
      $donorContactId = $this->processIndividual($params);
      if ($donorContactId) {
        // process organization if required
        if (isset($params['organization_name']) && !empty($params['organization_name'])) {
          $organizationId = $this->processOrganization($params, $donorContactId);
          if ($organizationId) {
            $donorContactId = $organizationId;
          }
        }
        // process contribution based on payment_instrument_id
        switch ($params['payment_instrument_id']) {
          case CRM_Apiprocessing_Config::singleton()->getSepaFirstPaymentInstrumentId():
            $sepaFirstData = $this->createSepaFirstParams($params, $donorContactId);
            $this->createSepaFirst($sepaFirstData);
            break;
          case CRM_Apiprocessing_Config::singleton()->getSepaOneOffPaymentInstrumentId():
            $sepaOneOffData = $this->createSepaOneOffParams($params, $donorContactId);
            $this->createSepaFirst($sepaOneOffData);
            break;
          default:
            $contributionData = $this->createContributionParams($params, $donorContactId);
            $this->createNonSepa($contributionData);
            break;
        }
      }
    }
  }
  public function createSepaOneOffParams($params, $donorContactId) {

  }
  public function createContributionParams($params, $donorContactId) {

  }
  public function createSepaFirstParams($params, $donorContactId) {

  }

  /**
   * Method to create or find individual
   *
   * @param $params
   * @return bool|int
   */
  public function processIndividual($params) {
    // return FALSE if no email in params
    if (!isset($params['email']) || empty($params['email'])) {
      return FALSE;
    }
    $contactParams = array(
      'email' => $params['email'],
      );
    $possibles = array('first_name', 'last_name', 'prefix_id', 'formal_title');
    foreach ($possibles as $possible) {
      if (isset($params[$possible]) && !empty($params[$possible])) {
        $contactParams[$possible] = $params[$possible];
      }
    }
    $contact = new CRM_Apiprocessing_Contact();
    // if prefix_id is used, generate gender_id
    if (isset($params['prefix_id']) && !empty($params['prefix_id'])) {
      $genderId = $contact->generateGenderFromPrefix($params['prefix_id']);
      if ($genderId) {
        $contactParams['gender_id'] = $genderId;
      }
    }
    return $contact->processIncomingContact($contactParams);
  }

  /**
   * Method to create or find organization
   *
   * @param $params
   * @return bool|int
   */
  public function processOrganization($params) {
    // return FALSE if no organization name in params
    if (!isset($params['organization_name']) || empty($params['organization_name'])) {
      return FALSE;
    }
    $contactParams = array(
      'email' => $params['email'],
    );
    $possibles = array('first_name', 'last_name', 'prefix_id', 'formal_title');
    foreach ($possibles as $possible) {
      if (isset($params[$possible]) && !empty($params[$possible])) {
        $contactParams[$possible] = $params[$possible];
      }
    }
    $contact = new CRM_Apiprocessing_Contact();
    // if prefix_id is used, generate gender_id
    if (isset($params['prefix_id']) && !empty($params['prefix_id'])) {
      $genderId = $contact->generateGenderFromPrefix($params['prefix_id']);
      if ($genderId) {
        $contactParams['gender_id'] = $genderId;
      }
    }
    return $contact->processIncomingContact($contactParams);

  }

  /**
   * Method to check if incoming parameters are valid
   *
   * @param $params
   * @return bool
   * @throws Exception when non valid parameters found
   */
  private function validIncomingParams($params) {
    // check if all generic mandatory params are present
    $mandatories = array('payment_instrument_id', 'email', 'amount');
    foreach ($mandatories as $mandatory) {
      if (!isset($params[$mandatory]) || empty($params[$mandatory])) {
        throw new Exception('Could not find mandatory parameter '.$mandatory.' when trying to add a donation in '.__METHOD__);
      }
    }
    // check if payment instrument is valid
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaRecurringPaymentInstrumentId()) {
      throw new Exception('Payment instrument for SEPA Recurring not allowed in '.__METHOD__);
    }
    try {
      civicrm_api3('OptionValue', 'getsingle', array(
        'option_group_id' => 'payment_instrument',
        'value' => $params['payment_instrument_id'],
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find a CiviCRM payment instrument with id '.$params['payment_instrument_id'].'in '.__METHOD__);
    }
    // check if the payment instrument dependent mandatory params are present
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaFirstPaymentInstrumentId()) {
      $mandatories = array('iban', 'frequency_unit');
      foreach ($mandatories as $mandatory) {
        if (!isset($params[$mandatory]) || empty($params[$mandatory])) {
          throw new Exception('Could not find mandatory parameter '.$mandatory.' for SEPA First when trying to add a donation in '.__METHOD__);
        }
      }
      // check if frequency unit is valid
      $validFrequencyUnits = array('monthly', 'quarterly', 'semi-annually', 'annually');
      if (!in_array($params['frequency_unit'], $validFrequencyUnits)) {
        throw new Exception('Invalid frequency unit '.$params['frequency_unit'].' used for SEPA First when trying to add a donation in '.__METHOD__);
      }
    }
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaOneOffPaymentInstrumentId()) {
      if (!isset($params['iban']) || empty($params['iban'])) {
        throw new Exception('Could not find mandatory parameter iban for SEPA One Off when trying to add a donation in '.__METHOD__);
      }
    }
    return TRUE;
  }

}