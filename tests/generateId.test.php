<?php

use BEM\BH;

class generateIdTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_generate_different_ids () {
        $this->bh->match('button', function ($ctx) {
            $this->assertNotEquals(
                $ctx->generateId(),
                $ctx->generateId()
            );
        });
        $this->bh->apply(['block' => 'button']);
    }

    function test_it_should_generate_different_ids_within_few_calls_of_apply () {
        $id1 = null;
        $id2 = null;

        $this->bh->match('button1', function ($ctx) use (&$id1) {
            $id1 = $ctx->generateId();
        });
        $this->bh->apply(['block' => 'button1']);

        $this->bh->match('button2', function ($ctx) use (&$id2) {
            $id2 = $ctx->generateId();
        });
        $this->bh->apply(['block' => 'button2']);

        $this->assertNotEquals($id1, $id2);
    }
}
