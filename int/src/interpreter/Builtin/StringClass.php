<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};

class StringClass extends SolClass
{
    public function __construct(private Scope $globalScope)
    {
        parent::__construct('String');

        /** @var SolClass */
        $Object = $this->globalScope->getVariable('Object');
        $this->parent = $Object;

        $this->methods = [
            'isString' => new BuiltinMethod(fn($args) => $this->returnTrue()),
            'asString' => new BuiltinMethod(fn($args) => $args[0]),
            'print' => new BuiltinMethod(fn($args) => $this->print($args)),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(function (array $args) {
                $str = new SolObject($this);
                $str->internalAttribute = '';
                return $str;
            }),
        ];
    }

    private function returnTrue(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('true');
    }

    /**
     * @param array<SolObject> $args
     */
    private function print(array $args): SolObject
    {
        $self = $args[0];
        echo (string)$self->internalAttribute;
        return $self;
    }
}
