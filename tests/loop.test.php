<?php

use BEM\BH;

class loopJsAttrTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
        $this->bh->enableInfiniteLoopDetection(true);
    }

    function test_it_should_throw_an_error_on_json_loop_detection () {
        $this->setExpectedException('Exception');
        $button = ['block' => 'button'];
        $this->bh->match('button', function ($ctx) use ($button) {
            $ctx->content($button);
        });
        $this->bh->apply($button);
    }

    function test_it_should_throw_an_error_on_matcher_loop_detection () {
        $this->setExpectedException('Exception');
        $this->bh->match('input', function ($ctx) {
            $ctx->content(['block' => 'button']);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['block' => 'input']);
        });
        $this->bh->apply(['block' => 'button']);
    }
}
