<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware;

use Apploud\ErrorMiddleware\Exception\HttpCodeException;
use Apploud\ErrorMiddleware\Exception\PublicMessageException;
use Apploud\ErrorMiddleware\Json\JsonEncoder;
use JsonSerializable;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Throwable;
use stdClass;

class JsonResponseFactory implements ErrorResponseFactory
{
	/** @var ResponseFactoryInterface */
	private $responseFactory;

	/** @var bool */
	private $displayErrorDetails;

	/** @var string */
	private $defaultMessage;

	/** @var int */
	private $defaultHttpCode;


	public function __construct(
		ResponseFactoryInterface $responseFactory,
		bool $displayErrorDetails,
		string $defaultMessage = 'Server Error',
		int $defaultHttpCode = 500
	)
	{
		$this->responseFactory = $responseFactory;
		$this->displayErrorDetails = $displayErrorDetails;
		$this->defaultMessage = $defaultMessage;
		$this->defaultHttpCode = $defaultHttpCode;
	}


	public function createResponse(Throwable $error, ServerRequestInterface $request): ResponseInterface
	{
		$body = JsonEncoder::encode(
			$this->getPayload($error, $this->displayErrorDetails, $request),
			JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
		);

		$code = $error instanceof HttpCodeException || $error instanceof HttpException ? $error->getCode() : $this->defaultHttpCode;

		$response = $this->responseFactory->createResponse($code);
		$response = $response->withHeader('Content-type', 'application/json');

		if ($error instanceof HttpMethodNotAllowedException) {
			$allowedMethods = implode(', ', $error->getAllowedMethods());
			$response = $response->withHeader('Allow', $allowedMethods);
		}

		$response->getBody()->write($body);

		return $response;
	}


	/**
	 * @return JsonSerializable|stdClass|array<mixed>
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	protected function getPayload(Throwable $error, bool $displayErrorDetails, ServerRequestInterface $request)
	{
		$payload = [
			'message' => $this->defaultMessage,
		];

		if ($error instanceof HttpException) {
			$payload['message'] = $error->getTitle();
		}

		if ($error instanceof PublicMessageException) {
			$payload['message'] = $error->getMessage() ?: $this->defaultMessage;
		}

		if ($displayErrorDetails) {
			$payload['exception'] = [];

			do {
				$error['exception'][] = $this->formatExceptionFragment($error);
				$error = $error->getPrevious();
			} while ($error !== null);
		}

		return $payload;
	}


	/**
	 * @return array<mixed>
	 */
	private function formatExceptionFragment(Throwable $exception): array
	{
		return [
			'type' => get_class($exception),
			'code' => $exception->getCode(),
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
		];
	}
}
