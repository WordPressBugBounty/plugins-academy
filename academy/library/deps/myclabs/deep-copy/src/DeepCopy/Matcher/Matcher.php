<?php
/**
 * @license MIT
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
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
