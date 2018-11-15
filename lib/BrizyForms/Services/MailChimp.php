<?php

namespace BrizyForms\Services;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;

class MailChimp extends Service
{
    const AUTO_GENERATE_FIELD = '_auto_generate';
    const EMAIL_FIELD         = 'email';

    /**
     * @return RedirectResponse
     */
    public function authenticate()
    {
        $login_url = MAILCHIMP_AUTH_URL."?response_type=code&client_id=%s&redirect_uri=%s";

        return new RedirectResponse(301, "RedirectResponse", sprintf(
            $login_url,
            MAILCHIMP_CLIENT_ID,
            MAILCHIMP_REDIRECT_URI
        ));
    }

    /**
     * @return array
     * @throws ServiceException
     */
    public function getGroups()
    {
        try {
            $mailChimp = new \DrewM\MailChimp\MailChimp('67392db60505618fb8d0f6f25db03ec4-us11');
            $lists     = $mailChimp->get('lists?count=30');
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        if (!isset($lists['lists'])) {
            throw new ServiceException("Invalid request");
        }

        $result = [];
        foreach ($lists['lists'] as $i => $list) {
            $group = new Group();
            $group
                ->setId($list['id'])
                ->setName($list['name']);

            $result[$i] = $group;
        }

        return $result;
    }

    /**
     * @param Group $group
     * @return array
     * @throws \Exception
     */
    public function getFields(Group $group)
    {
        try {
            $mailChimp    = new \DrewM\MailChimp\MailChimp('67392db60505618fb8d0f6f25db03ec4-us11');
            $customFields = $mailChimp->get("lists/{$group->getId()}/merge-fields?count=100");
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        if (!isset($customFields['merge_fields'])) {
            throw new ServiceException("Invalid request");
        }

        $result = [];
        foreach($customFields['merge_fields'] as $i => $customField) {
            $field = new Field();
            $field
                ->setName($customField['name'])
                ->setSlug($customField['tag'])
                ->setRequired($customField['required']);

            $result[$i] = $field;
        }

        $emailField = new Field();
        $emailField
            ->setName('Email')
            ->setSlug(self::EMAIL_FIELD)
            ->setRequired(true);

        $result = array_merge([$emailField], $result);

        return $result;
    }

    protected function internalCreateMember()
    {

    }

    protected function mapFields(FieldMap $fieldMap)
    {
        foreach ($fieldMap->toArray() as $fieldLink) {

        }
    }
}