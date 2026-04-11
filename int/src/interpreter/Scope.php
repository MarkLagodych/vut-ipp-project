<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolObject;
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class Scope
{
    protected ?Scope $parent;

    /**
     * @var array<string, object>
     */
    protected array $variables;

    /**
     * The parent scope can be null only for the global scope.
     * Initial variables are defined in the current scope and can shadow variables from parent
     * scopes.
     *
     * @param array<string, object> $initialVariables
     */
    public function __construct(?Scope $parent, array $initialVariables = [])
    {
        $this->parent = $parent;
        $this->variables = $initialVariables;
    }

    /**
     * Searches for a variable in the scope and then in parent scopes.
     * If not found, returns null.
     */
    final public function getVariable(string $name): ?object
    {
        if (isset($this->variables[$name])) {
            return $this->variables[$name];
        }

        return $this->parent?->getVariable($name);
    }

    /**
     * Tries to update an already defined variable. The variable is searched in the current scope
     * and then in parent scopes. If the variable is not found anywhere, returns false.
     *
     * This helps to prohibit variable shadowing, which is not supported in SOL.
     *
     * Returns true if the variable is already defined and has been updated, false otherwise.
     */
    protected function tryUpdateVariable(string $name, object $value): bool
    {
        if (isset($this->variables[$name])) {
            $this->variables[$name] = $value;
            return true;
        }

        return $this->parent?->tryUpdateVariable($name, $value) ?? false;
    }

    /**
     * Sets the variable value.
     * If the variable is defined in any of the parent scopes, it will be updated there.
     */
    final public function setVariable(string $name, object $value): void
    {
        if ($this->tryUpdateVariable($name, $value)) {
            return;
        }

        // Create the variable
        $this->variables[$name] = $value;
    }
}
