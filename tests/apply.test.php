<?php

use BEM\BH;

class applyTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_empty_string_on_null_bemjson () {
        $this->assertEquals($this->bh->apply(null), '');
    }

    function test_it_should_return_empty_string_on_falsy_template_result () {
        $this->bh->match('link', function ($ctx, $json) {
            if (empty($json['url'])) return null;
        });
        $this->assertEquals(
            '',
            $this->bh->apply(['block' => 'link']));
    }

    function test_it_should_return_valid_processed_element () {
        $this->bh->match('button', function ($ctx) {
            $inner = $ctx->apply(['block' => 'button', 'elem' => 'inner']);
            $this->assertEquals($inner->tag, 'span');
            $ctx->content($inner);
        });
        $this->bh->match('button__inner', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button">' .
                '<span class="button__inner"></span>' .
            '</div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_return_valid_processed_element_with_no_block_name () {
        $this->bh->match('button', function ($ctx) {
            $inner = $ctx->apply(['elem' => 'inner']);
            $this->assertEquals($inner->tag, 'span');
            $ctx->content($inner);
        });
        $this->bh->match('button__inner', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button">' .
                '<span class="button__inner"></span>' .
            '</div>',
            $this->bh->apply(['block' => 'button']));
    }
}
