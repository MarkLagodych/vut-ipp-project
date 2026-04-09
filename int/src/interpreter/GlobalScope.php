<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, Scope};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class GlobalScope extends Scope
{
    /**
     * Defines all built-in classes and instances.
     */
    public function __construct()
    {
        parent::__construct(null);

        // TODO built-in objects/classes
        $this->variables['Object'] = new class extends SolClass {
            public function __construct()
            {
                parent::__construct();
            }
        };
    }

    protected function tryUpdateVariable(string $name, SolObject $value): bool
    {
        if (isset($this->variables[$name])) {
            throw new InterpreterError(
                ErrorCode::SEM_OTHER,
                "Cannot redefine $name, it is already globally defined"
            );
        }

        return parent::tryUpdateVariable($name, $value);
    }
}
