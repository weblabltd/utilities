<?php

declare(strict_types=1);

namespace Weblabltd\Component\Util\Collection;

/**
 * Implements a list of prioritized objects that maintains the insertion order for items with the same priority.
 */
class PrioritizedObjectList implements \Countable, \Iterator
{
    const DEFAULT_PRIORITY = 100;

    /**
     * @var array
     */
    private $objects = [];
    /**
     * @var array
     */
    private $indexes = [];
    /**
     * @var int
     */
    private $cursor = 0;
    /**
     * @var int
     */
    private $serial = PHP_INT_MAX;
    /**
     * For lazy sorting the list when that's necessary.
     *
     * @var bool
     */
    private $sorted = true;

    /**
     * Adds an object to the list.
     *
     * Adding an object that's already in the list will effectively override the same object
     *
     * @param object   $object
     * @param null|int $priority Priority should be a positive number. If NULL the DEFAULT_PRIORITY will be used
     *
     * @return PrioritizedObjectList
     */
    public function add($object, ?int $priority = self::DEFAULT_PRIORITY): PrioritizedObjectList
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(
                sprintf('Expects an an object, got "%s" instead', gettype($object))
            );
        }

        if ($priority < 0) {
            throw new \InvalidArgumentException(
                sprintf('The priority should be a positive integer or null for the default priority, got %s instead', gettype($priority))
            );
        }

        $index = spl_object_hash($object);

        if (!isset($this->objects[$index])) {
            $this->objects[$index] = [$object, [$priority ?? static::DEFAULT_PRIORITY, $this->serial--]];
            $this->indexes[]       = $index;
        } else {
            // if object is present just override it's priority
            $this->objects[$index][1][0] = $priority ?? static::DEFAULT_PRIORITY;
        }

        // mark as not sorted
        $this->sorted = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->objects);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->sort();

        if (!isset($this->indexes[$this->cursor])) {
            throw new \OutOfBoundsException('There is no object at the current position');
        }

        return $this->objects[$this->indexes[$this->cursor]][0];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->sort();

        ++$this->cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $this->sort();

        return $this->cursor;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $this->sort();

        return isset($this->indexes[$this->cursor]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->sort();

        $this->cursor = 0;
    }

    /**
     * Sorts the list.
     */
    private function sort(): void
    {
        if ($this->sorted) {
            return;
        }

        uasort($this->objects, function ($o1, $o2) {
            return $o2[1][0] <=> $o1[1][0];
        });

        $this->indexes = array_keys($this->objects);

        $this->sorted = true;
    }
}
