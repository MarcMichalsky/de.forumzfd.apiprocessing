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
class CRM_Api_v3_FzfdAkademieTest extends CRM_Api_v3_FzfdAbstractTest {
	
	private $newsletterGroups = array();
	
  public function setUp() {
    parent::setUp();
		
		$groups = array(
			'newsletter_1',
			'newsletter_2',
			'newsletter_3',
		);
		
		$parent_group = civicrm_api3('Group', 'create', array(
			'name' => 'forumzfd_newsletters',
			'title' => 'forumzfd_newsletters',
		));
		
		for($i=0; $i<count($groups); $i++) {
			$group = civicrm_api3('Group', 'create', array(
				'name' => $groups[$i],
				'title' => $groups[$i],
				'parents' => $parent_group['id'],
			));
			$this->newsletterGroups[$i] = $group['id'];
		}
  }

  public function tearDown() {
    parent::tearDown();
  }
	
	/**
	 * Tests the Akademie Register API with only the required fields.
	 */
	public function testAkademieRegister() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
			
		$akademieParams['first_name'] = 'John';
		$akademieParams['last_name'] = 'Doe';
		$akademieParams['email'] = 'john.doe@example.com';
		$akademieParams['individual_addresses'] = array(
			array(
				'street_address' => 'Berliner Strasse 23',
				'postal_code' => '1234 AB',
				'city' => 'Köln',
				'country_iso' => 'DE',
			),
			array(
				'street_address' => 'Antwerpplaz 23',
				'postal_code' => '1234 AB',
				'city' => 'Köln',
				'country_iso' => 'DE',
				'is_billing' => 1,
			)
		);
		$akademieParams['organization_name'] = 'CiviCooP';
		$akademieParams['organization_street_address'] = 'Amsterdam Strasse 1';
		$akademieParams['organization_postal_code'] = '1234 Ab';
		$akademieParams['organization_city'] = 'Hall';
		$akademieParams['organization_country_iso'] = 'NL';
		$akademieParams['experience'] = 'Testing experience';
		$akademieParams['wishes'] = 'I would like to have a hamburger';
		$akademieParams['employer'] = 'Employer custom field';
		$akademieParams['phone'] = '06 123 4678';		
		$akademieParams['event_id'] = $event['id'];
		$akademieParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[1];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'register', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to register for an event');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		$organization = $this->callAPISuccessGetSingle('Contact', array('organization_name' => $akademieParams['organization_name'], 'contact_type' => 'Organization'));
		
		// Check whether a phone and the addresses are stored.
		$this->callAPISuccessGetCount('Address', array('contact_id' => $contact['id']), 2);
		$this->callAPISuccessGetCount('Phone', array('contact_id' => $contact['id']), 1);
		// Check the billing address
		$billingAddress = $this->callAPISuccessGetSingle('Address', array('contact_id' => $contact['id'], 'is_billing' => 1));
		$this->assertEquals('Antwerpplaz 23', $billingAddress['street_address']);
		
		// Check whether a relationship between the organization and the individual is created
		$employeeOfRelationshipTypeId = $this->apiConfig->getEmployeeRelationshipTypeId();
		$relationship = $this->callAPISuccessGetSingle('Relationship', array(
			'relationship_type_id' => $employeeOfRelationshipTypeId,
			'contact_id_a' => $contact['id'],
			'contact_id_b' => $organization['id']
		));
		
		$wishes = $this->callAPISuccessGetValue('Contact', array('id' => $contact['id'], 'return' => 'custom_'.$this->apiConfig->getWishesCustomFieldId()));
		$this->assertEquals($akademieParams['wishes'], $wishes, 'Wishes is not updated');
		
		$experience = $this->callAPISuccessGetValue('Contact', array('id' => $contact['id'], 'return' => 'custom_'.$this->apiConfig->getExperienceCustomFieldId()));
		$this->assertEquals($akademieParams['experience'], $experience, 'Experience is not updated');
		
		$employer = $this->callAPISuccessGetValue('Contact', array('id' => $contact['id'], 'return' => 'custom_'.$this->apiConfig->getEmployerCustomFieldId()));
		$this->assertEquals($akademieParams['employer'], $employer, 'Employer is not updated');
		
		$participant = $this->retrieveEventRegistration($contact['id'], $event['id']);
		$this->assertEquals($participant['participant_status_id'], $this->apiConfig->getRegisteredParticipantStatusId());
		
