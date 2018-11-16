<?php

namespace BrizyForms\Exception;

/**
 * Class AuthenticationDataException
 * @package BrizyForms\Exception
 */
class AuthenticationDataException extends \Exception
{
    /**
     * AuthenticationDataException constructor.
     * @param null $message
     * @param int $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}