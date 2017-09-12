<?php

/**
 * Class for ForumZFD Settings API processing to CiviCRM
 *
 * @author Erik Hommel <hommel@ee-atwork.nl>
 * @date 5 July 2017
 * @license AGPL-3.0
 */
class CRM_Apiprocessing_Settings {

	/**
	 * @var CRM_Apiprocessing_Settings
	 */ 
	private static $singleton;

  private $_settings = array();

  /**
   * CRM_Apiprocessing_Settings constructor.
   */
  public function __construct() {
    $config = CRM_Apiprocessing_Config::singleton();
			
    $container = CRM_Extension_System::singleton()->getFullContainer();
    $fileName = $container->getPath('de.forumzfd.apiprocessing').'/resources/settings.json';
    if (!file_exists($fileName)) {
      CRM_Core_Session::setStatus('Could not read the settings JSON file from resources/'.$fileName,
        'Error reading Settings', 'Error');
      return array();
    } else {
      $this->_settings = json_decode(file_get_contents($fileName), true);
    }
		
		if (empty($this->_settings['fzfd_petition_signed_activity_type_id'])) {
			$this->_settings['fzfd_petition_signed_activity_type_id'] = $config->getFzfdPetitionSignedActivityTypeId();
		}
  }
	
	/**
	 * @return CRM_Apiprocessing_Settings
	 */
	public static function singleton() {
		if (!self::$singleton) {
			self::$singleton = new CRM_Apiprocessing_Settings();
		}
		return self::$singleton;
	}

  /**
   * Getter for all or one specific setting
   *
   * @return array|mixed
   */
  public function get($key = NULL) {
    if (empty($key) || !isset($this->_settings[$key])) {
      return $this->_settings;
    } else {
      return $this->_settings[$key];
    }
  }
	
	/**
	 * Setter for settings.
	 */
	public function set($key, $value) {
		$this->_settings[$key] = $value;
	}

}