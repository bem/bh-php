<?php

use BEM\BH;

class paramTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_return_param()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'button',
                $ctx->param('type')
            );
        });
        $this->bh->apply(['block' => 'button', 'type' => 'button']);
    }

    public function test_it_should_set_param()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'button');
        });
        $this->assertEquals(
            'button',
            $this->bh->processBemJson(['block' => 'button'])->type
        );
    }

    public function test_it_should_not_override_user_param()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'button');
        });
        $this->assertEquals(
            'link',
            $this->bh->processBemJson(['block' => 'button', 'type' => 'link'])->type
        );
    }

    public function test_it_should_not_override_later_declarations()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'control');
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'button');
        });
        $this->assertEquals(
            'button',
            $this->bh->processBemJson(['block' => 'button'])->type
        );
    }

    public function test_it_should_override_later_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'control', true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'button');
        });
        $this->assertEquals(
            'control',
            $this->bh->processBemJson(['block' => 'button'])->type
        );
    }

    public function test_it_should_override_user_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->param('type', 'button', true);
        });
        $this->assertEquals(
            'button',
            $this->bh->processBemJson(['block' => 'button', 'type' => 'link'])->type
        );
    }
}
