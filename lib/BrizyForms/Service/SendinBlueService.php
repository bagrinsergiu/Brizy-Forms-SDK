<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;

class SendinBlueService extends Service
{
    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existCustomFields = $this->internalGetFields();

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $key_exist = array_search($fieldLink->getSourceTitle(), array_column($existCustomFields, 'name'));
                if ($key_exist !== false) {
                    $fieldLink->setTarget($existCustomFields[$key_exist]['slug']);
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

        $api_instance = new \SendinBlue\Client\Api\ContactsApi();
        try {
            $api_instance->createContact(new \SendinBlue\Client\Model\CreateContact([
                "email" => $data->getEmail(),
                "attributes" => $data->getFields(),
                "listIds" => [$group_id],
                'updateEnabled' => true
            ]));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['service' => ServiceFactory::SENDINBLUE, 'method' => 'internalCreateMember']);
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return array
     * @throws ServiceException
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
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {
        return [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('Last Name', 'LASTNAME', false),
            new Field('First Name', 'FIRSTNAME', false)
        ];
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

        \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        try {
            $api_instance = new \SendinBlue\Client\Api\ListsApi();
            $api_instance->getLists()->getLists();
            return new Response(200, 'Successfully authenticated');
        } catch (\Exception $e) {
            return new Response(401, 'Unauthenticated');
        }
    }

    /**
     * @return array
     * @throws ServiceException
     */
    private function _getGroups()
    {
        try {
            $api_instance = new \SendinBlue\Client\Api\ListsApi();
            $lists = $api_instance->getLists()->getLists();
        } catch (\Exception $e) {
            throw new ServiceException('');
        }

        return $lists;
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        // TODO: Implement internalCreateGroup() method.
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function hasValidGroupData(GroupData $groupData)
    {
        // TODO: Implement hasValidGroupData() method.
    }

    /**
     * @return Account
     */
    protected function internalGetAccount()
    {
        // TODO: Implement internalGetAccount() method.
    }
}