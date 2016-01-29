<?php

use BEM\BH;
use BEM\JsonCollection;
use BEM\Json;

class mixTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_return_mix()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                JsonCollection::normalize(['block' => 'mix']),
                $ctx->mix()
            );
        });
        $this->bh->apply(['block' => 'button', 'mix' => ['block' => 'mix']]);
    }

    public function test_it_should_set_single_mix()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix']);
        });
        $this->assertEquals(
            '<div class="button mix"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_set_array_mix()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix([['block' => 'mix']]);
        });
        $this->assertEquals(
            '<div class="button mix"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_extend_user_single_mix()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix2']);
        });
        $this->assertEquals(
            '<div class="button mix1 mix2"></div>',
            $this->bh->apply(['block' => 'button', 'mix' => ['block' => 'mix1']])
        );
    }

    public function test_it_should_extend_user_array_mix()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix([['block' => 'mix']]);
        });
        $this->assertEquals(
            '<div class="button user-mix mix"></div>',
            $this->bh->apply(['block' => 'button', 'mix' => [['block' => 'user-mix']]])
        );
    }

    public function test_it_should_extend_later_declarations()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix2']);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix1']);
        });
        $this->assertEquals(
            '<div class="button mix1 mix2"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_override_later_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix2'], true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix1']);
        });
        $this->assertEquals(
            '<div class="button mix2"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_override_user_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix(['block' => 'mix'], true);
        });
        $this->assertEquals(
            '<div class="button mix"></div>',
            $this->bh->apply(['block' => 'button', 'mix' => ['block' => 'user-mix']])
        );
    }

    public function test_it_should_inherit_block_name()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->mix([
                ['mods' => ['disabled' => true]],
                ['elem' => 'input', 'mods' => ['active' => true]],
                ['block' => 'clearfix']
            ]);
        });
        $this->assertEquals(
            '<div class="button button_disabled button__input button__input_active clearfix"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_inherit_element_name()
    {
        $this->bh->match('button__control', function ($ctx) {
            $ctx->mix([
                ['mods' => ['disabled' => true]],
                ['elem' => 'input', 'mods' => ['active' => true]],
                ['block' => 'clearfix']
            ]);
        });
        $this->assertEquals(
            '<div class="button__control button__control_disabled button__input button__input_active clearfix"></div>',
            $this->bh->apply(['block' => 'button', 'elem' => 'control'])
        );
    }
}
