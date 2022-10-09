<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class ColumnNameSQLInjectionBuilderRule implements Rule
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
        if (! $this->callsColumnNameMethod($node)) {
            return [];
        }

        if (! $this->isCalledOnBuilder($node->var, $scope)) {
            // Method was not called on a Builder, so no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->isArrayOrClosure($node->args[0])) {
            // If it's a closure or array (e.g. in a where clause), we will not process it.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return [
                sprintf(
                    "Method call %s on Eloquent/query builder instance with request data allows user input to dictate column names.",
                    $node->name->toString()
                )
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
    protected function callsColumnNameMethod(Node $node)
    {
        if (! $node->name instanceof Node\Identifier
            || ! in_array($node->name->toString(), [
                'where', 'orWhere', 'whereIn', 'orWhereIn', 'whereNotIn', 'orWhereNotIn', 'whereIntegerInRaw',
                'orWhereIntegerInRaw', 'whereIntegerNotInRaw', 'orWhereIntegerNotInRaw', 'whereNull', 'whereNotNull',
                'orWhereNull', 'whereBetween', 'whereBetweenColumns', 'orWhereBetween', 'whereNotBetween',
                'whereNotBetweenColumns', 'orWhereNotBetween', 'orWhereNotBetweenColumns', 'orWhereNotNull',
                'whereDate', 'orWhereDate', 'whereTime', 'orWhereTime', 'whereDay', 'orWhereDay', 'whereMonth',
                'orWhereMonth', 'whereYear', 'orwhereYear', 'whereRowValues', 'orWhereRowValues', 'whereJsonContains',
                'orWhereJsonContains', 'whereJsonDoesntContain', 'whereJsonLength', 'orWhereJsonLength', 'having',
                'orHaving', 'havingBetween', 'orderBy', 'orderByDesc', 'latest', 'oldest', 'reorder', 'value', 'get',
                'getCountForPagination', 'pluck', 'implode', 'count', 'min', 'max', 'sum', 'avg', 'average',
                'aggregate', 'increment', 'decrement', 'select', 'addSelect',
            ])) {
            // We are only looking for the above method calls
            return false;
        }

        return true;
    }

    /**
     * @param \PhpParser\Node\Arg $arg
     * @return bool
     */
    public function isArrayOrClosure(Node\Arg $arg)
    {
        if ($arg->value instanceof Node\Expr\Array_
            || $arg->value instanceof Node\Expr\Closure) {
            return true;
        }

        return false;
    }
}
