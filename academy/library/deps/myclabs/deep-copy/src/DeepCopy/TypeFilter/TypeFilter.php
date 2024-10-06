<?php
/**
 * @license MIT
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\DeepCopy\TypeFilter;

interface TypeFilter
{
    /**
     * Applies the filter to the object.
     *
     * @param mixed $element
     */
    public function apply($element);
}
