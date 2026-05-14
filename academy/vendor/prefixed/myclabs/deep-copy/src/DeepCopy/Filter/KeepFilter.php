<?php
/**
 * @license MIT
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\DeepCopy\Filter;

class KeepFilter implements Filter
{
    /**
     * Keeps the value of the object property.
     *
     * {@inheritdoc}
     */
    public function apply($object, $property, $objectCopier)
    {
        // Nothing to do
    }
}
