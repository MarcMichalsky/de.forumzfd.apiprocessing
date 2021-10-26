<?php

/**
 * Class for ForumZFD Contribution API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 7 Sept 2-017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Contribution {

  private $_tempId = NULL;

  /**
   * Method to get temp data (anonym)
   *
   * @param int $tempId
   * @return array
   * @throws API_Exception
   */
  public function getTempData($tempId) {
    if (empty($tempId)) {
      throw new API_Exception(ts('Empty temp ID, could not get data'), 3041);
    }
    $paymentOptionGroupId = CRM_Apiprocessing_Utils::getOptionGroupIdWithName('payment_instrument');
    if ($paymentOptionGroupId) {
      $tempData = [];
      $query = "SELECT cfm.status, cfm.iban, cfm.bic, cfm.amount, cfm.frequency_unit, cfc.payment_instrument_id,
      cfc.total_amount, ov.label AS payment_instrument
      FROM civicrm_fzfd_temp AS cft
      LEFT JOIN civicrm_fzfd_sdd_mandate AS cfm ON cft.id = cfm.temp_id
      LEFT JOIN civicrm_fzfd_contribution AS cfc ON cft.id = cfc.temp_id
      LEFT JOIN civicrm_option_value AS ov ON cfc.payment_instrument_id = ov.value AND ov.option_group_id = %1
      WHERE cft.id = %2";
      $dao = CRM_Core_DAO::executeQuery($query, [
        1 => [$paymentOptionGroupId, 'Integer'],
        2 => [$tempId, 'Integer'],
      ]);
      if ($dao->fetch()) {
        if (!empty($dao->status)) {
          $tempData[$tempId] = [
            'type' => $dao->status,
            'bic' => $dao->bic,
            'iban' => substr($dao->iban,0,3) . '**********' . substr($dao->iban, -4),
            'amount' => $dao->amount,
            'frequency' => $dao->frequency_unit
          ];
        }
        else {
          $tempData[$tempId] = [
            'payment_instrument' => $dao->payment_instrument,
            'amount' => $dao->total_amount,
          ];
        }
      }
    }
    else {
      throw new API_Exception(ts('No option group for payment instruments found.'), 3091);
    }
    return $tempData;
  }

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
   * Method to create a temporary contribution
   *
   * @param $contributionData
   */
  public function createTempContribution($contributionData) {
    try {
      civicrm_api3('FzfdContribution', 'create', $contributionData);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error('Could not create a temporary contribution, error from API FzfdContribution Create: '.$ex->getMessage());
    }
  }

  /**
   * Method to create a temp SEPA mandate
   *
   * @param array $sepaData
   * @throws API_Exception
   */
  public function createTempMandate($sepaData) {
    try {
      civicrm_api3('FzfdMandate', 'create', $sepaData);
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception(ts('Could not create a temporary First, error from API : ').$ex->getMessage(), 3009);
    }
  }
  /**
   * Method to create a SEPA mandate and log any errors in activity
   *
   * @param array $sepaData
   * @throws API_Exception
   */
  public function createSepaMandate($sepaData) {
    try {
      civicrm_api3('SepaMandate', 'createfull', $sepaData);
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception(ts('Could not create a SEPA mandate, error from API SepaMandate createfull: ').$ex->getMessage(), 1006);
      $errorMessage = 'Could not create a SEPA payment, please check and correct!';
      $activity = new CRM_Apiprocessing_Activity();
      $activity->createNewErrorActivity('forumzfd', $errorMessage, $sepaData);
    }
  }

  /**
   * Method to process the data coming in from the website
   *
   * @param array $params
   * @return int
   * @throws API_Exception when no temp id
   */
  public function processIncomingData($params) {
    if ($this->validIncomingParams($params) == TRUE) {
      // process donor (find contact id or create if required)
      $donor = new CRM_Apiprocessing_Contact();
      $donorContactId = $donor->processIncomingIndividual($params);
      // create temporary ID for request
      if ($params['is_test'] == TRUE) {
        $this->createTemporaryId($donorContactId, $params['payment_instrument_id'], TRUE);
      }
      else {
        $this->createTemporaryId($donorContactId, $params['payment_instrument_id'], FALSE);
      }
      if ($donorContactId) {
        // process organization if required
        if (isset($params['organization_name']) && !empty($params['organization_name'])) {
          $organization = new CRM_Apiprocessing_Contact();
          $organizationId = $organization->processOrganization($params, $donorContactId);
          if ($organizationId) {
            $donorContactId = $organizationId;
          }
        }
        // process contribution based on payment_instrument_id
        switch ($params['payment_instrument_id']) {
          case CRM_Apiprocessing_Config::singleton()->getSepaFrstPaymentInstrumentId():
            // first check if iban and bic are correct
            $this->validateIbanBic($params);
            $this->processBankAccount($donorContactId, $params['iban'], $params['bic']);
            $tempSddData = $this->createTempFrstData($params, $donorContactId);
            if (!empty($tempSddData)) {
              $this->createTempMandate($tempSddData);
            }
            break;

          case CRM_Apiprocessing_Config::singleton()->getSepaOoffPaymentInstrumentId():
            $this->validateIbanBic($params);
            $this->processBankAccount($donorContactId, $params['iban'], $params['bic']);
            $tempSddData = $this->createTempOoffData($params, $donorContactId);
            if (!empty($tempSddData)) {
              $this->createTempMandate($tempSddData);
            }
            break;

          default:
            $contributionData = $this->createTempContributionData($params, $donorContactId);
            $this->createTempContribution($contributionData);
            break;
        }
      }
    }
    if ($this->_tempId) {
      return $this->_tempId;
    }
    else {
      throw new API_Exception(ts('Could not generate a temporary ID for Donation'), 3001);
    }
  }

  /**
   * Method to generate the temporary donation id
   *
   * @param int $contactId
   * @param int $paymentInstrumentId
   * @param bool $isTest
   * @throws API_Exception
   */
  private function createTemporaryId($contactId, $paymentInstrumentId, $isTest) {
    $dateCreated = new DateTime();
    try {
      $temp = civicrm_api3('FzfdTemp', 'create', [
        'contact_id' => $contactId,
        'payment_instrument_id' => $paymentInstrumentId,
        'date_created' => $dateCreated->format('YmdHis'),
        'is_test' => $isTest,
      ]);
      if ($temp['values']->id) {
        $this->_tempId = (int) $temp['values']->id;
      }
      else {
        throw new API_Exception('Could not create a temporary ID for Donation', 3001);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception('Could not create a temporary ID for Donation', 3001);
    }
  }

  /**
   * Method to create parameter list for contribution
   *
   * @param int $contactId
   * @return array
   * @throws
   */
  public function createContributionParams($contactId) {
    // retrieve temp contribution data
    $contributionParams = $this->retrieveTempData('contribution');
    if (!empty($contributionParams)) {
      $contributionParams['contact_id'] = $contactId;
      $contributionParams['contribution_status_id'] = CRM_Apiprocessing_Config::singleton()->getPendingContributionStatusId();
      $receiveDate = new DateTime();
      $contributionParams['receive_date'] = $receiveDate->format('Ymd');

    }
    return $contributionParams;
  }

  /**
   * Method to retrieve temporary data
   *
   * @param string $entity
   * @return array
   * @throws API_Exception
   */
  private function retrieveTempData($entity) {
    $apiEntity = 'Fzfd' . ucfirst($entity);
    $tempData = [];
    if ($this->_tempId) {
      try {
        $tempData = civicrm_api3($apiEntity, 'getsingle', ['temp_id' => $this->_tempId]);
        if ($tempData) {
          unset($tempData['id']);
          unset($tempData['temp_id']);
          return $tempData;
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new API_Exception(ts('Could not find temporary ') . $entity . ts(' with id ') . $this->_tempId, 3031);
      }
    }
    return $tempData;
  }

  /**
   * Method to create parameter list for temp contribution
   *
   * @param array $params
   * @param int $donorContactId
   * @return array
   */
  public function createTempContributionData($params, $donorContactId) {
    $contributionParams = array(
      'total_amount' => $params['amount'],
      'financial_type_id' => CRM_Apiprocessing_Config::singleton()->getContributionFinancialTypeId(),
      'payment_instrument_id' => $params['payment_instrument_id'],
      'temp_id' => $this->_tempId,
      'currency' => CRM_Apiprocessing_Config::singleton()->getDefaultCurrency(),
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
        'iban' => $params['iban'],
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
   * Method to create parameter list for temp one off sepa
   *
   * @param array $params
   * @param int $donorContactId
   * @return array
   */
  public function createTempOoffData($params, $donorContactId) {
    $sepaParams = [];
    if (!empty($donorContactId)) {
      $sepaParams = [
        'temp_id' => $this->_tempId,
        'financial_type_id' => CRM_Apiprocessing_Config::singleton()->getSepaOoffFinancialTypeId(),
        'status' => CRM_Apiprocessing_Config::singleton()->getSepaOoffMandateStatus(),
        'type' => CRM_Apiprocessing_Config::singleton()->getSepaOoffMandateType(),
        'currency' => CRM_Apiprocessing_Config::singleton()->getDefaultCurrency(),
        'amount' => $params['amount'],
        'iban' => $params['iban'],
        'start_date' => $this->setSepaStartDate($params),
        'source' => $this->setSource($params),
      ];
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
   * Method to set the parameters for a sepa recurring mandate from a temporary record
   *
   * @param int $contactId
   * @return array
   * @throws API_Exception in retrieveTempMandate
   */
  public function createSepaParams($contactId) {
    // retrieve temp mandata data
    $sepaParams = $this->retrieveTempData('mandate');
    if (!empty($sepaParams)) {
      $creditor = CRM_Sepa_Logic_Settings::defaultCreditor();
      if (empty($creditor)) {
        $activity = new CRM_Apiprocessing_Activity();
        $activity->createNewErrorActivity('forumzfd', 'Could not find a default creditor for SEPA in '
          .__METHOD__.', create one in your CiviSepa Settings. Donation has not been processed!', $sepaParams);
      } else {
        $sepaParams['creditor_id'] = $creditor->id;
        $sepaParams['contact_id'] = $contactId;
        $sepaParams['creation_date'] = date('YmdHis');
      }
    }
    return $sepaParams;
  }

  /**
   * Method to set the parameters for a temp sepa recurring mandate
   *
   * @param array $params
   * @param int $donorContactId
   * @return array
   */
  public function createTempFrstData($params, $donorContactId) {
    $tempFrstParams = [];
    if (!empty($donorContactId)) {
      $tempFrstParams = [
        'financial_type_id' => CRM_Apiprocessing_Config::singleton()->getSepaRcurFinancialTypeId(),
        'status' => CRM_Apiprocessing_Config::singleton()->getSepaFrstMandateStatus(),
        'type' => CRM_Apiprocessing_Config::singleton()->getSepaRcurMandateType(),
        'currency' => CRM_Apiprocessing_Config::singleton()->getDefaultCurrency(),
        'amount' => $params['amount'],
        'start_date' => $this->setSepaStartDate($params),
        'frequency_interval' => $this->setFrequencyInterval($params),
        'cycle_day' => $this->setCycleDay($params),
        'frequency_unit' => $params['frequency_unit'],
        'source' => $this->setSource($params),
        'iban' => $params['iban'],
        'temp_id' => $this->_tempId,
      ];
      // set campaign if entered
      if (isset($params['campaign_id']) && !empty($params['campaign_id'])) {
        $tempFrstParams['campaign_id'] = $params['campaign_id'];
      }
      // set bic if entered
      if (isset($params['bic']) && !empty($params['bic'])) {
        $tempFrstParams['bic'] = $params['bic'];
      }
    }
    return $tempFrstParams;
  }

  /**
   * Method to validate or find bic with iban
   *
   * @param $params
   * @throws API_Exception
   */
  private function validateIbanBic(&$params) {
    // first check if Little Bic extension is installed. If not, create error as we can not check the BIC
    $query = 'SELECT COUNT(*) FROM civicrm_extension WHERE full_name = %1 AND is_active = %2';
    $count = CRM_Core_DAO::singleValueQuery($query, array(
      1 => array('org.project60.bic', 'String'),
      2 => array(1, 'Integer'),
    ));
    if ($count == 0) {
      throw new API_Exception(ts('Extension Little Bic does not seem to be installed so BIC can not be validated in ')
        . __METHOD__, 1001);
    }
    // iban has to be set!
    if (!isset($params['iban']) || empty($params['iban'])) {
      throw new API_Exception(ts('Mandatory parameter iban is not set or empty in ') . __METHOD__, 1002);
    }
    // remove spaces from iban
    $params['iban'] = str_replace(" ", "", $params['iban']);
    // find BIC with IBAN. Required for checking or defaulting
    try {
      $found = civicrm_api3('Bic', 'getfromiban', array('iban' => $params['iban']));
      if ($found['bic'] == "NAP") {
        throw new API_Exception(ts('Could not find a valid bic with iban'), 1005);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception(ts('Could not find a BIC in ') . __METHOD__ . ts(', error message from API Bic getfromiban: ') . $ex->getMessage(), 1003);
    }
    // if BIC is set, check if correct. If not, return error
    if (isset($params['bic']) && !empty($params['bic'])) {
      if ($params['bic'] != $found['bic']) {
        throw new API_Exception(ts('Parameter bic is not valid with iban'), 1004);
      }
    }
    // now lookup BIC if BIC is empty in params. Return error if not found
    else {
      $params['bic'] = $found['bic'];
    }
  }

  /**
   * Method to check if incoming parameters are valid
   *
   * @param array $params
   * @return bool
   * @throws Exception when non valid parameters found
   */
  private function validIncomingParams($params) {
    // set test to 0 if not set
    // check if all generic mandatory params are present
    $mandatories = array('payment_instrument_id', 'email', 'amount');
    foreach ($mandatories as $mandatory) {
      if (!isset($params[$mandatory]) || empty($params[$mandatory])) {
        throw new API_Exception('Could not find mandatory parameter '.$mandatory.' when trying to add a donation in '.__METHOD__, 3002);
      }
    }
    // check if payment instrument is valid
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaRcurPaymentInstrumentId()) {
      throw new API_Exception('Payment instrument for SEPA Recurring not allowed in '.__METHOD__, 3003);
    }
    try {
      civicrm_api3('OptionValue', 'getsingle', array(
        'option_group_id' => 'payment_instrument',
        'value' => $params['payment_instrument_id'],
      ));
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception('Could not find a CiviCRM payment instrument with id '.$params['payment_instrument_id'].'in '.__METHOD__, 3004);
    }
    // check if the payment instrument dependent mandatory params are present
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaFrstPaymentInstrumentId()) {
      $mandatories = array('iban', 'frequency_unit');
      foreach ($mandatories as $mandatory) {
        if (!isset($params[$mandatory]) || empty($params[$mandatory])) {
          throw new API_Exception('Could not find mandatory parameter '.$mandatory.' for SEPA First when trying to add a donation in '.__METHOD__, 3005);
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
        throw new API_Exception('Invalid frequency unit '.$params['frequency_unit'].' used for SEPA First when trying to add a donation in '.__METHOD__, 3006);
      }
    }
    if ($params['payment_instrument_id'] == CRM_Apiprocessing_Config::singleton()->getSepaOoffPaymentInstrumentId()) {
      if (!isset($params['iban']) || empty($params['iban'])) {
        throw new API_Exception('Could not find mandatory parameter iban for SEPA One Off when trying to add a donation in '.__METHOD__, 3007);
      }
    }
    return TRUE;
  }

  /**
   * Method to process bank account (and create if it does not exist yet)
   *
   * @param $contactId
   * @param $iban
   * @param $bic
   */
  private function processBankAccount($contactId, $iban, $bic) {
    // first check if bank account exists
    try {
      $count = civicrm_api3('BankingAccount', 'getcount', array(
        'iban' => $iban,
        'bic' => $bic,
        'contact_id' => $contactId,
      ));
      // if it does not exist, create
      if ($count == 0) {
        try {
          $createdBankAccount = civicrm_api3('BankingAccount', 'create', [
            'contact_id' => $contactId,
            'data_parsed' => '{}',
          ]);
          $bankAccountId = $createdBankAccount['id'];
          $bankData = [];
          if (!empty($bic)) {
            $bankData['BIC'] = trim($bic);
          }
          if (!empty($bankData)) {
            $bankBao = new CRM_Banking_BAO_BankAccount();
            $bankBao->get('id', $bankAccountId);
            $bankBao->setDataParsed($bankData);
            $bankBao->save();
          }
          // update/create bank reference
          $referenceParams = [
            'reference' => trim($iban),
            'reference_type_id' => CRM_Apiprocessing_Config::singleton()->getBankAccountReferenceType(),
            'ba_id' => $bankAccountId,
          ];
          civicrm_api3('BankingAccountReference', 'create', $referenceParams);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->warning(ts('Could not add a bank account for iban ' .$iban . ' and contact ' . $contactId . 'in ') . __METHOD__);
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(ts('Strange error trying from API BankingAccount getcount in ')
        . __METHOD__ . ts(', error message from API: ') . $ex->getMessage());
    }
  }

  /**
   * Method to process the confirm of a temp sdd mandate/contribution
   *
   * @param $tempId
   * @return mixed
   * @throws API_Exception
   */
  public function processConfirm($tempId) {
    // check if temp ID is still in database, error if not
    try {
      $temp = civicrm_api3('FzfdTemp', 'getsingle', ['id' => $tempId]);
      $this->_tempId = $tempId;
      if ($this->isValidTempData($temp)) {
        // process depending on payment_instrument
        $frstType = CRM_Apiprocessing_Config::singleton()->getSepaFrstPaymentInstrumentId();
        $ooffType = CRM_Apiprocessing_Config::singleton()->getSepaOoffPaymentInstrumentId();
        if ($temp['payment_instrument_id'] == $frstType || $temp['payment_instrument_id'] == $ooffType) {
          $sepaParams = $this->createSepaParams($temp['contact_id']);
          // check if this is a test and if so, pass param
          if ($temp['is_test'] == TRUE) {
            $sepaParams['is_test'] = 1;
          }
          $this->createSepaMandate($sepaParams);
          $this->removeTempData();
        }
        else {
          $contributionParams = $this->createContributionParams($temp['contact_id']);
          // check if this is a test and if so, pass param
          if ($temp['is_test'] == TRUE) {
            $contributionParams['is_test'] = 1;
          }
          $this->createNonSepa($contributionParams);
          $this->removeTempData();
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception(ts('Could not find a temporary donation with ID ') . $tempId, 3010);
    }
    return $tempId;
  }

  /**
   * Method to remove temporary data upon successfull processing
   */
  private function removeTempData() {
    if ($this->_tempId) {
      $params = [1 => [$this->_tempId, 'Integer']];
      $query = "DELETE FROM civicrm_fzfd_contribution WHERE temp_id = %1";
      CRM_Core_DAO::executeQuery($query, $params);
      $query = "DELETE FROM civicrm_fzfd_sdd_mandate WHERE temp_id = %1";
      CRM_Core_DAO::executeQuery($query, $params);
      $query = "DELETE FROM civicrm_fzfd_temp WHERE id = %1";
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Method to check if valid temp data
   *
   * @param $temp
   * @return bool
   * @throws API_Exception
   */
  private function isValidTempData($temp) {
    $mandatories = ['contact_id', 'payment_instrument_id'];
    foreach ($mandatories as $mandatory) {
      if (!isset($temp[$mandatory]) || empty($temp[$mandatory])) {
        throw new API_Exception(ts('No ') . $mandatory . ts(' in temporary data'), 3021);
      }
    }
    return TRUE;
  }

}
