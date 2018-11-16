<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\MailChimp;

class MailChimpService extends Service
{
    const AUTO_GENERATE_FIELD = '_auto_generate';
    const EMAIL_FIELD         = 'email';

    /**
     * @var MailChimp
     */
    protected $mailChimp;

    public function __construct(MailChimp $mailChimp)
    {
        $this->mailChimp = $mailChimp;
    }

    /**
     * @return array
     * @throws ServiceException
     */
    public function getGroups()
    {
        try {
            $mailChimp = new \DrewM\MailChimp\MailChimp($this->mailChimp->getApiKey());
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
            $mailChimp    = new \DrewM\MailChimp\MailChimp($this->mailChimp->getApiKey());
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