<?php

use BEM\BH;
use BEM\Context;

class isSimpleTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
        $this->ctx = new Context($this->bh);
    }

    public function test_it_should_return_true_for_nothing()
    {
        $this->assertTrue($this->ctx->isSimple(true));
    }

    public function test_it_should_return_true_for_null()
    {
        $this->assertTrue($this->ctx->isSimple(null));
    }

    public function test_it_should_return_true_for_number()
    {
        $this->assertTrue($this->ctx->isSimple(1));
    }

    public function test_it_should_return_true_for_string()
    {
        $this->assertTrue($this->ctx->isSimple('1'));
    }

    public function test_it_should_return_true_for_boolean()
    {
        $this->assertTrue($this->ctx->isSimple(false));
    }

    public function test_it_should_return_false_for_array()
    {
        $this->assertFalse($this->ctx->isSimple([]));
    }

    public function test_it_should_return_false_for_object()
    {
        $this->assertFalse($this->ctx->isSimple((object)[]));
    }
}
