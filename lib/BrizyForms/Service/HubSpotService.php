<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\NativeService\HubSpotNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\Utils\StringUtils;

class HubSpotService extends Service
{
    /**
     * @var HubSpotNativeService
     */
    protected $hubSpotNativeService;

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
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
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);
        $data = array_merge(['email' => $data->getEmail()], $data->getFields());

        $mergeFields = [];
        foreach ($data as $target => $value) {
            $mergeFields['properties'][] = [
                'property' => $target,
                'value' => $value
            ];
        }

        $this->hubSpotNativeService->request('/contacts/v1/contact', 'post', $mergeFields);

        if ($this->hubSpotNativeService->getResponseCode() != 200 && $this->hubSpotNativeService->getResponseCode() != 409) {
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return null
     */
    protected function internalGetGroups()
    {
        return null;
    }

    /**
     * @param Group $group
     *
     * @return mixed
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
        return null;
    }

    public function _getFields()
    {
        $existCustomFields = $this->hubSpotNativeService->request('/properties/v1/contacts/properties', 'get', []);

        return json_decode(json_encode($existCustomFields), true);
    }
}
