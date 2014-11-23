<?php

namespace BEM;

class Mods {

    /**
     * Mods
     * @param array [$mods]
     */
    public function __construct ($mods = null) {
        if (!$mods) {
            return;
        }
        foreach ($mods as $k => $v) {
            $this->$k = $v;
        }
    }

    public function __get ($k) {
        // suppress notices in templates
    }

}
