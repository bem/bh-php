<?php

use BEM\JsonCollection, BEM\Json;

class jsonCollection_jsonSerialize extends PHPUnit_Framework_TestCase {

    function test_itShouldSerializeBlock () {
        $this->assertJsonStringEqualsJsonString(
            '{"block":"name"}',
            json_encode(new Json(['block' => 'name']))
        );
    }

    function test_itShouldSerializeBlocks () {
        $this->assertJsonStringEqualsJsonString(
            '[{"block":"name"},1,"2",{"block":"name3"}]',
            json_encode(new JsonCollection([
                ['block' => 'name'],
                1,
                "2",
                new Json(['block' => 'name3'])
            ]))
        );
    }

    function test_itShouldSerializeDeepStructure () {
        $obj = [
            '1',
            ['block' => '2', 'content' => [
                ['tag' => 'name', 'html' => 'qwe'],
                ['mods' => ['visible' => true, 'yolo' => 'swag'], 'block' => 'name2']
            ]],
            '3',
            ['block' => '4', 'elem' => 'a'],
            '6',
            ['block' => '7']
        ];
        $this->assertJsonStringEqualsJsonString(
            json_encode($obj),
            json_encode(JsonCollection::normalize($obj))
        );
    }

}
