<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware\Test;

use Apploud\ErrorMiddleware\JsonResponseFactory;
use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;

class JsonResponseFactoryTest extends TestCase
{
	public function testSimpleException(): void
	{
		$request = Mockery::mock(ServerRequestInterface::class);

		$responseFactory = new JsonResponseFactory(new ResponseFactory(), false);
		$response = $responseFactory->createResponse(new Exception('test'), $request);

		self::assertSame(500, $response->getStatusCode());
		self::assertSame('application/json', $response->getHeaderLine('Content-type'));

		$body = json_encode(
			['message' => 'Server Error'],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
		);
		self::assertSame($body, (string) $response->getBody());
	}
}
