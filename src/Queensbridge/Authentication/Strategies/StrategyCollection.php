<?php

namespace Queensbridge\Authentication\Strategies;

class StrategyCollection implements \IteratorAggregate
{
    private $strategies;

    public function __construct()
    {
        $this->strategies = array();
    }

    public function add($name, $strategy)
    {
        $this->remove($name);

        $this->strategies[$name] = $strategy;
    }

    public function get($name)
    {
        if (isset($this->strategies[$name])) {
            return $this->strategies[$name];
        }

        return null;
    }

    public function remove($name)
    {
        if (isset($this->strategies[$name])) {
            unset($this->strategies[$name]);
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->strategies);
    }
}
