<?php

use BEM\BH;
use BEM\Context;

class isSimpleTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
        $this->ctx = new Context($this->bh);
    }

    function test_it_should_return_true_for_nothing () {
        $this->assertTrue($this->ctx->isSimple(true));
    }

    function test_it_should_return_true_for_null () {
        $this->assertTrue($this->ctx->isSimple(null));
    }

    function test_it_should_return_true_for_number () {
        $this->assertTrue($this->ctx->isSimple(1));
    }

    function test_it_should_return_true_for_string () {
        $this->assertTrue($this->ctx->isSimple('1'));
    }

    function test_it_should_return_true_for_boolean () {
        $this->assertTrue($this->ctx->isSimple(false));
    }

    function test_it_should_return_false_for_array () {
        $this->assertFalse($this->ctx->isSimple([]));
    }

    function test_it_should_return_false_for_object () {
        $this->assertFalse($this->ctx->isSimple((object)[]));
    }

}
