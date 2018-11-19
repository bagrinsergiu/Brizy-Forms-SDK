<?php

namespace BrizyForms;

use BrizyForms\Exception\ServiceException;
use BrizyForms\Model\AuthenticationData;

/**
 * Class ServiceFactory
 * @package BrizyForms
 */
class ServiceFactory
{
    const MAILCHIMP = 'mailchimp';
    const MADMIMI   = 'madmimi';

    /**
     * @param $service_name
     * @param AuthenticationData|null $authenticationData
     * @return mixed
     * @throws ServiceException
     */
    static public function getInstance($service_name, AuthenticationData $authenticationData = null)
    {
        if (isset(self::getServiceClasses()[$service_name])) {
            $class = self::getServiceClasses()[$service_name];
            return new $class($authenticationData);
        }

        throw new ServiceException('Invalid service name.');
    }

    /**
     * @return array
     */
    static public function getServiceClasses()
    {
        return [
            self::MAILCHIMP => 'BrizyForms\Service\MailChimpService',
            self::MADMIMI   => 'BrizyForms\Service\MadMimiService'
        ];
    }

    /**
     * @return array
     */
    static public function getServices()
    {
        return [
            self::MAILCHIMP,
            self::MADMIMI
        ];
    }
}