<?php

namespace BrizyForms\Exception;

/**
 * Class GroupDataException
 * @package BrizyForms\Exception
 */
class GroupDataException extends \Exception
{
    /**
     * GroupDataException constructor.
     * @param null $message
     * @param int $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}