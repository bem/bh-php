<?php

use BEM\BH;

class positionTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_calc_position____ () {
        $this->bh->match('button__inner', function ($ctx) {
            $ctx->mod('pos', $ctx->position());
        });
        $this->assertEquals(
            '<div class="button">' .
            '<div class="button__inner button__inner_pos_1"></div>' .
            '<div class="button__inner button__inner_pos_2"></div>' .
            '<div class="button__inner button__inner_pos_3"></div>' .
            '</div>',
            $this->bh->apply([
                'block' => 'button',
                'content' => [['elem' => 'inner'], ['elem' => 'inner'], ['elem' => 'inner']]
            ])
        );
    }
    function test_it_should_calc_position_with_array_mess_ () {
        $this->bh->match('button__inner', function ($ctx) {
            $ctx->mod('pos', $ctx->position());
        });
        $this->assertEquals(
            '<div class="button">' .
            '<div class="button__inner button__inner_pos_1"></div>' .
            '<div class="button__inner button__inner_pos_2"></div>' .
            '<div class="button__inner button__inner_pos_3"></div>' .
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
    function test_it_should_calc_position_for_single_element_ () {
        $this->bh->match('button__inner', function ($ctx) {
            $ctx->mod('pos', $ctx->position());
        });
        $this->assertEquals(
            '<div class="button">' .
            '<div class="button__inner button__inner_pos_1"></div>' .
            '</div>',
            $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner']])
        );
    }
}
