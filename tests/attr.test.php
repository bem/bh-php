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
            $this->assertEquals('button', $ctx->attr('type'));
        });
        $this->bh->apply(['block' => 'button', 'attrs' => ['type' =>'button']]);
    }

    function test_it_should_return_null_attr () {
        $this->bh->match('button', function ($ctx) {
            $this->assertNull($ctx->attr('type'));
        });
        $this->bh->apply(['block' => 'button']);
    }

    function test_it_should_set_attr () {
        $this->bh->match('checkbox', function ($ctx) {
            $ctx->attr('name', null);
            $ctx->attr('type', 'button');
            $ctx->attr('disabled', false);
            $ctx->attr('hidden', true);
            $ctx->attr('value', null);
        });
        $this->assertEquals(
            '<div class="checkbox" type="button" hidden></div>',
            $this->bh->apply(['block' => 'checkbox'])
        );
    }

    function test_it_should_not_override_user_attr () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button');
            $ctx->attr('disabled', true);
        });
        $this->assertEquals(
            '<div class="button" type="link"></div>',
            $this->bh->apply([
                'block' =>'button',
                'attrs' => [
                    'type' => 'link',
                    'disabled' => null
                ]
            ])
        );
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'control');
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button');
        });
        $this->assertEquals('<div class="button" type="button"></div>', $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'control', true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button');
        });
        $this->assertEquals('<div class="button" type="control"></div>', $this->bh->apply(['block' => 'button']));
    }

    function test_it_should_override_user_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->attr('type', 'button', true);
            $ctx->attr('disabled', null, true);
        });
        $this->assertEquals(
            '<div class="button" type="button"></div>',
            $this->bh->apply([
                'block' =>'button',
                'attrs' => [
                    'type' =>'link',
                    'disabled' =>'disabled'
                ]
            ])
        );
    }
}
