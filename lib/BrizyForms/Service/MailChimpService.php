<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\AuthenticationDataException;
use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;

class MailChimpService extends Service
{
    /**
     * @var \DrewM\MailChimp\MailChimp
     */
    protected $mailChimpSDK;

    /**
     * @return bool
     */
    public function hasValidAuthenticationData()
    {
        if (!$this->authenticationData) {
            return false;
        }

        $data = $this->authenticationData->getData();
        if (!isset($data['api_key'])) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws AuthenticationDataException
     */
    public function initializeNativeService()
    {
        try {
            $data = $this->authenticationData->getData();
            $this->mailChimpSDK = new \DrewM\MailChimp\MailChimp($data['api_key']);
        } catch (\Exception $e) {
            throw new AuthenticationDataException('Can\'t initialize native service');
        }
    }

    /**
     * @param array|null $options
     * @return RedirectResponse|\BrizyForms\Model\Response|null
     */
    public function authenticate(array $options = null)
    {
        return null;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function internalGetGroups()
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
     *
     * @return array
     * @throws \Exception
     */
    protected function internalGetFields(Group $group = null)
    {

        if (!$group) {
            throw new ServiceException('Group must be defined');
        }

        $result = [];
        foreach ($this->_getFields($group->getId()) as $i => $customField) {
            $field = new Field();
            $field
                ->setName($customField['name'])
                ->setSlug($customField['tag'])
                ->setRequired($customField['required']);

            $result[$i] = $field;
        }

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true)
        ];

        $result = array_merge($defaults, $result);

        return $result;
    }

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);

        $payload = [
            'email_address' => $data->getEmail(),
            'status' => 'pending',
        ];

        if (count($data->getFields()) > 0) {
            $payload['merge_fields'] = $data->getFields();
        }

        $member = $this->_createMember($group_id, $payload);
        if (!isset($member['id']) && $member['title'] != 'Member Exists') {
            $this->logger->error(json_encode($member), ['service' => ServiceFactory::MAILCHIMP, 'method' => 'internalCreateMember']);
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return FieldMap|mixed
     * @throws \Exception
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existCustomFields = $this->_getFields($group_id);

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $name = strip_tags($fieldLink->getSourceTitle());
                $key_exist = array_search($name, array_column($existCustomFields, 'name'));
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
     * @param $group_id
     *
     * @return mixed
     * @throws \Exception
     */
    private function _getFields($group_id)
    {
        $customFields = $this->mailChimpSDK->get("lists/{$group_id}/merge-fields?count=100");

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
        $lists = $this->mailChimpSDK->get('lists?count=30');

        if (!isset($lists['lists'])) {
            throw new ServiceException("Invalid request");
        }

        return $lists['lists'];
    }

    /**
     * @param $group_id
     * @param $payload
     *
     * @return array|false
     */
    private function _createField($group_id, $payload)
    {
        return $this->mailChimpSDK->post("lists/{$group_id}/merge-fields", $payload);
    }

    /**
     * @param $group_id
     * @param $payload
     *
     * @return array|false
     */
    private function _createMember($group_id, $payload)
    {
        return $this->mailChimpSDK->post("lists/{$group_id}/members", $payload);
    }
}