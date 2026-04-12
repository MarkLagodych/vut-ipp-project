<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};
use SplFileObject;

class StringClass extends BuiltinClass
{
    public ?SplFileObject $input = null;

    public function __construct(Scope $globalScope)
    {
        parent::__construct('String', $globalScope);

        $this->methods = [
            'isString' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'asString' => new BuiltinMethod(fn($args) => $args[0]),
            'print' => new BuiltinMethod(fn($args) => $this->print($args)),
            'equalTo:' => new BuiltinMethod(fn($args) => $this->equalTo($args)),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(fn($args) => $this->new()),
            'read' => new BuiltinMethod(fn($args) => $this->read()),
        ];
    }

    private function new(): SolObject
    {
        $str = new SolObject($this);
        $str->internalAttribute = '';
        return $str;
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

    private function read(): SolObject
    {
        $result = rtrim($this->input?->fgets() ?? '', "\r\n");

        $str = new SolObject($this);
        $str->internalAttribute = $result;
        return $str;
    }

    /**
     * @param array<SolObject> $args
     */
    private function equalTo(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            /** @var SolObject */
            return $this->getBuiltinObject('false');
        }

        return $self->internalAttribute === $other->internalAttribute
            ? $this->getBuiltinObject('true')
            : $this->getBuiltinObject('false');
    }
}
