<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
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

    protected function urlExec($value)
    {
        $client = new Client();

        $options = [];

        if(array_key_exists('message', $value)) {
            $options['form_params'] = $value['message'];
        }
        if(array_key_exists('settings', $value)) {
            foreach ($value['settings'] as  $key => $val) {
                $options[$key] = $val;
            }
        }

        try {
            $client->request($value['method'], $value['url'], $options);

            return true;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return true;
            } else {
                return false;
            }
        }
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
     * @param bool $confirmation_email
     * @return mixed|void
     * @throws ServiceException
     * @throws \BrizyForms\Exception\FieldMapException
     */
    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data, false);

        $email = [];
        if ($data->getEmail()) {
            $email = ['Email' => $data->getEmail()];
        }

        $data_json = json_encode(array_merge($email, $data->getFields()));
        $auth_data = $this->authenticationData->getData();

        $sends = ['url' => $auth_data['urlHooks'], 'method' => $auth_data['method'], 'message' => $email];

        if (!$this->urlExec($sends)) {
            throw new ServiceException('Can\'t send data to this WebHooks');
        }
    }

    /**
     * @param Folder|null $folder
     * @return mixed|null
     */
    protected function internalGetGroups(Folder $folder = null)
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
        return null;
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
        if (!isset($data['urlHooks']) || !isset($data['method'])) {
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

        $sends = [
            'method'=>'GET',
            'url'=> $data['urlHooks'],
            'settings' => [
                'timeout' => 3,
                'connect_timeout' => 1
            ]
        ];

        if (!$this->hasValidAuthenticationData() || !$this->urlExec($sends))
        {
            return new Response(400, 'Unauthenticated');
        }

        return new Response(200, 'Successfully authenticated');
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

        return new Account($data['urlHooks']);
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
                'name' => 'urlHooks',
                'title' => 'Webhook URL'
            ],
            [
                'name' => 'method',
                'title' => 'Request method'
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