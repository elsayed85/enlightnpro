<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Support\Facades\Redirect;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class OpenRedirectionFacadeRule implements Rule
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
            && $scope->resolveName($node->class) !== Redirect::class
            && ! is_subclass_of($scope->resolveName($node->class), Redirect::class)) {
            // We are only looking at static calls on the Redirect facade class.
            return [];
        }

        if ($node->class instanceof Node\Expr && ! $this->isCalledOn($node->class, $scope, Redirect::class)) {
            // We are only looking at static calls on the Redirect facade class.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($methodName = $node->name->toString(), [
                'to', 'guest', 'intended', 'setIntendedUrl', 'away', 'secure',
            ])) {
            // We are only looking for the above method calls.
            return [];
        }

        if (isset($node->args[0]) && $this->retrievesRequestInput($node->args[0], $scope)) {
            return ["Method call {$methodName} on Redirect facade allows a redirect to a user provided URL."];
        }

        return [];
    }
}
