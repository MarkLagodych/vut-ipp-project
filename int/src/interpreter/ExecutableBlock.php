<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolObject;

interface ExecutableBlock
{
    /**
     * @param array<string, SolObject> $args (name => value)
     */
    public function execute(array $args): SolObject;
}
