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
use BrizyForms\NativeService\MailjetNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;

class MailjetService extends Service
{

    /**
     * @var MailjetNativeService
     */
    private $nativeMailjetService;

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
                $name = StringUtils::getSlug($fieldLink->getSourceTitle());
                $key_exist = array_search($name, array_column($existCustomFields, 'Name'));
                if ($key_exist === false) {
                    $body = [
                        'Datatype' => "str",
                        'Name' => $name,
                        'NameSpace' => "static",
                        'method' => 'POST'
                    ];
                    $newCustomField = $this->nativeMailjetService->contactmetadata($body);
                }

                if ($newCustomField) {
                    if ($this->nativeMailjetService->_response_code != 201) {
                        continue;
                    }
                    $newCustomField = json_decode(json_encode($newCustomField), true);
                    $tag = $newCustomField['Data'][0]['Name'];
                } else {
                    $tag = $existCustomFields[$key_exist]['Name'];
                }

                $fieldLink->setTarget($tag);
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

        if (isset($mergeFields['name']) && !empty($mergeFields['name'])) {
            $name = $mergeFields['name'];
            unset($mergeFields['name']);
        } else {
            $name = explode("@", $data->getEmail());
            $name = $name[0];
        }

        $payload = [
            'Email' => $data->getEmail(),
            'Name' => $name,
            'method' => 'POST',
            'Properties' => $data->getFields(),
            'ID' => $group_id,
            'Action' => 'addnoforce'
        ];

        $this->nativeMailjetService->contactslistManageContact($payload);
        if ($this->nativeMailjetService->_response_code != 201) {
            $this->logger->error(json_encode($this->nativeMailjetService->_response), [
                    'service' => ServiceFactory::MAILJET,
                    'method' => 'internalCreateMember'
            ]);
            throw new ServiceException(json_encode($this->nativeMailjetService->_response));
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
        foreach ($this->_getGroups() as $i => $row) {
            $group = new Group();
            $group
                ->setId($row['ID'])
                ->setName($row['Name']);

            $result[$i] = $group;
        }

        return $result;
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
            $field
                ->setName($customField['Name'])
                ->setSlug($customField['Name'])
                ->setRequired(false);

            $response[$i] = $field;
        }

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true)
        ];

        $response = array_merge($defaults, $response);

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
        if (!isset($data['api_key']) || !isset($data['secret_key'])) {
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

        $this->nativeMailjetService = new MailjetNativeService($data['api_key'], $data['secret_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->nativeMailjetService) {
            return new Response(400, 'native service was not init');
        }

        $this->nativeMailjetService->contactslist();
        if ($this->nativeMailjetService->_response_code != 200) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    /**
     * @return array
     * @throws ServiceException
     */
    private function _getGroups()
    {
        $result = $this->nativeMailjetService->contactslist();
        if ($this->nativeMailjetService->_response_code != 200) {
            throw new ServiceException('Invalid request');
        }

        $data = json_decode(json_encode($result->Data), true);

        return $data;
    }

    /**
     * @return array
     * @throws ServiceException
     */
    private function _getFields()
    {
        $result = $this->nativeMailjetService->contactmetadata();
        if ($this->nativeMailjetService->_response_code != 200) {
            throw new ServiceException('Invalid request');
        }

        $data = json_decode(json_encode($result->Data), true);

        return $data;
    }

    /**
     * @param GroupData $groupData
     * @return Group|mixed
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();

        $payload = [
            'name' => $data['name'],
            'method' => 'POST'
        ];

        $result = $this->nativeMailjetService->contactslist($payload);
        if ($this->nativeMailjetService->_response_code != 201 || !isset($result->Data[0])) {
            throw new ServiceException('Group was not created');
        }

        return new Group($result->Data[0]->ID, $result->Data[0]->Name);
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
        $result = $this->nativeMailjetService->user();
        if ($this->nativeMailjetService->_response_code != 200 || !isset($result->Data[0])) {
            throw new ServiceException('Invalid request');
        }

        return new Account($result->Data[0]->Email);
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
            ],
            [
                'name' => 'secret_key',
                'title' => 'Secret Key',
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