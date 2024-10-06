<?php
/**
 * @license MIT
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\DeepCopy\Filter;

/**
 * Defines a decorator filter that will not stop the chain of filters.
 */
class ChainableFilter implements Filter
{
    /**
     * @var Filter
     */
    protected $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function apply($object, $property, $objectCopier)
    {
        $this->filter->apply($object, $property, $objectCopier);
    }
}
