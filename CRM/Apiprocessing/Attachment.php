<?php

use CRM_Apiprocessing_ExtensionUtil as E;

/**
 * Class for ForumZFD Attachment API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 25 Oct 2021
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Attachment {

  private $_file;

  /**
   * @param array $file
   */
  public function __construct(array $file) {
    $this->_file = $file;
  }

  /**
   * Method to add attachment to civicrm
   *
   * @param int $entityId
   * @param string $fieldName
   * @return false|int
   */
  public function addToCivi(int $entityId, string $fieldName) {
    if ($this->isValidFile()) {
      $mimeType = $this->getMimeType();
      if ($mimeType && $this->isValidMimeType($mimeType)) {
        try {
          $result = civicrm_api3('Attachment', 'create', [
            'name' => $this->_file['name'],
            'mime_type' => $mimeType,
            'entity_id' => $entityId,
            'field_name' => $fieldName,
            'content' => $this->_file['content'],
          ]);
          if ($result['id']) {
            return (int) $result['id'];
          }
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(E::ts("Could not create attachment in ") . __METHOD__ . E::ts(" with file name ")
            . $this->_file['name'] . E::ts(" and mime type ") . $mimeType);
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to get the mime type of the uploaded file
   *
   * @return false|string
   */
  public function getMimeType() {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $this->_file['name']);
    if ($mimeType) {
      return $mimeType;
    }
    return FALSE;
  }

  /**
   * Method to determine if upload is valid
   *
   * @param string $mimeType
   * @return bool
   */
  public function isValidMimeType(string $mimeType) {
    if (!empty($mimeType)) {
      $valids = CRM_Apiprocessing_Attachment::getValidUploads();
      foreach ($valids as $key => $ext) {
        if ($mimeType == $key) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to check that file has valid elements
   *
   * @return bool
   */
  public function isValidFile() {
    if (!isset($this->_file['content']) || !isset($this->_file['name'])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Method to get a list of valid uploads (mime_type => extension)
   *
   * @return string[]
   */
  public static function getValidUploads() {
    return [
      'application/msword' => '.doc',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
      'application/vnd.oasis.opendocument.text' => '.odt',
      'application/pdf' => '.pdf',
      'text/plain' => '.txt',
    ];
  }

}
