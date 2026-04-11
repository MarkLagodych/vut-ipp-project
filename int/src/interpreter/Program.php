<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\InputModel\{Program as ProgramSource, ClassDef};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class Program
{
    public Scope $globalScope;

    public function __construct()
    {
        $this->globalScope = new Scope(null);

        // TODO built-in classes and objects

        $this->globalScope->setVariable('Object', new SolClass('Object'));

        $nilClass = new SolClass('Nil');
        $this->globalScope->setVariable('Nil', $nilClass);
        $this->globalScope->setVariable('nil', new SolObject($nilClass));
    }

    public function run(): void
    {
        $this->checkMain();

        /** @var SolClass (never null here) */
        $mainClass = $this->globalScope->getVariable('Main');
        $main = new SolObject($mainClass);
        $main->send('run');
    }

    public function loadSource(ProgramSource $programSource): void
    {
        $this->loadClassDefinitions($programSource);
        $this->updateClassParents($programSource);
        $this->checkRecursiveInheritance($programSource);
        $this->loadClassMethods($programSource);
        $this->checkMain();
    }

    private function loadClassDefinitions(ProgramSource $programSource): void
    {
        foreach ($programSource->classes as $classDef) {
            $class = new SolClass($classDef->name);

            if ($this->globalScope->getVariable($classDef->name) !== null) {
                throw new InterpreterError(
                    ErrorCode::SEM_ERROR,
                    "Class '$classDef->name' is already defined"
                );
            }

            $this->globalScope->setVariable($classDef->name, $class);
        }
    }

    private function updateClassParents(ProgramSource $programSource): void
    {
        foreach ($programSource->classes as $classDef) {
            /** @var SolClass */
            $parent = $this->globalScope->getVariable($classDef->parent)
                ?? throw new InterpreterError(
                    ErrorCode::SEM_UNDEF,
                    "Parent class '$classDef->parent' is not defined"
                );

            /** @var SolClass */
            $class = $this->globalScope->getVariable($classDef->name);
            $class->parent = $parent;
        }
    }

    private function checkRecursiveInheritance(ProgramSource $programSource): void
    {
        foreach ($programSource->classes as $classDef) {
            /** @var SolClass (never null here) */
            $class = $this->globalScope->getVariable($classDef->name);
            $this->checkRecursiveInheritanceForClass($class);
        }
    }

    /**
     * @param array<SolClass> $visited
     */
    private function checkRecursiveInheritanceForClass(SolClass $class, array $visited = []): void
    {
        if (in_array($class, $visited, true)) {
            throw new InterpreterError(
                ErrorCode::SEM_ERROR,
                "Recursive inheritance detected for class '$class->name'"
            );
        }

        $visited[] = $class;

        if ($class->parent === null) {
            return;
        }

        $this->checkRecursiveInheritanceForClass($class->parent, [...$visited, $class]);
    }

    private function loadClassMethods(ProgramSource $programSource): void
    {
        foreach ($programSource->classes as $classDef) {
            /** @var SolClass */
            $class = $this->globalScope->getVariable($classDef->name);
            $class->loadMethods($this->globalScope, $classDef);
        }
    }

    private function checkMain(): void
    {
        /** @var SolClass */
        $mainClass = $this->globalScope->getVariable('Main')
            ?? throw new InterpreterError(ErrorCode::SEM_UNDEF, "Class 'Main' is not defined");

        if ($mainClass->getMethod('run') === null) {
            throw new InterpreterError(ErrorCode::SEM_UNDEF, "Method 'Main::run' is not defined");
        }
    }
}
