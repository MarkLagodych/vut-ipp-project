<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{ExecutableBlock, SolObject};

class Method implements ExecutableBlock
{
    /**
     * @param \Closure(array<SolObject>): SolObject $func
     */
    public function __construct(
        private \Closure $func
    ) {
    }

    public function execute(array $args): SolObject
    {
        return ($this->func)($args);
    }
}
