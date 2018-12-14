<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $mailjetService service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"My Name", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"bodnar1212@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}

$mailjetService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::MAILJET);

$mailjetService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => 'd52ecbd1a970c726a159edb340ca0c72',
    'secret_key' => '6aac0c35e5abdbea767aa008f9214e93'
]));

var_dump($mailjetService->authenticate());

var_dump($mailjetService->getAccount());

$groupData = new \BrizyForms\Model\GroupData([
    'name' => 'List'.rand(1,1000)
]);

$group = $mailjetService->createGroup($groupData);
var_dump($group);

$groups = $mailjetService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

$fields = $mailjetService->getFields();

var_dump($fields);

$mailjetService->createFields($fieldMap, $group->getId());

$mailjetService->createMember($fieldMap, $active_group, $dataArray);