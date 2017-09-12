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
            if (!empty($sepaOneOffData)) {
              $this->createSepaFirst($sepaOneOffData);
            }
            break;
          default:
            $contributionData = $this->createContributionParams($params, $donorContactId);
            $this->createNonSepa($contributionData);
            break;
        }
      }
    }
  }

  /**
   * Method to create parameter list for one off sepa
   *
   * @param $params
   * @param $donorContactId
   * @return array
   */
  public function createSepaOneOffParams($params, $donorContactId) {
    $sepaParams = array();
    $creditor = CRM_Sepa_Logic_Settings::defaultCreditor();
    if (empty($creditor)) {
      $activity = new CRM_Apiprocessing_Activity();
      $activity->createNewErrorActivity('forumzfd', 'Could not find a default creditor for SEPA in '
        .__METHOD__.', create one in your CiviSepa Settings. Donation has not been processed!', $params);
    } else {

    }
    return $sepaParams;

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
    $individualParams = $params;
    $removes = array(
      'payment_instrument_id',
      'amount',
      'donation_date',
      'campaign_id',
      'source',
      'organization_name',
      'organization_street_address',
      'organization_postal_code',
      'organization_city',
      'organization_country_iso',
      'iban',
      'start_date',
      'bic',
      'frequency_interval',
      'frequency_unit',
      'cycle_day',
      );
    foreach ($removes as $remove) {
      if (isset($individualParams[$remove])) {
        unset($individualParams[$remove]);
      }
    }
    $individual = new CRM_Apiprocessing_Contact();
    // if prefix_id is used, generate gender_id
    if (isset($params['prefix_id']) && !empty($params['prefix_id'])) {
      $genderId = $individual->generateGenderFromPrefix($params['prefix_id']);
      if ($genderId) {
        $individualParams['gender_id'] = $genderId;
      }
    }
    return $individual->processIncomingIndividual($individualParams);
  }

  /**
   * Method to create or find organization
   *
   * @param $params
   * @param $individualId
   * @return bool|int
   */
  public function processOrganization($params, $individualId) {
    // return FALSE if no organization name in params
    if (!isset($params['organization_name']) || empty($params['organization_name'])) {
      return FALSE;
    }
    $organizationParams = array(
      'organization_name' => $params['organization_name'],
      'contact_type' => 'Organization',
    );
    $possibles = array(
      'organization_street_address',
      'organization_postal_code',
      'organization_city',
      'organization_country_iso',
    );
    foreach ($possibles as $possible) {
      if (isset($params[$possible]) && !empty($params[$possible])) {
        $organizationParams[$possible] = $params[$possible];
      }
    }
    $organization = new CRM_Apiprocessing_Contact();
    $organizationId = $organization->processIncomingOrganization($organizationParams);
    // now process relationship between organization and individual
    $relationship = new CRM_Apiprocessing_Relationship();
    $relationship->processEmployerRelationship($organizationId, $individualId);
    return $organizationId;
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