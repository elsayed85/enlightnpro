<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Validation\Rules\Unique;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class ValidationSQLInjectionRule implements Rule
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
        if (! $node->name instanceof Node\Identifier
            || $node->name->toString() !== 'ignore') {
            // We are only looking for ignore(...) method calls
            return [];
        }

        if (! $this->isCalledOn($node->var, $scope, Unique::class)) {
            // Method was not called on a Unique Rule, so no errors.
            return [];
        }

        if ((isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope))
            || (isset($node->args[1]) && $this->retrievesRequestInput($node->args[1], $scope))) {
            return ["A maliciously crafted request could lead to an SQL injection attack if user controlled data "
                ."is provided to a Unique Rule's ignore ID or column."];
        }

        return [];
    }
}
