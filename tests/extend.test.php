<?php


use BEM\BH;

class extendTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_extend_empty_target()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'bar',
                $ctx->extend(null, ['foo' => 'bar'])['foo']
            );
        });
        $this->bh->apply(['block' => 'button']);
    }

    public function test_it_should_extend_object()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'foo',
                $ctx->extend(['foo' => 'bar'], ['foo' => 'foo'])['foo']
            );
        });
        $this->bh->apply(['block' => 'button']);
    }
}
