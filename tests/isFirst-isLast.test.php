<?php

use BEM\BH;

class isFirstLastTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_calc_isFirst_isLast () {
        $this->bh->match('button__inner', function ($ctx) {
            if ($ctx->isFirst()) {
                $ctx->mod('first', 'yes');
            }
            if ($ctx->isLast()) {
                $ctx->mod('last', 'yes');
            }
        });
        $this->assertEquals(
            '<div class="button">' .
            '<div class="button__inner button__inner_first_yes"></div>' .
            '<div class="button__inner"></div>' .
            '<div class="button__inner button__inner_last_yes"></div>' .
            '</div>',
            $this->bh->apply(['block' => 'button', 'content' => [
                ['elem' => 'inner'], ['elem' => 'inner'], ['elem' => 'inner']]])
        );
    }

    function test_it_should_calc_isFirst_isLast_with_array_mes__s () {
        $this->bh->match('button__inner', function ($ctx) {
            if ($ctx->isFirst()) {
                $ctx->mod('first', 'yes');
            }
            if ($ctx->isLast()) {
                $ctx->mod('last', 'yes');
            }
        });
        $this->assertEquals(
            '<div class="button">' .
            '<div class="button__inner button__inner_first_yes"></div>' .
            '<div class="button__inner"></div>' .
            '<div class="button__inner button__inner_last_yes"></div>' .
            '</div>',
            $this->bh->apply([
                'block' => 'button',
                'content' => [
                    [['elem' => 'inner']],
                    [['elem' => 'inner'], [['elem' => 'inner']]]
                ]
            ])
        );
    }

    function test_it_should_calc_isFirst_isLast_for_single_elemen__t () {
        $this->bh->match('button__inner', function ($ctx) {
            if ($ctx->isFirst()) {
                $ctx->mod('first', 'yes');
            }
            if ($ctx->isLast()) {
                $ctx->mod('last', 'yes');
            }
        });
        $this->assertEquals(
            '<div class="button">' .
            '<div class="button__inner button__inner_first_yes button__inner_last_yes"></div>' .
            '</div>',
            $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner']])
        );
    }

    function test_it_should_ignore_empty_array_items () {
        $this->bh->match('button', function ($ctx) {
            if ($ctx->isFirst()) {
                $ctx->mod('first', 'yes');
            }
            if ($ctx->isLast()) {
                $ctx->mod('last', 'yes');
            }
        });
        $this->assertEquals(
            '<div class="button button_first_yes"></div>' .
                '<div>' .
                '<div class="button button_first_yes"></div>' .
                '<div class="button"></div>' .
                '<div class="button button_last_yes"></div>' .
            '</div>',
            $this->bh->apply([
                false,
                ['block' => 'button'],
                ['content' => [
                    false,
                    ['block' => 'button'],
                    ['block' => 'button'],
                    ['block' => 'button'],
                    [null]
                ]],
                null
            ])
        );
    }
}
