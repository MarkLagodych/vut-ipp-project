<?php

declare(strict_types=1);

namespace IPP\Interpreter\Loaded;

use IPP\Interpreter\{SolClass, Scope};
use IPP\Interpreter\Loaded\{LoadedBlock};
use IPP\Interpreter\InputModel\{Block, Method, ClassDef};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

use function IPP\Interpreter\{getSelectorArity};

final class LoadedClass extends SolClass
{
    public function __construct(ClassDef $source, Scope $globalScope)
    {
        parent::__construct();

        $this->parent = $globalScope->getVariable($source->parent);

        $this->methods = [];
        foreach ($source->methods as $method) {
            $selectorArity = getSelectorArity($method->selector);

            if ($selectorArity !== $method->block->arity) {
                throw new InterpreterError(
                    ErrorCode::SEM_ARITY,
                    "$source->name::$method->selector arity mismatch: "
                    . "expected $selectorArity, got {$method->block->arity}"
                );
            }

            if (isset($this->methods[$method->selector])) {
                throw new InterpreterError(
                    ErrorCode::SEM_ERROR,
                    "Redefinition of method: $method->selector in class $source->name"
                );
            }

            $this->methods[$method->selector] = new LoadedBlock($method->block, $this);
        }
    }
}
