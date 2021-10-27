# de.forumzfd.apiprocessing
CiviCRM extension for specific ForumZFD API Processing

## Changes per oktober 2021 (version 2.8)

### Attachments for lebenslauf and bewerbungsschreiben
This change reflects the _FzfdAkademie_ _register_ API. It enables the uploading of files for 2 fields in the custom group for participants.

The change introduces an additional setting: which file extensions will be accepted. Initially the options are: .doc, .dox, .odt, .pdf and .txt.

Two additional parameters will be accepted:
* bewerbungsschreiben - corresponding with custom field with name _fzfd_bewerbungsschreiben_
* lebenslauf - corresponding with custom field with name _fzfd_lebenslauf_.

Both parameters expect an array describing an uploaded file, with at least:
>* _name_ - the name of the file, for example erikhtst.txt or sites/default/files/civicrm/custom/eriktst.pdf
>* _content_ - the actual content of the file

The core API Attachment will be used to add the uploaded files to the relevant custom fields.

The actual processing is coded in the new class `CRM_Apiprocessing_Attachment`.

### Contact find/create with XCM

The finding of or creation of an individual of organization is done in the class `CRM_Apiprocessing_Contact`. This class is called by all the other classes processing the different ForumZFD specific API calls, so this change affects all API calls where a person or organization needs to be found or created.

This now uses the API _Contact_ _getorcreate_, provided with the XCM extension, This also means that this dependency on XCM has been coded in the info.xml.

XCM deals finds or creates a contact, and deals with data changes including email, phone and a single address if there is only 1 address in the API call.
If there are more addresses in the API call, code in the class `CRM_Apiprocessing_Address` deals with this as XCM can only process 1 address.

### Rechnungsadresse

The API _FzfdAkademie_ _register_ now also accepts a parameter called _rechnungsadresse_. It expects an array with the CiviCRM address elements (street_address, supplemental_addres_1 etc.).

If this parameter is filled, an address of the location type id in the settings will be added. A setting specific to the ForumZFD API Processing is introduced for the billing location type id.

### Preparing for the Invoice API

In the long run ForumZFD expects to create an extension that deals with invoicing for the participants. Although this extension does not exist yet, the code to process the invoice has already been introduced but is partially commented out.
The first part is in the _CRM_Apiprocessing_Participant_ class:

```
// process invoice @todo uncomment once billing extension is complete
//$invoice = new CRM_Apiprocessing_Invoice();
//$invoice->processParticipantInvoice((int) $result['id'], (int) $contactId, $activity);
return $this->createApi3SuccessReturnArray($apiParams['event_id'], $contactId, $result['id'], $participantParams['status_id']);
```
Note: this means that an invoice is only created when a participant registers on the website! If a participant is manually added in the CiviCRM UI there is no link to the invoice. If we want to add that we need to replace the call above with a `postCommit` hook on the `Participant` entity.

The actual processing of the invoice is handled in the CRM_Apiprocessing_Invoice class, where the actual call to the API is commented out:

````
//civicrm_api3('FzfdInvoice', 'create', $invoiceParams);
return TRUE;
````

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
* FzfdAkademie.register
* FzfdAkademie.apply
* FzfdEvent.get
* FzfdPerson.create

To run the unit tests use the following command

   cd de.forumzfd.apiprocessing
   phpunit4 tests/phpunit/CRM/Api/v3/FzfdAllTests.php

The best way to run unit tests is to build a civicrm environment with CiviCRM Buildkit and then install this extension and CiviSepa.
