<?php
/**
 * @license MIT
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\DeepCopy\Matcher;

interface Matcher
{
    /**
     * @param object $object
     * @param string $property
     *
     * @return boolean
     */
    public function matches($object, $property);
}
