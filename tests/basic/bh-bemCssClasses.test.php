<?php

use BEM\BH;

class bh_bemCssClasses extends PHPUnit_Framework_TestCase
{
    public function test_itShouldParseCssClasses()
    {
        $this->assertEquals(
            [ 'block' => 'button',
              'blockMod' => 'disabled',
              'blockModVal' => true,
              'elem' => 'control',
              'elemMod' => null,
              'elemModVal' => null],
            BH::parseBemCssClasses('button_disabled__control')
        );
    }
}
