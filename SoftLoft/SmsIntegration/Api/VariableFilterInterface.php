<?php

declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Api;

interface VariableFilterInterface
{
    /**
     * @param string $variableName
     * @param string $variable
     * @param string $template
     * @return string
     */
    public function filter(string $variableName, string $variable, string $template): string;
}
