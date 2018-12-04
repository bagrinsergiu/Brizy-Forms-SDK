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

        $member = $this->hubSpotNativeService->request('/contacts/v1/contact', 'post', $mergeFields);

        if ($this->hubSpotNativeService->getResponseCode() != 200 && $this->hubSpotNativeService->getResponseCode() != 409) {
            $this->logger->error(json_encode($member), ['service' => ServiceFactory::HUBSPOT, 'method' => 'internalCreateMember']);
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
}
