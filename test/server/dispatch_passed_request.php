<?php

	namespace MehrItEliDispatcherTest\Server\DispatchPassedRequest;

	use Guzzle\Http\Message\Request;
	use Nyholm\Psr7\Factory\Psr17Factory;
	use Nyholm\Psr7\ServerRequest;

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
				->withBody($factory->createStream(implode(' ', [
					$request->getMethod(),
					(string)$request->getUri(),
					$request->getHeaderLine('x-test')
				])));
		}

	}

	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
		->handler(new Handler())
		->dispatch(new ServerRequest('GET', 'http://test.de/', ['X-TEST' => 'test value']));
