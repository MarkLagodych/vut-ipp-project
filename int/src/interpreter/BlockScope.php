<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, Scope};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class BlockScope extends Scope
{
    /**
     * Map: parameter name => argument value
     */
    protected array $params;

    /**
     * `params` is map: parameter name => argument value.
     */
    public function __construct(Scope $globalScope, array $params)
    {
        parent::__construct($globalScope);

        $this->params = $params;
        $this->variables = $params;
    }

    protected function tryUpdateVariable(string $name, SolObject $value): bool
    {
        if (isset($this->params[$name])) {
            throw new InterpreterError(
                ErrorCode::SEM_COLLISION,
                "Cannot assign to block parameter: $name"
            );
        }

        return parent::tryUpdateVariable($name, $value);
    }
}
