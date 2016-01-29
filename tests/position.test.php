<?php

use BEM\BH;

class positionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_calc_position()
    {
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
    public function test_it_should_calc_position_with_array_mess()
    {
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
    public function test_it_should_calc_position_for_single_element()
    {
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
