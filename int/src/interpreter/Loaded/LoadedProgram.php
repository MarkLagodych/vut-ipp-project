<?php

declare(strict_types=1);

namespace IPP\Interpreter\Loaded;

use IPP\Interpreter\GlobalScope;
use IPP\Interpreter\Loaded\LoadedClass;
use IPP\Interpreter\InputModel\Program;

/**
 * Global state of a program
 */
final class LoadedProgram
{
    public GlobalScope $globalScope;

    public function __construct(Program $source)
    {
        $this->globalScope = new GlobalScope();

        foreach ($source->classes as $classDef) {
            $class = new LoadedClass($classDef, $this->globalScope);
            $this->globalScope->setVariable($classDef->name, $class);
        }
    }
}
