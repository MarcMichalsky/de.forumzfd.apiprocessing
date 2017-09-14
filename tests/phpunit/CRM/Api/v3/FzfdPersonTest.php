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
class CRM_Api_v3_FzfdPersonTest extends CRM_Api_v3_FzfdAbstractTest {
		
	public function setUp() {
    parent::setUp();
	}
	
	public function tearDown() {
    parent::tearDown();
  }
	
	public function testPersonCreateApi() {
		$prefixes = civicrm_api3('OptionValue', 'get', array('option_group_id' => "individual_prefix", 'options' => array('limit' => 1)));
		$prefixes = reset($prefixes['values']);
			
		$personParams['prefix_id'] = $prefixes['value'];
		$personParams['formal_title'] = 'Herr Dr.';
		$personParams['job_title'] = 'Developer';
		$personParams['first_name'] = 'John';
		$personParams['last_name'] = 'Doe';
		$personParams['email'] = 'john.doe@example.com';
		$personParams['individual_addresses'] = array(
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
		$personParams['additional_data'] = 'This is additional data';
		$personParams['department'] = 'Testing department';
		$personParams['phone'] = '06 123 4678';
		$result = $this->callAPISuccess('FzfdPerson', 'create', $personParams);
		
		$contact = $this->callAPISuccessGetSingle('Contact', array(
			'email' => $personParams['email'],
			'return' => array(
				'prefix_id',
				'formal_title',
				'job_title',
				'custom_'.$this->apiConfig->getAdditionalDataCustomFieldId(),
				'custom_'.$this->apiConfig->getDepartmentCustomFieldId(),
			)
		));
		$this->assertEquals($personParams['prefix_id'], $contact['prefix_id']);
		$this->assertEquals($personParams['formal_title'], $contact['formal_title']);
		$this->assertEquals($personParams['job_title'], $contact['job_title']);
		$this->assertEquals($personParams['additional_data'], $contact['custom_'.$this->apiConfig->getAdditionalDataCustomFieldId()]);
		$this->assertEquals($personParams['department'], $contact['custom_'.$this->apiConfig->getDepartmentCustomFieldId()]);
		
		$this->callAPISuccessGetCount('Address', array('contact_id' => $contact['id']), 2);
		$this->callAPISuccessGetCount('Phone', array('contact_id' => $contact['id']), 1);
	}
}
