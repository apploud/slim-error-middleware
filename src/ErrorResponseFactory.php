<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ErrorResponseFactory
{
	public function createResponse(Throwable $error, ServerRequestInterface $request, ?string $logRecordId): ResponseInterface;
}
