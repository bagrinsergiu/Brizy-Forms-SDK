<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\AccessDeniedException;
use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Field;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\NativeService\HubSpotNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;

class HubSpotService extends Service
{
    /**
     * @var HubSpotNativeService
     */
    protected $hubSpotNativeService;

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
                $key_exist = array_search($name, array_column($existCustomFields, 'name'));
                if ($key_exist === false) {
                    $payload = [
                        'label' => $fieldLink->getSourceTitle(),
                        'name' => $name,
                        'type' => 'string',
                        'groupName' => 'contactinformation'
                    ];
                    $newCustomField = $this->hubSpotNativeService->request('/properties/v1/contacts/properties', 'post', $payload);
                }

                if ($newCustomField) {
                    if (!isset($newCustomField->name)) {
                        continue;
                    }
                    $name = $newCustomField->name;
                } else {
                    $name = $existCustomFields[$key_exist]['name'];
                }

                $fieldLink->setTarget($name);
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

        $mergeFields = [];
        foreach ($data->getFields() as $target => $value) {
            $mergeFields['properties'][] = [
                'property' => $target,
                'value' => $value
            ];
        }

        if (empty($mergeFields)) {
            $name = explode("@", $data->getEmail());
            $mergeFields['properties'][] = [
                'property' => 'firstname',
                'value' => $name[0]
            ];
        }

        $mergeFields['properties'][] = [
            'property' => 'email',
            'value' => $data->getEmail()
        ];

        $member = $this->hubSpotNativeService->request('/contacts/v1/contact', 'post', $mergeFields);

        if ($this->hubSpotNativeService->getResponseCode() != 200 && $this->hubSpotNativeService->getResponseCode() != 409) {
            $this->logger->error(json_encode($member), ['service' => ServiceFactory::HUBSPOT, 'method' => 'internalCreateMember']);
            throw new ServiceException('Member was not created.');
        }

        if ($group_id && isset($member->vid)) {
            $addToList = $this->hubSpotNativeService->request("/contacts/v1/lists/{$group_id}/add", 'post', [
                "vids" => [
                    $member->vid
                ]
            ]);
            if ($this->hubSpotNativeService->getResponseCode() != 200) {
                $this->logger->error(json_encode($addToList), ['service' => ServiceFactory::HUBSPOT, 'method' => 'internalCreateMember']);
                //throw new ServiceException(json_encode($addToList));
            }
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     * @throws AccessDeniedException
     * @throws ServiceException
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $result = $this->hubSpotNativeService->request('/contacts/v1/lists', 'get', []);
        // if account don't support lists
        if ($this->hubSpotNativeService->getResponseCode() == 403) {
            throw new AccessDeniedException('Account don\'t support lists');
        }

        if ($this->hubSpotNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        $response = [];
        foreach ($result->lists as $list) {
            if ($list->dynamic === false) {
                $group = new Group();
                $group
                    ->setId($list->listId)
                    ->setName($list->name);

                $response[] = $group;
            }
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
            $field
                ->setName($customField['label'])
                ->setSlug($customField['name']);

            $customField['name'] == ServiceConstant::EMAIL_FIELD ? $field->setRequired(true) : $field->setRequired(false);

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

        $this->hubSpotNativeService = new HubSpotNativeService($data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->hubSpotNativeService) {
            return new Response(400, 'native service was not init');
        }

        try {
            $this->_getFields();
            return new Response(200, 'Successfully authenticated');
        } catch (\Exception $e) {
            return new Response(401, 'Unauthenticated');
        }
    }

    /**
     * @return array
     * @throws ServiceException
     */
    public function _getFields()
    {
        $existCustomFields = $this->hubSpotNativeService->request('/properties/v1/contacts/properties', 'get', []);

        if ($this->hubSpotNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request.');
        }

        return json_decode(json_encode($existCustomFields), true);
    }

    /**
     * @param GroupData $groupData
     * @return Group|mixed
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();
        $group = $this->hubSpotNativeService->request('/contacts/v1/lists', 'post', [
            'name' => $data['name']
        ]);

        if ($this->hubSpotNativeService->getResponseCode() != 200) {
            throw new ServiceException('Group was not created.');
        }

        return new Group($group->listId, $group->name);
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
        $result = $this->hubSpotNativeService->request('/integrations/v1/me', 'get', []);
        if ($this->hubSpotNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        return new Account($result->portalId);
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
