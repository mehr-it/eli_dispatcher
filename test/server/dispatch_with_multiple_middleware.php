<?php

	namespace MehrItEliDispatcherTest\Server\DispatchWithMultipleMiddleware;

	use Nyholm\Psr7\Factory\Psr17Factory;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	require_once __DIR__ . '/../../vendor/autoload.php';

	class Handler implements \Psr\Http\Server\RequestHandlerInterface
	{
		/**
		 * @inheritDoc
		 */
		public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
			$factory = new Psr17Factory();

			return $factory->createResponse()
				->withStatus(200)
				->withBody($factory->createStream('from handler'));
		}

	}

	class Middleware1 implements MiddlewareInterface
	{
		/**
		 * @inheritDoc
		 */
		public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

			$response = $handler->handle($request);

			$response = $response->withHeader('X-ADDED-HEADER', $response->getHeaderLine('X-ADDED-HEADER') . 'm1');

			return $response;

		}

	}

	class Middleware2 implements MiddlewareInterface
	{
		/**
		 * @inheritDoc
		 */
		public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {


			$factory = new Psr17Factory();

			$response = $factory->createResponse()
				->withStatus(200)
				->withBody($factory->createStream('from middleware 2'));


			$response = $response->withHeader('X-ADDED-HEADER', $response->getHeaderLine('X-ADDED-HEADER') . 'm2');

			return $response;

		}

	}

	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
		->middleware(new Middleware1())
		->middleware(new Middleware2())
		->handler(new Handler())
		->dispatch();
