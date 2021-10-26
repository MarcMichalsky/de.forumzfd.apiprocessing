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

### Rechnungsadresse

### Preparing for the Invoice API

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
