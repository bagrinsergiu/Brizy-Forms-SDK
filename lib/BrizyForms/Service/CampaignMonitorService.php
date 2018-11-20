<?php

namespace BrizyForms\Service;


use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;

class CampaignMonitorService extends Service
{

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        // TODO: Implement mapFields() method.
    }

    /**
     * @param FieldMap $fieldMap
     * @param $group_id
     * @param $data
     *
     * @return mixed
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        // TODO: Implement internalCreateMember() method.
    }

    /**
     * @return mixed
     */
    protected function internalGetGroups()
    {
        // TODO: Implement internalGetGroups() method.
    }

    /**
     * @param Group $group
     *
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {
        // TODO: Implement internalGetFields() method.
    }

    /**
     * @return bool
     */
    protected function hasValidAuthenticationData()
    {
        if ( ! $this->authenticationData ) {
            return false;
        }

        $data = $this->authenticationData->getData();
        if ( ! isset( $data['access_token'] ) || ! isset( $data['refresh_token'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    protected function initializeNativeService()
    {
        // TODO: Implement initializeNativeService() method.
    }

    /**
     * @return RedirectResponse|Response|null
     */
    public function authenticate()
    {
        $authorize_url = \CS_REST_General::authorize_url(
            CAMPAIGNMONITOR_CLIENT_ID,
            CAMPAIGNMONITOR_REDIRECT_URI,
            CAMPAIGNMONITOR_SCOPE
        );

        return new RedirectResponse( 301, "RedirectResponse", sprintf(
            $authorize_url,
            MAILCHIMP_CLIENT_ID,
            MAILCHIMP_REDIRECT_URI
        ) );
    }
}