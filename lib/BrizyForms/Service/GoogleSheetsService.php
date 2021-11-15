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
use BrizyForms\NativeService\GoogleSheetsNativeService;
use BrizyForms\ServiceConstant;
use BrizyForms\Utils\StringUtils;

final class GoogleSheetsService extends Service
{
    /** @var GoogleSheetsNativeService */
    private $nativeService;

    protected function mapFields(FieldMap $fieldMap, $group_id = null)
    {
        foreach ($fieldMap->toArray() as $fieldLink) {
            if ($fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD) {
                $fieldLink->setTarget($fieldLink->getSourceTitle());
            }
        }
        return $fieldMap;
    }

    protected function internalCreateMember(FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false)
    {
        $data = $fieldMap->transform($data, false);
        $this->nativeService->appendRow($group_id, $data);
    }

    protected function internalGetGroups(Folder $folder = null)
    {
        $sheets = $this->nativeService->getSpreadSheetSheets();
        $result = [];
        foreach ($sheets as $i => $row) {
            $group = new Group();
            $group
                ->setId($row['id'])
                ->setName($row['title']);

            $result[$i] = $group;
        }

        return $result;
    }

    protected function internalCreateGroup(GroupData $groupData)
    {
        $this->nativeService->createSheet($groupData->getData());
    }

    protected function internalGetFields(Group $group = null)
    {
        return null;
    }

    protected function hasValidAuthenticationData()
    {
        if(!$this->authenticationData){
            return false;
        }

        $data = $this->authenticationData->getData();
        if (!isset($data['spreadsheetId'])) {
            return false;
        }

        return true;
    }

    protected function initializeNativeService()
    {
        $data = $this->authenticationData->getData();
        $this->nativeService = new GoogleSheetsNativeService($data['spreadsheetId']);
    }

    protected function hasValidGroupData(GroupData $groupData)
    {
        return true;
    }

    protected function internalGetAccount()
    {
        $data = $this->authenticationData->getData();
        return new Account($data['spreadsheetId']);
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
                'name'=>'spreadsheetId',
                'title'=>'Spreadsheet Id',
            ]
        ];
    }

    protected function internalHasConfirmation()
    {
        return false;
    }

    public function authenticate(array $options = null)
    {
        if (!$this->hasValidAuthenticationData()
        ) {
            return new Response(400, 'Unauthenticated');
        }

        $response = $this->nativeService->checkAuthentication();
        $statusCode = $response->getResponseObject()->getStatusCode();

        if ($statusCode === 200) {
            return new Response(200, 'Successfully authenticated');
        } else {
            return new Response($statusCode, $response->getResponseObject()->getReasonPhrase());
        }
    }
}
