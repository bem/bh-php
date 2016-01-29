<?php

use BEM\BH;

class attrsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_return_empty_attrs()
    {
        $this->bh->match('button', function ($ctx) {
            $attrs = $ctx->attrs();
            $this->assertInternalType('array', $attrs);
            $this->assertEmpty($ctx->attrs());
        });
        $this->bh->apply(['block' => 'button']);
    }

    public function test_it_should_return_attrs()
    {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals('button', $ctx->attrs()['type']);
        });
        $this->bh->apply(['block' => 'button', 'attrs' => ['type' => 'button']]);
    }

    public function test_it_should_set_attrs()
    {
        $this->bh->match('checkbox', function ($ctx) {
            $ctx->attrs([
                'name' => null,
                'type' => 'button',
                'disabled' => false,
                'hidden' => true,
                'value' => null
            ]);
        });
        $this->assertEquals(
            '<div class="checkbox" type="button" hidden></div>',
            $this->bh->apply(['block' => 'checkbox'])
        );
    }

    public function test_it_should_not_override_user_attrs()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->attrs([
                'type' => 'button',
                'disabled' => true
            ]);
        });
        $this->assertEquals(
            '<div class="button" type="link" name="button"></div>',
            $this->bh->apply([
                'block' => 'button',
                'attrs' => [
                    'type' => 'link',
                    'disabled' => null,
                    'name' => 'button'
                ]
            ])
        );
    }

    public function test_it_should_not_override_later_declarations()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->attrs(['type' => 'control', 'tabindex' => 0]);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->attrs(['type' => 'button']);
        });
        $this->assertEquals(
            '<div class="button" type="button" tabindex="0"></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    public function test_it_should_override_later_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->attrs(['type' => 'control'], true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->attrs(['type' => 'button', 'tabindex' => 0]);
        });
        $this->assertEquals('<div class="button" type="control" tabindex="0"></div>',
            $this->bh->apply(['block' => 'button']));
    }

    public function test_it_should_override_user_declarations_with_force_flag()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->attrs([
                'type' => 'button',
                'disabled' => null
            ], true);
        });
        $this->assertEquals(
            '<div class="button" type="button" name="button"></div>',
            $this->bh->apply([
                'block' => 'button',
                'attrs' => [
                    'type' => 'link',
                    'disabled' => 'disabled',
                    'name' => 'button'
                ]
            ])
        );
    }
}
