<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Json;

use JsonSerializable;
use stdClass;

class JsonEncoder
{
	/**
	 * @param JsonSerializable|stdClass|array<mixed> $value
	 */
	public static function encode($value, int $options = 0, int $depth = 512): string
	{
		$result = json_encode($value, $options, $depth);

		if ($result === false) {
			throw JsonException::createFromPhpError();
		}

		return $result;
	}
}
