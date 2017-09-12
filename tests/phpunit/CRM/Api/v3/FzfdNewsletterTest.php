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
class CRM_Api_v3_FzfdNewsletterTest extends CRM_Api_v3_FzfdAbstractTest implements HeadlessInterface, TransactionalInterface {
	
	private $newsletterGroups = array();
	
	private $new_contact_group_id;
	
	protected $_apiversion = 3;
	
	/**
	 * @var CRM_Apiprocessing_Settings
	 */
	protected $apiSettings;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
			->install('org.project60.sepa')
      ->installMe(__DIR__)
      ->apply();
  }

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
		
		$this->createLoggedInUser();
		
		$apiConfig = CRM_Apiprocessing_Config::singleton();
		$this->apiSettings = CRM_Apiprocessing_Settings::singleton();
		$new_contact_group = civicrm_api3('Group', 'create', array(
			'name' => 'forumzfd_new_contacts',
			'title' => 'forumzfd_new_contacts',
		));
		$this->new_contact_group_id = $new_contact_group['id'];
		
		// Fake API settings as the settings in the JSON file does not reflect the data in the test database.
		$this->apiSettings->set('new_contacts_group_id', $new_contact_group['id']);
		$this->apiSettings->set('forumzfd_error_activity_type_id', $apiConfig->getForumzfdApiProblemActivityTypeId());
		$this->apiSettings->set('forumzfd_error_activity_assignee_id', CRM_Core_Session::singleton()->get('userID'));
  }

  public function tearDown() {
    parent::tearDown();
  }
  
  /**
	 * Test a subscribe action to multiple newsletters for a new contact.
	 * This test also tests whether a contact is added to the group for new contacts
	 */
  public function testSubscribeCreateNewContact() {
  	$subset = array(
  		'is_error' => 0,
  		'count' => 2,
  		'values' => array(),
		);
		
  	$apiParams['first_name'] = 'John';
		$apiParams['last_name'] = 'Smith';
		$apiParams['email'] = 'john.smith@example.com';
		$apiParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		
		$result = civicrm_api3('FzfdNewsletter', 'subscribe', $apiParams);
		$this->assertArraySubset($subset, $result, 'Failed test to subscribe a new contact');
		
		$contact_id = $this->callAPISuccessGetValue('Contact', array('email' => 'john.smith@example.com', 'return' => 'id'));
		// Test whether the contact is added to the group for new contacts.
		$this->callAPISuccessGetSingle('GroupContact', array(
			'group_id' => $this->new_contact_group_id,
			'contact_id' => $contact_id,
			'status' => 'Added'
		));
  }
	
	/**
	 * Test a subscribe action to multiple newsletters for an existing contact.
	 */
	public function testSubscribeWithExistingContact() {
		$contact = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'James',
			'last_name' => 'Armstrong',
			'email' => 'james.armstrong@example.com'
		));
		
		$subset = array(
  		'is_error' => 0,
  		'count' => 2,
  		'values' => array(),
		);
		
  	$apiParams['first_name'] = 'James';
		$apiParams['last_name'] = 'Armstrong';
		$apiParams['email'] = 'james.armstrong@example.com';
		$apiParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		
		$result = civicrm_api3('FzfdNewsletter', 'subscribe', $apiParams);
		$this->assertArraySubset($subset, $result, 'Failed test to subscribe an existing contact');
	}
	
	/**
	 * Test a subscribe action to multiple newsletters for an existing contacst. 
	 * The result is that it should create a new contact and create an error activity
	 */
	public function testSubscribeWithExistingMultipleContacts() {
		// Turn off the setting for adding new contacts to a  group. 
		$this->apiSettings->set('new_contacts_group_id', '');
		
		$contact1 = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'Tim',
			'last_name' => 'Fuller',
			'email' => 'tim@example.com'
		));
		$contact2 = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'Tim',
			'last_name' => 'fuler',
			'email' => 'tim@example.com'
		));
		
		$subset = array(
  		'is_error' => 0,
  		'count' => 2,
  		'values' => array(),
		);
		
  	$apiParams['first_name'] = 'Timothy';
		$apiParams['last_name'] = 'Fuller';
		$apiParams['email'] = 'tim@example.com';
		$apiParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		
		$result = civicrm_api3('FzfdNewsletter', 'subscribe', $apiParams);
		$this->assertArraySubset($subset, $result, 'Failed test to subscribe a new contact which already exists in the system');
		
		// A get single should fail as we have three contacts with this email address in the system.
		$contact = $this->callAPIFailure('Contact', 'getsingle', array('email' => 'tim@example.com', 'return' => 'id'));
		// A get single with the first and last name should succeed as we have only one with a first and last name in the database.
		$contact = $this->callAPISuccessGetSingle('Contact', array(
			'email' => 'tim@example.com',
			'first_name' => 'Timothy',
			'last_name' => 'Fuller', 
			'return' => 'id'
		));
		// Test whether the contact is added to the group for new contacts. It should not have been added.
		$this->callAPIFailure('GroupContact', 'getsingle', array(
			'group_id' => $this->new_contact_group_id,
			'contact_id' => $contact['id'],
			'status' => 'Added'
		));
		
		$this->apiSettings->set('new_contacts_group_id', $this->new_contact_group_id);
	}
	
	/**
	 * Test a subscribe action to multiple non existing newsletters for a new contact.
	 */
  public function testSubscribeCreateNewContactNonExistingNewsletters() {		
  	$apiParams['first_name'] = 'Tom';
		$apiParams['last_name'] = 'Johnson';
		$apiParams['email'] = 'tom@example.com';
		$apiParams['newsletter_ids'] = '12345;6789';
		
		$result = $this->callAPIFailure('FzfdNewsletter', 'subscribe', $apiParams);
  }
	
	/**
	 * Test unsubscribe api for existing contact.
	 */
	 public function testUnsubscribeExistingContact() {
		$subset = array(
			'count' => 2,
			'values' => array(),
			'is_error' => 0,
		);	 		
		$contactParams['first_name'] = 'Claire';
		$contactParams['last_name'] = 'Baker';
		$contactParams['email'] = 'claire.baker@example.com';
		$contactParams['contact_type'] = 'Individual';
		$contact = civicrm_api3('Contact', 'create', $contactParams);
	 		
		$subscribeParams['first_name'] = 'Claire';
		$subscribeParams['last_name'] = 'Baker';
		$subscribeParams['email'] = 'claire.baker@example.com';
		$subscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		civicrm_api3('FzfdNewsletter', 'Subscribe', $subscribeParams);
	 		
		$unsubscribeParams['email'] = 'claire.baker@example.com';
		$unsubscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[1];
		$result = civicrm_api3('FzfdNewsletter', 'Unsubscribe', $unsubscribeParams);
		$this->assertArraySubset($subset, $result, 'Failed test to unsubscribe an existing contact');
	 }

	/**
	 * Test unsubscribe api for non existing contact.
	 */
	 public function testUnsubscribeNonExistingContact() {
		$unsubscribeParams['email'] = 'hannah.baker@example.com';
		$unsubscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[1];
		$this->callAPIFailure('FzfdNewsletter', 'Unsubscribe', $unsubscribeParams);
	 }
	 
	 /**
	  * Test the subscribe and unsubscribe api with the contact hash.
	  */
	public function testSubscribeAndUnsubscribeWithHash() {
		$contact = civicrm_api3('Contact', 'create', array(
			'contact_type' => 'Individual',
			'first_name' => 'Peter',
			'last_name' => 'Fisher',
			'email' => 'peter.fisher@example.com'
		));
		$contact_hash = civicrm_api3('Contact', 'getvalue', array('id' => $contact['id'], 'return' => 'hash'));

		// Test whether subscribe with an invalid hash is failing.
		$subscribeParams['first_name'] = 'Peter';
		$subscribeParams['last_name'] = 'Fisher';
		$subscribeParams['email'] = 'peter.fisher@example.com';
		$subscribeParams['contact_hash'] = $contact_hash.'abcd'; // make it an invalid hash
		$subscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		$this->callAPIFailure('FzfdNewsletter', 'Subsubscribe', $subscribeParams);
		
		// Test whether the subscribe with a valid hash is succeeding.
		$subscribeSubset = array(
  		'is_error' => 0,
  		'count' => 2,
  		'values' => array(),
		);
		$subscribeParams = array();
  	$subscribeParams['first_name'] = 'Peter';
		$subscribeParams['last_name'] = 'Fisher';
		$subscribeParams['email'] = 'peter.fisher@example.com';
		$subscribeParams['contact_hash'] = $contact_hash;
		$subscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		$result = civicrm_api3('FzfdNewsletter', 'subscribe', $subscribeParams);
		$this->assertArraySubset($subscribeSubset, $result, 'Failed test to subscribe an existing contact');
		$this->callAPISuccessGetSingle('GroupContact', array(
			'group_id' => $this->newsletterGroups[0],
			'contact_id' => $contact['id'],
			'status' => 'Added'
		));
		
		// Test whether unsubscribe with an invalid hash is failing.
		$unsubscribeParams = array();
		$unsubscribeParams['email'] = 'peter.fisher@example.com';
		$unsubscribeParams['contact_hash'] = $contact_hash.'abcf'; // make it an invalid hash.
		$unsubscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[1];
		$this->callAPIFailure('FzfdNewsletter', 'Unsubscribe', $unsubscribeParams); 
		
		// Test whether unsubscribe with a valid hash is succeeding.
		$unsubscribeSubset = array(
			'count' => 2,
			'values' => array(),
			'is_error' => 0,
		);
		$unsubscribeParams = array();
		$unsubscribeParams['email'] = 'peter.fisher@example.com';
		$unsubscribeParams['contact_hash'] = $contact_hash;
		$unsubscribeParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[1];
		$result = civicrm_api3('FzfdNewsletter', 'unsubscribe', $unsubscribeParams);
		$this->assertArraySubset($unsubscribeSubset, $result, 'Failed test to unsubscribe an existing contact');
		$this->callAPISuccessGetSingle('GroupContact', array(
			'group_id' => $this->newsletterGroups[0],
			'contact_id' => $contact['id'],
			'status' => 'Removed'
		));
		
	}  
	
	
}
