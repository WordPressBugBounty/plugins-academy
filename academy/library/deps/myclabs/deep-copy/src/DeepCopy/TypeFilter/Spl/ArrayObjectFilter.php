<?php
/**
 * @license MIT
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace Academy\DeepCopy\TypeFilter\Spl;

use Academy\DeepCopy\DeepCopy;
use Academy\DeepCopy\TypeFilter\TypeFilter;

/**
 * In PHP 7.4 the storage of an ArrayObject isn't returned as
 * ReflectionProperty. So we deep copy its array copy.
 */
final class ArrayObjectFilter implements TypeFilter
{
    /**
     * @var DeepCopy
     */
    private $copier;

    public function __construct(DeepCopy $copier)
    {
        $this->copier = $copier;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($arrayObject)
    {
        $clone = clone $arrayObject;
        foreach ($arrayObject->getArrayCopy() as $k => $v) {
            $clone->offsetSet($k, $this->copier->copy($v));
        }

        return $clone;
    }
}

