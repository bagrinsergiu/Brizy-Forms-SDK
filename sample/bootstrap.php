<?php

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit(1);
}

require $composerAutoload;
require __DIR__ . '/config.php';

use BrizyForms\ServiceFactory;
use BrizyForms\Services\MailChimp;
use BrizyForms\FieldMap;
use BrizyForms\Model\RedirectResponse;

$serviceFactory = new ServiceFactory();

/**
 * @var MailChimp $mailchimp
 */
$mailchimp = $serviceFactory->getInstance('mailchimp');
$fields    = json_decode('[{"source":"name", "target":"FNAME1"}, {"source":"email", "target":"EMAIL"}]', true);
$fieldMap  = new FieldMap($fields);
$fields    = $fieldMap->toArray();

$response = $mailchimp->authenticate();
if ($response instanceof RedirectResponse) {
    //@todo redirect
}

$groups = $mailchimp->getGroups();

foreach ($groups as $group) {
    var_dump($mailchimp->getFields($group));
}

var_dump($groups);

$mailchimp->createMember($fieldMap);