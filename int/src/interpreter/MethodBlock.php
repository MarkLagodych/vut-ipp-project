<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\{SolObject, ExecutableBlock};
use IPP\Interpreter\InputModel\{Method as MethodSource, Block as BlockSource};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

class MethodBlock implements ExecutableBlock
{
    public BlockSource $body;

    public function __construct(public MethodSource $source)
    {
    }

    public function execute(array $args): SolObject
    {
        // TODO implement method execution
        return new SolObject(new SolMetaClass());
    }
}
