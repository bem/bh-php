<?php

namespace BEM;

/**
 * BemJson node context
 */
class Json {

    /** @var string */
    public $block;

    /** @var string */
    public $elem;

    /** @var mixed */
    public $content;

    /** @var Mods */
    protected $mods;
    /** @var Mods */
    protected $blockMods;
    /** @var Mods */
    protected $elemMods;

    /** @var array */
    public $attrs;

    public $_stop = false;
    public $__m = [];

    /**
     * Constructor
     * @param array|object $node
     */
    public function __construct ($node) {
        if (is_object($node)) {
            $node = (array)($node);
        }
        elseif (!is_array($node)) {
            throw new \Exception('Incorrect data for Json creation');
        }

        $this->block    = isset($node['block']) ? $node['block'] : null;
        $this->elem     = isset($node['elem']) ? $node['elem'] : null;
        $this->setContent(isset($node['content'])? $node['content'] : null);
        $this->mods     = isset($node['mods']) ? new Mods($node['mods']) : null;
        $this->elemMods = isset($node['elemMods']) ? new Mods($node['elemMods']) : null;

        isset($node['mix']) && ($this->mix = JsonCollection::normalize($node['mix']));

        unset($node['block'], $node['elem'], $node['content'], $node['mods'], $node['elemMods'], $node['mix']);

        // param
        foreach ($node as $k => $v) {
            $this->$k = $v;
        }
    }

    public function setContent ($content) {
        $this->content = is_null($content) || is_scalar($content) ? $content
            : JsonCollection::normalize($content);
    }

    public function __get ($name) {
        if ($name === 'mods' || $name === 'blockMods' || $name === 'elemMods') {
            if (is_null($this->$name)) {
                $this->$name = new Mods();
            }
            return $this->$name;
        }
        return null;
    }

    public function __set ($name, $value) {
        if ($name === 'mods' || $name === 'blockMods' || $name === 'elemMods') {
            $this->$name = is_array($value) ? new Mods($value) : $value;
        } else {
            $this->$name = $value;
        }
    }

    public function __isset ($name) {
        if ($name === 'mods' || $name === 'blockMods' || $name === 'elemMods') {
            return !empty($this->$name);
        }
        return isset($this->$name);
    }

/*
    public function iterateNodes ($fn) {
        if (isList($this->content)) {
            do {
                $flatten = false;
                for ($i = 0, $l = sizeof($content); $i < $l; $i++) {
                    if (isList($content[$i])) {
                        $flatten = true;
                        break;
                    }
                }
                if ($flatten) {
                    $json['content'] = call_user_func_array("array_merge", $content);
                    $content = &$json['content'];
                }
            } while ($flatten);

            $j = 0;
            foreach ($this->content as $child) {
                if (!is_array($child)) {
                    return;
                }
                $fn(new Json($child));
            }
            $this->_listLength = $j;
        } elseif ($this->content) {
            $fn($this);
        }
    }*/

}
