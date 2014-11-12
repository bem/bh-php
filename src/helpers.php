<?php

namespace BEM;

/**
 * Checks an array to be a simple array
 * @param mixed $array
 */
function isList ($array) {
    if (empty($array) || !is_array($array)) {
        return null;
    }

    $l = sizeof($array);

    // empty and arrays with only 0 key are lists
    if ($l <= 1) {
        return $l === 1 ? isset($array[0]) : true;
    }

    // array with last and inner keys are exists
    return isset($array[$l - 1]) && ($l <= 2 || isset($array[$l >> 1]));
}

/**
 * Group up selectors by some key
 * @param array data
 * @param string key
 * @return array
 */
function groupBy ($data, $key) {
    $res = [];
    for ($i = 0, $l = sizeof($data); $i < $l; $i++) {
        $item = $data[$i];
        $value = $item[$key] || '__no_value__';
        if (empty($res[$value])) {
            $res[$value] = [];
        }
        $res[$value][] = $item;
    }
    return $res;
}

// todo: add encoding here
function xmlEscape($str) {
    return htmlspecialchars($str, ENT_NOQUOTES);
}

function attrEscape($str) {
    return htmlspecialchars($str, ENT_QUOTES);
}

function strEscape($str) {
    return str_replace(array('\\', '"'), array('\\\\', '\\"'), $str);
}

function toBemCssClasses($json, $base, $parentBase) {
    $res = '';

    if ($parentBase !== $base) {
        if ($parentBase) {
            $res .= ' ';
        }
        $res .= $base;
    }

    $mods = isset($json['mods']) ? $json['mods'] : (isset($json['elem']) ? $json['elemMods'] : false);
    if ($mods) {
        foreach ($json['mods'] as $mod) {
            if ($mod) {
                $res .= ' ' . $base . '_' . $i . ($mod === true ? '' : '_' . $mod);
            }
        }
    }

    return $res;
}
