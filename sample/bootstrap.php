<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;
require __DIR__ . '/config.php';

//create MailChimp service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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

$mailChimpService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::MAILCHIMP);

$response = $mailChimpService->authenticate();
if ($response instanceof \BrizyForms\Model\RedirectResponse) {
    //@todo handle response
    $mailChimpService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
        'access_token' => '27abb01297b7832e89cde4ef82ca0051',
        'dc' => 'us13'
    ]));
} else {
	$mailChimpService->setAuthenticationData();
}

$groups = $mailChimpService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

foreach ($groups as $group) {
    var_dump($mailChimpService->getFields($group));
}

$mailChimpService->createMember($fieldMap, $active_group, $dataArray);


//create SendinBlue service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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

$sendinBlueService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::SENDINBLUE);

$sendinBlueService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => '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'
]));

$groups = $sendinBlueService->getGroups();

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group->getId();
}

$fields = $sendinBlueService->getFields();

var_dump($fields);

$sendinBlueService->createMember($fieldMap, $active_group, $dataArray);



//create Zapier service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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
    'webhook_url' => 'https://webhook.site/9a0c8a1e-cff7-4469-a305-e963aa4c515b'
]));

//$zapierService->createMember($fieldMap, null, $dataArray);



//create $campaignMonitorService service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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

$campaignMonitorService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::CAMPAIGNMONITOR);

$campaignMonitorService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'access_token'  => 'AcF1B9iqyuVDuybgAvToAzMxMw==',
    'refresh_token' => 'AS6ASOzvRp5Kuio8AlMK6XUxMw=='
]));

$groups = $campaignMonitorService->getGroups();

var_dump($groups);

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group;
    break;
}

$fields = $campaignMonitorService->getFields($active_group);

var_dump($fields);

$campaignMonitorService->createMember($fieldMap, $active_group->getId(), $dataArray);


//create $convertKitService service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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

$convertKitService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::CONVERTKIT);

$convertKitService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key'    => 'wrRowWfjkKQhDTlARCq59g',
    'api_secret' => '1iopMMrUAef8ptm71HIS_phBpt4iS1PitM0b88OXe9A'
]));

$groups = $convertKitService->getGroups();

var_dump($groups);

$active_group = null;
foreach ($groups as $group) {
    var_dump($group);
    $active_group = $group;
    break;
}

$fields = $convertKitService->getFields($active_group);

var_dump($fields);

$convertKitService->createMember($fieldMap, $active_group->getId(), $dataArray);


//create $activeCampaignService service

$fields   = '[{"source_id":"1", "source_title":"Email", "target":"email"}, {"source_id":"2", "source_title":"My Name", "target":"_auto_generate"}]';
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

$activeCampaignService = \BrizyForms\ServiceFactory::getInstance(\BrizyForms\ServiceFactory::ACTIVECAMPAIGN);

$activeCampaignService->setAuthenticationData(new \BrizyForms\Model\AuthenticationData([
    'api_key' => 'd66c759eba166d4c339d0a841fc42a5300c6392f5e5aba2379533ed0a5877f1ac30173ae',
    'api_url' => 'https://bodnar1212.api-us1.com'
]));

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

$activeCampaignService->createMember($fieldMap, $active_group, $dataArray);