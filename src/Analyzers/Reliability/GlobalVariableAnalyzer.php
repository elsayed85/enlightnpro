<?php

namespace Enlightn\EnlightnPro\Analyzers\Reliability;

use Enlightn\Enlightn\Analyzers\Concerns\InspectsCode;
use Enlightn\Enlightn\Analyzers\Reliability\ReliabilityAnalyzer;
use Enlightn\Enlightn\Inspection\Inspector;
use Enlightn\Enlightn\Inspection\QueryBuilder;

class GlobalVariableAnalyzer extends ReliabilityAnalyzer
{
    use InspectsCode;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application does not use global variables or functions.";

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MINOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 10;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application does uses PHP global variables or functions. It is recommended that you do "
            ."not directly access these variables or functions as modifying them may create issues within "
            ."your application and decreases it's reliability.";
    }

    /**
     * Execute the analyzer.
     *
     * @param Inspector $inspector
     * @return void
     */
    public function handle(Inspector $inspector)
    {
        $builder = (new QueryBuilder())->doesntHaveGlobalStatement();

        collect(config('enlightn.global_variable_blacklist', [
            'GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV',
        ]))->each(function ($variableName) use ($builder) {
            $builder->doesntHaveGlobalVariable($variableName);
        });

        collect(config('enlightn.global_function_blacklist', [
            'header', 'header_remove', 'headers_list', 'http_response_code', 'setcookie', 'setrawcookie',
            'session_abort', 'session_cache_expire', 'session_cache_limiter', 'session_commit', 'session_create_id',
            'session_decode', 'session_destroy', 'session_encode', 'session_gc', 'session_get_cookie_params',
            'session_id', 'session_is_registered', 'session_module_name', 'session_name', 'session_regenerate_id',
            'session_register_shutdown', 'session_register', 'session_reset', 'session_save_path',
            'session_set_cookie_params', 'session_set_save_handler', 'session_start', 'session_status',
            'session_unregister', 'session_unset', 'session_write_close', 'getenv', 'putenv',
        ]))->each(function ($functionName) use ($builder) {
            $builder->doesntHaveFunctionCall($functionName);
        });

        $this->inspectCode($inspector, $builder);
    }
}
