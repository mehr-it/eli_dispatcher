<?php

	namespace MehrItEliDispatcherTest\Server\DispatchBootScript;

	use Nyholm\Psr7\Factory\Psr17Factory;

	require_once __DIR__ . '/../../vendor/autoload.php';




	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher(__DIR__ . '/boot/boot_script.php'))
		->dispatch();
