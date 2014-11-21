<?php

use BEM\BH;

class applyBaseTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_apply_templates_for_new_mod () {
        $this->bh->match('button', function ($ctx) {
            $ctx->mod('type', 'span');
            $ctx->applyBase();
        });
        $this->bh->match('button_type_span', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<span class="button button_type_span"></span>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_apply_base_matcher_for_content () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content([
                ['elem' => 'base-before'],
                $ctx->content(),
                ['elem' => 'base-after']
            ], true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->applyBase();
            $ctx->content([
                ['elem' => 'before'],
                $ctx->content(),
                ['elem' => 'after']
            ], true);
        });
        $this->assertEquals(
            '<div class="button">' .
                '<div class="button__before"></div>' .
                '<div class="button__base-before"></div>' .
                'Hello' .
                '<div class="button__base-after"></div>' .
                '<div class="button__after"></div>' .
            '</div>',
            $this->bh->apply(['block' => 'button', 'content' => 'Hello'])
        );
    }

    function test_it_should_apply_base_matcher_while_wrapping () {
        $this->bh->match('button', function ($ctx) {
            return [
                ['elem' => 'base-before'],
                $ctx->json(),
                ['elem' => 'base-after']
            ];
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->applyBase();
            return [
                ['elem' => 'before'],
                $ctx->json(),
                ['elem' => 'after']
            ];
        });
        $this->assertEquals(
            '<div class="button__before"></div>' .
                '<div class="button__base-before"></div>' .
                    '<div class="button">' .
                        'Hello' .
                    '</div>' .
                '<div class="button__base-after"></div>' .
            '<div class="button__after"></div>',
            $this->bh->apply(['block' => 'button', 'content' => 'Hello'])
        );
    }

    function test_it_should_preserve_tParam () {
        $this->bh->match('select__control', function ($ctx) {
            $ctx->tParam('lol', 33);
        });
        $this->bh->match('select', function ($ctx) {
            $ctx->tParam('foo', 22);
        });
        $this->bh->match('select_disabled', function ($ctx) {
            $ctx->applyBase();
            $ctx->tParam('bar', 11);
        });
        $this->bh->match('select__control', function ($ctx) {
            $ctx->applyBase();
            $this->assertEquals(66, $ctx->tParam('foo') + $ctx->tParam('bar') + $ctx->tParam('lol'));
        });
        $this->bh->apply(['block' => 'select', 'mods' => ['disabled' => true], 'content' => ['elem' => 'control']]);
    }

    function test_it_should_preserve_position () {
        $this->bh->match('button', function ($ctx) {
            if ($ctx->isFirst()) {
                $ctx->mod('first', 'yes');
            }
            if ($ctx->isLast()) {
                $ctx->mod('last', 'yes');
            }
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->applyBase();
        });
        $this->assertEquals(
            '<div class="button button_first_yes"></div>' .
            '<div class="button"></div>' .
            '<div class="button button_last_yes"></div>',
            $this->bh->apply([
                ['block' => 'button'],
                ['block' => 'button'],
                ['block' => 'button']
            ])
        );
    }
}
