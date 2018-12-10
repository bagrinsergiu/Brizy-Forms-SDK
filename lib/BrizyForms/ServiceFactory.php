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
    const SENDINBLUE = 'sendinblue';
    const ZAPIER = 'zapier';
    const CAMPAIGNMONITOR = 'campaignmonitor';
    const CONVERTKIT = 'convertkit';
    const ACTIVECAMPAIGN = 'activecampaign';
    const GETRESPONSE = 'getresponse';
    const MAILJET = 'mailjet';
    const EGOI = 'egoi';
    const HUBSPOT = 'hubspot';
    const MAILERLITE = 'mailerlite';
    const DRIP = 'drip';

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
            self::SENDINBLUE => 'BrizyForms\Service\SendinBlueService',
            self::ZAPIER => 'BrizyForms\Service\ZapierService',
            self::CAMPAIGNMONITOR => 'BrizyForms\Service\CampaignMonitorService',
            self::CONVERTKIT => 'BrizyForms\Service\ConvertKitService',
            self::ACTIVECAMPAIGN => 'BrizyForms\Service\ActiveCampaignService',
            self::GETRESPONSE => 'BrizyForms\Service\GetResponseService',
            self::MAILJET => 'BrizyForms\Service\MailjetService',
            self::EGOI => 'BrizyForms\Service\EgoiService',
            self::HUBSPOT => 'BrizyForms\Service\HubSpotService',
            self::MAILERLITE => 'BrizyForms\Service\MailerLiteService',
            self::DRIP => 'BrizyForms\Service\DripService'
        ];
    }

    /**
     * @return array
     */
    static public function getServices()
    {
        return [
            self::MAILCHIMP,
            self::SENDINBLUE,
            self::ZAPIER,
            self::CAMPAIGNMONITOR,
            self::CONVERTKIT,
            self::ACTIVECAMPAIGN,
            self::GETRESPONSE,
            self::MAILJET,
            self::EGOI,
            self::HUBSPOT,
            self::MAILERLITE,
            self::DRIP
        ];
    }
}