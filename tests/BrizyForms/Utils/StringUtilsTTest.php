<?php

namespace Tests\BrizyForms\Utils;

use BrizyForms\Utils\StringUtils;

require __DIR__ . '/../../../vendor/autoload.php';

class StringUtilsTTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSlug()
    {
        foreach ($this->getTestDataForAssetEquals() as $row)
        {
            $this->assertEquals($row['expected'], $row['actual']);
        }

        foreach ($this->getTestDataForContainsCheck() as $row)
        {
            $this->assertStringStartsWith($row['expected'], $row['actual']);
        }
    }

    private function getTestDataForAssetEquals()
    {
        return [
            [
                'actual' => StringUtils::getSlug('My TEst nAme!'),
                'expected' => 'my_test_name'
            ],
            [
                'actual' => StringUtils::getSlug('My TEst nAme фыв23 ацу!!'),
                'expected' => 'my_test_name_23_'
            ],
            [
                'actual' => StringUtils::getSlug('  My TEst nAme!  '),
                'expected' => 'my_test_name'
            ],
            [
                'actual' => StringUtils::getSlug('Sénégal'),
                'expected' => 'sngal'
            ],
            [
                'actual' => StringUtils::getSlug('Привет, bob'),
                'expected' => '_bob'
            ]
        ];
    }

    private function getTestDataForContainsCheck()
    {
        return [
            [
                'actual' => StringUtils::getSlug('____'),
                'expected' => 'custom_field'
            ],
            [
                'actual' => StringUtils::getSlug('впроалвы аы ва ыва'),
                'expected' => 'custom_field'
            ],
            [
                'actual' => StringUtils::getSlug(' !#@##@      '),
                'expected' => 'custom_field'
            ],
            [
                'actual' => StringUtils::getSlug('é'),
                'expected' => 'custom_field'
            ],
            [
                'actual' => StringUtils::getSlug('Привет,    '),
                'expected' => 'custom_field'
            ]
        ];
    }
}