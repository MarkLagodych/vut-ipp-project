<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{
    ObjectClass, NilClass, TrueClass, FalseClass, IntegerClass, StringClass, BlockClass
};
use IPP\Interpreter\InputModel\{Program as ProgramSource, ClassDef};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};
use SplFileObject;

class Program
{
    public Scope $globalScope;

    private StringClass $String;

    public function __construct()
    {
        $this->globalScope = new Scope(null);

        $this->globalScope->setVariable('Object', new ObjectClass($this->globalScope));

        $Nil = new NilClass($this->globalScope);
        $this->globalScope->setVariable('Nil', $Nil);
        $this->globalScope->setVariable('nil', new SolObject($Nil));

        $True = new TrueClass($this->globalScope);
        $this->globalScope->setVariable('True', $True);
        $this->globalScope->setVariable('true', new SolObject($True));

        $False = new FalseClass($this->globalScope);
        $this->globalScope->setVariable('False', $False);
        $this->globalScope->setVariable('false', new SolObject($False));

        $this->globalScope->setVariable('Integer', new IntegerClass($this->globalScope));
        $this->globalScope->setVariable('Block', new BlockClass($this->globalScope));

        $this->String = new StringClass($this->globalScope);
        $this->globalScope->setVariable('String', $this->String);
    }

    public function setInput(?SplFileObject $input): void
    {
        $this->String->input = $input;
    }

    public function run(): void
    {
        $this->checkMain();

        /** @var SolClass (never null here) */
        $Main = $this->globalScope->getVariable('Main');
        $main = $Main->send('new');
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
