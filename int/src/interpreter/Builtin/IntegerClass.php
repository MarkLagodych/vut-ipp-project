<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};

class IntegerClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('Integer', $globalScope);

        $this->methods = [
            'isNumber' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'asString' => new BuiltinMethod(function ($args) {
                $self = $args[0];
                /** @var number */
                $num = $self->internalAttribute;

                $result = $this->getBuiltinObject('String')->send('new');
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
}
