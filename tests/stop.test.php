<?php

use BEM\BH;

class stop extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setupBhInstance()
    {
        $this->bh = new BH();
    }

    public function test_it_should_prevent_base_matching()
    {
        $this->bh->match('button', function ($ctx) {
            $ctx->tag('button', true);
        });

        $this->bh->match('button', function ($ctx) {
            $ctx->tag('span');
            $ctx->stop();
        });

        $this->assertEquals(
            '<span class="button"></span>',
            $this->bh->apply(['block' => 'button'])
        );
    }
}
