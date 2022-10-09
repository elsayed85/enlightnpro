<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class ColumnNameSQLInjectionModelRule extends ColumnNameSQLInjectionBuilderRule
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
        if (! $this->callsColumnNameMethod($node)) {
            return [];
        }

        if ($node->class instanceof Node\Name
            && ! is_subclass_of($scope->resolveName($node->class), Model::class)) {
            // We are only looking at static calls on a Model class
            return [];
        }

        if ($node->class instanceof Node\Expr && ! $this->isCalledOn($node->class, $scope, Model::class)) {
            // We are only looking at static calls on a Model class
            return [];
        }

        if (isset($node->args[0]) && $this->isArrayOrClosure($node->args[0])) {
            // If it's a closure or array (e.g. in a where clause), we will not process it.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                sprintf(
                    "Static call %s on Eloquent Model instance with request data allows user input to dictate column names.",
                    $node->name->toString()
                )
            ];
        }

        return [];
    }
}
