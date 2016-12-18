<?php
/**
 * Created by PhpStorm.
 * User: tk
 * Date: 12/18/16
 * Time: 6:19 PM
 */

namespace Afk11\Blocktrail;

class Blocktrail
{

    const EXCEPTION_INVALID_CREDENTIALS = "Your credentials are incorrect.";
    const EXCEPTION_GENERIC_HTTP_ERROR = "An HTTP Error has occurred!";
    const EXCEPTION_GENERIC_SERVER_ERROR = "A Server Error has occurred!";
    const EXCEPTION_EMPTY_RESPONSE = "The HTTP Response was empty.";
    const EXCEPTION_UNKNOWN_ENDPOINT_SPECIFIC_ERROR = "The endpoint returned an unknown error.";
    const EXCEPTION_MISSING_ENDPOINT = "The endpoint you've tried to access does not exist. Check your URL.";
    const EXCEPTION_OBJECT_NOT_FOUND = "The object you've tried to access does not exist.";
}
