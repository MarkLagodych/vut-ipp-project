<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};

class BlockClass extends SolClass
{
    public function __construct(private Scope $globalScope)
    {
        parent::__construct('Block');

        /** @var SolClass */
        $Object = $this->globalScope->getVariable('Object');
        $this->parent = $Object;

        $this->methods = [
            'isBlock' => new BuiltinMethod(fn($args) => $this->returnTrue()),
        ];
    }

    private function returnTrue(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('true');
    }
}
