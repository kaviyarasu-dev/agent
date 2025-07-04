<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Exceptions;

use Exception;

class AIAgentException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @return void
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
