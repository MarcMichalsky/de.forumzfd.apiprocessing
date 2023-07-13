<?php

/**
 * A simple custom exception that indicates a problem in de.forumzfd.apiprocessing
 */
class CRM_Apiprocessing_Exceptions_ParticipantAttachmentException extends CRM_Apiprocessing_Exceptions_BaseException
{

    public const ERROR_CODE_COULD_NOT_ATTACH_FILE = "could_not_attach_file";
    public const ERROR_CODE_NOT_AN_ARRAY = "not_an_array";

}
