<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use BrizyForms\Utils\StringUtils;

class GetResponseService extends Service
{

    /**
     * @var \GetResponse
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
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);

        $mergeFields = [];
        foreach ($data->getFields() as $target => $value) {
            $mergeFields[] = [
                'customFieldId' => $target,
                'value' => [$value]
            ];
        }

        if (isset($mergeFields['name']) && !empty($mergeFields['name'])) {
            $name = $mergeFields['name'];
            unset($mergeFields['name']);
        } else {
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

        $this->getResponseNativeService->addContact($payload);

        if ($this->getResponseNativeService->http_status != 202 && $this->getResponseNativeService->http_status != 409) {
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetGroups()
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

        $this->getResponseNativeService = new \GetResponse($data['api_key']);
    }

    /**
     * @return RedirectResponse|Response|null
     */
    public function authenticate()
    {
        return null;
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
}