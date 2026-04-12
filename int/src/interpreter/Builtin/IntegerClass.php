<?php

declare(strict_types=1);

namespace IPP\Interpreter\Builtin;

use IPP\Interpreter\{Scope, SolClass, SolObject};
use IPP\Interpreter\Builtin\{BuiltinMethod, BuiltinClass};
use IPP\Interpreter\Exception\{ErrorCode, InterpreterError};

class IntegerClass extends BuiltinClass
{
    public function __construct(Scope $globalScope)
    {
        parent::__construct('Integer', $globalScope);

        $this->methods = [
            'isNumber' => new BuiltinMethod(fn($args) => $this->getBuiltinObject('true')),
            'asString' => new BuiltinMethod(fn($args) => $this->asString($args)),
            'asInteger' => new BuiltinMethod(fn($args) => $args[0]),
            'equalTo:' => new BuiltinMethod(fn($args) => $this->equalTo($args)),
            'greaterThan:' => new BuiltinMethod(fn($args) => $this->greaterThan($args)),
            'plus:' => new BuiltinMethod(fn($args) => $this->plus($args)),
            'minus:' => new BuiltinMethod(fn($args) => $this->minus($args)),
            'multiplyBy:' => new BuiltinMethod(fn($args) => $this->multiplyBy($args)),
            'divBy:' => new BuiltinMethod(fn($args) => $this->dividedBy($args)),
            'timesRepeat:' => new BuiltinMethod(fn($args) => $this->timesRepeat($args)),
        ];

        $this->staticMethods = [
            'new' => new BuiltinMethod(function (array $args) {
                $str = new SolObject($this);
                $str->internalAttribute = 0;
                return $str;
            }),
        ];
    }

    /**
     * @param array<SolObject> $args
     */
    private function asString(array $args): SolObject
    {
        $self = $args[0];

        $result = $this->getBuiltinObject('String')->send('new');
        $result->internalAttribute = (string)$self->internalAttribute;
        return $result;
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
    private function greaterThan(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            return $this->getBuiltinObject('false');
        }

        return $self->internalAttribute > $other->internalAttribute
            ? $this->getBuiltinObject('true')
            : $this->getBuiltinObject('false');
    }

    /**
    * @param array<SolObject> $args
    */
    private function plus(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            return $this->getBuiltinObject('nil');
        }

        $result = new SolObject($this);
        $result->internalAttribute = $self->internalAttribute + $other->internalAttribute;
        return $result;
    }

    /**
    * @param array<SolObject> $args
    */
    private function minus(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            return $this->getBuiltinObject('nil');
        }

        $result = new SolObject($this);
        $result->internalAttribute = $self->internalAttribute - $other->internalAttribute;
        return $result;
    }

    /**
     * @param array<SolObject> $args
     */
    private function multiplyBy(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            return $this->getBuiltinObject('nil');
        }

        $result = new SolObject($this);
        $result->internalAttribute = $self->internalAttribute * $other->internalAttribute;
        return $result;
    }

    /**
    * @param array<SolObject> $args
    */
    private function dividedBy(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        if (!$other->isInstanceOf($this)) {
            return $this->getBuiltinObject('nil');
        }

        if ($other->internalAttribute === 0) {
            throw new InterpreterError(ErrorCode::INT_INVALID_ARG, "Division by zero");
        }

        $result = new SolObject($this);
        $result->internalAttribute = intdiv($self->internalAttribute, $other->internalAttribute);
        return $result;
    }

    /**
    * @param array<SolObject> $args
    */
    private function timesRepeat(array $args): SolObject
    {
        $self = $args[0];
        $other = $args[1];

        $lastValue = $this->getBuiltinObject('nil');

        for ($i = 1; $i <= $self->internalAttribute; $i++) {
            $iObject = new SolObject($this);
            $iObject->internalAttribute = $i;

            $lastValue = $other->send('value:', [$iObject]);
        }

        return $lastValue;
    }
}
