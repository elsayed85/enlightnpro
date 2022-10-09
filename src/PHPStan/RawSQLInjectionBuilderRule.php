<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class RawSQLInjectionBuilderRule implements Rule
{
    use AnalyzesNodes;

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->callsBlacklistedMethod($node)) {
            return [];
        }

        if (! $this->isCalledOnBuilder($node->var, $scope)) {
            // Method was not called on a Builder, so no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                "Method call {$node->name->toString()} on Eloquent/query builder instance with request data "
                ."allows user input to determine raw SQL statements."
            ];
        }

        return [];
    }

    /**
     * Determine whether the method name matches the blacklisted methods.
     *
     * @param \PhpParser\Node $node
     * @return bool
     */
    protected function callsBlacklistedMethod(Node $node)
    {
        if (! $node->name instanceof Node\Identifier
            || ! in_array($node->name->toString(), [
                'whereRaw', 'orWhereRaw', 'groupByRaw', 'havingRaw', 'orHavingRaw', 'orderByRaw', 'selectRaw',
                'fromRaw', 'fromQuery',
            ])) {
            // We are only looking for the above method calls
            return false;
        }

        return true;
    }
}
