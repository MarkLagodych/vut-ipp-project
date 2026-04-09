<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, Scope, BlockScope};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class MethodScope extends BlockScope
{
    /**
     * `params` is map: parameter name => argument value.
     */
    public function __construct(Scope $globalScope, SolObject $self, array $params)
    {
        parent::__construct($globalScope, $params);
        $this->variables['self'] = $self;
        $this->variables['super'] = $self;
    }

    protected function tryUpdateVariable(string $name, SolObject $value): bool
    {
        // This is consistent with the fact that block parameters are not assignable.
        if ($name === 'self' || $name === 'super') {
            throw new InterpreterError(
                ErrorCode::SEM_COLLISION,
                "Cannot assign to reserved variable: $name"
            );
        }

        return parent::tryUpdateVariable($name, $value);
    }
}
