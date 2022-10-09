<?php

namespace Enlightn\EnlightnPro\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

class FileBombValidationRule extends RequestValidationRule
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
            if ($this->analyzeRecursively($item->value, $scope, [$this, 'hasMimeRule'])
                && $this->analyzeRecursively($item->value, $scope, [$this, 'includesZipOrXml'])) {
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
        return ["Your application allows ZIP or XML files as valid file types for user uploads. This can expose your "
            . "application to a ZIP or XML bomb attack."];
    }

    /**
     * Determine if a validation item has allows ZIP or XML file mime types.
     *
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool|null
     */
    protected function includesZipOrXml(Node $node, Scope $scope)
    {
        return $this->hasString($node, $scope, 'zip') || $this->hasString($node, $scope, 'xml');
    }

    /**
     * Determine if a validation item has the file mimes or mimetypes rule.
     *
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool|null
     */
    protected function hasMimeRule(Node $node, Scope $scope)
    {
        return $this->hasString($node, $scope, 'mimetypes:') || $this->hasString($node, $scope, 'mimes:');
    }
}
