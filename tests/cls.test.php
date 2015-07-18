<?php

use BEM\BH;

class cls extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_cls () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'btn',
                $ctx->cls());
        });
        $this->bh->apply(['block' => 'button', 'cls' => 'btn']);
    }
    function test_it_should_set_cls () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('btn');
        });
        $this->assertEquals(
            '<div class="button btn"></div>',
            $this->bh->apply(['block' => 'button']));
    }
    function test_it_should_trim_cls () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('  btn  ');
        });
        $this->assertEquals(
            '<div class="button btn"></div>',
            $this->bh->apply(['block' => 'button']));
    }
    function test_it_should_escape_cls () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('url="a=b&c=d"');
        });
        $this->assertEquals(
            '<div class="button url=&quot;a=b&amp;c=d&quot;"></div>',
            $this->bh->apply(['block' => 'button']));
    }
    function test_it_should_not_override_user_cls () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('btn');
        });
        $this->assertEquals(
            '<div class="button user-btn"></div>',
            $this->bh->apply(['block' => 'button', 'cls' => 'user-btn']));
    }
    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('control');
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('btn');
        });
        $this->assertEquals(
            '<div class="button btn"></div>',
            $this->bh->apply(['block' => 'button']));
    }
    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('control', true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('btn');
        });
        $this->assertEquals(
            '<div class="button control"></div>',
            $this->bh->apply(['block' => 'button']));
    }
    function test_it_should_override_user_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->cls('btn', true);
        });
        $this->assertEquals(
            '<div class="button btn"></div>',
            $this->bh->apply(['block' => 'button', 'cls' => 'user-btn']));
    }
}
