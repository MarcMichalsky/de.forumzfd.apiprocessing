<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Apiprocessing_Upgrader extends CRM_Apiprocessing_Upgrader_Base {

  public function install() {
    $this->executeCustomDataFile('xml/Akademie.xml');
		$this->executeCustomDataFile('xml/Campaign.xml');
		$this->executeCustomDataFile('xml/WeitereInformation.xml');
		$this->executeCustomDataFile('xml/Weiterbildung.xml');
		$this->executeCustomDataFile('xml/EventNew.xml');
    $this->executeCustomDataFile('xml/ParticipantNew.xml');
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
