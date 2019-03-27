<?php

	namespace MehrItEliDispatcherTest\Server\DispatchDefaultHandler;

	require_once __DIR__ . '/../../vendor/autoload.php';


	(new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
		->dispatch();
