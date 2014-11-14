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
