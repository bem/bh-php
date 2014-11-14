<?php

use BEM\BH;

class tParamTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_tParam_value_in_nested_element () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('name', 'sample-name');
        });
        $this->bh->match('button__inner', function ($ctx) {
            $this->assertEquals('sample-name', $ctx->tParam('name'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner']]);
    }

    function test_it_should_return_tParam_value_in_sub_nested_element () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('name', 'sample-name');
        });
        $this->bh->match('button__sub-inner', function ($ctx) {
            $this->assertEquals('sample-name', $ctx->tParam('name'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner', 'content' => ['elem' => 'sub-inner']]]);
    }

    function test_it_should_not_return_tParam_value_in_non_nested_element () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('name', 'sample-name');
        });
        $this->bh->match('input', function ($ctx) {
            $this->assertNull($ctx->tParam('name'));
        });
        $this->bh->apply([['block' => 'button'], ['block' => 'input']]);
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', 1);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', 2);
        });
        $this->bh->match('button__control', function ($ctx) {
            $this->assertEquals(2, $ctx->tParam('foo'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'control']]);
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', 1, true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', 2);
        });
        $this->bh->match('button__control', function ($ctx) {
            $this->assertEquals(1, $ctx->tParam('foo'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'control']]);
    }
}
