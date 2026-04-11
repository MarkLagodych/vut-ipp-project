<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolClass;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class SolObject
{
    public SolClass $class;

    /** @var array<string, SolObject> */
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
     *
     * @param array<SolObject> $args
     */
    final public function send(
        string $selector,
        array $args = [],
        ?SolClass $class = null,
    ): SolObject {
        $class ??= $this->class;

        $method = $class->getMethod($selector);
        if ($method === null) {
            throw new InterpreterError(
                ErrorCode::INT_DNU,
                "'$class->name' does not understand message '$selector'"
            );
        }

        // TODO
        return $method->execute([$this, ...$args]);
    }
}
