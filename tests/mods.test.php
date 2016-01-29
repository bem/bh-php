<?php

use BEM\BH;

class mods extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_return_empty_mods()
    {
        $this->bh->match('button', function ($ctx) {
            $mods = $ctx->mods(); // is_a BEM\Mods
            $this->assertInternalType('object', $mods);
            $this->assertEmpty((array)$ctx->mods());
        });
        $this->bh->apply(['block' => 'button']);
    }

    public function test_it_should_return_mods()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'button',
                $ctx->mods()->type
            );
        });
        $this->bh->apply(['block' => 'button', 'mods' => ['type' => 'button']]);
    }

    public function test_it_should_return_elem_mods()
    {
        $this->bh->match('button__control', function ($ctx) {
            $this->assertEquals(
                'button',
                $ctx->mods()->type
            );
        });
        $this->bh->apply([ 'block' => 'button', 'elem' => 'control', 'elemMods' => [ 'type' => 'button' ] ]);
    }

    public function test_it_should_return_boolean_mods()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                true,
                $ctx->mods()->disabled
            );
        });
        $this->bh->apply(['block' => 'button', 'mods' => ['disabled' => true]]);
    }

    public function test_it_should_set_mods()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mods(['type' => 'button', 'disabled' => true]);
        });
        $this->assertEquals(
            '<div class="button button_type_button button_disabled"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_not_override_user_mods()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mods([
                'type' => 'button',
                'disabled' => true
            ]);
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

    public function test_it_should_not_override_later_declarations()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mods(['type' => 'control']);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->mods(['type' => 'button', 'disabled' => true]);
        });
        $this->assertEquals(
            '<div class="button button_type_button button_disabled"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_override_later_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mods(['type' => 'control'], true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->mods(['type' => 'button']);
        });
        $this->assertEquals(
            '<div class="button button_type_control"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_override_user_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mods(['type' => 'button'], true);
        });
        $this->assertEquals(
            '<div class="button button_type_button"></div>',
            $this->bh->apply(['block' => 'button', 'mods' => ['type' => 'link']])
        );
    }
}