		// Test whether the contact is added to the newsletter group
		$this->callAPISuccessGetSingle('GroupContact', array(
			'group_id' => $this->newsletterGroups[0],
			'contact_id' => $contact['id'],
			'status' => 'Added'
		));
	}
	
	/**
	 * Test an already registered contact and see whether the activity ForumZFD API problem is created
	 */
	public function testAkademiRegisterAlreadyRegistered() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
		
		$contact = civicrm_api3('Contact', 'create', array('email' => 'tim.clarke@example.com', 'contact_type' => 'Individual'));
		$participantParams['event_id'] = $event['id'];
		$participantParams['contact_id'] = $contact['id'];
		$participantParams['status_id'] = $this->apiConfig->getRegisteredParticipantStatusId();
		$participant = civicrm_api3('Participant', 'create', $participantParams);
		$participant = civicrm_api3('Participant', 'getsingle', $participantParams);
		
		$akademieParams['first_name'] = 'Tim';
		$akademieParams['last_name'] = 'Clarke';
		$akademieParams['email'] = 'tim.clarke@example.com';
		$akademieParams['event_id'] = $event['id'];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'register', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to register for an event');
		$values = reset($result['values']);
		$this->assertEquals($participant['id'], $values['participant_id']);
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		
		$newParticipant = $this->retrieveEventRegistration($contact['id'], $event['id']);
		$this->assertEquals($participant['id'], $newParticipant['id']);
		$this->assertEquals($newParticipant['participant_status_id'], $this->apiConfig->getRegisteredParticipantStatusId());
		
		$apiProblemActivity = $this->retrieveActivity($contact['id']);
		$this->assertEquals($apiProblemActivity['subject'], 'Error message from API: ' .  ts('Request to check the data'));
	}
	
	/**
	 * Test an already registered contact with the status cancelled and see whether the activity ForumZFD API problem is created,
	 *  and whether the status of registration is updated to registered.
	 */
	public function testAkademiRegisterACancelledRegistration() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
		
		$contact = civicrm_api3('Contact', 'create', array('email' => 'james.armstrong@example.com', 'contact_type' => 'Individual'));
		$participantParams['event_id'] = $event['id'];
		$participantParams['contact_id'] = $contact['id'];
		$participantParams['status_id'] = $this->apiConfig->getCancelledParticipantStatusId();
		$participant = civicrm_api3('Participant', 'create', $participantParams);
			
		$akademieParams['first_name'] = 'James';
		$akademieParams['last_name'] = 'Armstrong';
		$akademieParams['email'] = 'james.armstrong@example.com';
		$akademieParams['event_id'] = $event['id'];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'register', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to register for an event');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		
		$newParticipant = $this->retrieveEventRegistration($contact['id'], $event['id']);
		$this->assertEquals($participant['id'], $newParticipant['id']);
		$this->assertEquals($newParticipant['participant_status_id'], $this->apiConfig->getRegisteredParticipantStatusId());
		
		$apiProblemActivity = $this->retrieveActivity($contact['id']);
		$this->assertEquals($apiProblemActivity['subject'], 'Error message from API: ' .  ts('Request to check the data'));
	}

	/**
	 * Tests the Akademie Apply API with only the required fields.
	 */
	public function testAkademieApply() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
			
		$akademieParams['first_name'] = 'John';
		$akademieParams['last_name'] = 'Doe';
		$akademieParams['email'] = 'john.doe@example.com';
		$akademieParams['individual_addresses'] = array(
			array(
				'street_address' => 'Berliner Strasse 23',
				'postal_code' => '1234 AB',
				'city' => 'Köln',
				'country_iso' => 'DE',
			),
			array(
				'street_address' => 'Antwerpplaz 23',
				'postal_code' => '1234 AB',
				'city' => 'Köln',
				'country_iso' => 'DE',
				'is_billing' => 1,
			)
		);
		$akademieParams['organization_name'] = 'CiviCooP';
		$akademieParams['organization_street_address'] = 'Amsterdam Strasse 1';
		$akademieParams['organization_postal_code'] = '1234 Ab';
		$akademieParams['organization_city'] = 'Hall';
		$akademieParams['organization_country_iso'] = 'NL';
		$akademieParams['experience'] = 'Testing experience';
		$akademieParams['wishes'] = 'I would like to have a hamburger';
		$akademieParams['employer'] = 'Employer custom field';
		$akademieParams['phone'] = '06 123 4678';		
		$akademieParams['event_id'] = $event['id'];
		$akademieParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[1];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'apply', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to apply for an event');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		$organization = $this->callAPISuccessGetSingle('Contact', array('organization_name' => $akademieParams['organization_name'], 'contact_type' => 'Organization'));
		
		// Check whether a phone and the addresses are stored.
		$this->callAPISuccessGetCount('Address', array('contact_id' => $contact['id']), 2);
		$this->callAPISuccessGetCount('Phone', array('contact_id' => $contact['id']), 1);
		// Check the billing address
		$billingAddress = $this->callAPISuccessGetSingle('Address', array('contact_id' => $contact['id'], 'is_billing' => 1));
		$this->assertEquals('Antwerpplaz 23', $billingAddress['street_address']);
		
		// Check whether a relationship between the organization and the individual is created
		$employeeOfRelationshipTypeId = $this->apiConfig->getEmployeeRelationshipTypeId();
		$relationship = $this->callAPISuccessGetSingle('Relationship', array(
			'relationship_type_id' => $employeeOfRelationshipTypeId,
			'contact_id_a' => $contact['id'],
			'contact_id_b' => $organization['id']
		));
		
		$wishes = $this->callAPISuccessGetValue('Contact', array('id' => $contact['id'], 'return' => 'custom_'.$this->apiConfig->getWishesCustomFieldId()));
		$this->assertEquals($akademieParams['wishes'], $wishes, 'Wishes is not updated');
		
		$experience = $this->callAPISuccessGetValue('Contact', array('id' => $contact['id'], 'return' => 'custom_'.$this->apiConfig->getExperienceCustomFieldId()));
		$this->assertEquals($akademieParams['experience'], $experience, 'Experience is not updated');
		
		$employer = $this->callAPISuccessGetValue('Contact', array('id' => $contact['id'], 'return' => 'custom_'.$this->apiConfig->getEmployerCustomFieldId()));
		$this->assertEquals($akademieParams['employer'], $employer, 'Employer is not updated');
		
		$participant = $this->retrieveEventRegistration($contact['id'], $event['id']);
		$this->assertEquals($participant['participant_status_id'], $this->apiConfig->getNeuParticipantStatusId());
		
		// Test whether the contact is added to the newsletter group
		$this->callAPISuccessGetSingle('GroupContact', array(
			'group_id' => $this->newsletterGroups[0],
			'contact_id' => $contact['id'],
			'status' => 'Added'
		));
	}
	
	/**
	 * Test an already registered contact and see whether the activity ForumZFD API problem is created
	 */
	public function testAkademiApplyAlreadyApplied() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
		
		$contact = civicrm_api3('Contact', 'create', array('email' => 'tim.clarke@example.com', 'contact_type' => 'Individual'));
		$participants = civicrm_api3('Participant', 'get', array('contact_id' => $contact['id']));
		$participantParams['event_id'] = $event['id'];
		$participantParams['contact_id'] = $contact['id'];
		$participantParams['status_id'] = $this->apiConfig->getNeuParticipantStatusId();
		$participant = civicrm_api3('Participant', 'create', $participantParams);
		$participants = civicrm_api3('Participant', 'get', array('contact_id' => $contact['id']));
		$participant = civicrm_api3('Participant', 'getsingle', array('id' => $participant['id']));
		
		$akademieParams['first_name'] = 'Tim';
		$akademieParams['last_name'] = 'Clarke';
		$akademieParams['email'] = 'tim.clarke@example.com';
		$akademieParams['event_id'] = $event['id'];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'apply', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to apply for an event');
		$values = reset($result['values']);
		$this->assertEquals($participant['participant_id'], $values['participant_id']);
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		
		$newParticipant = $this->retrieveEventRegistration($contact['id'], $event['id']);
		$this->assertEquals($participant['id'], $newParticipant['id'], 'Somehow a new registration record is created');
		$this->assertEquals($newParticipant['participant_status_id'], $this->apiConfig->getNeuParticipantStatusId(), 'The status of the registration is not NEU');
		
		$apiProblemActivity = $this->retrieveActivity($contact['id']);
		$this->assertEquals($apiProblemActivity['subject'], 'Error message from API: ' .  ts('Request to check the data'));
	}

	/**
	 * Test an already registered contact and see whether the activity ForumZFD API problem is created
	 */
	public function testAkademiApplyAlreadyRegistered() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
		
		$contact = civicrm_api3('Contact', 'create', array('email' => 'tim.clarke@example.com', 'contact_type' => 'Individual'));
		$participantParams['event_id'] = $event['id'];
		$participantParams['contact_id'] = $contact['id'];
		$participantParams['status_id'] = $this->apiConfig->getRegisteredParticipantStatusId();
		$participant = civicrm_api3('Participant', 'create', $participantParams);
		$participant = civicrm_api3('Participant', 'getsingle', $participantParams);
		
		$akademieParams['first_name'] = 'Tim';
		$akademieParams['last_name'] = 'Clarke';
		$akademieParams['email'] = 'tim.clarke@example.com';
		$akademieParams['event_id'] = $event['id'];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'apply', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to apply for an event');
		$values = reset($result['values']);
		$this->assertNotEquals($participant['participant_id'], $values['participant_id']);
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		
		$newParticipant = $this->retrieveEventRegistration($contact['id'], $event['id'], $this->apiConfig->getNeuParticipantStatusId());
		$this->assertNotEquals($participant['id'], $newParticipant['id']);
		$this->assertEquals($newParticipant['participant_status_id'], $this->apiConfig->getNeuParticipantStatusId());
		
		$apiProblemActivity = $this->retrieveActivity($contact['id']);
		$this->assertEquals($apiProblemActivity['subject'], 'Error message from API: ' .  ts('Request to check the data'));
	}
	
	/**
	 * Test an already registered contact with the status cancelled and see whether the activity ForumZFD API problem is created,
	 *  and whether the status of registration is updated to registered.
	 */
	public function testAkademiApplyACancelledRegistration() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$event_types = civicrm_api3('OptionValue', 'get', array('option_group_id' => 'event_type', 'options' => array('limit' => 1)));
		$event_types = reset($event_types['values']);
		$now = new DateTime();
		$now->modify('+1 week');
		$eventParams = array(
			'is_online_registration' => 1,
			'title' => 'PHP Unit Test',
			'start_date' => $now->format('Ymd His'),
			'event_type_id' => $event_types['value'],
		);	
		$event = civicrm_api3('Event', 'create', $eventParams);
		
		$contact = civicrm_api3('Contact', 'create', array('email' => 'james.armstrong@example.com', 'contact_type' => 'Individual'));
		$participantParams['event_id'] = $event['id'];
		$participantParams['contact_id'] = $contact['id'];
		$participantParams['status_id'] = $this->apiConfig->getCancelledParticipantStatusId();
		$participant = civicrm_api3('Participant', 'create', $participantParams);
			
		$akademieParams['first_name'] = 'James';
		$akademieParams['last_name'] = 'Armstrong';
		$akademieParams['email'] = 'james.armstrong@example.com';
		$akademieParams['event_id'] = $event['id'];
		
		$result = $this->callAPISuccess('FzfdAkademie', 'apply', $akademieParams);
		$this->assertArraySubset($subset, $result, 'Failed test to apply for an event');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $akademieParams['email']));
		
		$newParticipant = $this->retrieveEventRegistration($contact['id'], $event['id']);
		$this->assertEquals($participant['id'], $newParticipant['id']);
		$this->assertEquals($newParticipant['participant_status_id'], $this->apiConfig->getNeuParticipantStatusId());
		
		$apiProblemActivity = $this->retrieveActivity($contact['id']);
		$this->assertEquals($apiProblemActivity['subject'], 'Error message from API: ' .  ts('Request to check the data'));
	}
	
	/**
	 * Retrieve a an event registration.
	 */
	private function retrieveEventRegistration($contact_id, $event_id, $status_id=false) {
		$participantParams['event_id'] = $event_id;
		$participantParams['contact_id'] = $contact_id;
		if ($status_id) {
			$participantParams['status_id'] = $status_id;
		}
		return $this->callAPISuccessGetSingle('Participant', $participantParams);
	}
	
	/**
	 * Retrieve an error activity
	 */
	private function retrieveActivity($contact_id) {
		$activityParams['activity_type_id'] = $this->apiSettings->get('akademie_error_activity_type_id');
		$activityParams['contact_id'] = $contact_id;
		return $this->callAPISuccessGetSingle('Activity', $activityParams);
	}
}