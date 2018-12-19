<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create Zapier service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"Email"}, {"sourceId":"2", "sourceTitle":"My Name", "target":"My Name"}]';
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

$zapierService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::ZAPIER);

$zapierService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'webhook_url' => 'https://hooks.zapier.com/hooks/catch/1671845/fro8it/'
]));

var_dump($zapierService->authenticate());

$zapierService->createMember($fieldMap, null, $dataArray);