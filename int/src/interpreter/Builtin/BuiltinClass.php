<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};

/**
 * This is a helper class that gives access to all built-in objects through the global scope.
 */
class BuiltinClass extends SolClass
{
    public function __construct(string $name, protected Scope $globalScope)
    {
        parent::__construct($name);

        $this->parent = $this->getBuiltinClass('Object');
    }

    protected function getBuiltinObject(string $name): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable($name)
            ?? throw new \RuntimeException("Builtin object '$name' not found");
    }

    protected function getBuiltinClass(string $name): SolClass
    {
        /** @var SolObject */
        $class = $this->globalScope->getVariable($name)
            ?? throw new \RuntimeException("Builtin class '$name' not found");

        if (!$class instanceof SolClass) {
            throw new \RuntimeException("Builtin '$name' is not a class");
        }

        return $class;
    }
}
