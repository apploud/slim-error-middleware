<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Exception;

use Exception;
use InvalidArgumentException;
use Throwable;

class HttpCodeException extends Exception
{
	public function __construct(string $message = '', int $code = 500, ?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);

		if ($code < 100 || $code >= 600) {
			throw new InvalidArgumentException('Exception code must be between 100 and 599 (inclusive)', 0, $this);
		}
	}
}
