<?php

require_once "./src/helpers.php";

class weakJsonTest extends PHPUnit_Framework_TestCase
{
    protected function assertSameList($a)
    {
        foreach ($a as $assert) {
            $this->assertSame($assert[0], \BEM\weakjson_decode($assert[1]));
        }
    }

    public function test_itShouldParseNumbers()
    {
        $this->assertSameList([
            [123,    "123"],
            [123.45, '123.45'],
            [.123,   '.123'],
            [-123,   "-123"],
            [123,    "+123"],
        ]);
    }

    public function test_itShouldParseNumbersInsideSpaces()
    {
        $this->assertSameList([
            [123,    " 123 "],
            [123.45, ' 123.45 '],
            [.123,   ' .123 '],
            [-123,   " -\t123"],
            [123,    " +\t123"],
        ]);
    }

    public function test_itShouldParseHexanumbers()
    {
        $this->assertSameList([
            [0x12,   "0x12"],
            [0x12,   " 0x12 "],
        ]);
    }

    public function test_itShouldParseStrings()
    {
        $this->assertSameList([
            ['',     "''"], // empty strings
            ['',     '""'],

            ['123',  '"123"'], // number-like string
            ['123',  ' "123" '],

            ['single quoted string', "'single quoted string'"],
            ['double quoted string', '"double quoted string"']
        ]);
    }

    public function test_itShouldParseEscapedStrings()
    {
        $this->assertSameList([
            ['"',   "\"\\\"\""],
            ["\"'", "'\\\"\\''"], // single quoted escaped double and single quotes
            ["q\\", '"q\\\\"'],
            ["w\\", "'w\\\\'"],
        ]);
    }

    // function test_itShouldThrow
        // $this->assertSame('unquotedString', \BEM\weakjson_decode('unquotedString'));
        // $this->assertSame('unquoted_string_with_lodashes', \BEM\weakjson_decode('unquoted_string_with_lodashes'));

    public function test_itShouldParseSpecials()
    {
        $this->assertNull(\BEM\weakjson_decode("undefined"));
        // $this->assertNull(\BEM\weakjson_decode("void"));
        $this->assertNull(\BEM\weakjson_decode("null"));
        $this->assertTrue(\BEM\weakjson_decode("true"));
        $this->assertFalse(\BEM\weakjson_decode("false"));
    }

    public function test_itShouldThrowUnexpectedKeyword()
    {
        $this->setExpectedException('Exception');
        \BEM\weakjson_decode("undefinedore");
    }
    public function test_itShouldThrowUnexpectedKeyword2()
    {
        $this->setExpectedException('Exception');
        \BEM\weakjson_decode("true_ly");
    }
    public function test_itShouldThrowUnexpectedKeyword3()
    {
        $this->setExpectedException('Exception');
        \BEM\weakjson_decode("falsey");
    }

    public function test_itShouldParseObjects()
    {
        $this->assertSameList([
            [["a" => 0],            "{a:0}"],
            [["a" => "x"],          "{a:'x'}"],
            [["null" => null],      "{null:null}"],
            [["undefined" => null], "{undefined:undefined}"],
            [["" => 0],             "{'':0}"],
            [["_a1s_d" => 0],       "{_a1s_d:0}"], // unquoted string "_a1s_d"
        ]);
    }

    public function test_itShouldParseWsHeavyObjects()
    {
        $this->assertSame(["a"=>0, "b"=>42], \BEM\weakjson_decode("  { a  :  0  \n, b  : 42  }  "));
        $this->assertSame([" a "=>"  x  "], \BEM\weakjson_decode("\n {  ' a '    \t:  \t  '  x  '  }  "));
    }

    public function test_itShouldParseNestedObjectsWs()
    {
        $this->assertSame(["a"=>["b" => ["c" => null]]],
            \BEM\weakjson_decode("  { a  : {   b :  {\n  c  : null \n} \t } \t }  "));
    }

    public function test_itShouldParseArrays()
    {
        $this->assertSame([], \BEM\weakjson_decode("[]"));
        $this->assertSame([0], \BEM\weakjson_decode("[0]"));
        $this->assertSame(['0'], \BEM\weakjson_decode("['0']"));
        $this->assertSame([-13, null, 2], \BEM\weakjson_decode("[-13, undefined, 2]"));
        $this->assertSame([0, 1, null, 3], \BEM\weakjson_decode("[0, 1, , 3]"));
    }

    public function test_itShouldParseArraysWithHoles()
    {
        $this->assertSame([null, null], \BEM\weakjson_decode("[,,]"));
        $this->assertSame([null, null, null], \BEM\weakjson_decode("[ \n , \n , \t\n , \r\n ]"));
    }

    public function test_itShouldParseNestedArrays()
    {
        $this->assertSame([[], [[[null]]], [[[1]]]], \BEM\weakjson_decode("[[],[[[null]]],[[[1]]]]"));
    }

    public function test_itShouldParseNestedArraysWs()
    {
        $this->assertSame([[[[['zxc', []]]]]],
            \BEM\weakjson_decode(" [  [   [  [   [  'zxc'  ,  [  ]   ]   ]   ]   ]  ] "));
    }

    public function test_itShouldParseObjectsInsideArrays()
    {
        $this->assertSame([0, [1, ['a' => 'b'], 2], null],
            \BEM\weakjson_decode(" [  0, [  1  , {  a   :  \"b\"  }   , 2   ]   ,  null  ]  "));
    }

    public function test_itShouldParseMixedObjects()
    {
        $this->assertSame(
            [
                'key1' => 'val',
                'key2' => 'val',
                '' => 1,
                'null' => null,
                "key3" => [
                    null,
                    1,
                    [
                        "key4" => "yay!"
                    ]
                ]
            ],
            \BEM\weakjson_decode(
<<<weakjson
            {
                key1: 'val',
                'key2': "val",
                '': 1,
                null: undefined,
                "key3": [
                    ,
                    1,
                    {key4:'yay!'}
                ]
            }
weakjson
        ));
    }
}
