<?php

use BEM\BH;

class tParamTest extends PHPUnit_Framework_BHTestCase {

    // bh.js ported tests

    function test_it_should_return_tParam_value_in_nested_element () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('name', $_ = 'sample-name');
        });
        $this->bh->match('button__inner', function ($ctx) {
            $this->assertEquals('sample-name', $ctx->tParam('name'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner']]);
    }

    function test_it_should_return_tParam_value_in_nested_block () {
        $this->bh->match('button', function($ctx) {
            $ctx->tParam('name', $_ = 'sample-name');
        });
        $this->bh->match('input', function($ctx) {
            $this->assertEquals('sample-name', $ctx->tParam('name'));
        });
        $this->bh->apply([ 'block' => 'button', 'content' => [ 'block' => 'input' ] ]);
    }

    function test_it_should_return_tParam_value_in_sub_nested_element () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('name', $_ = 'sample-name');
        });
        $this->bh->match('button__sub-inner', function ($ctx) {
            $this->assertEquals('sample-name', $ctx->tParam('name'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner', 'content' => ['elem' => 'sub-inner']]]);
    }

    function test_it_should_not_return_tParam_value_in_non_nested_element () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('name', $_ = 'sample-name');
        });
        $this->bh->match('input', function ($ctx) {
            $this->assertNull($ctx->tParam('name'));
        });
        $this->bh->apply([['block' => 'button'], ['block' => 'input']]);
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', $_ = 1);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', $_ = 2);
        });
        $this->bh->match('button__control', function ($ctx) {
            $this->assertEquals(2, $ctx->tParam('foo'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'control']]);
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', $_ = 1, true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->tParam('foo', $_ = 2);
        });
        $this->bh->match('button__control', function ($ctx) {
            $this->assertEquals(1, $ctx->tParam('foo'));
        });
        $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'control']]);
    }


    // php specific tests

    function test_itShouldFetchTParamsByReference () {
        $this->bh->match('button', function ($ctx) {
            // last again without
            $this->assertEquals(['baz' => 1, 'bar' => 3], $ctx->tParam('foo'));
        });
        $this->bh->match('button', function ($ctx) {
            // second - with ref
            $foo =& $ctx->tParamRef('foo');
            $foo['bar'] = $foo['baz'] + 2;
        });
        $this->bh->match('button', function ($ctx) {
            // first - set without ref
            $ctx->tParam('foo', ['baz' => 1]);
        });
        $this->bh->apply(['block' => 'button']);
    }

    function test_itShouldPassParamsByReference () {
        $foo = ['baz' => 1];
        $this->bh->match('button', function ($ctx) use (&$foo) {
            // last - testing referenced
            $this->assertEquals(['baz' => 1, 'bar' => 2], $foo);
        });
        $this->bh->match('button', function ($ctx) {
            // second - using by ref
            $ctx->tParamRef('foo')['bar'] = 2;
        });
        $this->bh->match('button', function ($ctx) use (&$foo) {
            // first - passing with ref
            $ctx->tParamRef('foo', $foo);
        });
        $this->bh->apply(['block' => 'button']);
    }
}
