<?php
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"Test Field Name", "target":"_auto_generate"}]';
$fieldMap = new \BrizyForms\FieldMap(json_decode($fields, true));

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

$webHooksService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::WEBHOOKS);

$header = [
    'content-type'=>' application/x-www-form-urlencoded',
];

$initParam = [
    'urlHooks' => 'https://webhook.site/6fc96a6c-d82d-47db-a7aa-d7d03b965ad5',
    'sendType' => 'GET',
    'header'   => $header
];

$webHooksService->init($initParam);

//var_dump($webHooksService->getInitParametrs());

var_dump($dataArray);

$dataArray = ["email"=>"serghei@gmail.com"];

$webHooksService::events($dataArray);

