<?php

use BEM\BH;

class contentTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_bemjson_content () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'Hello',
                $ctx->content());
        });
        $this->bh->apply(['block' => 'button', 'content' => 'Hello']);
    }

    function test_it_should_set_bemjson_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'text']);
        });
        $this->assertEquals(
            '<div class="button"><div class="button__text"></div></div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_set_bemjson_array_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content([['elem' => 'text1'], ['elem' => 'text2']]);
        });
        $this->assertEquals(
            '<div class="button"><div class="button__text1"></div><div class="button__text2"></div></div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_set_bemjson_string_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content('Hello World');
        });
        $this->assertEquals(
            '<div class="button">Hello World</div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_set_bemjson_numeric_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(123);
        });
        $this->assertEquals(
            '<div class="button">123</div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_set_bemjson_zero_numeric_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(0);
        });
        $this->assertEquals(
            '<div class="button">0</div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_not_override_user_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'text']);
        });
        $this->assertEquals(
            '<div class="button">Hello</div>',
            $this->bh->apply(['block' => 'button', 'content' => 'Hello']));
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'text2']);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'text1']);
        });
        $this->assertEquals(
            '<div class="button"><div class="button__text1"></div></div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'text2'], true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'text1']);
        });
        $this->assertEquals(
            '<div class="button"><div class="button__text2"></div></div>',
            $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_override_user_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content('text', true);
        });
        $this->assertEquals(
            '<div class="button">text</div>',
            $this->bh->apply(['block' => 'button', 'content' => 'Hello']));
    }

}
