<?php

declare(strict_types=1);

namespace SoftLoft\SmsIntegration\Model\MessageProcessor\Filter;

use SoftLoft\SmsIntegration\Api\VariableFilterInterface;

class DefaultFilter implements VariableFilterInterface
{
    /**
     * @param string $variableName
     * @param string $variable
     * @param string $template
     * @return string
     */
    public function filter(string $variableName, string $variable, string $template): string
    {
        return  str_replace('%' . $variableName . '%', $variable, $template);
    }
}
