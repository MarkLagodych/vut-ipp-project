<?php

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\SolClass;

/**
 * A metaclass contains all the static methods of a class.
 */
class SolMetaClass extends SolClass
{
    public function __construct()
    {
        // TODO
        $this->methods = [
            "new" => new class implements ExecutableBlock {
                public function execute(array $args): SolObject
                {
                    throw new \RuntimeException("Not implemented yet");
                }
            },
            "from:" => new class implements ExecutableBlock {
                public function execute(array $args): SolObject
                {
                    throw new \RuntimeException("Not implemented yet");
                }
            },
        ];
    }
}
