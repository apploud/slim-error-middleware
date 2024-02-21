<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Json;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;

class JsonEncoder
{
	/**
	 * @param JsonSerializable|stdClass|array<mixed> $value
	 */
	public static function encode(JsonSerializable|stdClass|array $value, int $options = 0, int $depth = 512): string
	{
		if ($depth < 1) {
			throw new InvalidArgumentException('Depth must be greater than zero');
		}

		$result = json_encode($value, $options, $depth);

		if ($result === false) {
			throw JsonException::createFromPhpError();
		}

		return $result;
	}
}
