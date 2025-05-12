<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\AuthenticationData;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WebHooksService extends Service
{
    /**
     * @var Client
     */
    private $client;


    public function __construct(AuthenticationData $authenticationData = null)
    {
        parent::__construct($authenticationData);

        $this->client = new Client();
    }

    /**
     * @param FieldMap $fieldMap
     * @param string $group_id
     *
     * @return mixed
     */
    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $fieldLink->setTarget($fieldLink->getSourceTitle());
            }
        }
        return $fieldMap;
    }

    /**
     * @param FieldMap $fieldMap
     * @param null $group_id
     * @param array $data
     * @param bool $confirmation_email $data
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(
        FieldMap $fieldMap,
                 $group_id = null,
        array    $data = [],
                 $confirmation_email = false
    )
    {
        $formFields = $fieldMap->transform($data, false);
        $email = [];
        if ($formFields->getEmail()) {
            $email = ['Email' => $formFields->getEmail()];
        }
        $auth_data = $this->authenticationData->getData();

        $request_method_decode = explode('|', $auth_data['request_method']);
        $request_method = $request_method_decode[0];
        isset($request_method_decode[1]) ? $format = $request_method_decode[1] : $format = 'json';

        try {
            $this->client->request($request_method, $auth_data['webhook_url'], [
                $format => array_merge($email, $formFields->getFields())
            ]);
        } catch (\Exception $e) {
            throw new ServiceException($e->getMessage());
        }
    }

    /**
     * @param Folder|null $folder
     * @return mixed|null
     */

    /**
     * @param Group $group
     *
     * @return mixed
     */
    protected function internalGetFields(Group $group = null)
    {
        return null;
    }

    /**
     * @return bool
     */
    protected function hasValidAuthenticationData()
    {
        $data = $this->authenticationData->getData();
        if (!isset($data['webhook_url']) || !isset($data['request_method'])) {
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    protected function initializeNativeService()
    {
    }

    /**
     * @param array $options
     * @return RedirectResponse|Response|null
     */
    public function authenticate(array $options = null)
    {
        $data = $this->authenticationData->getData();

        try {
            $response = $this->client->request('GET', $data['webhook_url'], [
                'http_errors' => false,
                'timeout' => 3,
                'connect_timeout' => 1
            ]);

            if (preg_match('/^(100|[1-5][0-9]{2})$/', $response->getStatusCode())) {
                return new Response(200, 'Successfully authenticated');
            }
        } catch (RequestException $e) {
            return new Response(401, 'Unauthenticated');
        }
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
     */
    protected function internalGetAccount()
    {
        $data = $this->authenticationData->getData();

        return new Account($data['webhook_url']);
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
                'name' => 'title',
                'title' => 'title',
                'type' => FieldTypes::TYPE_TEXT
            ],
            [
                'name' => 'webhook_url',
                'title' => 'Webhook URL',
                'type' => FieldTypes::TYPE_TEXT

            ],
            [
                'name' => 'request_method',
                'title' => 'Request method',
                'type' => FieldTypes::TYPE_RADIO,
                'choices' => [
                    ['id' => 'GET', 'name' => 'GET'],
                    ['id' => 'POST', 'name' => 'POST'],
                ]
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

    /**
     * @param Folder|null $folder
     * @return mixed|null
     */
    protected function internalGetGroups(Folder $folder = null)
    {
        return null;
    }
}