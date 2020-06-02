<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Json;

use Exception;

class JsonException extends Exception
{
	public static function createFromPhpError(): self
	{
		return new self(json_last_error_msg(), json_last_error());
	}
}
