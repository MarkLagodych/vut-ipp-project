<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolClass;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

use function IPP\Interpreter\Utils\{getSelectorArity, selectorToAttribute};

class SolObject
{
    public ?SolClass $class;

    /** @var array<string, SolObject> */
    public array $attributes = [];

    public mixed $internalAttribute = null;

    public function __construct(SolClass $class)
    {
        $this->class = $class;
    }

    public function isInstanceOf(SolClass $class): bool
    {
        return $this->class?->isSubclassOf($class) ?? false;
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
        $class ??= $this->class
            ?? throw new \RuntimeException("object class is null (internal error)");

        $method = $class->getMethod($selector);

        if ($method === null) {
            if (getSelectorArity($selector) === 1) {
                $attrName = selectorToAttribute($selector);

                if ($class->getMethod($attrName) !== null) {
                    throw new InterpreterError(
                        ErrorCode::INT_INST_ATTR,
                        "Cannot set attribute '$attrName'"
                        . " (collision with '$class->name::$attrName')"
                    );
                }

                $this->attributes[$attrName] = $args[0];
                return $this;
            }

            if (getSelectorArity($selector) === 0 && isset($this->attributes[$selector])) {
                return $this->attributes[$selector];
            }

            throw new InterpreterError(
                ErrorCode::INT_DNU,
                "'$class->name' does not understand message '$selector'"
            );
        }

        return $method->execute([$this, ...$args]);
    }
}
