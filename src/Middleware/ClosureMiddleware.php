<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 24.03.19
	 * Time: 00:17
	 */

	namespace MehrIt\EliDispatcher\Middleware;


	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	/**
	 * Middleware using closure or callable internally
	 * @package MehrIt\EliDispatcher\Middleware
	 */
	class ClosureMiddleware implements MiddlewareInterface
	{
		/**
		 * @var callable
		 */
		protected $closure;

		/**
		 * Creates a new instance
		 * @param callable $closure The closure or callable. Receives request and next handler and must return response
		 */
		public function __construct(callable $closure) {
			$this->closure = $closure;
		}


		/**
		 * @inheritDoc
		 */
		public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
			$ret = call_user_func($this->closure, $request, $handler);

			// check result
			if (!($ret instanceof ResponseInterface))
				throw new \RuntimeException('Middleware closure did not return instance of ' . ResponseInterface::class);

			return $ret;
		}

	}