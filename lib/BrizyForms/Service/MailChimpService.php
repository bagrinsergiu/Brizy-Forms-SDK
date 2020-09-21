<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\AuthenticationDataException;
use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Field;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
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
        if (!$this->mailChimpSDK) {
            return new Response(400, 'native service was not init');
        }

        $this->mailChimpSDK->get('lists?count=1');

        if (!$this->mailChimpSDK->success()) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     * @throws \Exception
     */
    protected function internalGetGroups(Folder $folder = null)
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
     * @param bool $confirmation_email
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data);

        $status = 'subscribed';
        if ($confirmation_email) {
            $status = 'pending';
        }

        $payload = [
            'email_address' => $data->getEmail(),
            'status' => $status,
        ];

        if (count($data->getFields()) > 0) {
            $payload['merge_fields'] = $data->getFields();
        }

        $member = $this->_createMember($group_id, $payload);
        if (!isset($member['id']) && $member['title'] != 'Member Exists') {
            $this->logger->error(json_encode($member), ['service' => ServiceFactory::MAILCHIMP, 'method' => 'internalCreateMember']);
            throw new ServiceException(json_encode($member));
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
        $lists = $this->mailChimpSDK->get('lists?count=100');

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

    /**
     * @param GroupData $groupData
     * @return Group
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();

        $account = $this->mailChimpSDK->get('');
        if (!isset($account['contact'])) {
            throw new ServiceException('Invalid request');
        }

        $account['contact']['address1'] = $account['contact']['addr1'];
        unset($account['contact']['addr1']);
        $account['contact']['address2'] = $account['contact']['addr2'];
        unset($account['contact']['addr2']);

        $payload = [
            'name' => $data['name'],
            'permission_reminder' => $data['reminder_message'],
            'email_type_option' => false,
            'campaign_defaults' => [
                'from_email' => $data['from_email'],
                'from_name' => $data['from_name'],
                'subject' => $data['name'],
                'language' => 'en'
            ],
            'contact' => $account['contact']
        ];

        $result = $this->mailChimpSDK->post('lists', $payload);

        if (!$this->mailChimpSDK->success()) {
            throw new ServiceException('Group was not created' . $this->mailChimpSDK->getLastError());
        }

        return new Group($result['id'], $result['name']);
    }

    /**
     * @param GroupData $groupData
     * @return bool
     */
    public function hasValidGroupData(GroupData $groupData)
    {
        $data = $groupData->getData();
        if (!isset($data['name']) ||
            !isset($data['from_name']) ||
            !isset($data['from_email']) ||
            !isset($data['reminder_message'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return Account
     * @throws ServiceException
     */
    protected function internalGetAccount()
    {
        $account = $this->mailChimpSDK->get('');
        if (!$this->mailChimpSDK->success()) {
            throw new ServiceException('Invalid request');
        }

        return new Account($account['account_name']);
    }

    /**
     * @return array|null
     */
    protected function internalGetFolders()
    {
        return null;
    }

    /**
     * @return array
     */
    protected function internalGetGroupProperties()
    {
        return [
            [
                'name' => 'name',
                'title' => 'Name',
                'type' => 'input',
                'choices' => null
            ],
            [
                'name' => 'from_name',
                'title' => 'From Name',
                'type' => 'input',
                'choices' => null
            ],
            [
                'name' => 'from_email',
                'title' => 'From Email',
                'type' => 'input',
                'choices' => null
            ],
            [
                'name' => 'reminder_message',
                'title' => 'Remind people how they signed up to your list',
                'type' => 'textarea',
                'choices' => null
            ]
        ];
    }

    /**
     * @return array
     */
    protected function internalGetAccountProperties()
    {
        return [
            [
                'name' => 'api_key',
                'title' => 'Api Key'
            ]
        ];
    }

    /**
     * @return boolean
     */
    protected function internalHasConfirmation()
    {
        return true;
    }
}