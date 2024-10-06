<?php
/**
 * @license MIT
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\DeepCopy\Filter\Doctrine;

use Academy\DeepCopy\Filter\Filter;

/**
 * @final
 */
class DoctrineProxyFilter implements Filter
{
    /**
     * Triggers the magic method __load() on a Doctrine Proxy class to load the
     * actual entity from the database.
     *
     * {@inheritdoc}
     */
    public function apply($object, $property, $objectCopier)
    {
        $object->__load();
    }
}
