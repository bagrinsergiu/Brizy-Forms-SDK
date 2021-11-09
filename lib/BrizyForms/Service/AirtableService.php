<?php

namespace BrizyForms\Service;

use BrizyForms\NativeService\GetResponseNativeService;
use TANIOS\Airtable\Airtable;
use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Field;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;

final class AirtableService extends Service
{
    /** @var  Airtable */
    private $airtableNativeService;

    /**
     * @var GetResponseNativeService
     */
    private $getResponseNativeService;

    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        $existCustomFields = $this->getTableFields();

        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $newCustomField = null;
                $name = StringUtils::getSlug($fieldLink->getSourceTitle());

                $key_exist = array_search($name, array_column($existCustomFields, 'name'));

                if ($key_exist === false) {
                    $payload = [
                        'name' => $name,
                        'type' => 'text',
                        'hidden' => false,
                        'values' => []
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
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data);

        $mergeFields = [];
        $name = null;
        foreach ($data->getFields() as $target => $value){
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

    protected function internalGetGroups(Folder $folder = null)
    {
        $table = $this->getResponseNativeService->getCampaign($this->authenticationData->getData()['table']);
        if ($this->getResponseNativeService->http_status != 200) {
            throw new ServiceException('Invalid request');
        }
        $campaigns = json_decode(json_encode($table), true);

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

    protected function internalGetFields(Group $group = null)
    {
        $clearFields = $this->getTableFields();
    }

    private function getTableFields()
    {
        $table = $this->authenticationData->getData()['table'];
        $fields = $this->airtableNativeService->getContent($table)['records']['fields'];
        $clearFields = [];
        foreach ((array)$fields as $key => $row) {
            if (is_numeric($key)) {
                $field = new Field();
                $field
                    ->setName($key)
                    ->setSlug("field[{$key},0]")
                    ->setRequired(false);

                $clearFields[] = $field;
            }
        }

        return $clearFields;

    }

    protected function hasValidAuthenticationData()
    {
        if (!$this->authenticationData) {
            return false;
        }

        $data = $this->authenticationData->getData();

        if (!isset($data['api_key']) || !isset($data['base']) || !isset($data['table'])) {
            return false;
        }

        return true;
    }

    protected function initializeNativeService()
    {
        $data = $this->authenticationData->getData();
        if (!isset($data['api_key']) || !isset($data['base']) || !isset($data['table'])) {
            throw new ServiceException('required data to initialize native service not present');
        }

        $this->airtableNativeService = new Airtable($data);
    }

    protected function hasValidGroupData(GroupData $groupData)
    {
        return false;
    }

    protected function internalGetAccount()
    {
        $data = $this->authenticationData->getData();

        return new Account(StringUtils::masking($data['api_key'] . $data['base'] . $data['table']));
    }

    protected function internalGetFolders()
    {
        return null;
    }

    protected function internalGetGroupProperties()
    {
        return null;
    }

    protected function internalGetAccountProperties()
    {
        return [
            [
                'name' => 'api_key',
                'title' => 'Api Key'
            ],
            [
                'name' => 'base_id',
                'title' => 'Base Id'
            ],
            [
                'name' => 'table',
                'title' => 'Table'
            ],
        ];
    }

    protected function internalHasConfirmation()
    {
        return false;
    }

    public function authenticate(array $options = null)
    {
        if (!$this->airtableNativeService) {
            return new Response(400, 'native service was not init');
        }
        $table = $this->authenticationData->getData()['table'];
        $response = $this->airtableNativeService->quickCheck($table);
        var_dump($response);
        return new Response(200, 'Successfully authenticated');
    }
}
