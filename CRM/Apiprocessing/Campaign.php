<?php

/**
 * Class for ForumZFD Campaign API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 13 September 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Campaign {

  /**
   * Method to get campaigns (including custom field for on line)
   *
   * @param $params
   * @return array
   */
  public static function getValues($params) {
    $fzfdCampaigns = array();
    // retrieve custom field id for campaign on line
    $customFieldId = 'custom_'.CRM_Apiprocessing_Config::singleton()->getCampaignOnLineCustomFieldId();
    // make sure only on line available campaigns are retrieved
    $params[$customFieldId] = 1;
    $campaigns = civicrm_api3('Campaign', 'get', $params);
    foreach ($campaigns['values'] as $campaign) {
      if (isset($campaign[$customFieldId])) {
        unset($campaign[$customFieldId]);
        $fzfdCampaigns[] = $campaign;
      }
    }
    return $fzfdCampaigns;
  }
}