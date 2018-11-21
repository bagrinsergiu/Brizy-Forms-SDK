<?php

namespace BrizyForms\Service;

use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\NativeService\ConvertKitNativeService;
use BrizyForms\ServiceConstant;

class ConvertKitService extends Service
{
    /**
     * @var ConvertKitNativeService
     */
    private $nativeConvertKit;

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existCustomFields = $this->_getFields();
        $existCustomFields = json_decode(json_encode($existCustomFields), true);

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $key_exist = array_search($fieldLink->getSourceTitle(), array_column($existCustomFields, 'label'));
                if ($key_exist === false) {
                    $payload = [
                        'label' => $fieldLink->getSourceTitle(),
                        'api_secret' => $this->authenticationData->getData()['api_secret']
                    ];
                    try {
                        $newCustomField = $this->nativeConvertKit->request("custom_fields", "post", $payload);
                    } catch (\Exception $e) {
                        continue;
                    }

                    $newCustomField = json_decode(json_encode($newCustomField), true);
                }

                if ($newCustomField) {
                    if (!isset($newCustomField['key'])) {
                        continue;
                    }
                    $tag = $newCustomField['key'];
                } else {
                    $tag = $existCustomFields[$key_exist]['key'];
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
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [])
    {
        $data = $fieldMap->transform($data);

        $mergeFields = $data->getFields();

        if (isset($mergeFields['name']) && !empty($mergeFields['name'])) {
            $name = $mergeFields['name'];
            unset($mergeFields['name']);
        } else {
            $name = explode("@", $data->getEmail());
            $name = $name[0];
        }

        $payload = [
            "email" => $data->getEmail(),
            "name" => $name,
            "fields" => $mergeFields
        ];

        $this->nativeConvertKit->request("courses/{$group_id}/subscribe", "post", $payload);
    }

    /**
     * @return mixed
     */
    protected function internalGetGroups()
    {
        $sequences = $this->nativeConvertKit->request("sequences");
        if (!isset($sequences->courses)) {
            return [];
        }

        $response = [];
        foreach ($sequences->courses as $sequence) {
            $group = new Group();
            $group
                ->setId($sequence->id)
                ->setName($sequence->name);

            $response[] = $group;
        }

        return $response;
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
                ->setName($customField->label)
                ->setSlug($customField->key)
                ->setRequired(false);

            $response[$i] = $field;
        }

        $emailField = new Field();
        $emailField
            ->setName('Email')
            ->setSlug(ServiceConstant::EMAIL_FIELD)
            ->setRequired(true);

        $nameField = new Field();
        $nameField
            ->setName('Name')
            ->setSlug('Name')
            ->setRequired(false);

        $response = array_merge([$emailField, $nameField], $response);

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
        if (!isset($data['api_key']) || !isset($data['api_secret'])) {
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

        $this->nativeConvertKit = new ConvertKitNativeService($data['api_key']);
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
        $customFields = $this->nativeConvertKit->request("custom_fields");

        return $customFields->custom_fields;
    }
}