<?php

namespace BrizyForms;

use BrizyForms\Exception\ServiceException;

class ServiceFactory
{
    const MAILCHIMP = 'mailchimp';
    const MADMIMI   = 'madmimi';

    /**
     * @param $service_name
     * @return mixed
     * @throws ServiceException
     */
    public function getInstance($service_name)
    {
        if ($class = $this->getServiceClasses()[$service_name]) {
            return new $class;
        }

        throw new ServiceException('Invalid service name.');
    }

    /**
     * @return array
     */
    public function getServiceClasses()
    {
        return [
            self::MAILCHIMP => 'BrizyForms\Services\MailChimp',
            self::MADMIMI   => 'BrizyForms\Services\Madmimi'
        ];
    }
}