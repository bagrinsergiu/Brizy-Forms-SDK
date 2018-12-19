<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Field;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\NativeService\MailerLiteNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use Symfony\Component\Yaml\Tests\A;

class MailerLiteService extends Service
{
    /**
     * @var MailerLiteNativeService
     */
    protected $mailerLiteNativeService;

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @return FieldMap|mixed
     * @throws ServiceException
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existCustomFields = $this->_getFields();

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $key_exist = array_search($fieldLink->getSourceTitle(), array_column($existCustomFields, 'title'));
                if ($key_exist === false) {
                    $payload = [
                        'title' => $fieldLink->getSourceTitle(),
                        'type' => 'TEXT'
                    ];
                    $newCustomField = $this->mailerLiteNativeService->request('fields', 'post', $payload);
                }

                if ($newCustomField) {
                    if (!isset($newCustomField->key)) {
                        continue;
                    }
                    $target = $newCustomField->key;
                } else {
                    $target = $existCustomFields[$key_exist]['key'];
                }

                $fieldLink->setTarget($target);
            }
        }

        return $fieldMap;
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

        $mergeFields = $data->getFields();

        $name = null;
        if (isset($mergeFields['name']) && !empty($mergeFields['name'])) {
            $name = $mergeFields['name'];
            unset($mergeFields['name']);
        }

        $member = $this->mailerLiteNativeService->request("groups/{$group_id}/subscribers", 'post', [
            'name' => $name,
            'email' => $data->getEmail(),
            'fields' => $mergeFields
        ]);

        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            $this->logger->error(json_encode($member), ['service' => ServiceFactory::MAILERLITE, 'method' => 'internalCreateMember']);
            throw new ServiceException(json_encode($member));
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $groups = $this->mailerLiteNativeService->request('groups');
        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        $response = [];
        foreach ($groups as $row) {
            $group = new Group();
            $group
                ->setId($row->id)
                ->setName($row->name);

            $response[] = $group;
        }

        return $response;
    }

    /**
     * @param Group|null $group
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetFields(Group $group = null)
    {
        $customFields = $this->_getFields();

        $response = [];
        foreach ($customFields as $i => $customField) {
            $field = new Field();
            $field->setName($customField['title']);
            $field->setSlug($customField['key']);

            if ($customField['key'] == 'email') {
                $field->setRequired(true);
            } else {
                $field->setRequired(false);
            }

            $response[$i] = $field;
        }

        return $response;
    }

    /**
     * @return bool
     */
    protected function hasValidAuthenticationData()
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
     */
    protected function initializeNativeService()
    {
        $data = $this->authenticationData->getData();

        $this->mailerLiteNativeService = new MailerLiteNativeService($data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->mailerLiteNativeService) {
            return new Response(400, 'native service was not init');
        }

        $this->mailerLiteNativeService->request('groups');
        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    /**
     * @return array|bool|mixed|object|string
     * @throws ServiceException
     */
    protected function _getFields()
    {
        $fields = $this->mailerLiteNativeService->request('fields');
        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        $fields = json_decode(json_encode($fields), true);

        return $fields;
    }

    /**
     * @param GroupData $groupData
     * @return Group
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();
        $group = $this->mailerLiteNativeService->request("groups", 'post', [
            'name' => $data['name']
        ]);

        if ($this->mailerLiteNativeService->getResponseCode() != 201) {
            throw new ServiceException('Group was not created.');
        }

        return new Group($group->id, $group->name);
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function hasValidGroupData(GroupData $groupData)
    {
        $data = $groupData->getData();
        if (!isset($data['name'])) {
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
        $response = $this->mailerLiteNativeService->request('me');
        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        return new Account($response->account->email);
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
                'title' => 'Api Key',
            ]
        ];
    }

    /**
     * @return boolean
     */
    protected function internalHasConfirmation()
    {
        return false;
    }
}