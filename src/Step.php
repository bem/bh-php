<?php

namespace BEM;

class Step {

    public $json;
    public $arr;
    public $blockName;
    public $blockMods;
    public $parentNode;
    public $index;
    public $position;
    public $tParams = [];

    /**
     * @param array $node lib
     */
    function __construct ($node) {
        foreach ($node as $k => &$v) {
            if (!property_exists($this, $k)) {
                throw new \Exception('Unknown key ' . $k);
            }
            $this->$k = $v;
        }
    }

    public function __set ($name, $value) {
        throw new \Exception("Cannot add new property \$$name to instance of " . __CLASS__);
    }
}
