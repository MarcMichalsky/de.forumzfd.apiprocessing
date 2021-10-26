<?php

use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * Class for ForumZFD Invoicce API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 26 Oct 2021
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Invoice {

  /**
   * Method to process invoice
   *
   * @param int $participantId
   * @param int $contactId
   * @param CRM_Apiprocessing_Activity $activity
   * @return false|int
   */
  public function processParticipantInvoice(int $participantId, int $contactId, CRM_Apiprocessing_Activity $activity) {
    if (!empty($participantId) && !empty($contactId)) {
      $address = new CRM_Apiprocessing_Address();
      $invoiceParams = [
        'participant_id' => $participantId,
        'contact_id' => $contactId,
      ];
      $primaryAddress = $address->getAddress($contactId, TRUE);
      if ($primaryAddress) {
        $invoiceParams['primary_address'] = $primaryAddress;
      }
      $billingAddress = $address->getAddress($contactId, FALSE, CRM_Apiprocessing_Settings::singleton()->get('fzfd_billing_location_type'));
      if ($billingAddress) {
        $invoiceParams['invoice_address'] = $billingAddress;
      }
      //civicrm_api3('FzfdInvoice', 'create', $invoiceParams);
      return TRUE;
    }
    else {
      $activity->createNewErrorActivity('akademie', E::ts("Could not create invoice as either participant id or contact id is empty in ") . __METHOD__, [
        'participant_id' => $participantId,
        'contact_id' => $contactId,
      ]);
    }
    return FALSE;
  }

}
