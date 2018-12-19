<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $mailerLiteService service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"My Namelllssssddd", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"ddddd1111sssssdd@gmail.com","required":false,"type":"email","slug":"email"}]';
$data = json_decode($data, true);

$dataArray = [];
foreach ($data as $row) {
    $data = new \BrizyForms\Model\Data();
    $data
        ->setName($row['name'])
        ->setValue($row['value']);
    $dataArray[] = $data;
}

$mailerLiteService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::MAILERLITE);

$mailerLiteService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => 'b37a47c4374f5917a49805dca0230651'
]));

//var_dump($mailerLiteService->authenticate());

//var_dump($mailerLiteService->getAccount());

$groupData = new \BrizyForms\Model\GroupData([
    'name' => 'List'.rand(1,1000)
]);

$group = $mailerLiteService->createGroup($groupData);
//var_dump($group);

$groups = $mailerLiteService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    //var_dump($group);
    $active_group = $group->getId();
}

var_dump($mailerLiteService->getFields());

$fieldMap = $mailerLiteService->createFields($fieldMap, $group->getId());

var_dump($fieldMap);

$mailerLiteService->createMember($fieldMap, $active_group, $dataArray);