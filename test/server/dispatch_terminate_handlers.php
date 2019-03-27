<?php

	namespace MehrItEliDispatcherTest\Server\DispatchTerminateHandlers;

	use MehrIt\EliDispatcher\Dispatch\Dispatcher;
	use Nyholm\Psr7\Factory\Psr17Factory;

	require_once __DIR__ . '/../../vendor/autoload.php';

	$file = tempnam(sys_get_temp_dir(), 'eli_test_');


	class Handler implements \Psr\Http\Server\RequestHandlerInterface
	{
		protected $file;

		/**
		 * Handler constructor.
		 * @param $file
		 */
		public function __construct($file) {
			$this->file = $file;
		}


		/**
		 * @inheritDoc
		 */
		public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
			$factory = new Psr17Factory();

			$file = $this->file;

			Dispatcher::onTerminate(function() use ($file) {
				file_put_contents($file, ":terminate2", FILE_APPEND);
			});

			file_put_contents($file, ":handle", FILE_APPEND);

			return $factory->createResponse()
				->withStatus(200)
				->withBody($factory->createStream($file));
		}

	}

	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
		->onTerminate(function() use ($file) {
			usleep(500000);
			file_put_contents($file, ":terminate1", FILE_APPEND);
		})
		->handler(new Handler($file))
		->dispatch();
