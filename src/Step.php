<?php

namespace BEM;

class Step
{
    public $json;
    public $arr;
    public $index;
    public $block;
    public $mods;
    public $position;
    public $parentNode;

    public $tParams = [];

    /**
     * @param array $node lib
     */
    public function __construct($json, $arr, $index, $blockName, $blockMods, $position = 0, Step $parentNode = null)
    {
        $this->json = $json;
        $this->arr = $arr;
        $this->index = $index;
        $this->block = $blockName;
        $this->mods = $blockMods;
        $this->position = $position;
        $this->parentNode = $parentNode;
    }

    public function __set($name, $value)
    {
        throw new \Exception("Cannot add new property \$$name to instance of " . __CLASS__);
    }
}
