<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 24.03.19
	 * Time: 00:27
	 */

	namespace MehrItEliDispatcherTest\Cases\Unit\Middleware;


	use MehrIt\EliDispatcher\Middleware\ClosureMiddleware;
	use MehrItEliDispatcherTest\Cases\Unit\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	class ClosureMiddlewareTest extends TestCase
	{

		public function testClosure() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var RequestHandlerInterface|MockObject $handlerMock */
			$handlerMock = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$callback = function ($request, $next) use ($requestMock, $handlerMock, $responseMock) {

				$this->assertSame($requestMock, $request);
				$this->assertSame($handlerMock, $next);

				return $responseMock;
			};


			$middleware = new ClosureMiddleware($callback);

			$this->assertSame($responseMock, $middleware->process($requestMock, $handlerMock));

		}

		public function testClosure_notReturningResponse() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var RequestHandlerInterface|MockObject $handlerMock */
			$handlerMock = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$callback = function ($request, $next) use ($requestMock, $handlerMock, $responseMock) {
				// we return nothing, which should raise a runtime exception
			};


			$middleware = new ClosureMiddleware($callback);

			$this->expectException(\RuntimeException::class);

			$middleware->process($requestMock, $handlerMock);

		}

		public function testCallable() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var RequestHandlerInterface|MockObject $handlerMock */
			$handlerMock = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$callback = function ($request, $next) use ($requestMock, $handlerMock, $responseMock) {

				$this->assertSame($requestMock, $request);
				$this->assertSame($handlerMock, $next);

				return $responseMock;
			};

			$callable = new ClosureMiddlewareTest_CallableWrapper($callback, true);

			$middleware = new ClosureMiddleware([$callable, 'callHandler']);

			$this->assertSame($responseMock, $middleware->process($requestMock, $handlerMock));

		}

		public function testCallable_notReturningResponse() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var RequestHandlerInterface|MockObject $handlerMock */
			$handlerMock = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$callback = function ($request, $next) use ($requestMock, $handlerMock, $responseMock) {
				// we return nothing, which should raise a runtime exception
			};


			$callable = new ClosureMiddlewareTest_CallableWrapper($callback, true);

			$middleware = new ClosureMiddleware([$callable, 'callHandler']);


			$this->expectException(\RuntimeException::class);

			$middleware->process($requestMock, $handlerMock);

		}

	}

	class ClosureMiddlewareTest_CallableWrapper {

		protected $handler;

		protected $return;

		/**
		 * ClosureMiddlewareTest_CallableWrapper constructor.
		 * @param $handler
		 * @param $return
		 */
		public function __construct($handler, $return) {
			$this->handler = $handler;
			$this->return  = $return;
		}


		public function callHandler() {
			$ret = call_user_func_array($this->handler, func_get_args());

			if ($this->return)
				return $ret;
		}
	}