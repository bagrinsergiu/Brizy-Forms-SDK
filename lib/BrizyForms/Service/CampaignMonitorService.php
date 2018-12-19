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

/**
 * Class CampaignMonitorService
 * @package BrizyForms\Service
 */
class CampaignMonitorService extends Service
{
    /**
     * @var array
     */
    private $authData = [];

    /**
     * @var mixed
     */
    private $clients;

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @return FieldMap|mixed
     * @throws ServiceException
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        if (!$group_id) {
            throw new ServiceException("Group is required");
        }
        $campaignMonitor = $this->_getCS_RESTLists($group_id);
        $existCustomFields = $this->_getFields($campaignMonitor);
        $existCustomFields = json_decode(json_encode($existCustomFields->response), true);

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $key_exist = array_search($fieldLink->getSourceTitle(), array_column($existCustomFields, 'FieldName'));
                if ($key_exist === false) {
                    $newCustomField = $campaignMonitor->create_custom_field([
                        'FieldName' => $fieldLink->getSourceTitle(),
                        'DataType' => 'Text',
                    ]);
                }

                if ($newCustomField) {
                    if (!$newCustomField->was_successful()) {
                        continue;
                    }
                    $tag = $newCustomField->response;
                } else {
                    $tag = $existCustomFields[$key_exist]['Key'];
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

        $campaignMonitor = $this->_getCS_REST('subscriber', $group_id);

        $mergeFields = [];
        foreach ($data->getFields() as $target => $value) {
            $mergeFields[] = [
                'Key' => $target,
                'Value' => $value
            ];
        }

        $key = array_search('Name', array_column($mergeFields, 'Key'));
        if ($key !== false) {
            $payload['Name'] = $mergeFields[$key]['Value'];
            unset($mergeFields[$key]);
        }

        $payload['EmailAddress'] = $data->getEmail();
        $payload['CustomFields'] = array_values($mergeFields);
        $payload['Resubscribe'] = false;
        $payload['ConsentToTrack'] = 'Yes';

        $result = $campaignMonitor->add($payload);
        if (!$result->was_successful()) {
            $this->logger->error(json_encode($result), ['service' => ServiceFactory::CAMPAIGNMONITOR, 'method' => 'internalCreateMember']);
            throw new ServiceException(json_encode($result));
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        if (!$folder) {
            throw new ServiceException("Folder not found");
        }
        
        $campaignMonitor = $this->_getCS_REST('clients', $folder->getId());
        $lists = $campaignMonitor->get_lists();

        if (!$lists->was_successful()) {
            throw new ServiceException('Invalid request');
        }

        $response = [];
        foreach ($lists->response as $key => $listValue) {
            $group = new Group();
            $group
                ->setId($listValue->ListID)
                ->setName($listValue->Name);

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
        if (!$group) {
            throw new ServiceException("Group is required");
        }

        $campaignMonitor = $this->_getCS_RESTLists($group->getId());
        $customFields = $this->_getFields($campaignMonitor);

        $response = [];
        foreach ($customFields->response as $i => $customField) {
            $field = new Field();
            $field
                ->setName($customField->FieldName)
                ->setSlug($customField->Key)
                ->setRequired(false);

            $response[$i] = $field;
        }

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('Name', 'Name', false),
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
        if (!isset($data['api_key'])) {
            return false;
        }

        return true;
    }

    /**
     * @throws ServiceException
     */
    protected function initializeNativeService()
    {
        $data = $this->authenticationData->getData();

        $this->authData = ['api_key' => $data['api_key']];

        $this->clients = $this->_getClients();
    }

    /**
     * @param array|null $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        try {
            $this->_getClients();
            return new Response(200, 'Successfully authenticated');
        } catch (\Exception $e) {
            return new Response(401, 'Unauthenticated');
        }
    }

    /**
     * @param $type
     * @param null $id
     * @return \CS_REST_Clients|\CS_REST_General|\CS_REST_Lists|\CS_REST_Subscribers
     * @throws ServiceException
     */
    private function _getCS_REST($type, $id = null)
    {
        switch ($type) {
            case 'general':
                $wrap = new \CS_REST_General($this->authData);
                break;
            case 'clients':
                $wrap = new \CS_REST_Clients($id, $this->authData);
                break;
            case 'lists':
                $wrap = new \CS_REST_Lists($id, $this->authData);
                break;
            case 'subscriber':
                $wrap = new \CS_REST_Subscribers($id, $this->authData);
                break;
            case 'fields':
                $wrap = new \CS_REST_Lists($id, $this->authData);
                break;
            default:
                throw new ServiceException('Invalid CS REST');
        }

        return $wrap;
    }

    /**
     * @return array
     * @throws ServiceException
     */
    private function _getClients()
    {
        $campaignMonitor = $this->_getCS_REST('general');
        $clients = $campaignMonitor->get_clients();

        if ($clients->was_successful()) {
            $response = [];
            foreach ($clients->response as $key => $value) {
                $response[$value->ClientID] = $value->Name;
            }

            return $response;
        }

        throw new ServiceException(json_encode($clients->response));
    }

    /**
     * @param $group_id
     * @return \CS_REST_Clients|\CS_REST_General|\CS_REST_Lists|\CS_REST_Subscribers
     * @throws ServiceException
     */
    private function _getCS_RESTLists($group_id)
    {
        return $this->_getCS_REST('fields', $group_id);
    }

    /**
     * @param \CS_REST_Lists $campaignMonitor
     * @return \CS_REST_Wrapper_Result
     * @throws ServiceException
     */
    private function _getFields(\CS_REST_Lists $campaignMonitor)
    {
        $customFields = $campaignMonitor->get_custom_fields();
        if (!$customFields->was_successful()) {
            throw new ServiceException('Fields not found.');
        }

        return $customFields;
    }

    /**
     * @param GroupData $groupData
     * @return Group|mixed
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();

        $campaignMonitor = $this->_getCS_REST('lists', $data['folder']);
        $list = $campaignMonitor->create($data['folder'], [
            'Title' => $data['name']
        ]);

        if (!$list->was_successful()) {
            throw new ServiceException('Invalid request');
        }

        return new Group($list->response, $data['name']);
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
     */
    protected function internalGetAccount()
    {
        $data = $this->authenticationData->getData();

        return new Account(StringUtils::masking($data['api_key']));
    }

    /**
     * @return array|null
     */
    protected function internalGetFolders()
    {
        $response = [];
        foreach ($this->clients as $clientId => $clientValue) {
            $response[] = new Folder($clientId, $clientValue);
        }

        return $response;
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