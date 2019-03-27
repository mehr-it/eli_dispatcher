<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 13.03.19
	 * Time: 21:50
	 */

	namespace MehrIt\EliDispatcher\Dispatch;

	use MehrIt\EliDispatcher\Handlers\StatusCodeHandler;
	use MehrIt\EliDispatcher\Middleware\ClosureMiddleware;
	use Narrowspark\HttpEmitter\SapiEmitter;
	use Narrowspark\HttpEmitter\SapiStreamEmitter;
	use Narrowspark\HttpEmitter\Util;
	use Nyholm\Psr7\Factory\Psr17Factory;
	use Nyholm\Psr7Server\ServerRequestCreator;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	/**
	 * Class RequestHandler
	 * @package MehrIt\EliEndpoint
	 */
	class Dispatcher
	{
		protected static $stack = [];

		/**
		 * @var MiddlewareInterface[]
		 */
		protected $middleware = [];

		/**
		 * @var RequestHandlerInterface|null
		 */
		protected $handler;

		/**
		 * @var int
		 */
		protected $sendBufferSize = 1024 * 50;

		/**
		 * @var callable[]
		 */
		protected $terminateHandlers = [];

		/**
		 * @var bool
		 */
		protected $dispatching = false;


		/**
		 * @var string|null
		 */
		protected $bootScript;

		/**
		 * Creates a new instance
		 * @param string|null $bootScript string $bootScript The path to the boot script. The boot script can access the dispatcher using "$dispatcher"
		 */
		public function __construct(string $bootScript = null) {
			$this->bootScript = $bootScript;
		}


		/**
		 * Puts a middleware onto the stack
		 * @param MiddlewareInterface|callable $middleware The middleware
		 * @return $this
		 */
		public function middleware($middleware) {
			if ($this->dispatching)
				throw new \RuntimeException('Cannot add middleware while dispatching');

			// convert parameter
			if (!($middleware instanceof MiddlewareInterface)) {
				if (is_callable($middleware))
					$middleware = new ClosureMiddleware($middleware);
				else
					throw new \InvalidArgumentException('Expected callable or class implementing ' . MiddlewareInterface::class);
			}

			$this->middleware[] = $middleware;

			return $this;
		}

		/**
		 * Sets the handler for dispatched requests. The handler is the end of the processing pipeline
		 * and must return a response. If not set, the request is terminated with a 404 response
		 * @param RequestHandlerInterface $handler The handler
		 * @return $this
		 */
		public function handler(RequestHandlerInterface $handler) {
			if ($this->dispatching)
				throw new \RuntimeException('Cannot set handler while dispatching');

			$this->handler = $handler;

			return $this;
		}

		/**
		 * Sets the send buffer size. If 0 or less, the response body is sent at-once
		 * @param int $size The sent buffer size
		 * @return $this
		 */
		protected function sendBuffer(int $size) {
			// note: this method is available public via magic methods

			$this->sendBufferSize = $size;

			return $this;
		}

		/**
		 * Adds a function to be executed after a request has been dispatched und output is closed
		 * @param callable $handler The handler
		 * @param bool $once True if to call termination handler only once and remove it after first call
		 * @return $this
		 */
		protected function onTerminate(callable $handler, bool $once = false) {
			// note: this method is available public via magic methods

			$this->terminateHandlers[] = [
				'fn' => $handler,
				'once' => $once
			];

			return $this;
		}

		/**
		 * Dispatches the request
		 * @param ServerRequestInterface|null $request The request to dispatch. If null, request is captured from globals
		 * @param bool $emit True to emit response. Else the response is not emitted but only returned
		 * @return ResponseInterface The dispatched response
		 */
		public function dispatch(ServerRequestInterface $request = null, bool $emit = true) {

			// put this instance onto stack
			static::$stack[] = $this;

			try {

				// execute boot script
				$this->boot();

				// mark dispatched
				$this->dispatching = true;

				// capture request
				if (!$request)
					$request = $this->captureRequest();

				// process request
				$handler  = new Chain($this->middleware, $this->handler ?: new StatusCodeHandler(404));
				$response = $handler->handle($request);

				// inject Content-Length
				if ($response->getBody())
					$response = Util::injectContentLength($response);

				// emit the response
				if ($emit)
					$this->emit($response);

				// call terminate handlers
				$keepTerminateHandlers = [];
				foreach ($this->terminateHandlers as $key => $curr) {
					call_user_func($curr['fn'], $request, $response);

					if (!$curr['once'])
						$keepTerminateHandlers[$key] = true;
				}

				// remove "once" handlers
				$this->terminateHandlers = array_intersect_key($this->terminateHandlers, $keepTerminateHandlers);

				return $response;
			}
			finally {
				$this->dispatching = false;

				// pop instance from stack
				array_pop(static::$stack);
			}

		}

		/**
		 * Captures the request
		 * @return ServerRequestInterface The captured request
		 */
		protected function captureRequest() {
			$psr17Factory = new Psr17Factory();

			$creator = new ServerRequestCreator(
				$psr17Factory, // ServerRequestFactory
				$psr17Factory, // UriFactory
				$psr17Factory, // UploadedFileFactory
				$psr17Factory  // StreamFactory
			);

			return $creator->fromGlobals();
		}

		/**
		 * Emits a response
		 * @param ResponseInterface $response The response to emit
		 */
		protected function emit(ResponseInterface $response) {

			if ($this->sendBufferSize > 0) {
				// stream
				(new SapiStreamEmitter())
					->setMaxBufferLength($this->sendBufferSize)
					->emit($response);
			}
			else {
				// at-once
				(new SapiEmitter())
					->emit($response);
			}
		}

		/**
		 * Boots the dispatcher using specified boot script. The boot script can access the dispatcher using "$dispatcher"
		 */
		protected function boot() {

			if ($this->bootScript)
				bootDispatcher($this->bootScript, $this);
		}

		/**
		 * @inheritDoc
		 */
		public function __call($name, $arguments) {
			switch($name) {
				case 'onTerminate':
				case 'sendBuffer':
					return $this->{$name}(...$arguments);
			}

			throw new \BadMethodCallException("Calling undefined method $name");
		}


		/**
		 * @inheritdoc
		 */
		public static function __callStatic($name, $arguments) {

			// we pass on the call to the instance on dispatcher stack top
			$current = end(static::$stack);

			if (!$current)
				throw new \RuntimeException('Cannot invoke static method while not dispatching');

			return call_user_func_array([$current, $name], $arguments);
		}


	}


	/**
	 * Include boot script decoupled from $this variable
	 * @param string $bootScript The boot script
	 * @param Dispatcher $dispatcher The dispatcher
	 */
	function bootDispatcher(string $bootScript, Dispatcher $dispatcher) {
		/** @noinspection PhpIncludeInspection */
		include $bootScript;
	}