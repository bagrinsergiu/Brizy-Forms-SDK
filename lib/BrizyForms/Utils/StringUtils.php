<?php

namespace AppBundle\Utils;


class StringUtils
{
    public static function getSlug($string)
    {
        $string = strtolower(str_replace(' ', '_', trim($string)));
        $string = preg_replace('/[^A-Za-z0-9\_]/', '', $string);

        if ($string == '' || preg_match('/^\_+$/', $string)) {
            $string = 'custom_field-' . self::generate(4);
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

}