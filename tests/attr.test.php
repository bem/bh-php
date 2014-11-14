<?php

use BEM\BH;

class attrTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_attr () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals($ctx->attr('type'), 'button');
        });
        $this->bh->apply(['block' => 'button', 'attrs' => ['type' =>'button']]);
    }

    function test_it_should_return_null_attr () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals($ctx->attr('type'), null);
        });
        $this->bh->apply(['block' => 'button', 'attrs' => ['disabled' =>'disabled']]);
    }

    function test_it_should_set_attr () {
        $this->bh->match('checkbox', function ($ctx) {
            $ctx->attr('name', null);
            $ctx->attr('type', 'button');
            $ctx->attr('disabled', false);
            $ctx->attr('value', null);
        });
        $this->assertEquals($this->bh->apply(['block' => 'checkbox']), '<div class="checkbox" type="button" disabled="false"></div>');
    }

    function test_it_should_not_override_user_attr () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button');
            $ctx->attr('disabled', true);
        });
        $this->assertEquals(
            $this->bh->apply([
                'block' =>'button',
                'attrs' => [
                    'type' => 'link',
                    'disabled' => null
                ]
            ]),
            '<div class="button" type="link"></div>');
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'control');
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button');
        });
        $this->assertEquals($this->bh->apply(['block' => 'button']), '<div class="button" type="button"></div>');
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'control', true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button');
        });
        $this->assertEquals($this->bh->apply(['block' => 'button']), '<div class="button" type="control"></div>');
    }

    function test_it_should_override_user_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button', true);
            $ctx->attr('disabled', null, true);
        });
        $this->assertEquals(
            $this->bh->apply([
                'block' =>'button',
                'attrs' => [
                    'type' =>'link',
                    'disabled' =>'disabled'
                ]
            ]), '<div class="button" type="button"></div>');
    }
}
