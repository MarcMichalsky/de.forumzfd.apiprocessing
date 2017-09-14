<?php

/**
 * FzfdMaterial.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_fzfd_campaign_Get($params) {
  return civicrm_api3_create_success(CRM_Apiprocessing_Campaign::getValues($params), $params, 'FzfdCampaign', 'Get');
}

