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
use PHPFUI\ConstantContact\Client;
use PHPFUI\ConstantContact\Definition\ContactCreateOrUpdateInput;
use PHPFUI\ConstantContact\Definition\ContactPostRequest;
use PHPFUI\ConstantContact\Definition\CustomFieldInput;
use PHPFUI\ConstantContact\V3\ContactCustomFields;
use PHPFUI\ConstantContact\V3\Contacts;
use SendinBlue\Client\Model\CreateContact;

/**
 * Class ConstantContactService
 * @package BrizyForms\Service
 */
class ConstantContactService extends Service
{

    /**
     * @var Client
     */
    private $client;

    public function authorizationType()
    {
        return self::AUTH_TYPE_AUTHORIZATION_CODE;
    }

    public function obtainAuthorizationToken(array $options = null)
    {
        $this->client->acquireAccessToken($options);

        return [
            'access_token' => $this->client->accessToken,
            'refresh_token' => $this->client->refreshToken,
        ];
    }

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
                $arrayColumn = array_column($existCustomFields, 'label');
                $key_exist = array_search($fieldLink->getSourceTitle(), $arrayColumn);
                if ($key_exist === false) {
                    $input = new CustomFieldInput(
                        ['label' => $fieldLink->getSourceTitle(), 'name' => $perstag, 'type' => 'string']
                    );
                    $contactCustomField = new ContactCustomFields($this->client);
                    $newCustomField = $contactCustomField->post($input);
                    $tag = $newCustomField['custom_field_id'];
                } else {
                    $tag = $existCustomFields[$key_exist]['custom_field_id'];
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
     *
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(
        FieldMap $fieldMap,
        $group_id = null,
        array $data = [],
        $confirmation_email = false
    ) {
        $defaultFieldKeys = array_map(function (Field $field) {
            return $field->getSlug();
        }, $this->getDefaultFields());
        unset($defaultFieldKeys['email']);

        $data = $fieldMap->transform($data);
        $status = 1;
        if ($confirmation_email) {
            $status = 0;
        }

        $fields = $data->getFields();

        array_map(function ($key, $value) {
            return ['custom_field_id' => $key, 'value' => $value];
        }, array_keys($fields), array_values($fields));

        $payload = [
            'email_address' => ['address'=>$data->getEmail()],
            'create_source'=>'Contact',
            'custom_fields' => [],
            'list_memberships'=>[$group_id]
        ];

        foreach ($fields as $slug => $value) {
            if (in_array($slug, $defaultFieldKeys)) {
                $payload[$slug] = $value;
            } else {
                $payload['custom_fields'][] = ['custom_field_id' => $slug, 'value' => $value];
            }
        }

        $request = new ContactPostRequest($payload);
        $contacts = new Contacts($this->client);
        $result = $contacts->post($request);

        if(count($result)==0) {
            $this->logger->error(json_encode($payload), ['service' => ServiceFactory::CONSTANTCONTACT, 'method' => 'internalCreateMember','error'=>$this->client->getLastError()]);
            throw new ServiceException('Member was not created.');
        }
    }

    /**
     * @param Folder|null $folder
     *
     * @return array|mixed
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        $listEndPoint = new \PHPFUI\ConstantContact\V3\ContactLists($this->client);
        $lists = $listEndPoint->get();
        $response = [];
        do {
            foreach ((array)$lists['lists'] as $key => $list) {
                if (is_numeric($key)) {
                    $group = new Group();
                    $group->setId($list['list_id'])->setName($list['name']);
                    $response[] = $group;
                }
            }
            $lists = $listEndPoint->next();
        } while ($lists);

        return $response;
    }

    /**
     * @param Group $group
     *
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {

        $defaults = $this->getDefaultFields();

        $customFields = [];
        foreach ($this->_getFields() as $i => $customField) {
            $field = new Field();
            $field
                ->setName($customField['label'])
                ->setSlug($customField['custom_field_id'])
                ->setRequired(false);

            $customFields[] = $field;
        }

        $response = array_merge($defaults, $customFields);

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
        if (!isset($data['client_key']) || !isset($data['client_secret'])) {
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
        $this->client = new Client($data['client_key'], $data['client_secret'], $data['redirect_url']);
        $this->client->setScopes(['contact_data', 'account_read']);

        if (isset($data['access_token'])) {
            $this->client->accessToken = $data['access_token'];
        }

        if (isset($data['refresh_token'])) {
            $this->client->refreshToken = $data['refresh_token'];
        }
    }

    /**
     * @return array
     */
    protected function internalGetAccountProperties()
    {
        return [
            [
                'name' => 'client_key',
                'title' => 'API Key',
                'type' => FieldTypes::TYPE_TEXT,
            ],
            [
                'name' => 'client_secret',
                'title' => 'Client Secret',
                'type' => FieldTypes::TYPE_TEXT,
            ],
            [
                'name' => 'redirect_url',
                'title' => 'Redirect Url',
                'type' => FieldTypes::TYPE_REDIRECT_URL,
                'description' => 'Use this value as a redirect uri when creating your application',
            ],
        ];
    }

    /**
     * @param array $options
     *
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        if (!$this->client) {
            return new Response(400, 'native service was not init');
        }
        try {

            $authorizeUrl = $this->client->getAuthorizationURL()."&".http_build_query($options);

            $urlparts = parse_url($authorizeUrl);
            $params = [];
            parse_str($urlparts['query'],$params);
            $_SESSION[$params['state']] = serialize($options);
            return new RedirectResponse(302, 'OAtuh2 Authorize App', $authorizeUrl);

        } catch (\Exception $e) {
            return new Response(401, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
    }

    private function _getFields()
    {
        $endpoint = new \PHPFUI\ConstantContact\V3\ContactCustomFields($this->client);
        $lists = $endpoint->get();
        $response = [];
        do {
            foreach ((array)$lists['custom_fields'] as $key => $list) {
                if (is_numeric($key)) {
                    $response[] = $list;
                }
            }
            $lists = $endpoint->next();
        } while ($lists);

        return $response;

    }

    /**
     * @param GroupData $groupData
     *
     * @return mixed
     */
    protected function internalCreateGroup(GroupData $groupData)
    {
        return null;
    }

    /**
     * @param GroupData $groupData
     *
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

        $data = $this->authenticationData->getData();

        return new Account($data['client_key']);
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
     * @return boolean
     */
    protected function internalHasConfirmation()
    {
        return false;
    }

    /**
     * @return array
     */
    protected function getDefaultFields()
    {
        $defaults = [
            new Field('Email', ServiceConstant::EMAIL_FIELD, true),
            new Field('First Name', 'first_name', false),
            new Field('Last Name', 'last_name', false),
            new Field('Company Name', 'company_name', false),
            new Field('Job Title', 'job_title', false),
        ];

        return $defaults;
    }
}