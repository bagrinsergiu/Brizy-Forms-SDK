<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create Airtable service

$fields = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"Test Field Name", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

const API_KEY = 'keyQ1cq8owDvhmm5K';
const BASE_ID = 'appPf9H1j7XCpCmn8';
const TABLE = 'Features';

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"bodnar.brizy@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}
/** @var \BrizyForms\Service\AirtableService $airtableService */
$airtableService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::AIRTABLE,
new \BrizyForms\Model\AuthenticationData( [
    'api_key' => API_KEY,
    'base' => BASE_ID,
    'table' => TABLE,
]));


var_dump($airtableService->authenticate());

var_dump($airtableService->getAccount());

$fields = $airtableService->getFields();

var_dump($fields);

$airtableService->createMember($fieldMap, $active_group, $dataArray);
