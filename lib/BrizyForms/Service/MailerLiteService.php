<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\NativeService\MailerLiteNativeService;
use BrizyForms\ServiceConstant;

class MailerLiteService extends Service
{
    /**
     * @var MailerLiteNativeService
     */
    protected $mailerLiteNativeService;

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
                $key_exist = array_search($fieldLink->getSourceTitle(), array_column($existCustomFields, 'title'));
                if ($key_exist === false) {
                    $payload = [
                        'title' => $fieldLink->getSourceTitle(),
                        'type' => 'TEXT'
                    ];
                    $newCustomField = $this->mailerLiteNativeService->request('fields', 'post', $payload);
                }

                if ($newCustomField) {
                    if (!isset($newCustomField->key)) {
                        continue;
                    }
                    $target = $newCustomField->key;
                } else {
                    $target = $existCustomFields[$key_exist]['key'];
                }

                $fieldLink->setTarget($target);
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

        $mergeFields = $data->getFields();

        $name = null;
        if (isset($mergeFields['name']) && !empty($mergeFields['name'])) {
            $name = $mergeFields['name'];
            unset($mergeFields['name']);
        }

        $this->mailerLiteNativeService->request("groups/{$group_id}/subscribers", 'post', [
            'name' => $name,
            'email' => $data->getEmail(),
            'fields' => $mergeFields
        ]);

        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @return array|mixed
     * @throws ServiceException
     */
    protected function internalGetGroups()
    {
        $groups = $this->mailerLiteNativeService->request('groups');
        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        $response = [];
        foreach ($groups as $row) {
            $group = new Group();
            $group
                ->setId($row->id)
                ->setName($row->name);

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
                ->setName($customField['title'])
                ->setSlug($customField['id'])
                ->setRequired(false);

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

        $this->mailerLiteNativeService = new MailerLiteNativeService($data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        return null;
    }

    /**
     * @return array|bool|mixed|object|string
     * @throws ServiceException
     */
    protected function _getFields()
    {
        $fields = $this->mailerLiteNativeService->request('fields');
        if ($this->mailerLiteNativeService->getResponseCode() != 200) {
            throw new ServiceException('Invalid request');
        }

        $fields = json_decode(json_encode($fields), true);

        return $fields;
    }
}