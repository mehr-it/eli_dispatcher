<?php

	namespace MehrItEliDispatcherTest\Server\DispatchNoEmit;

	use Nyholm\Psr7\Factory\Psr17Factory;

	require_once __DIR__ . '/../../vendor/autoload.php';

	class Handler implements \Psr\Http\Server\RequestHandlerInterface
	{
		/**
		 * @inheritDoc
		 */
		public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
			$factory = new Psr17Factory();

			return $factory->createResponse()
				->withStatus(500)
				->withBody($factory->createStream('should not be emitted'));
		}

	}


	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
		->handler(new Handler())
		->dispatch(null, false);

	echo 'after emit';