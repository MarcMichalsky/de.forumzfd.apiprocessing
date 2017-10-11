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
class CRM_Api_v3_FzfdPetitionTest extends CRM_Api_v3_FzfdAbstractTest {
	
	private $campaign_id = false;

  public function setUp() {
    parent::setUp();
		
		$this->apiSettings->set('fzfd_petition_signed_activity_type_id', $this->apiConfig->getFzfdPetitionSignedActivityTypeId());
		
		$campaign = civicrm_api3('Campaign', 'create', array('title' => 'test'));
		$this->campaign_id = $campaign['id'];
		
  }

  public function tearDown() {
    parent::tearDown();
  }
	
	/**
	 * Test the petition sign api with only the required values.
	 */
	public function testPetitionSignWithRequiredValues() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$petitionParams['first_name'] = 'John';
		$petitionParams['last_name'] = 'Doe';
		$petitionParams['email'] = 'john.doe@example.com';
		$result = $this->callAPISuccess('FzfdPetition', 'sign', $petitionParams);
		$this->assertArraySubset($subset, $result, 'Failed test to sign a petition');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $petitionParams['email']));
		$activity = $this->retrieveActivity($contact['id'], true);
	}
	
	/**
	 * Test the petition sign api with only the required values and the campaign.
	 */
	public function testPetitionSignWithRequiredValuesAndCampaign() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$petitionParams['first_name'] = 'John';
		$petitionParams['last_name'] = 'Doe';
		$petitionParams['email'] = 'john.doe@example.com';
		$petitionParams['campaign_id'] = $this->campaign_id;
		$result = $this->callAPISuccess('FzfdPetition', 'sign', $petitionParams);
		$this->assertArraySubset($subset, $result, 'Failed test to sign a petition');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $petitionParams['email']));
		$activity = $this->retrieveActivity($contact['id'], true);
		$this->assertEquals($this->campaign_id, $activity['campaign_id'], 'The activity is not linked to an campaign');
	}
	
	/**
	 * Test the petition sign api with only the required values, the campaign and an address.
	 */
	public function testPetitionSignWithRequiredValuesCampaignAndAddress() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$petitionParams['first_name'] = 'John';
		$petitionParams['last_name'] = 'Doe';
		$petitionParams['email'] = 'john.doe@example.com';
		$petitionParams['campaign_id'] = $this->campaign_id;
		$petitionParams['individual_addresses'] = array(
			array(
				'street_address' => 'Berliner Strasse 23',
				'supplemental_address_1' => 'Supplement address non billing',
				'postal_code' => '1234 AB',
				'city' => 'Köln',
				'country_iso' => 'DE',
			),
			array(
				'street_address' => 'Antwerpplaz 23',
				'supplemental_address_1' => 'Supplement address billing',
				'postal_code' => '1234 AB',
				'city' => 'Köln',
				'country_iso' => 'DE',
				'is_billing' => 1,
			)
		);
		$result = $this->callAPISuccess('FzfdPetition', 'sign', $petitionParams);
		$this->assertArraySubset($subset, $result, 'Failed test to sign a petition');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $petitionParams['email']));
		$activity = $this->retrieveActivity($contact['id'], true);
		$this->assertEquals($this->campaign_id, $activity['campaign_id'], 'The activity is not linked to an campaign');
		
		$this->callAPISuccessGetCount('Address', array('contact_id' => $contact['id']), 2);
		// Check the non billing address
		$nonBillingAddress = $this->callAPISuccessGetSingle('Address', array('contact_id' => $contact['id'], 'is_billing' => 0));
		$this->assertEquals('Berliner Strasse 23', $nonBillingAddress['street_address']);
		$this->assertEquals('Supplement address non billing', $nonBillingAddress['supplemental_address_1']);
		// Check the billing address
		$billingAddress = $this->callAPISuccessGetSingle('Address', array('contact_id' => $contact['id'], 'is_billing' => 1));
		$this->assertEquals('Antwerpplaz 23', $billingAddress['street_address']);
		$this->assertEquals('Supplement address billing', $billingAddress['supplemental_address_1']);
	}

