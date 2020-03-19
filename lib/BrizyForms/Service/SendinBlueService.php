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
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;
use SendinBlue\Client\Model\CreateAttribute;

class SendinBlueService extends Service
{
    /**
     * @var \SendinBlue\Client\Configuration
     */
    protected $config;

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existCustomFields = json_decode(json_encode($this->internalGetFields()), true);
        foreach ($fieldMap->toArray() as $fieldLink) {
            $attrSlug = strtoupper(StringUtils::getSlug($fieldLink->getSourceTitle()));
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $key_exist = array_search($attrSlug, array_column($existCustomFields, 'name'));
                if ($key_exist !== false) {
                    $fieldLink->setTarget($existCustomFields[$key_exist]['slug']);
                } else {
                    $this->_createField($attrSlug);
                    $fieldLink->setTarget($attrSlug);
                }
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

        $payload = [
            'email' => $data->getEmail(),
            'listIds' => [(int)$group_id],
            'updateEnabled' => true
        ];

        if (count($data->getFields()) > 0) {
            $payload['attributes'] = $data->getFields();
        }

        $api_instance = new \SendinBlue\Client\Api\ContactsApi(null, $this->config);
        try {
            $api_instance->createContact(new \SendinBlue\Client\Model\CreateContact($payload));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['service' => ServiceFactory::SENDINBLUE, 'method' => 'internalCreateMember']);
            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $result = [];
        foreach ($this->_getGroups($folder) as $i => $row) {
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
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {
        $result = [];
        foreach ($this->_getFields() as $i => $customField) {
            $field = new Field();
            $field
                ->setName($customField->getName())
                ->setSlug($customField->getName())
                ->setRequired(false);

            $result[$i] = $field;
        }

        $default = [
            new Field('EMAIL', ServiceConstant::EMAIL_FIELD, true),
        ];

        return array_merge($default, $result);
    }

    private function _getFields()
    {
        $api_instance = new \SendinBlue\Client\Api\AttributesApi(null, $this->config);
        $attributes = $api_instance->getAttributes()->getAttributes();

        $result = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getCategory() == 'normal') {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    private function _createField($name)
    {
        $api_instance = new \SendinBlue\Client\Api\AttributesApi(null, $this->config);
        $createAttribute = new CreateAttribute();
        $createAttribute->setType('text');

        $api_instance->createAttribute('normal', $name, $createAttribute);
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

        $this->config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        try {
            $api_instance = new \SendinBlue\Client\Api\ListsApi(null, $this->config);
            $api_instance->getLists()->getLists();
            return new Response(200, 'Successfully authenticated');
        } catch (\Exception $e) {
            return new Response(401, 'Unauthenticated');
        }
    }

    /**
     * @param Folder|null $folder
     * @return array
     * @throws ServiceException
     */
    private function _getGroups(Folder $folder = null)
    {
        try {
            $api_instance = new \SendinBlue\Client\Api\ListsApi(null, $this->config);
            if ($folder) {
                $lists = $api_instance->getFolderLists($folder->getId(),50, 0)->getLists();
            } else {
                $lists = $api_instance->getLists(50, 0)->getLists();
            }
        } catch (\Exception $e) {
            throw new ServiceException('');
        }

        return $lists;
    }

    /**
     * @param GroupData $groupData
     * @return Group|mixed
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();

        $api_instance = new \SendinBlue\Client\Api\ListsApi(null, $this->config);
        try {
            $result = $api_instance->createList(new \SendinBlue\Client\Model\CreateList([
                'name' => $data['name'],
                'folderId' => $data['folder']
            ]));
        } catch (\Exception $e) {
            throw new ServiceException('Group was not created.');
        }

        return new Group($result->getId(), $data['name']);
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function hasValidGroupData(GroupData $groupData)
    {
        $data = $groupData->getData();
        if (!isset($data['name']) || !isset($data['folder'])) {
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
        try {
            $api_instance = new \SendinBlue\Client\Api\AccountApi(null, $this->config);
            $account = $api_instance->getAccount();
        } catch (\Exception $e) {
            throw new ServiceException('Invalid request');
        }

        return new Account($account['email']);
    }

    /**
     * @return array|null
     * @throws ServiceException
     */
    protected function internalGetFolders()
    {
        try {
            $api_instance = new \SendinBlue\Client\Api\FoldersApi(null, $this->config);
            $folders = $api_instance->getFolders(50,0)->getFolders();
        } catch (\Exception $e) {
            throw new ServiceException('Invalid request');
        }

        $response = [];
        foreach ($folders as $row) {
            $response[] = new Folder($row['id'], $row['name']);
        }

        return $response;
    }

    /**
     * @return array
     * @throws ServiceException
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
                'name' => 'folder',
                'title' => 'Folder',
                'type' => 'select',
                'choices' => $this->internalGetFolders()
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
        return false;
    }
}