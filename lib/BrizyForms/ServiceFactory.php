<?php

namespace BrizyForms;

use BrizyForms\Exception\ServiceException;

/**
 * @todo need refactoring or remove it
 * Class ServiceFactory
 * @package BrizyForms
 */
class ServiceFactory
{
    const MAILCHIMP = 'mailchimp';
    const MADMIMI   = 'madmimi';

    /**
     * @param $service_name
     * @return mixed
     * @throws ServiceException
     */
    static public function getInstance($service_name)
    {
        if (isset(self::getServiceClasses()[$service_name])) {
            $class = self::getServiceClasses()[$service_name];
            return new $class;
        }

        throw new ServiceException('Invalid service name.');
    }

    /**
     * @return array
     */
    static public function getServiceClasses()
    {
        return [
            self::MAILCHIMP => 'BrizyForms\Services\MailChimpService',
            self::MADMIMI   => 'BrizyForms\Services\MadMimiService'
        ];
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return [
            self::MAILCHIMP,
            self::MADMIMI
        ];
    }
}