
<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;

//create $activeCampaignService service

$fields   = '[{"sourceId":"1", "sourceTitle":"Email", "target":"email"}, {"sourceId":"2", "sourceTitle":"My Name", "target":"_auto_generate"}]';
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

$activeCampaignService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::ACTIVECAMPAIGN);

$activeCampaignService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => '3d11044108ef83f9677a19b166ed1a763bc0edf0bf4bc10cf7d6280e886fc28f7d362453',
    'api_url' => 'https://monintehsan.api-us1.com'
]));

var_dump($activeCampaignService->authenticate());

var_dump($activeCampaignService->getAccount());

$groups = $activeCampaignService->getGroups();

var_dump($groups);

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

foreach ($groups as $group) {
    var_dump($activeCampaignService->getFields($group));
}

$activeCampaignService->createMember($fieldMap, $active_group, $dataArray, true);