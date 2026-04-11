<?php

declare(strict_types=1);

namespace IPP\Interpreter\Validation;

use IPP\Interpreter\Scope;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

/**
 * This scope performs validation checks on every variable assignment.
 * It is intended to be used for validating methods and closure blocks:
 * it is constructed with predefined variables such as 'self', 'super' and block parameters
 * and ensures their immutability.
 */
class ValidationScope extends Scope
{
    /**
     * Contains variable names that are predefined immutable variables.
     * @var array<string, true>
     */
    private array $predefined;

    /**
     * @param array<string> $predefined
     */
    public function __construct(?Scope $parent, array $predefined = [])
    {
        $this->predefined = array_fill_keys($predefined, true);
        parent::__construct($parent, array_fill_keys($predefined, new \stdClass()));
    }

    protected function tryUpdateVariable(string $name, object $value): bool
    {
        if (isset($this->predefined[$name])) {
            throw new InterpreterError(
                ErrorCode::SEM_COLLISION,
                "Cannot assign to '$name'"
                . " (assigning to parameters and predefined variables is not allowed)"
            );
        }

        return parent::tryUpdateVariable($name, $value);
    }

    /**
     * Simulates assignment to a variable. This makes it defined.
     * Throws if the variable is a predefined variable.
     */
    public function checkAssign(string $name): void
    {
        $this->setVariable($name, new \stdClass());
    }

    public function checkDefined(string $name): void
    {
        if ($this->getVariable($name) === null) {
            throw new InterpreterError(ErrorCode::SEM_UNDEF, "Undefined variable '$name'");
        }
    }
}
