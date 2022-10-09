<?php

namespace Enlightn\EnlightnPro\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

class FileSizeValidationRule extends RequestValidationRule
{
    /**
     * Determine if the node argument is a valid request validation string.
     *
     * @param \PhpParser\Node\Expr $expr
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool
     */
    protected function checkValidation(Node\Expr $expr, Scope $scope)
    {
        if (! $expr instanceof Node\Expr\Array_) {
            return false;
        }

        foreach ($expr->items as $item) {
            if ($this->analyzeRecursively($item->value, $scope, [$this, 'hasFileRule'])
                && ! $this->analyzeRecursively($item->value, $scope, [$this, 'hasFileSizeRule'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function getErrorMessage(): array
    {
        return ["Your application does not validate file size for file uploads. This can expose your application "
            . "to a storage DOS attack."];
    }

    /**
     * Determine if a validation item has the file size rule.
     *
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool|null
     */
    protected function hasFileSizeRule(Node $node, Scope $scope)
    {
        return $this->hasString($node, $scope, 'size:')
            || $this->hasString($node, $scope, 'between:') || $this->hasString($node, $scope, 'max:');
    }

    /**
     * Determine if a validation item has the file rule.
     *
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool|null
     */
    protected function hasFileRule(Node $node, Scope $scope)
    {
        return $this->hasString($node, $scope, 'file');
    }
}
