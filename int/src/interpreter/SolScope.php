<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolObject;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class SolScope
{
    public ?SolScope $parent;

    /**
     * This is null only for the global scope. This is never null inside any method/block.
     */
    public ?SolObject $self = null;

    public array $variables = [];

    public function __construct(?SolScope $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Searches for a variable in the scope. If not found, searches in parent scopes.
     */
    public function getVariable(string $name): SolObject
    {
        if (isset($this->variables[$name])) {
            return $this->variables[$name];
        }

        if ($this->parent !== null) {
            return $this->parent->getVariable($name);
        }

        throw new InterpreterError(ErrorCode::SEM_UNDEF, "Undefined variable: $name");
    }

    /**
     * Sets the variable value.
     * If the variable is defined in any of the parent scopes, it will be updated there.
     *
     * Returns true if the variable was found in the current scope, false otherwise.
     */
    public function setVariable(string $name, SolObject $value): bool
    {
        if ($this->parent !== null) {
            if ($this->parent->setVariable($name, $value)) {
                return false;
            }
        }

        $this->variables[$name] = $value;
        return true;
    }
}
