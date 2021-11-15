<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

const SPREADSHEET_ID = '';
const SHEET_ID = '';
$authenticationData = new \BrizyForms\Model\AuthenticationData(['spreadsheetId' => SPREADSHEET_ID]);
$service = new \BrizyForms\Service\GoogleSheetsService($authenticationData);
//authenticate
$service->authenticate();
//get sheets list
$service->getGroups();
//create new sheet
$groupData = new  \BrizyForms\Model\GroupData([]);
$service->createGroup($groupData);
//add new row
$memberData = new \BrizyForms\FieldMap([]);
$service->createMember($memberData, SHEET_ID);



