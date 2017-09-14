<?php

/**
 * Class for ForumZFD Contribution API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 7 Sept 2-017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Contribution {

  /**
   * Method to create a contribution
   *
   * @param $contributionData
   */
  public function createNonSepa($contributionData) {
    try {
      civicrm_api3('Contribution', 'create', $contributionData);
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message('Could not create a contribution, error from API Contribution Create: '.$ex->getMessage());
      $errorMessage = 'Could not create a contribution, please check and correct!';
      $activity = new CRM_Apiprocessing_Activity();
      $activity->createNewErrorActivity('forumzfd', $errorMessage, $contributionData);
    }
  }

  /**
   * Method to create a SEPA mandate and log any errors in activity
   *
   * @param array $sepaData
   */
  public function createSepaMandate($sepaData) {
    try {
      civicrm_api3('SepaMandate', 'createfull', $sepaData);
    }
    catch (CiviCRM_API3_Exception $ex) {
      CRM_Core_Error::debug_log_message('Could not create a SEPA mandate, error from API SepaMandate createfull: '.$ex->getMessage());
      $errorMessage = 'Could not create a SEPA payment, please check and correct!';
      $activity = new CRM_Apiprocessing_Activity();
      $activity->createNewErrorActivity('forumzfd', $errorMessage, $sepaData);
    }
  }

  /**
   * Method to process the data coming in from the website
   *
   * @param array $params
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
          case CRM_Apiprocessing_Config::singleton()->getSepaFrstPaymentInstrumentId():
            $sepaFrstParams = $this->createSepaFrstParams($params, $donorContactId);
            $this->createSepaMandate($sepaFrstParams);
            break;
          case CRM_Apiprocessing_Config::singleton()->getSepaOoffPaymentInstrumentId():
            $sepaOoffParams = $this->createSepaOoffParams($params, $donorContactId);
            if (!empty($sepaOoffParams)) {
              $this->createSepaMandate($sepaOoffParams);
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
   * Method to create parameter list for contribution
   *
   * @param array $params
   * @param int $donorContactId
   * @return array
   */
  public function createContributionParams($params, $donorContactId) {
    $contributionParams = array(
      'total_amount' => $params['amount'],
      'financial_type_id' => CRM_Apiprocessing_Config::singleton()->getContributionFinancialTypeId(),
      'payment_instrument_id' => $params['payment_instrument_id'],
      'contact_id' => $donorContactId,
      'currency' => CRM_Apiprocessing_Config::singleton()->getDefaultCurrency(),
      'contribution_status_id' => CRM_Apiprocessing_Config::singleton()->getCompletedContributionStatusId(),
      'receive_date' => $this->setContributionReceiveDate($params),
      'source' => $this->setSource($params),
    );
    // add campaign_id if in parameters
    if (isset($params['campaign_id']) && !empty($params['campaign_id'])) {
      $contributionParams['campaign_id'] = $params['campaign_id'];
    }
    return $contributionParams;
  }

  /**
   * Method to create parameter list for one off sepa
   *
   * @param array $params
   * @param int $donorContactId
   * @return array
   */
  public function createSepaOoffParams($params, $donorContactId) {
    $sepaParams = array();
    $creditor = CRM_Sepa_Logic_Settings::defaultCreditor();
    if (empty($creditor)) {
      $activity = new CRM_Apiprocessing_Activity();
      $activity->createNewErrorActivity('forumzfd', 'Could not find a default creditor for SEPA in '
        .__METHOD__.', create one in your CiviSepa Settings. Donation has not been processed!', $params);
    } else {
      $sepaParams = array(
        'creditor_id' => $creditor->id,
        'contact_id' => $donorContactId,
        'financial_type_id' => CRM_Apiprocessing_Config::singleton()->getSepaOoffFinancialTypeId(),
        'status' => CRM_Apiprocessing_Config::singleton()->getSepaOoffMandateStatus(),
        'type' => CRM_Apiprocessing_Config::singleton()->getSepaOoffMandateType(),
        'currency' => CRM_Apiprocessing_Config::singleton()->getDefaultCurrency(),
        // todo 'reference' =>
        'total_amount' => $params['amount'],
        'start_date' => $this->setSepaStartDate($params),
        'source' => $this->setSource($params),
        'creation_date' => date('YmdHis'),
      );
      // set campaign if entered
      if (isset($params['campaign_id']) && !empty($params['campaign_id'])) {
        $sepaParams['campaign_id'] = $params['campaign_id'];
      }
      // set bic if entered
      if (isset($params['bic']) && !empty($params['bic'])) {
        $sepaParams['bic'] = $params['bic'];
      }
    }
    return $sepaParams;
  }

  /**
   * Method to set the passed source or the default one
   *
   * @param array $params
   * @return string
   */
  private function setSource($params) {
    // default source to standard text if not entered
    if (isset($params['source']) && !empty($params['source'])) {
      return $params['source'];
    } else {
      return 'from website (extension de.forumzfd.apiprocessing)';
    }
  }

  /**
   * Method to set the start date for sepa
   *
   * @param array $params
   * @return false|string
   */
  private function setSepaStartDate($params) {
    // default start date to system date if not set
    if (isset($params['start_date']) && !empty($params['start_date'])) {
      $startDate = new DateTime($params['start_date']);
      return $startDate->format('YmdHis');
    } else {
      return date('YmdHis');
    }

  }

  /**
   * Method to set the receive date for contribution
   *
   * @param array $params
   * @return false|string
   */
  private function setContributionReceiveDate($params) {
    // default receive date to system date if not set
    if (isset($params['receive_date']) && !empty($params['receive_date'])) {
      $receiveDate = new DateTime($params['receive_date']);
      return $receiveDate->format('YmdHis');
    } else {
      return date('YmdHis');
    }
  }

  /**
   * Method to set the frequency interval for sepa
   *
   * @param array $params
   * @return false|string
   */
  private function setFrequencyInterval($params) {
    // default frequency interval to 1 if not set
    if (isset($params['frequency_interval']) && !empty($params['frequency_interval'])) {
      return $params['frequency_interval'];
    } else {
      return 1;
    }
  }

  /**
   * Method to determine what cycle day to use, one from params if valid else default
   *
   * @param array $params
   * @return array|mixed
   */
  private function setCycleDay($params) {
    if (isset($params['cycle_day']) && !empty($params['cycle_day'])) {
      $validCycleDays = array();
      $settingCycleDays = civicrm_api3('Setting', 'getvalue', array('name' => 'cycledays',));
      if (!empty($settingCycleDays)) {
        $values = explode(',', $settingCycleDays);
        foreach ($values as $value) {
          $validCycleDays[] = $value;
        }
      }
      if (in_array($params['cycle_day'], $validCycleDays)) {
        return $params['cycle_day'];
      } else {
        $activity = new CRM_Apiprocessing_Activity();
        $errorMessage = 'Cycle day '.$params['cycle_day'].' is not valid, default cycle day used. Please check and correct.';
        $activity->createNewErrorActivity('forumzfd', $errorMessage, $params);
        return CRM_Apiprocessing_Settings::singleton()->get('default_cycle_day_sepa');
      }
    } else {
      $activity = new CRM_Apiprocessing_Activity();
      $errorMessage = 'Cycle day '.$params['cycle_day'].' is not set in parameters, default cycle day used. Please check and correct.';
      $activity->createNewErrorActivity('forumzfd', $errorMessage, $params);
      return CRM_Apiprocessing_Settings::singleton()->get('default_cycle_day_sepa');
    }
  }

  /**
   * Method to set the parameters for a sepa recurring mandate
   *
   * @param array $params
   * @param int $donorContactId
   * @return array
   */
  public function createSepaFrstParams($params, $donorContactId) {
    $sepaParams = array();
    $creditor = CRM_Sepa_Logic_Settings::defaultCreditor();
    if (empty($creditor)) {
      $activity = new CRM_Apiprocessing_Activity();
      $activity->createNewErrorActivity('forumzfd', 'Could not find a default creditor for SEPA in '
        .__METHOD__.', create one in your CiviSepa Settings. Donation has not been processed!', $params);
    } else {
      $sepaParams = array(
        'creditor_id' => $creditor->id,
        'contact_id' => $donorContactId,
        'financial_type_id' => CRM_Apiprocessing_Config::singleton()->getSepaRcurFinancialTypeId(),
        'status' => CRM_Apiprocessing_Config::singleton()->getSepaFrstMandateStatus(),
        'type' => CRM_Apiprocessing_Config::singleton()->getSepaRcurMandateType(),
        'currency' => CRM_Apiprocessing_Config::singleton()->getDefaultCurrency(),
        // todo 'reference' =>
        'total_amount' => $params['amount'],
        'creation_date' => date('YmdHis'),
        'start_date' => $this->setSepaStartDate($params),
        'frequency_interval' => $this->setFrequencyInterval($params),
        'cycle_day' => $this->setCycleDay($params),
        'frequency_unit' => $params['frequency_unit'],
        'source' => $this->setSource($params),
        'iban' => $params['iban'],
      );
      // set campaign if entered
      if (isset($params['campaign_id']) && !empty($params['campaign_id'])) {
        $sepaParams['campaign_id'] = $params['campaign_id'];
      }
      // set bic if entered
      if (isset($params['bic']) && !empty($params['bic'])) {
        $sepaParams['bic'] = $params['bic'];
      }
    }
    return $sepaParams;
  }

  /**
   * Method to create or find individual
   *
   * @param array $params
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
   * @param array $params
   * @param int $individualId
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
   * @param array $params
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
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaRcurPaymentInstrumentId()) {
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
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaFrstPaymentInstrumentId()) {
      $mandatories = array('iban', 'frequency_unit', 'cycle_day');
      foreach ($mandatories as $mandatory) {
        if (!isset($params[$mandatory]) || empty($params[$mandatory])) {
          throw new Exception('Could not find mandatory parameter '.$mandatory.' for SEPA First when trying to add a donation in '.__METHOD__);
        }
      }
      // check if frequency unit is valid
      try {
        civicrm_api3('OptionValue', 'getsingle', array(
          'option_group_id' => 'recur_frequency_units',
          'value' => $params['frequency_unit'],
        ));
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Invalid frequency unit '.$params['frequency_unit'].' used for SEPA First when trying to add a donation in '.__METHOD__);
      }
    }
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaOoffPaymentInstrumentId()) {
      if (!isset($params['iban']) || empty($params['iban'])) {
        throw new Exception('Could not find mandatory parameter iban for SEPA One Off when trying to add a donation in '.__METHOD__);
      }
    }
    return TRUE;
  }

}