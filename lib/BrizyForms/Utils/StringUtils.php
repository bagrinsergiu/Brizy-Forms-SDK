<?php

namespace BrizyForms\Utils;


class StringUtils
{
    static public function getSlug($string)
    {
        $string = strtolower(str_replace(' ', '_', trim($string)));
        $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string);

        if ($string == '' || preg_match('/^\_+$/', $string)) {
            $string = 'custom_field_' . self::generate(4);
        }

        return $string;
    }

    static public function generate($length = 4)
    {
        $seed = str_split('abcdefghijklmnopqrstuvwxyz');
        shuffle($seed);
        $rand = '';
        foreach (array_rand($seed, $length) as $k) {
            $rand .= $seed[$k];
        }

        return $rand;
    }

    static public function masking($string, $maskingCharacter = 'X')
    {
        return substr($string, 0, 4) . str_repeat($maskingCharacter, strlen($string) - 8) . substr($string, -4);
    }
}