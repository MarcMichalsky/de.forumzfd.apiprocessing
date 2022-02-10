<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Apiprocessing_Upgrader extends CRM_Apiprocessing_Upgrader_Base {

  public function install() {
		$this->executeCustomDataFile('xml/Campaign.xml');
		$this->executeCustomDataFile('xml/WeitereInformation.xml');
		$this->executeCustomDataFile('xml/Weiterbildung.xml');
		$this->executeCustomDataFile('xml/EventNew.xml');
    $this->executeCustomDataFile('xml/ParticipantNew.xml');
    new CRM_Apiprocessing_Initialize();
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  public function postInstall() {
    CRM_Apiprocessing_Config::singleton();
  }

  /**
   * Add new custom data on update 1001
   *
   * @return TRUE on success
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001');
    if (!CRM_Core_DAO::checkTableExists('civicrm_value_forumzfd_event_data_new')) {
      $this->executeCustomDataFile('xml/EventNew.xml');
    }
    if (!CRM_Core_DAO::checkTableExists('civicrm_value_fzfd_participant_data_new')) {
      $this->executeCustomDataFile('xml/ParticipantNew.xml');
    }
    return TRUE;
  }

  /**
   * Move custom group Akademie from Contact to Participant
   *
   * @return TRUE on success
   */
  public function upgrade_1002() {
    $this->ctx->log->info('Applying update 1002');
    // no action required if custom group akademie already on participant
    $queryGroup = 'SELECT COUNT(*) FROM civicrm_custom_group WHERE name = %1 AND table_name = %2 AND extends = %3';
    $count = CRM_Core_DAO::singleValueQuery($queryGroup, array(
      1 => array('fzfd_akademie_data', 'String'),
      2 => array('civicrm_value_fzfd_akademie_data', 'String'),
      3 => array('Participant', 'String'),
    ));
    if ($count == 0) {
      // remove existing custom group Akademie if necessary
      if (CRM_Core_DAO::checkTableExists('civicrm_value_fzfd_akademie_data')) {
        $queryGroupId = 'SELECT id FROM civicrm_custom_group WHERE name = %1 AND table_name = %2';
        $customGroupId = CRM_Core_DAO::singleValueQuery($queryGroupId, array(
          1 => array('fzfd_akademie_data', 'String'),
          2 => array('civicrm_value_fzfd_akademie_data', 'String'),
        ));
        // delete all fields
        $queryCustomFields = "SELECT id FROM civicrm_custom_field WHERE custom_group_id = %1";
        $daoCustomFields = CRM_Core_DAO::executeQuery($queryCustomFields, array(1 => array($customGroupId, 'Integer')));
        while ($daoCustomFields->fetch()) {
          try {
            civicrm_api3('CustomField', 'delete', array('id' => $daoCustomFields->id));
                      }
          catch (CiviCRM_API3_Exception $ex) {
            CRM_Core_Error::debug_log_message(ts('Could not remove custom field with id ') . $daoCustomFields->id
              . ts(' in ') . __METHOD__ . ts(', error from CustomField delete API: ') . $ex->getMessage());
          }
        }
        // finally remove custom group
        try {
          civicrm_api3('CustomGroup', 'delete', array('id' => $customGroupId));
        }
        catch (CiviCRM_API3_Exception $ex) {
          CRM_Core_Error::debug_log_message(ts('Could not remove custom roup with id ') . $customGroupId
            . ts(' in ') . __METHOD__ . ts(', error from CustomGroup delete API: ') . $ex->getMessage());
        }
      }
      // create new one
      $this->executeCustomDataFile('xml/Akademie.xml');
    }
    return TRUE;
  }

  /**
   * Create custom group privacy options with website consent cusom field
   *
   * @return TRUE on success
   */
  public function upgrade_1003() {
    $this->ctx->log->info('Applying update 1003');
    // check if custom group exists and create if not
    try {
      $groupCount = civicrm_api3('CustomGroup', 'getcount', [
        'name' => 'fzfd_privacy_options',
        'extends' => 'Contact',
      ]);
      if ($groupCount == 0) {
        CRM_Apiprocessing_CustomData::createPrivacyCustomGroup();
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Unexpected error in ') . __METHOD__ . ts('API CustomGroup getcount: ') . $ex->getMessage());
    }
    // check if web consent custom field exists and create if not
    try {
      $fieldCount = civicrm_api3('CustomField', 'getcount', [
        'custom_group_id' => 'fzfd_privacy_options',
        'name' => 'fzfd_website_consent',
      ]);
      if ($fieldCount == 0) {
        $result = CRM_Apiprocessing_CustomData::createWebsiteDataConsentCustomField();
        // now populate fields for all contacts
        if ($result) {
          $query = "INSERT INTO civicrm_value_contact_privacy_options
            (entity_id, fzfd_website_consent) SELECT DISTINCT id, 1 FROM civicrm_contact";
          CRM_Core_DAO::executeQuery($query);
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(ts('Unexpected error in ') . __METHOD__ . ts('API CustomField getcount: ') . $ex->getMessage());
    }
    return TRUE;
  }

  /**
   * Create new tables for temporary processing
   *
   * @return TRUE on success
   */
  public function upgrade_1010() {
    $this->ctx->log->info('Applying update 1010 - temporary tables');
    $this->executeSqlFile('sql/auto_install.sql');
    new CRM_Apiprocessing_Initialize();
    return TRUE;
  }

  /**
   * Upgrade 1015 - add fields wishes and experience to new participant data custom group
   *
   * @return bool
   */
  public function upgrade_1015() {
    $this->ctx->log->info('Applying update 1015 - wishes and experience into participant custom group');
    $newFieldsCreated = CRM_Apiprocessing_CustomData::createExpAndWishesParticipantCustomFields();
    if ($newFieldsCreated) {
      CRM_Apiprocessing_CustomData::migrateOldExperienceAndWishes();
    }
    return TRUE;
  }

  /**
   * Upgrade 1020 - add field employer to new participant data custom group
   *
   * @return bool
   */
  public function upgrade_1020() {
    $this->ctx->log->info('Applying update 1020 - employer into participant custom group');
    CRM_Apiprocessing_CustomData::createEmployerParticipantCustomFields();
    return TRUE;
  }

  /**
   * Upgrade 1021 - add is_test and facilitate test processing for contributions
   *
   * @return bool
   */
  public function upgrade_1021() {
    $this->ctx->log->info('Applying update 1021 - add is_test column');
    if (!CRM_Core_DAO::checkFieldExists('civicrm_fzfd_temp', 'is_test')) {
      $query = "ALTER TABLE civicrm_fzfd_temp ADD COLUMN `is_test` TINYINT DEFAULT 0 COMMENT 'is test donation'";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  /**
   * Upgrade 1022 - add additional fields participant new
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_1022() {
    $this->ctx->log->info('Applying update 1022 - add additional fields participant new');
    $customGroupId = CRM_Apiprocessing_Config::getNewParticipantCustomGroupId();
    if ($customGroupId) {
      $customFields = $this->getAdditionalParticipantFields();
      foreach ($customFields as $customFieldName => $customFieldData) {
        if (!CRM_Apiprocessing_CustomData::existsCustomField((int) $customGroupId, $customFieldName)) {
          CRM_Apiprocessing_CustomData::createCustomField((int) $customGroupId, $customFieldData);
        }
      }
    }
    return TRUE;
  }

  /**
   * Method to get additional fields for participant new
   *
   * @return array
   */
  private function getAdditionalParticipantFields() {
    return [
      'i_will_use_this_machine' => [
        'name' => 'i_will_use_this_machine',
        'label' => 'I will use this machine',
        'data_type' => 'Boolean',
        'html_type' => 'Radio',
        'weight' => 150,
      ],
      'browser_version' => [
        'name' => 'browser_version',
        'label' => 'Browser version',
        'data_type' => 'String',
        'html_type' => 'Text',
        'weight' => 151,
      ],
      'ping' => [
        'name' => 'ping',
        'label' => 'Ping',
        'data_type' => 'String',
        'html_type' => 'Text',
        'weight' => 152,
      ],
      'download' => [
        'name' => 'download',
        'label' => 'Download',
        'data_type' => 'String',
        'html_type' => 'Text',
        'weight' => 153,
      ],
      'upload' => [
        'name' => 'upload',
        'label' => 'Upload',
        'data_type' => 'String',
        'html_type' => 'Text',
        'weight' => 154,
      ],
    ];
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
