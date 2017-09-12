# de.forumzfd.apiprocessing
CiviCRM extension for specific ForumZFD API Processing

## Description of the API

See redmine of ForumZFD

## Requirements

* CiviCRM 4.7
* CiviSepa (org.project60.sepa)

## Unit tests

This extension ships with unit tests for the following API's:

* FzfdNewsletter.subscribe
* FzfdNewsletter.unsubscribe
* FzfdPetition.sign
* FzfdAkademi.register
* FzfdEvent.get

To run the unit tests use the following command

   cd de.forumzfd.apiprocessing
   phpunit4 tests/phpunit/CRM/Api/v3/FzfdAllTests.php
   
The best way to run unit tests is to build a civicrm environment with CiviCRM Buildkit and then install this extension and CiviSepa.