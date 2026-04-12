<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};

class BlockClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('Block', $globalScope);

        $this->methods = [
            'isBlock' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'whileTrue:' => new BuiltinMethod(fn($args) => $this->whileTrue($args)),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(fn($args) => $this->new()),
        ];
    }

    /**
     * @param array<SolObject> $args
     */
    private function whileTrue(array $args): SolObject
    {
        $self = $args[0];
        $body = $args[1];

        $lastResult = $this->getBuiltinObject('nil');
        for (;;) {
            $condition = $self->send('value');
            // If the condition anything other than `true` (even not a boolean), we just break
            if ($condition !== $this->getBuiltinObject('true')) {
                break;
            }

            $lastResult = $body->send('value');
        }

        return $lastResult;
    }

    /**
     * This creates a block that understands a `value` message which does nothing.
     */
    private function new(): SolObject
    {
        /*
            This translates to:
            ```
            class AnonymousEmptyBlock : Block { value [ | "do nothing" ] }
            returnValue := AnonymousEmptyBlock new.
            ```
        */
        return new SolObject(new class ($this->globalScope) extends BuiltinClass {
            public function __construct(Scope $globalScope)
            {
                parent::__construct('AnonymousEmptyBlock', $globalScope);
                $this->parent = $this->getBuiltinClass('Block');
                $this->methods = [
                    'value' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('nil')),
                ];
            }
        });
    }
}
