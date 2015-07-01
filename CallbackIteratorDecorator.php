<?php

namespace Experium\ExtraBundle;

/**
 * @author Alexey Shockov <shokov@experium.ru>
 */
class CallbackIteratorDecorator implements \Iterator
{
    private $iterator;

    private $decorator;

    private $key;

    private $element;

    public function __construct(\Iterator $iterator, $decorator)
    {
        $this->iterator = $iterator;

        if (!is_callable($decorator)) {
            throw new \InvalidArgumentException();
        }

        $this->decorator = $decorator;
    }

    public function next()
    {
        $this->iterator->next();

        $this->key = $this->element = null;
        if ($this->valid()) {
            list($this->key, $this->element) = call_user_func($this->decorator, $this->iterator->key(), $this->iterator->current());
        }
    }

    public function key()
    {
        return $this->key;
    }

    public function current()
    {
        return $this->element;
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }
}
