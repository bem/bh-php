<?php

use BEM\BH;

class bhMatchGlobalTest extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_apply_beforeEach_template()
    {
        $this->bh->beforeEach(function ($ctx) {
            $ctx->tag('b');
            $ctx->bem(false);
        });
        $this->assertEquals(
            '<b>foo</b><b></b><b></b>',
            $this->bh->apply([
                [ 'content' => 'foo' ],
                [ 'block' => 'button' ],
                [ 'block' => 'input', 'elem' => 'control' ]
            ])
        );
    }

    public function test_it_should_match_beforeEach_before_other_template()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->tag('button');
        });
        $this->bh->beforeEach(function ($ctx) {
            $ctx->tag('span');
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->tag('strong');
        });
        $this->assertEquals(
            '<span class="button"></span>',
            $this->bh->apply([ 'block' => 'button' ])
        );
    }

    public function test_it_should_apply_several_beforeEach_templates_in_proper_order()
    {
        $this->bh->beforeEach(function ($ctx, $json) {
            $json->cls .= '2';
        });
        $this->bh->beforeEach(function ($ctx, $json) {
            $json->cls .= '1';
        });
        $this->assertEquals(
            '<div class="button foo12"></div>',
            $this->bh->apply([ 'block' => 'button', 'cls' => 'foo' ])
        );
    }


    public function test_it_should_apply_afterEach_template()
    {
        $this->bh->afterEach(function ($ctx) {
            $ctx->tag('b');
            $ctx->bem(false);
        });
        $this->assertEquals(
            '<b>foo</b><b></b><b></b>',
            $this->bh->apply([
                [ 'content' => 'foo' ],
                [ 'block' => 'button' ],
                [ 'block' => 'input', 'elem' => 'control' ]
            ])
        );
    }

    public function test_it_should_match_afterEach_after_other_template()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->tag('button', true);
        });
        $this->bh->afterEach(function ($ctx) {
            $ctx->tag('span', true);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->tag('strong', true);
        });

        $this->assertEquals(
            '<span class="button"></span>',
            $this->bh->apply([ 'block' => 'button' ])
        );
    }

    public function test_it_should_apply_several_afterEach_templates_in_proper_order()
    {
        $this->bh->afterEach(function ($ctx, $json) {
            $json->cls .= '2';
        });
        $this->bh->afterEach(function ($ctx, $json) {
            $json->cls .= '1';
        });
        $this->assertEquals(
            '<div class="button foo12"></div>',
            $this->bh->apply([ 'block' => 'button', 'cls' => 'foo' ])
        );
    }
}
