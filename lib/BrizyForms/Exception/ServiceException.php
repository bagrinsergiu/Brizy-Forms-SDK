<?php

namespace BrizyForms\Exception;

/**
 * Class ServiceException
 * @package BrizyForms\Exception
 */
class ServiceException extends \Exception
{
    /**
     * ServiceException constructor.
     * @param null $message
     * @param int $code
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}