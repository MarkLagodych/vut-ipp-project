<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};

class IntegerClass extends SolClass
{
    public function __construct(private Scope $globalScope)
    {
        parent::__construct('Integer');

        /** @var SolClass */
        $Object = $this->globalScope->getVariable('Object');
        $this->parent = $Object;

        $this->methods = [
            'isNumber' => new BuiltinMethod(fn($args) => $this->returnTrue()),
            'asString' => new BuiltinMethod(function ($args): SolObject {
                $self = $args[0];
                /** @var number */
                $num = $self->internalAttribute;

                /** @var SolObject */
                $String = $this->globalScope->getVariable('String');

                $result = $String->send('new');
                $result->internalAttribute = (string)$num;

                return $result;
            }),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(function (array $args) {
                $str = new SolObject($this);
                $str->internalAttribute = 0;
                return $str;
            }),
        ];
    }

    private function returnTrue(): SolObject
    {
        /** @var SolObject */
        return $this->globalScope->getVariable('true');
    }
}
