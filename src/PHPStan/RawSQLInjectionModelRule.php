<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class RawSQLInjectionModelRule implements Rule
{
    use AnalyzesNodes;

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->class instanceof Node\Name
            && ! is_subclass_of($scope->resolveName($node->class), Model::class)) {
            // We are only looking at static calls on the Model class.
            return [];
        }

        if ($node->class instanceof Node\Expr && ! $this->isCalledOn($node->class, $scope, Model::class)) {
            // We are only looking at static calls on the Model class.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($node->name->toString(), [
                'whereRaw', 'orWhereRaw', 'groupByRaw', 'havingRaw', 'orHavingRaw', 'orderByRaw',
                'selectRaw', 'fromRaw', 'fromQuery',
            ])) {
            // We are only looking for the above method calls.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                "Method call {$node->name->toString()} on Eloquent Model instance with request data "
                ."allows user input to determine raw SQL statements."
            ];
        }

        return [];
    }
}
