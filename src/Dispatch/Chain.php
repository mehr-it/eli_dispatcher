<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 16.03.19
	 * Time: 00:47
	 */

	namespace MehrIt\EliDispatcher\Dispatch;


	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	/**
	 * Implements a request handling chain with multiple middleware chained before the request handler
	 * @package MehrIt\EliDispatcher\Dispatch
	 */
	class Chain implements RequestHandlerInterface
	{

		protected $middleware;
		protected $handler;

		/**
		 * Creates a new instance
		 * @param MiddlewareInterface[] $middleware The middleware instances
		 * @param RequestHandlerInterface $handler The handler
		 */
		public function __construct(array $middleware, RequestHandlerInterface $handler) {
			$this->middleware = $middleware;
			$this->handler    = $handler;
		}


		/**
		 * @inheritdoc
		 */
		public function handle(ServerRequestInterface $request): ResponseInterface {

			// build request processing chain
			$head = $this->handler;
			foreach (array_reverse($this->middleware) as $currMiddleware) {
				$head = new Delegate($currMiddleware, $head);
			}

			// handle request
			return $head->handle($request);
		}


	}