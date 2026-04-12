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
            'asInteger' => new BuiltinMethod(fn($args) => $this->asInteger($args)),
            'concatenateWith:' => new BuiltinMethod(fn($args) => $this->concatenateWith($args)),
            'startsWith:endsBefore:' => new BuiltinMethod(fn($args) => $this->substring($args)),
            'length' => new BuiltinMethod(fn($args) => $this->length($args)),
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
            return $this->getBuiltinObject('false');
        }

        return $self->internalAttribute === $other->internalAttribute
            ? $this->getBuiltinObject('true')
            : $this->getBuiltinObject('false');
    }

    /**
     * @param array<SolObject> $args
     */
    private function asInteger(array $args): SolObject
    {
        $self = $args[0];

        $int = filter_var(
            $self->internalAttribute,
            FILTER_VALIDATE_INT,
            FILTER_NULL_ON_FAILURE
        );

        if ($int === null) {
            return $this->getBuiltinObject('nil');
        }

        $result = $this->getBuiltinObject('Integer')->send('new');
        $result->internalAttribute = $int;
        return $result;
    }

    /**
     * @param array<SolObject> $args
     */
    private function concatenateWith(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            return $this->getBuiltinObject('nil');
        }

        $result = $this->getBuiltinObject('String')->send('new');
        $result->internalAttribute = $self->internalAttribute . $other->internalAttribute;
        return $result;
    }

    /**
     * @param array<SolObject> $args
     */
    private function substring(array $args): SolObject
    {
        $self = $args[0];
        $startIndex = $args[1]; // 1-based
        $beforeIndex = $args[2]; // 1-based, the substring ends before this index

        if (
            !$startIndex->isInstanceOf($this->getBuiltinClass('Integer')) ||
            !$beforeIndex->isInstanceOf($this->getBuiltinClass('Integer'))
        ) {
            return $this->getBuiltinObject('nil');
        }

        $source = $self->internalAttribute;
        $startIndex = $startIndex->internalAttribute - 1; // make 0-based
        $beforeIndex = $beforeIndex->internalAttribute - 1; // make 0-based

        if ($startIndex < 0 || $beforeIndex < 0) {
            return $this->getBuiltinObject('nil');
        }

        if ($beforeIndex <= $startIndex || $startIndex >= strlen($source)) {
            return $this->new();
        }

        if ($beforeIndex > strlen($source)) {
            $beforeIndex = strlen($source);
        }

        $len = $beforeIndex - $startIndex;

        $result = $this->getBuiltinObject('String')->send('new');
        $result->internalAttribute = substr($source, $startIndex, $len);
        return $result;
    }

    /**
     * @param array<SolObject> $args
     */
    private function length(array $args): SolObject
    {
        $self = $args[0];

        $length = strlen($self->internalAttribute);

        $result = $this->getBuiltinObject('Integer')->send('new');
        $result->internalAttribute = $length;
        return $result;
    }
}
