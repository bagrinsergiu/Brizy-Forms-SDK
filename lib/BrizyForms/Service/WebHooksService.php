<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Account;
use BrizyForms\Model\Folder;
use BrizyForms\Model\Group;
use BrizyForms\Model\GroupData;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
class WebHooksService
{

    private $urlHooks;
    private $sendType;
    private static $initParamer;

    public function init(array $param)
    {
        if (!array_key_exists('urlHooks', $param) ) return false;

        if (!array_key_exists('sendType', $param)){
            $param['sendType'] = 'POST';
        }
       self::$initParamer = $param;
        return true;
    }
    public static function events(array $message)
    {
        $message = json_encode($message);

        $sends = [
            'sendType' => self::$initParamer['sendType'],
            'message' => $message
        ];
        self::curlExec(self::$initParamer['urlHooks'],$sends);

    }
    public function getInitParametrs()
    {
        return self::$initParamer;
    }

    private function curlExec(string $url, array $value)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        if ($value['sendType'] === 'GET'){
            var_dump($value['message']);
            $url = $url . '?' . http_build_query(json_decode($value['message'],true));
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        if ($value['sendType'] === 'POST'){
            var_dump($value['message']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $value['message']);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            print "Error: " . curl_error($ch);
        }

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception($error_msg);
        }
        curl_close($ch);

        return $response;
    }
}