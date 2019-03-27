<?php

	namespace MehrItEliDispatcherTest\Server\DispatchWithMiddleware;

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

	class Middleware implements MiddlewareInterface
	{
		/**
		 * @inheritDoc
		 */
		public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

			if ($request->getHeaderLine('X-PASS-TO-HANDLER') == 'yes') {
				$response = $handler->handle($request);
			}
			else {
				$factory = new Psr17Factory();

				$response = $factory->createResponse()
					->withStatus(200)
					->withBody($factory->createStream('from middleware'));
			}

			$response = $response->withAddedHeader('X-ADDED-HEADER', 'my value');

			return $response;

		}


	}

	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
		->middleware(new Middleware())
		->handler(new Handler())
		->dispatch();
