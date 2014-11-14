<?php

use BEM\BH;

class jsonElemModsTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_match_and_process_boolean_elemMods_ () {
        $this->bh->match('button__inner_valid', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button"><span class="button__inner button__inner_valid"></span></div>',
            $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner', 'elemMods' => ['valid' => true]]])
        );
    }
    function test_it_should_match_and_process_string_elemMods_ () {
        $this->bh->match('button__inner_valid_yes', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button"><span class="button__inner button__inner_valid_yes"></span></div>',
            $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner', 'elemMods' => ['valid' => 'yes']]])
        );
    }
    function test_it_should_not_match_string_values_of_boolean_elemMod_s () {
        $this->bh->match('button__inner_valid', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button"><div class="button__inner button__inner_valid_valid"></div></div>',
            $this->bh->apply(['block' => 'button', 'content' => ['elem' => 'inner', 'elemMods' => ['valid' => 'valid']]])
        );
    }
}
