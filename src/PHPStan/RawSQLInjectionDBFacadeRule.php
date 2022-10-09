<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class RawSQLInjectionDBFacadeRule implements Rule
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
            && $scope->resolveName($node->class) !== DB::class
            && ! is_subclass_of($scope->resolveName($node->class), DB::class)) {
            // We are only looking at static calls on the DB facade.
            return [];
        }

        if ($node->class instanceof Node\Expr && ! $this->isCalledOn($node->class, $scope, DB::class)) {
            // We are only looking at static calls on the DB facade.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($node->name->toString(), [
                'select', 'insert', 'statement', 'unprepared', 'affectingStatement', 'delete', 'update',
                'selectOne',
            ])) {
            // We are only looking for the above method calls.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                "Static call {$node->name->toString()} on DB facade with request data allows user input to "
                ."determine raw SQL statements."
            ];
        }

        return [];
    }
}
