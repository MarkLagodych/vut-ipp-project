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

        $this->staticMethods = [
            'new' => new BuiltinMethod(fn ($args) => $this->createNewBlock()),
        ];
    }

    private function returnTrue(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('true');
    }

    /**
     * This creates a block that understands a `value` message which does nothing.
     */
    private function createNewBlock(): SolObject
    {
        /*
            This translates to:
            ```
            class AnonymousEmptyBlock : Block { value [ | "do nothing" ] }
            returnValue := AnonymousEmptyBlock new.
            ```
        */
        return new SolObject(new class ($this, $this->globalScope) extends SolClass {
            public function __construct(SolClass $Block, private Scope $globalScope)
            {
                parent::__construct('AnonymousEmptyBlock');
                $this->parent = $Block;
                $this->methods = [
                    'value' => new BuiltinMethod(function ($args): SolObject {
                        /** @var SolObject */
                        return $this->globalScope->getVariable('nil');
                    })
                ];
            }
        });
    }
}
