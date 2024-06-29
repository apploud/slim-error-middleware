<?php

declare(strict_types = 1);

namespace Apploud\ErrorMiddleware;

use Apploud\ErrorMiddleware\Log\LogMessageGetter;
use Apploud\ErrorMiddleware\Log\PlaintextLogMessageGetter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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

	private LogMessageGetter $defaultLogMessageGetter;

	/** @var array<class-string, LogMessageGetter> */
	private array $logMessageGetters = [];


	public function __construct(
		ErrorResponseFactory $defaultResponseFactory,
		?LoggerInterface $logger = null,
		string $defaultLogLevel = LogLevel::ERROR,
		?LogMessageGetter $defaultLogMessageGetter = null
	) {
		$this->defaultResponseFactory = $defaultResponseFactory;
		$this->logger = $logger;
		$this->defaultLogLevel = $defaultLogLevel;

		if ($defaultLogMessageGetter === null) {
			$defaultLogMessageGetter = new PlaintextLogMessageGetter();
		}

		$this->defaultLogMessageGetter = $defaultLogMessageGetter;
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


	/**
	 * @phpstan-param class-string $throwableClass
	 */
	public function addLogMessageGetter(string $throwableClass, LogMessageGetter $logMessageGetter): void
	{
		$this->logMessageGetters = [$throwableClass => $logMessageGetter] + $this->logMessageGetters;
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

		$logMessageGetter = $this->defaultLogMessageGetter;
		foreach ($this->logMessageGetters as $class => $messageGetter) {
			if ($error instanceof $class) {
				$logMessageGetter = $messageGetter;
				break;
			}
		}

		$this->logger->log($logLevel, $logMessageGetter->getLogMessage($error), ['exception' => $error]);
	}
}
