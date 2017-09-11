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
	
	protected $_apiversion = 3;

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
  }

  public function tearDown() {
    parent::tearDown();
  }
  
  /**
	 * Test a subscribe action to multiple newsletters for a new contact.
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
		
  	$apiParams['first_name'] = 'Tim';
		$apiParams['last_name'] = 'Fuller';
		$apiParams['email'] = 'tim@example.com';
		$apiParams['newsletter_ids'] = $this->newsletterGroups[0].';'.$this->newsletterGroups[2];
		
		$result = civicrm_api3('FzfdNewsletter', 'subscribe', $apiParams);
		$this->assertArraySubset($subset, $result, 'Failed test to subscribe a new contact which already exists in the system');
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
	
	
}
