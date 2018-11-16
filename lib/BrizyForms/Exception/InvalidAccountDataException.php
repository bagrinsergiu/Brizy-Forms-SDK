<?php

namespace BrizyForms\Exception;

/**
 * Class InvalidAccountDataException
 * @package BrizyForms\Exception
 */
class InvalidAccountDataException extends \Exception
{
    /**
     * InvalidAccountDataException constructor.
     * @param null $message
     * @param int $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}