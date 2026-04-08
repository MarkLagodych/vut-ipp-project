<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\InputModel\Block;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class SolObject
{
    public SolClass $class;

    public array $attributes = [];

    public function __construct(SolClass $class)
    {
        $this->class = $class;
    }

    /**
     * Args:
     * - class: the class from which to start method lookup, defaults to the class of this object.
     */
    public function send(
        string $selector,
        array $args,
        SolClass $class = null,
    ): SolObject {
        $class ??= $this->class;

        $method = $class->searchMethod($selector);
        if ($method === null) {
            throw new InterpreterError(
                ErrorCode::SEM_UNDEF,
                "$class does not understand message $selector"
            );
        }

        // TODO
        return new SolObject($class);
    }
}
