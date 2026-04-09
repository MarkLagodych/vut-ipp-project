<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolObject;

interface ExecutableBlock
{
    public function execute(array $args): SolObject;
}
