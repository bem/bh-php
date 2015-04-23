<?php

use BEM\BH;

class processTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_return_valid_processed_json () {
        $this->bh->match('search', function ($ctx) {
            $ctx->content($ctx->process([ 'block' => 'input' ]));
        });
        $this->bh->match('input', function ($ctx) {
            $ctx->tag('input');
        });
        $this->assertEquals(
            '<div class="search"><input class="input"/></div>',
            $this->bh->apply([ 'block' => 'search' ])
        );
    }

    function test_it_should_return_valid_processed_element_with_no_block_name () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content($ctx->process([ 'elem' => 'inner' ]));
        });
        $this->bh->match('button__inner', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button"><span class="button__inner"></span></div>',
            $this->bh->apply([ 'block' => 'button' ])
        );
    }
}
