<?php

namespace Tests\BrizyForms;

use PHPUnit\Framework\TestCase;

use BrizyForms\FieldMap;
use BrizyForms\Model\Data;
use BrizyForms\Model\FieldLink;
use BrizyForms\Model\TransformedData;

require __DIR__ . '/../../vendor/autoload.php';

class FieldMapTest extends TestCase
{
    public function testMap1()
    {
        $this->expectException('BrizyForms\Exception\FieldMapException');
        $this->expectExceptionMessage('"sourceId" && "sourceTitle" && "target" are required properties');

        new FieldMap([['test' => 'test']]);
    }

    public function testMap2()
    {
        $this->expectException('BrizyForms\Exception\FieldMapException');
        $this->expectExceptionMessage('"sourceId" && "sourceTitle" && "target" are required properties');

        new FieldMap([['source_id' => 'dfdfd']]);
    }

    public function testMap3()
    {
        $this->expectException('BrizyForms\Exception\FieldMapException');
        $this->expectExceptionMessage('"sourceId" && "sourceTitle" && "target" are required properties');

        new FieldMap([['source_id' => 'dfdfd', 'source_title' => 'dfdfd']]);
    }

    public function testMap4()
    {
        $this->expectException('BrizyForms\Exception\FieldMapException');
        $this->expectExceptionMessage('"sourceId" && "sourceTitle" && "target" are required properties');

        new FieldMap([['source_id' => 'dfdfd', 'target' => 'dfdfd']]);
    }

    public function testMapOutputToArray()
    {
        $fieldMap = new FieldMap([['sourceId' => 'dfdfd', 'target' => 'dfdfd', 'sourceTitle' => 'sdasdas']]);

        $fieldLink = new FieldLink();
        $fieldLink
            ->setSourceTitle('sdasdas')
            ->setSourceId('dfdfd')
            ->setTarget('dfdfd');

        $output = [
            $fieldLink
        ];

        $this->assertEquals($output, $fieldMap->toArray());
    }

    public function testTransform()
    {
        //data from editor integration setting
        $fieldMap = new FieldMap([
            ['sourceId' => '1', 'target' => 'email', 'sourceTitle' => 'Email'],
            ['sourceId' => '2', 'target' => 'name', 'sourceTitle' => 'Name']
        ]);

        // data from preview (form.submit)
        $data = [
            new Data('1', 'bodnar@gmail.com'),
            new Data('2', 'Anthony')
        ];

        $actual = $fieldMap->transform($data);

        $output = new TransformedData();
        $output
            ->setEmail("bodnar@gmail.com")
            ->setFields([
                'name' => 'Anthony'
            ]);

        $this->assertEquals($output, $actual);
    }

    public function testTransformExpectedException()
    {
        $this->expectException('BrizyForms\Exception\FieldMapException');
        $this->expectExceptionMessage('Not instanceof Data');

        //data from editor integration setting
        $fieldMap = new FieldMap([
            ['sourceId' => '1', 'target' => 'email', 'sourceTitle' => 'Email'],
            ['sourceId' => '2', 'target' => 'name', 'sourceTitle' => 'Name']
        ]);

        $data = '[{"name":"2","value":"Anthony","required":false,"type":"text","slug":"name"},{"name":"1","value":"bodnar.llk@gmail.com","required":false,"type":"email","slug":"email"}]';
        $data = json_decode($data, true);

        $actual = $fieldMap->transform($data);

        $output = new TransformedData();
        $output
            ->setEmail("bodnar@gmail.com")
            ->setFields([
                'name' => 'Anthony'
            ]);

        $this->assertEquals($output, $actual);
    }

    public function testTransformExpectedExceptionNotEmail()
    {
        $this->expectException('BrizyForms\Exception\FieldMapException');
        $this->expectExceptionMessage('Email was not found.');

        //data from editor integration setting
        $fieldMap = new FieldMap([
            ['sourceId' => '1', 'target' => 'email', 'sourceTitle' => 'Email'],
            ['sourceId' => '2', 'target' => 'name', 'sourceTitle' => 'Name']
        ]);

        // data from preview (form.submit)
        $data = [
            new Data('1', 'notvalidemail'),
            new Data('2', 'Anthony')
        ];

        $actual = $fieldMap->transform($data);

        $output = new TransformedData();
        $output
            ->setEmail("bodnar@gmail.com")
            ->setFields([
                'name' => 'Anthony'
            ]);

        $this->assertEquals($output, $actual);
    }

}