/**
	 * Test the petition sign api with only the required values, the campaign and an organisation.
	 */
	public function testPetitionSignWithRequiredValuesCampaignAndOrganisation() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$petitionParams['first_name'] = 'John';
		$petitionParams['last_name'] = 'Doe';
		$petitionParams['email'] = 'john.doe@example.com';
		$petitionParams['campaign_id'] = $this->campaign_id;
		$petitionParams['organization_name'] = 'CiviCooP';
		$petitionParams['organization_street_address'] = 'Amsterdam Strasse 1';
		$petitionParams['organization_supplemental_address_1'] = 'Organization Supplement address';
		$petitionParams['organization_postal_code'] = '1234 Ab';
		$petitionParams['organization_city'] = 'Hall';
		$petitionParams['organization_country_iso'] = 'NL';
		$result = $this->callAPISuccess('FzfdPetition', 'sign', $petitionParams);
		$this->assertArraySubset($subset, $result, 'Failed test to sign a petition');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $petitionParams['email']));
		$organization = $this->callAPISuccessGetSingle('Contact', array('organization_name' => $petitionParams['organization_name'], 'contact_type' => 'Organization'));
		// Check the organization address
		$organizationAddress = $this->callAPISuccessGetSingle('Address', array('contact_id' => $organization['id']));
		$this->assertEquals('Amsterdam Strasse 1', $organizationAddress['street_address']);
		$this->assertEquals('Organization Supplement address', $organizationAddress['supplemental_address_1']);
		$activity = $this->retrieveActivity($organization['id'], true);
		$this->assertEquals($this->campaign_id, $activity['campaign_id'], 'The activity is not linked to an campaign');
		// Check whether a relationship between the organization and the individual is created
		$employeeOfRelationshipTypeId = $this->apiConfig->getEmployeeRelationshipTypeId();
		$relationship = $this->callAPISuccessGetSingle('Relationship', array(
			'relationship_type_id' => $employeeOfRelationshipTypeId,
			'contact_id_a' => $contact['id'],
			'contact_id_b' => $organization['id']
		));
	}

	/**
	 * Test the petition sign api with only the required values, prefix, formal_title and source
	 */
	public function testPetitionSignWithRequiredValuesAndOptionalFields() {
		$subset = array(
			'is_error' => 0,
			'count' => 1,
		);
		
		$prefixes = civicrm_api3('OptionValue', 'get', array('option_group_id' => "individual_prefix", 'options' => array('limit' => 1)));
		$prefixes = reset($prefixes['values']);
		
		$petitionParams['first_name'] = 'John';
		$petitionParams['last_name'] = 'Doe';
		$petitionParams['email'] = 'john.doe@example.com';
		$petitionParams['prefix_id'] = $prefixes['value'];
		$petitionParams['formal_title'] = 'Herr Dr.';
		$petitionParams['source'] = 'Unit test';
		$result = $this->callAPISuccess('FzfdPetition', 'sign', $petitionParams);
		$this->assertArraySubset($subset, $result, 'Failed test to sign a petition');
		$contact = $this->callAPISuccessGetSingle('Contact', array('email' => $petitionParams['email']));
		$this->assertEquals($petitionParams['prefix_id'], $contact['prefix_id']);
		$this->assertEquals($petitionParams['formal_title'], $contact['formal_title']);
		$activity = $this->retrieveActivity($contact['id'], true);
		$this->assertEquals($petitionParams['source'], $activity['subject']);
	}
	
	/**
	 * Retrieve a petition signed activity for a contact and test whether it could be found or not be found.
	 */
	private function retrieveActivity($contact_id, $testForSuccess=true) {
		$activityParams['activity_type_id'] = $this->apiSettings->get('fzfd_petition_signed_activity_type_id');
		$activityParams['contact_id'] = $contact_id;
		if ($testForSuccess) {
			return $this->callAPISuccessGetSingle('Activity', $activityParams);
		} else {
			$this->callAPIFailure('Activity', 'getsingle', $activityParams);
		}
	}
	
}
