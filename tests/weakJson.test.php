<?php

require_once "./src/helpers.php";

class weakJsonTest extends PHPUnit_Framework_TestCase {

    function test_emptyPageRender () {
        $this->assertEquals(
            [
                'key1' => 'val',
                'key2' => 'val',
                '' => 1,
                'null' => null,
                "key3" => [
                    null,
                    1,
                    2
                ]
            ],
            \BEM\weakjson_decode(
<<<weakjson
            {
                key1: 'val',
                'key2': "val",
                0: 1,
                null: undefined,
                "key3": [
                    ,
                    1,
                    2
                ]
            }
weakjson
        ));
    }

}
