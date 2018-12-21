<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\Model\Account;
use BrizyForms\Model\Folder;
use BrizyForms\Model\GroupData;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;

/**
 * Class ActiveCampaignService
 * @package BrizyForms\Service
 */
class ActiveCampaignService extends Service
{

    /**
     * @var \ActiveCampaign
     */
    private $nativeActiveCampaign;

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
                $perstag = StringUtils::getSlug($fieldLink->getSourceTitle());
                $key_exist = array_search($fieldLink->getSourceTitle(), array_column($existCustomFields, 'title'));
                if ($key_exist === false) {
                    $payload = [
                        "title" => $fieldLink->getSourceTitle(),
                        "type" => 1,
                        "req" => false,
                        "perstag" => "%" . strtoupper($perstag) . "%",
                        "p[{$group_id}]" => $group_id
                    ];
                    $newCustomField = $this->nativeActiveCampaign->api("list/field_add", $payload);
                }

                if ($newCustomField) {
                    if ((int)$newCustomField->success != 1) {
                        continue;
                    }
                    $tag = "field[{$newCustomField->fieldid},0]";
                } else {
                    $tag = "field[{$existCustomFields[$key_exist]['id']},0]";
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

        $status = 1;
        if ($confirmation_email) {
            $status = 0;
        }

        $contact = [
            "email" => $data->getEmail(),
            "p[{$group_id}]" => $group_id,
            "status[{$group_id}]" => $status,
        ];

        $contact = array_merge($contact, $data->getFields());
        $contact_sync = $this->nativeActiveCampaign->api("contact/sync", $contact);

        if (!(int)$contact_sync->success) {
            $this->logger->error(json_encode($contact_sync), ['service' => ServiceFactory::ACTIVECAMPAIGN, 'method' => 'internalCreateMember']);
            throw new ServiceException(json_encode($contact_sync));
        }
    }

    /**
     * @param Folder|null $folder
     * @return array|mixed
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $lists = $this->nativeActiveCampaign->api("list/list?ids=all");

        if (!(int)$lists->success) {
            return [];
        }

        $response = [];
        foreach ((array)$lists as $key => $list) {
            if (is_numeric($key)) {
                $group = new Group();
                $group
                    ->setId($list->id)
                    ->setName($list->name);

                $response[] = $group;
            }
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
        $clearFields = $this->_getFields();

        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('First Name', 'first_name', false),
            new Field('Last Name', 'last_name', false),
            new Field('Organization', 'orgname', false),
            new Field('phone', 'phone', false)
        ];

        $response = array_merge($defaults, $clearFields);

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
        if (!isset($data['api_key']) || !isset($data['api_url'])) {
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

        $this->nativeActiveCampaign = new \ActiveCampaign($data['api_url'], $data['api_key']);
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->nativeActiveCampaign) {
            return new Response(400, 'native service was not init');
        }

        $lists = $this->nativeActiveCampaign->api("account/view");

        if (!(int)$lists->success) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    private function _getFields()
    {
        $fields = $this->nativeActiveCampaign->api("list/field_view?ids=all");
        $clearFields = [];
        foreach ((array)$fields as $key => $row) {
            if (is_numeric($key)) {
                $field = new Field();
                $field
                    ->setName($row->title)
                    ->setSlug("field[{$row->id},0]")
                    ->setRequired(false);

                $clearFields[] = $field;
            }
        }

        return $clearFields;
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
        return true;
    }

    /**
     * @return Account
     * @throws ServiceException
     */
    protected function internalGetAccount()
    {
        $account = $this->nativeActiveCampaign->api("account/view");
        if (!isset($account->email)) {
            throw new ServiceException('Invalid request');
        }

        return new Account($account->email);
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
                'name' => 'api_url',
                'title' => 'Api Url'
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