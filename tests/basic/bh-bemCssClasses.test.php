<?php

use BEM\BH;

class bh_bemCssClasses extends PHPUnit_Framework_TestCase
{
    public function test_itShouldParseCssClasses()
    {
        $naming = ['elem' => '__', 'mod' => '_', 'val' => '_'];

        $this->assertEquals(
            [ 'block' => 'button',
              'blockMod' => 'disabled',
              'blockModVal' => true,
              'elem' => 'control',
              'elemMod' => null,
              'elemModVal' => null],
            BH::parseBemCssClasses('button_disabled__control', $naming)
        );

        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => 'mod',
                'blockModVal' => 'val',
                'elem' => 'elem',
                'elemMod' => 'modelem',
                'elemModVal' => 'valelem'],
            BH::parseBemCssClasses('button_mod_val__elem_modelem_valelem', $naming)
        );

        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => 'disabled',
                'blockModVal' => true,
                'elem' => 'control',
                'elemMod' => null,
                'elemModVal' => null],
            BH::parseBemCssClasses('button--disabled__control', ['mod' => '--'] + $naming)
        );

        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => 'mod',
                'blockModVal' => 'val',
                'elem' => 'elem',
                'elemMod' => 'modelem',
                'elemModVal' => 'valelem'],
            BH::parseBemCssClasses('button--mod_val__elem--modelem_valelem', ['mod' => '--'] + $naming)
        );

        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => 'mod',
                'blockModVal' => 'val',
                'elem' => 'elem',
                'elemMod' => 'modelem',
                'elemModVal' => 'valelem'],
            BH::parseBemCssClasses('button--mod--val__elem--modelem--valelem', ['mod' => '--', 'val' => '--'] + $naming)
        );

        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => null,
                'blockModVal' => null,],
            BH::parseBemCssClasses('button', ['mod' => '--'] + $naming)
        );
        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => null,
                'blockModVal' => null,
            'elem' => 'control',
            'elemMod' => 'type',
            'elemModVal' => 'span'],
            BH::parseBemCssClasses('button__control_type_span', ['mod' => '_'] + $naming)
        );
        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => null,
                'blockModVal' => null,
                'elem' => 'control',
                'elemMod' => 'type',
                'elemModVal' => true],
            BH::parseBemCssClasses('button__control_type', $naming)
        );
        $this->assertEquals(
            [ 'block' => 'button',
                'blockMod' => null,
                'blockModVal' => null,
                'elem' => 'control',
                'elemMod' => null,
                'elemModVal' => null],
            BH::parseBemCssClasses('button__control', $naming)
        );
    }
}
