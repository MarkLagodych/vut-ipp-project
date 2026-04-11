<?php

declare(strict_types=1);

namespace IPP\Interpreter\Utils;

use IPP\Interpreter\InputModel\{Expr, Variable, Literal, Block, Send};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};

enum ExprType: string
{
    case Variable = 'variable';
    case Literal = 'literal';
    case Block = 'block';
    case Send = 'send';
}

function getExprType(Expr $expr): ExprType
{
    if ($expr->variable !== null) {
        return ExprType::Variable;
    } elseif ($expr->literal !== null) {
        return ExprType::Literal;
    } elseif ($expr->block !== null) {
        return ExprType::Block;
    } elseif ($expr->send !== null) {
        return ExprType::Send;
    }

    throw new InterpreterError(ErrorCode::SEM_ERROR, "Invalid expression");
}

function getExprVariable(Expr $expr): Variable
{
    return $expr->variable
        ?? throw new InterpreterError(ErrorCode::SEM_ERROR, "Expression is not a variable");
}

function getExprLiteral(Expr $expr): Literal
{
    return $expr->literal
        ?? throw new InterpreterError(ErrorCode::SEM_ERROR, "Expression is not a literal");
}

function getExprBlock(Expr $expr): Block
{
    return $expr->block
        ?? throw new InterpreterError(ErrorCode::SEM_ERROR, "Expression is not a block");
}

function getExprSend(Expr $expr): Send
{
    return $expr->send
        ?? throw new InterpreterError(ErrorCode::SEM_ERROR, "Expression is not a send");
}
