<?php

    namespace MehrItEliDispatcherTest\Server\DispatchHandlerOnly;

    use Nyholm\Psr7\Factory\Psr17Factory;

    require_once __DIR__ . '/../../vendor/autoload.php';


    class Handler implements \Psr\Http\Server\RequestHandlerInterface {
	    /**
	     * @inheritDoc
	     */
	    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface {
	    	$factory = new Psr17Factory();

	    	return $factory->createResponse()
			    ->withStatus(200)
			    ->withBody($factory->createStream('test body'));
	    }

    }

    (new \MehrIt\EliDispatcher\Dispatch\Dispatcher())
	    ->handler(new Handler())
		->dispatch();
