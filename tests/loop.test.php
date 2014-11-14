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

    function test_it_should_throw_an_error_on_json_loop_detection ($done) {
        $button = ['block' => 'button'];
        $this->bh->match('button', function ($ctx) use ($button) {
            $ctx->content($button);
        });
        try {
            $this->bh->apply($button);
            done(new Error('no error was thrown'));
        } catch (Exception $e) {
            done();
        }
    }

    function test_it_should_throw_an_error_on_matcher_loop_detection ($done) {
        $this->bh->match('input', function ($ctx) {
            $ctx->content(['block' => 'button']);
        });
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['block' => 'input']);
        });
        try {
            $this->bh->apply(['block' => 'button']);
            done(new Error('no error was thrown'));
        } catch (Exception $e) {
            done();
        }
    }
}
