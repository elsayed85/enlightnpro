<?php

namespace Enlightn\EnlightnPro\PHPStan;

use Enlightn\Enlightn\PHPStan\AnalyzesNodes;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Rules\Rule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

abstract class RequestValidationRule implements Rule
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
        if (! $this->isCalledOn($node->var, $scope, Request::class)
            && ! $this->isCalledOn($node->var, $scope, Controller::class)) {
            // If the method is not called on a Request or Controller class, no errors.
            return [];
        }

        if (! $node->name instanceof Node\Identifier
            || ! in_array($node->name->toString(), [
                'validate', 'validateWithBag'
            ])) {
            // If the method name doesn't match the names above, no errors.
            return [];
        }

        if ($this->isCalledOn($node->var, $scope, Request::class)
            && $node->name->toString() == 'validate' && isset($node->args[0])
            && $this->checkValidation($node->args[0]->value, $scope)) {
            return $this->getErrorMessage();
        }

        if ($this->isCalledOn($node->var, $scope, Request::class)
            && $node->name->toString() == 'validateWithBag' && isset($node->args[1])
            && $this->checkValidation($node->args[1]->value, $scope)) {
            return $this->getErrorMessage();
        }

        if ($this->isCalledOn($node->var, $scope, Controller::class)
            && $node->name->toString() == 'validate' && isset($node->args[1])
            && $this->checkValidation($node->args[1]->value, $scope)) {
            return $this->getErrorMessage();
        }

        if ($this->isCalledOn($node->var, $scope, Controller::class)
            && $node->name->toString() == 'validateWithBag' && isset($node->args[2])
            && $this->checkValidation($node->args[2]->value, $scope)) {
            return $this->getErrorMessage();
        }

        return [];
    }

    /**
     * Determine if the node argument is a valid request validation string.
     *
     * @param \PhpParser\Node\Expr $expr
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool
     */
    abstract protected function checkValidation(Node\Expr $expr, Scope $scope);

    /**
     * @return string[]
     */
    abstract protected function getErrorMessage(): array;
}
