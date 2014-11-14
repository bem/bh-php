<?php

use BEM\BH;

class htmlTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_bemjson_html () {
        $this->bh->match('button', function ($ctx) {
            $this->assertEquals(
                'Hello',
                $ctx->html());
        });
        $this->bh->apply(['block' => 'button', 'html' => 'Hello']);
    }

    function test_it_should_set_bemjson_html () {
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('<i>&nbsp;</i>');
        });
        $this->assertEquals(
            '<div class="icon"><i>&nbsp;</i></div>',
            $this->bh->apply(['block' => 'icon']));
    }

    function test_it_should_not_override_user_html () {
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('<i>&nbsp;</i>');
        });
        $this->assertEquals(
            '<div class="icon">&nbsp;</div>',
            $this->bh->apply(['block' => 'icon', 'html' => '&nbsp;']));
    }

    function test_it_should_not_override_later_declarations () {
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('text2');
        });
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('text1');
        });
        $this->assertEquals(
            '<div class="icon">text1</div>',
            $this->bh->apply(['block' => 'icon']));
    }

    function test_it_should_override_later_declarations_with_force_flag () {
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('text2', true);
        });
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('text1');
        });
        $this->assertEquals(
            '<div class="icon">text2</div>',
            $this->bh->apply(['block' => 'icon']));
    }

    function test_it_should_override_user_declarations_with_force_flag () {
        $this->bh->match('icon', function ($ctx) {
            $ctx->html('text', true);
        });
        $this->assertEquals(
            '<div class="icon">text</div>',
            $this->bh->apply(['block' => 'icon', 'html' => 'Hello']));
    }
}
