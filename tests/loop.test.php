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

    /**
     * @medium
     * @expectedException Exception
     * @expectedExceptionMessage Infinite matcher loop detected
     */
    function test_it_should_throw_an_error_on_json_loop_detection () {
        $button = ['block' => 'button'];
        $this->bh->match('button', function ($ctx) use ($button) {
            $ctx->content($button);
        });
        $this->bh->apply($button);
    }

    /**
     * @medium
     * @expectedException Exception
     * @expectedExceptionMessage Infinite matcher loop detected
     */
    function test_it_should_throw_an_error_on_matcher_loop_detection () {
        $this->bh->match('input', function ($ctx) {
            $ctx->content(['block' => 'button']);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['block' => 'input']);
        });
        $this->bh->apply(['block' => 'button']);
    }
}
