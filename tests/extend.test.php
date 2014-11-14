<?php


use BEM\BH;

class extendTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_extend_empty_target () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                ['foo' => 'bar'],
                $ctx->extend(null, ['foo' => 'bar'])
            );
        });
        $this->bh->apply(['block' => 'button']);
    }

    function test_it_should_extend_object____ () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                ['foo' => 'foo'],
                $ctx->extend(['foo' => 'bar'], ['foo' => 'foo'])
            );
        });
        $this->bh->apply(['block' => 'button']);
    }

}
