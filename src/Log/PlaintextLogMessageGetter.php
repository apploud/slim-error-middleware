<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Log;

use Throwable;

class PlaintextLogMessageGetter implements LogMessageGetter
{
	public function getLogMessage(Throwable $error): string
	{
		if ($error->getCode() === 0) {
			return sprintf('%s: %s', $error::class, $error->getMessage());
		}

		return sprintf('%s (%d): %s', $error::class, $error->getCode(), $error->getMessage());
	}
}
