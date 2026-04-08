<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolClass, SolObject, SolScope};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};
use IPP\Interpreter\InputModel\Program;

/**
 * Global state of a program
 */
final class SolState
{
    /**
     * Map: class name => class definition
     */
    private array $classes = [];

    public SolScope $globalScope;

    private function __construct()
    {
        // TODO core things
        $this->classes['Object'] = new SolClass();
        $this->globalScope = new SolScope();
    }

    public static function fromSource(Program $source): self
    {
        $instance = new self();

        foreach ($source->classes as $classDef) {
            if (isset($instance->classes[$classDef->name])) {
                throw new InterpreterError(ErrorCode::SEM_ERROR, "Class $classDef->name redefined");
            }

            $instance->classes[$classDef->name] = SolClass::fromSource($classDef, $instance);
        }

        return $instance;
    }

    /**
     * Returns the class or otherwise throws an error
     */
    public function getClass(string $name): SolClass
    {
        if (!isset($this->classes[$name])) {
            throw new InterpreterError(ErrorCode::SEM_UNDEF, "Undefined class: $name");
        }

        return $this->classes[$name];
    }
}
