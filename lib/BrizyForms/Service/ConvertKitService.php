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
use BrizyForms\NativeService\ConvertKitNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;

class ConvertKitService extends Service
{
    /**
     * @var ConvertKitNativeService
     */
    private $nativeConvertKit;

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @return FieldMap|mixed
     * @throws ServiceException
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
     * @param bool $confirmation_email
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
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
            "first_name" => $name,
            "fields" => $mergeFields
        ];

        $response = $this->nativeConvertKit->request("courses/{$group_id}/subscribe", "post", $payload);
        if (!isset($response->subscription->id)) {
            $this->logger->error(json_encode($response), ['service' => ServiceFactory::CONVERTKIT, 'method' => 'internalCreateMember']);
            throw new ServiceException(json_encode($response));
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     */
    protected function internalGetGroups(Folder $folder = null)
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
                ->setName($customField->label)
                ->setSlug($customField->key)
                ->setRequired(false);

            $response[$i] = $field;
        }

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('Name', 'name', false),
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

        $this->nativeConvertKit = new ConvertKitNativeService($data['api_key'], $data['api_secret']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->nativeConvertKit) {
            return new Response(400, 'native service was not init');
        }

        $sequences = $this->nativeConvertKit->request("sequences");
        if (!isset($sequences->courses)) {
            return new Response(401, 'Unauthenticated');
        }

        $account = $this->nativeConvertKit->request('account');
        if (!isset($account->primary_email_address)) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    /**
     * @return mixed
     * @throws ServiceException
     */
    private function _getFields()
    {
        $customFields = $this->nativeConvertKit->request("custom_fields");

        if (!isset($customFields->custom_fields)) {
            throw new ServiceException('Invalid request');
        }

        return $customFields->custom_fields;
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        return null;
    }

    /**
     * @param GroupData $groupData
     * @return mixed
     */
    protected function hasValidGroupData(GroupData $groupData)
    {
        return null;
    }

    /**
     * @return Account
     * @throws ServiceException
     */
    protected function internalGetAccount()
    {
        $account = $this->nativeConvertKit->request('account');
        if (!isset($account->primary_email_address)) {
            throw new ServiceException('Invalid request');
        }

        return new Account($account->primary_email_address);
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
        return null;
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
            ],
            [
                'name' => 'api_secret',
                'title' => 'Api Secret'
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