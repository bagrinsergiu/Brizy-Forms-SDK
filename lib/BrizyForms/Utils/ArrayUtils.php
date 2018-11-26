<?php

namespace BrizyForms\Utils;

class ArrayUtils
{
    static public function rewrite(array $source = null, array $target = null)
    {
        if (!empty($source) && !empty($target)) {
            return array_replace($source, $target);
        }

        return $source;
    }
}