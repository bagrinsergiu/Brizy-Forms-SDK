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
use BrizyForms\NativeService\GetResponseNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;

class GetResponseService extends Service
{

    /**
     * @var GetResponseNativeService
     */
    private $getResponseNativeService;

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

                if (in_array($name, $this->getReservedCustomFieldName())) {
                    $fieldLink->setTarget($name);
                    continue;
                }

                $key_exist = array_search($name, array_column($existCustomFields, 'name'));

                if ($key_exist === false) {
                    $payload = [
                        "name"   => $name,
                        "type"   => "text",
                        "hidden" => false,
                        "values" => []
                    ];
                    $newCustomField = $this->getResponseNativeService->setCustomField($payload);
                    if ($this->getResponseNativeService->http_status != 201) {
                        continue;
                    }

                    $newCustomField = json_decode(json_encode($newCustomField), true);
                }

                if ($newCustomField) {
                    $tag = $newCustomField['customFieldId'];
                } else {
                    $tag = $existCustomFields[$key_exist]['customFieldId'];
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

        $mergeFields = [];
        $name = null;
        foreach ($data->getFields() as $target => $value) {
            if ($target == 'name') {
                $name = $value;
                continue;
            }
            $mergeFields[] = [
                'customFieldId' => $target,
                'value' => [$value]
            ];
        }

        if (!$name) {
            $name = explode("@", $data->getEmail());
            $name = $name[0];
        }

        $payload = [
            'email' => $data->getEmail(),
            'name'  => $name,
            'campaign' => [
                'campaignId' => $group_id
            ],
            'customFieldValues' => $mergeFields
        ];

        $member = $this->getResponseNativeService->addContact($payload);

        if ($this->getResponseNativeService->http_status != 202 && $this->getResponseNativeService->http_status != 409 && (isset($member->code) && $member->code != 1002)) {
            $this->logger->error(json_encode($member), ['service' => ServiceFactory::GETRESPONSE, 'method' => 'internalCreateMember']);
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
        $campaigns = $this->getResponseNativeService->getCampaigns();
        if ($this->getResponseNativeService->http_status != 200) {
            throw new ServiceException('Invalid request');
        }

        $campaigns = json_decode(json_encode($campaigns), true);

        $response = [];
        foreach ($campaigns as $campaign) {
            $group = new Group();
            $group
                ->setId($campaign['campaignId'])
                ->setName($campaign['name']);

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
            $field
                ->setName($customField['name'])
                ->setSlug($customField['customFieldId'])
                ->setRequired(false);

            $response[$i] = $field;
        }

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('name', 'name', false),
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
     * @return void
     */
    protected function initializeNativeService()
    {
        $data = $this->authenticationData->getData();

        $this->getResponseNativeService = new GetResponseNativeService($data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->getResponseNativeService) {
            return new Response(400, 'native service was not init');
        }

        $this->getResponseNativeService->getCampaigns();
        if ($this->getResponseNativeService->http_status != 200) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    private function _getFields()
    {
        $customFields = $this->getResponseNativeService->getCustomFields();
        if ($this->getResponseNativeService->http_status != 200) {
            throw new ServiceException('Invalid request');
        }

        $customFields = json_decode(json_encode($customFields), true);

        return $customFields;
    }

    private function getReservedCustomFieldName()
    {
        return [
            'name',
            'twitter',
            'facebook',
            'buzz',
            'myspace',
            'linkedin',
            'digg',
            'googleplus',
            'pinterest',
            'responder',
            'campaign',
            'change'
        ];
    }

    /**
     * @param GroupData $groupData
     * @return Group|mixed
     * @throws ServiceException
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        $data = $groupData->getData();
        $group = $this->getResponseNativeService->createCampaign([
            'name' => StringUtils::getSlug(strtolower($data['name']))
        ]);
        if ($this->getResponseNativeService->http_status != 201) {
            throw new ServiceException('Group was not created');
        }

        return new Group($group->campaignId, $group->name);
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
     */
    protected function internalGetAccount()
    {
        $account = $this->getResponseNativeService->accounts();

        $name = null;
        if (isset($account->email)) {
            $name = $account->email;
        }

        return new Account($name);
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