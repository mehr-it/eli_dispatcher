<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 15.03.19
	 * Time: 23:59
	 */

	namespace MehrIt\EliDispatcher\Dispatch;


	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	/**
	 * A container for middleware implementing the RequestHandlerInterface. This allows easy chaining
	 * @package MehrIt\EliEndpoint\Psr
	 */
	class Delegate implements RequestHandlerInterface
	{
		protected $middleware;
		protected $next;

		/**
		 * Creates a new instance
		 * @param MiddlewareInterface $middleware The middleware
		 * @param RequestHandlerInterface $next The next handler
		 */
		public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next) {
			$this->middleware = $middleware;
			$this->next       = $next;
		}


		/**
		 * @inheritdoc
		 */
		public function handle(ServerRequestInterface $request): ResponseInterface {
			return $this->middleware->process($request, $this->next);
		}

	}