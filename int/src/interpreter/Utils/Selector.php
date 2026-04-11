<?php

declare(strict_types=1);

namespace IPP\Interpreter\Utils;

function getSelectorArity(string $selector): int
{
    return substr_count($selector, ':');
}

function selectorToAttribute(string $selector): string
{
    return str_replace(':', '', $selector);
}

function makeBlockSelector(int $arity): string
{
    if ($arity === 0) {
        return 'value';
    }

    return str_repeat('value:', $arity);
}
