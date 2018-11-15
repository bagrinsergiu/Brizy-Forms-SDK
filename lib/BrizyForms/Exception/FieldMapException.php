<?php

namespace BrizyForms\Exception;

/**
 * Class FieldMapException
 * @package BrizyForms\Exception
 */
class FieldMapException extends \Exception
{
    /**
     * FieldMapException constructor.
     * @param null $message
     * @param int $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}