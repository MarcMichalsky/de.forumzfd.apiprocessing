<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

require_once('FzfdAbstractTest.php');
/**
 * Tests the FzfdNewsletter subscribe and unsubscribe
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Api_v3_FzfdEventTest extends CRM_Api_v3_FzfdAbstractTest {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }
	
	public function testEventApi() {
		
		// Create trainers
		$trainer1 = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'John',
			'last_name' => 'Smith'
		));
		$trainer2 = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'James',
			'last_name' => 'Armstrong'
		));
		// Create ansprechers
		$ansprecher1 = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'Sarah',
			'last_name' => 'Johnson'
		));
		$ansprecher2 = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'Hannah',
			'last_name' => 'Lee'
		));
		// Create organisation
		$teilnemendeOrganisation = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Organization',
			'organization_name' => 'ForumZFD',
		));
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		
		// Create two events one with online registration and one with no online registration.
		// This way we can check if the FzfdEvent get api retrieves only the ones with online registration.
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams1['is_online_registration'] = 1;
		$eventParams1['start_date'] = $now->format('Ymd His');
		$eventParams1['custom_'.$this->apiConfig->getTrainerCustomFieldId()] = $trainer1['id'].';'.$trainer2['id'];
		$eventParams1['custom_'.$this->apiConfig->getTeilnahmeOrganisationCustomFieldId()] = $teilnemendeOrganisation['id'];
		$eventParams1['custom_'.$this->apiConfig->getAnsprechInhaltCustomFieldId()] = $ansprecher1['id'].';'.$ansprecher2['id'];
		$eventParams1['event_type_id'] = $event_types['value'];
		$eventParams1['title'] = 'Unit test - online registration';
		
		$eventParams2['is_online_registration'] = 0;
		$eventParams2['start_date'] = $now->format('Ymd His');
		$eventParams2['custom_'.$this->apiConfig->getTrainerCustomFieldId()] = $trainer1['id'].';'.$trainer2['id'];
		$eventParams2['custom_'.$this->apiConfig->getTeilnahmeOrganisationCustomFieldId()] = $teilnemendeOrganisation['id'];
		$eventParams2['custom_'.$this->apiConfig->getAnsprechInhaltCustomFieldId()] = $ansprecher1['id'].';'.$ansprecher2['id'];
		$eventParams2['event_type_id'] = $event_types['value'];
		$eventParams2['title'] = 'Unit test - no online registration';
		
		$eventParams3['is_online_registration'] = 1;
		$eventParams3['start_date'] = $now->format('Ymd His');
		$eventParams3['custom_'.$this->apiConfig->getTrainerCustomFieldId()] = $trainer1['id'].';'.$trainer2['id'];
		$eventParams3['custom_'.$this->apiConfig->getTeilnahmeOrganisationCustomFieldId()] = $teilnemendeOrganisation['id'];
		$eventParams3['custom_'.$this->apiConfig->getAnsprechInhaltCustomFieldId()] = $ansprecher1['id'].';'.$ansprecher2['id'];
		$eventParams3['event_type_id'] = $event_types['value'];

		$event1 = civicrm_api3('Event', 'create', $eventParams1);
		$event2 = civicrm_api3('Event', 'create', $eventParams2);
		$event3 = civicrm_api3('Event', 'create', $eventParams3);
		
		$this->callAPISuccessGetCount('FzfdEvent', array(), 2);
		$events = $this->callAPISuccess('FzfdEvent', 'get', array());
		$expectedEvents = array(
			'is_error' => 0,
			'count' => 2,
			'values' => array(
				0 => array(
					'event_id' => $event1['id'],
					'event_title' => 'Unit test - online registration',
					'trainer' => array(
						0 => array(
							'contact_id' => $trainer1['id'],
							'contact_name' => 'John Smith'
						),
						1 => array(
							'contact_id' => $trainer2['id'],
							'contact_name' => 'James Armstrong'
						),
					),
					'teilnahme_organisation_id' => $teilnemendeOrganisation['id'],
					'teilnahme_organisation_name' => 'ForumZFD',
					'ansprech_inhalt' => array(
						0 => array(
							'ansprech_inhalt_id' => $ansprecher1['id'],
							'ansprech_inhalt_name' => 'Sarah Johnson'
						),
						1 => array(
							'ansprech_inhalt_id' => $ansprecher2['id'],
							'ansprech_inhalt_name' => 'Hannah Lee'
						)
					),
				),
			)
		);
		$this->assertArraySubset($expectedEvents, $events);
	}
	
}