<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Log;

use Throwable;

interface LogMessageGetter
{
	public function getLogMessage(Throwable $error): string;
}
