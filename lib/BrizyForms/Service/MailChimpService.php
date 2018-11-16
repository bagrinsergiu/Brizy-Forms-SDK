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
     * @throws \Exception
     */
    public function getGroups()
    {
        $result = [];
        foreach ($this->_getGroups() as $i => $row) {
            $group = new Group();
            $group
                ->setId($row['id'])
                ->setName($row['name']);

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
        $result = [];
        foreach($this->_getFields($group->getId()) as $i => $customField) {
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

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     * @return FieldMap|mixed
     * @throws \Exception
     */
    protected function mapFields(FieldMap $fieldMap, $group_id)
    {
        $existCustomFields = $this->_getFields($group_id);

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == self::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $name           = strip_tags($fieldLink->getSourceTitle());
                $key_exist      = array_search($name, array_column($existCustomFields, 'name'));
                if ($key_exist === false) {
                    $payload = [
                        'name' => $name,
                        'type' => 'text'
                    ];
                    $newCustomField = $this->_createField($group_id, $payload);
                }

                if ($newCustomField) {
                    if (!isset($newCustomField['tag'])) {
                        continue;
                    }
                    $tag = $newCustomField['tag'];
                } else {
                    $tag = $existCustomFields[$key_exist]['tag'];
                }

                $fieldLink->setTarget($tag);
            }
        }

        return $fieldMap;
    }

    /**
     * @param $list
     * @return mixed
     * @throws \Exception
     */
    private function _getFields($list)
    {
        try {
            $mailChimp = new \DrewM\MailChimp\MailChimp($this->mailChimp->getApiKey().'-'.$this->mailChimp->getDC());
            $customFields = $mailChimp->get("lists/{$list}/merge-fields?count=100");
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        if (!isset($customFields['merge_fields'])) {
            throw new ServiceException("Invalid request");
        }

        return $customFields['merge_fields'];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function _getGroups()
    {
        try {
            $mailChimp = new \DrewM\MailChimp\MailChimp($this->mailChimp->getApiKey().'-'.$this->mailChimp->getDC());
            $lists = $mailChimp->get('lists?count=30');
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        if (!isset($lists['lists'])) {
            throw new ServiceException("Invalid request");
        }

        return $lists['lists'];
    }

    /**
     * @param $group_id
     * @param $payload
     * @return array|false
     * @throws \Exception
     */
    private function _createField($group_id, $payload)
    {
        try {
            $mailChimp = new \DrewM\MailChimp\MailChimp($this->mailChimp->getApiKey().'-'.$this->mailChimp->getDC());
            $newCustomField = $mailChimp->post("lists/{$group_id}/merge-fields", $payload);
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }

        return $newCustomField;
    }
}