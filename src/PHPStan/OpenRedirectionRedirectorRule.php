<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Routing\Redirector;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

class OpenRedirectionRedirectorRule implements Rule
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
            || ! in_array($methodName = $node->name->toString(), [
                'to', 'guest', 'intended', 'setIntendedUrl', 'away', 'secure',
            ])) {
            // We are only looking for the above method calls.
            return [];
        }

        if (! $this->isCalledOn($node->var, $scope, Redirector::class)) {
            // Method was not called on a Redirector, so no errors.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Method call {$methodName} on Redirector class allows a redirect to a user provided URL."];
        }

        return [];
    }
}
