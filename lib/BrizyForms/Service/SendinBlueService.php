<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;

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
     * @param $group_id
     * @param array $data
     * @return mixed|void
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);

        $api_instance = new \SendinBlue\Client\Api\ContactsApi();
        try {
            $api_instance->createContact(new \SendinBlue\Client\Model\CreateContact([
                "email" => $data->getEmail(),
                "attributes" => $data->getFields(),
                "listid" => [$group_id]
            ]));
        } catch (\Exception $e) {
            //@todo save logs
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
            0 => [
                'name' => 'Email',
                'slug' => ServiceConstant::EMAIL_FIELD,
                'required' => true
            ],
            1 => [
                'name' => 'Last Name',
                'slug' => 'LASTNAME',
                'required' => false
            ],
            2 => [
                'name' => 'First Name',
                'slug' => 'FIRSTNAME',
                'required' => false
            ]
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
     * @return RedirectResponse|Response|null
     */
    public function authenticate()
    {
        return null;
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
}