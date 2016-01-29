<?php

use BEM\JsonCollection;
use BEM\Json;

class jsonCollection_normalize extends PHPUnit_Framework_TestCase
{
    public function test_itShouldCreateCollectionWithOneBlock()
    {
        $this->assertEquals(
            new JsonCollection([new Json(['block' => 'name'])]),
            JsonCollection::normalize(['block' => 'name'])
        );
    }

    public function test_itShouldFlattenDeeps()
    {
        $this->assertEquals(
            new JsonCollection([
                new Json(['block' => 'name']),
                new Json(['block' => 'name2']),
                new Json(['block' => 'name3'])
            ]),
            JsonCollection::normalize([
                ['block' => 'name'],
                [['block' => 'name2']],
                [JsonCollection::normalize([
                    ['block' => 'name3']
                ])]
            ])
        );
    }

    public function test_itShouldGracefulyIgnoreHoles()
    {
        $this->assertEquals(
            new JsonCollection([
                false,
                new Json(['block' => 'button']),
                null,
                false,
                new Json(['block' => 'button']),
                null
            ]),
            JsonCollection::normalize([
                false,
                ['block' => 'button'],
                [null, false], // 2 separated rows
                [], // nothing
                ['block' => 'button'],
                null
            ])
        );
    }

    public function test_itShouldGracefulyIgnoreDeepHoles()
    {
        $this->assertEquals(
            new JsonCollection([
                false,
                new Json(['block' => 'button']),
                new Json(['content' => new JsonCollection([
                    false,
                    new Json(['block' => 'button']),
                    null
                ])]),
                null
            ]),
            JsonCollection::normalize([
                false,
                ['block' => 'button'],
                ['content' => [
                    false,
                    ['block' => 'button'],
                    [null]
                ]],
                null
            ])
        );
    }

    public function test_itShouldGracefulyAppendAnyObjects()
    {
        $collection = JsonCollection::normalize([
            '1'
        ]);
        $collection->append(['block' => '2']);
        $collection->append('3');
        $collection->append([['block' => '4', 'elem' => 'a'], ['block' => '5'], '6']);
        $collection->append([[['block' => '7']]]);
        $this->assertEquals(
            new JsonCollection([
                '1',
                new Json(['block' => '2']),
                '3',
                new Json(['block' => '4', 'elem' => 'a']),
                new Json(['block' => '5']),
                '6',
                new Json(['block' => '7'])
            ]),
            $collection
        );
    }
}
