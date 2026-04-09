<?php

declare(strict_types=1);

namespace IPP\Interpreter\Loaded;

use IPP\Interpreter\{ExecutableBlock, SolClass, SolObject};
use IPP\Interpreter\InputModel\{Block, Assign, Expr};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class LoadedBlock implements ExecutableBlock
{
    public SolClass $class;
    public Block $source;

    public function __construct(Block $source, SolClass $class)
    {
        $this->source = $source;
        $this->class = $class;
    }

    public function execute(array $args): SolObject
    {
        if (count($args) !== $this->source->arity) {
            throw new InterpreterError(
                ErrorCode::INT_ARITY,
                "Block arity mismatch: expected {$this->source->arity}, got " . count($args)
            );
        }

        // TODO
        echo "executing block\n";
        return $this->class;
    }
}
