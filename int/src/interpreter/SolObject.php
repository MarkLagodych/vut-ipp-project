<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\InputModel\Block;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

use function IPP\Interpreter\{getSelectorArity};

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
     * - selector: the message selector to send in the form "x", "x:", "x:y:", etc.
     * - args: the arguments to pass to the method, defaults to an empty array.
     * - class: the class from which to start method lookup, defaults to the class of this object.
     */
    final public function send(
        string $selector,
        array $args = [],
        SolClass $class = null,
    ): SolObject {
        $class ??= $this->class;

        $method = $class->searchMethod($selector);

        if ($method !== null) {
            $method->execute($args);
        }

        if ($method === null) {
            throw new InterpreterError(
                ErrorCode::INT_DNU,
                "$class does not understand message $selector"
            );
        }

        // TODO
        return new SolObject($class);
    }
}
