<?php

/**
 * Class with generic extension helper methods
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 3 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Utils {

  /**
   * Method to store newsletter ids from string in array
   *
   * @param string $newsletterIdsString
   * @return array
   */
  public static function storeNewsletterIds($newsletterIdsString) {
    $newsletterIds = array();
    $ids = explode(";", $newsletterIdsString);
    foreach ($ids as $key => $value) {
      $newsletterIds[] = trim($value);
    }
    return $newsletterIds;
  }

}