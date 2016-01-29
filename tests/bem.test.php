<?php

use BEM\BH;

class ctxBemTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_return_bem_by_default()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(null, $ctx->bem());
        });
        $this->bh->apply(['block' => 'button']);
    }

    public function test_it_should_set_bem_to_false__()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(false);
        });
        $this->assertEquals(
            '<div></div>',
            $this->bh->apply(['block' => 'button']));
    }

    public function test_it_should_not_override_user_bem__()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(false);
        });
        $this->assertEquals(
            '<div class="button"></div>',
            $this->bh->apply(['block' => 'button', 'bem' => true]));
    }

    public function test_it_should_not_override_later_declarations__()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(false);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(true);
        });
        $this->assertEquals(
            '<div class="button"></div>',
            $this->bh->apply(['block' => 'button']));
    }

    public function test_it_should_override_later_declarations_with_force_fla__g()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(false, true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(true);
        });
        $this->assertEquals(
            '<div></div>',
            $this->bh->apply(['block' => 'button']));
    }

    public function test_it_should_override_user_declarations_with_force_fla__g()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->bem(false, true);
        });
        $this->assertEquals(
            '<div></div>',
            $this->bh->apply(['block' => 'button', 'bem' => true]));
    }
}
