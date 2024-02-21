<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class ErrorMiddleware implements MiddlewareInterface
{
	private ErrorResponseFactory $defaultResponseFactory;

	/** @var array<class-string, ErrorResponseFactory> */
	private array $responseFactories = [];

	private ?LoggerInterface $logger = null;

	private string $defaultLogLevel;

	/** @var array<class-string, string> */
	private array $logLevels = [];

	private ErrorRendererInterface $logErrorRenderer;

	private bool $logErrorDetails;


	public function __construct(
		ErrorResponseFactory $defaultResponseFactory,
		?LoggerInterface $logger = null,
		string $defaultLogLevel = LogLevel::ERROR,
		?ErrorRendererInterface $logErrorRenderer = null,
		bool $logErrorDetails = true
	) {
		$this->defaultResponseFactory = $defaultResponseFactory;
		$this->logger = $logger;
		$this->defaultLogLevel = $defaultLogLevel;

		if ($logErrorRenderer === null) {
			$logErrorRenderer = new PlainTextErrorRenderer();
		}

		$this->logErrorRenderer = $logErrorRenderer;
		$this->logErrorDetails = $logErrorDetails;
	}


	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (Throwable $e) {
			$this->log($e);

			foreach ($this->responseFactories as $class => $responseFactory) {
				if ($e instanceof $class) {
					return $responseFactory->createResponse($e, $request);
				}
			}

			return $this->defaultResponseFactory->createResponse($e, $request);
		}
	}


	/**
	 * @phpstan-param class-string $throwableClass
	 */
	public function addResponseFactory(string $throwableClass, ErrorResponseFactory $responseFactory): void
	{
		$this->responseFactories = [$throwableClass => $responseFactory] + $this->responseFactories;
	}


	/**
	 * @phpstan-param class-string $throwableClass
	 */
	public function setLogLevel(string $throwableClass, string $logLevel): void
	{
		$this->logLevels = [$throwableClass => $logLevel] + $this->logLevels;
	}


	private function log(Throwable $error): void
	{
		if ($this->logger === null) {
			return;
		}

		$logLevel = $this->defaultLogLevel;

		foreach ($this->logLevels as $class => $customLogLevel) {
			if ($error instanceof $class) {
				$logLevel = $customLogLevel;

				break;
			}
		}

		$logErrorRenderer = $this->logErrorRenderer;
		$this->logger->log($logLevel, $logErrorRenderer($error, $this->logErrorDetails), ['exception' => $error]);
	}
}
