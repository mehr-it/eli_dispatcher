<?php

	namespace MehrItEliDispatcherTest\Server\DispatchBootScriptBoot;

	use MehrIt\EliDispatcher\Dispatch\Dispatcher;
	use Nyholm\Psr7\Factory\Psr17Factory;

	class Handler implements \Psr\Http\Server\RequestHandlerInterface
	{
		/**
		 * @inheritDoc
		 */
		public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
			$factory = new Psr17Factory();

			return $factory->createResponse()
				->withStatus(200)
				->withBody($factory->createStream('test body from boot script handler'));
		}

	}


	/** @var Dispatcher $dispatcher */
	$dispatcher->handler(new Handler());