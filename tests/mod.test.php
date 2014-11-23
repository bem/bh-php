<?php

use BEM\BH;

class modTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_mod () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'button',
                $ctx->mod('type')
            );
        });
        $this->bh->apply(['block' => 'button', 'mods' => ['type' => 'button']]);
    }

    function test_it_should_return_null_mod () {
        $this->bh->match('button', function ($ctx) {
            $this->assertNull($ctx->mod('type'));
        });
        $this->bh->apply(['block' => 'button']);
    }

    function test_it_should_return_boolean_mod () {
        $this->bh->match('button', function ($ctx) {
            $this->assertTrue($ctx->mod('disabled'));
        });
        $this->bh->apply(['block' => 'button', 'mods' => ['disabled' => true]]);
    }

    function test_it_should_set_mod () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'button');
        });
        $this->assertEquals(
            '<div class="button button_type_button"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_set_boolean_mod () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('disabled', true);
        });
        $this->assertEquals(
            '<div class="button button_disabled"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_not_override_user_mod () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'button');
            $ctx->mod('disabled', true);
        });
        $this->assertEquals(
            '<div class="button button_type_link"></div>',
            $this->bh->apply([
                'block' => 'button',
                'mods' => [
                    'type' => 'link',
                    'disabled' => null
                ]
            ])
        );
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'control');
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'button');
        });
        $this->assertEquals(
            '<div class="button button_type_button"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'control', true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'button');
        });
        $this->assertEquals(
            '<div class="button button_type_control"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_override_user_declarations_with_force_flag () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'button', true);
            $ctx->mod('disabled', null, true);
        });
        $this->assertEquals(
            '<div class="button button_type_button"></div>',
            $this->bh->apply([
                'block' => 'button',
                'mods' => [
                    'type' => 'link',
                    'disabled' => true
                ]
            ])
        );
    }
}
