<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create MailChimp service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

$data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"bodnar121211111111@gmail.com","required":false,"type":"email","slug":"email"}]';
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

$groups = $mailerLiteService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

var_dump($mailerLiteService->getFields());

$mailerLiteService->createMember($fieldMap, $active_group, $dataArray);