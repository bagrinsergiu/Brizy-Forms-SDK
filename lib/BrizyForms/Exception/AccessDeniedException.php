<?php

namespace BrizyForms\Exception;

/**
 * Class AccessDeniedException
 * @package BrizyForms\Exception
 */
class AccessDeniedException extends \Exception
{
    /**
     * AccessDeniedException constructor.
     * @param null $message
     * @param int $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}