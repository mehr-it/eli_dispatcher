<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 24.03.19
	 * Time: 00:42
	 */

	namespace MehrItEliDispatcherTest\Cases\Unit\Dispatch;


	use Guzzle\Http\Message\Response;
	use MehrIt\EliDispatcher\Dispatch\Dispatcher;
	use MehrItEliDispatcherTest\Cases\Unit\TestCase;
	use Nyholm\Psr7\Factory\Psr17Factory;
	use PHPUnit\Framework\MockObject\MockObject;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	class DispatcherTest extends TestCase
	{
		public function testAddMiddlewareDuringDispatch() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$dispatcher = new Dispatcher();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->once())
				->method('handle')
				->with($requestMock)
				->willReturnCallback(function () use ($responseMock, $dispatcher) {
					$this->expectException(\RuntimeException::class);

					$dispatcher->middleware(function() {});

					return $responseMock;
				});


			$dispatcher->handler($handler);

			$dispatcher->dispatch($requestMock, false);

		}

		public function testSetNewHandlerDuringDispatch() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$dispatcher = new Dispatcher();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->once())
				->method('handle')
				->with($requestMock)
				->willReturnCallback(function () use ($responseMock, $dispatcher) {
					$this->expectException(\RuntimeException::class);

					$dispatcher->handler($this->getMockBuilder(RequestHandlerInterface::class)->getMock());

					return $responseMock;
				});


			$dispatcher->handler($handler);

			$dispatcher->dispatch($requestMock, false);

		}

		public function testSendBufferDuringDispatch() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$dispatcher = new Dispatcher();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->once())
				->method('handle')
				->with($requestMock)
				->willReturnCallback(function () use ($responseMock, $dispatcher) {

					$this->assertSame($dispatcher, Dispatcher::sendBuffer(100));

					return $responseMock;
				});


			$dispatcher->handler($handler);

			$dispatcher->dispatch($requestMock, false);

		}

		public function testSendBuffer() {

			$dispatcher = new Dispatcher();

			$this->assertSame($dispatcher, $dispatcher->sendBuffer(12));
		}

		public function testMiddlewareInvalidArgument() {
			$dispatcher = new Dispatcher();

			$this->expectException(\InvalidArgumentException::class);

			$dispatcher->middleware(new \stdClass());
		}

		public function testDispatchWithMiddleware() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			/** @var MiddlewareInterface|MockObject $middleware */
			$middleware = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
			$middleware->expects($this->once())
				->method('process')
				->with($requestMock)
				->willReturn($responseMock);

			$dispatcher = new Dispatcher();

			$this->assertSame($dispatcher, $dispatcher->middleware($middleware));

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertSame($responseMock, $response);
		}

		public function testDispatchWithClosureAsMiddleware() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$middleware = function($request, $next) use ($requestMock, $responseMock) {
				$this->assertSame($requestMock, $request);
				$this->assertInstanceOf(RequestHandlerInterface::class, $next);

				return $responseMock;
			};

			$dispatcher = new Dispatcher();

			$this->assertSame($dispatcher, $dispatcher->middleware($middleware));

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertSame($responseMock, $response);
		}

		public function testDispatchWithCallableAsMiddleware() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$callableObj = new DispatcherTest_CallableWrapper(function ($request, $next) use ($requestMock, $responseMock) {
				$this->assertSame($requestMock, $request);
				$this->assertInstanceOf(RequestHandlerInterface::class, $next);

				return $responseMock;
			});

			$dispatcher = new Dispatcher();

			$this->assertSame($dispatcher, $dispatcher->middleware([$callableObj, 'callHandler']));

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertSame($responseMock, $response);
		}

		public function testDispatchWithMultipleMiddleware() {
			/** @var ServerRequestInterface|MockObject $requestMock */
			/** @var ServerRequestInterface|MockObject $requestMock2 */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
			$requestMock2 = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			/** @var ResponseInterface|MockObject $responseMock2 */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
			$responseMock2 = $this->getMockBuilder(ResponseInterface::class)->getMock();

			/** @var MiddlewareInterface|MockObject $middleware1 */
			$middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
			$middleware1->expects($this->once())
				->method('process')
				->with($requestMock)
				->willReturnCallback(function($request, RequestHandlerInterface $next) use ($responseMock, $requestMock2, $responseMock2) {
					$this->assertSame($responseMock2, $next->handle($requestMock2));

					return $responseMock;
				});

			/** @var MiddlewareInterface|MockObject $middleware2 */
			$middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
			$middleware2->expects($this->once())
				->method('process')
				->with($requestMock2)
				->willReturn($responseMock2);

			$dispatcher = new Dispatcher();

			$this->assertSame($dispatcher, $dispatcher->middleware($middleware1));
			$this->assertSame($dispatcher, $dispatcher->middleware($middleware2));

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertSame($responseMock, $response);
		}

		public function testDispatchWithCustomHandler() {

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->once())
				->method('handle')
				->with($requestMock)
				->willReturn($responseMock);

			$dispatcher = new Dispatcher();

			$this->assertSame($dispatcher, $dispatcher->handler($handler));

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertSame($responseMock, $response);
		}

		public function testDispatchWithTerminateHandlers() {

			$term1Calls = 0;
			$term2Calls = 0;

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();


			$dispatcher = new Dispatcher();

			// add first terminator
			$this->assertSame($dispatcher, $dispatcher->onTerminate(function($request, $response) use ($requestMock, &$term1Calls) {
				$this->assertSame($requestMock, $request);
				$this->assertInstanceOf(ResponseInterface::class, $response);

				++$term1Calls;
			}));

			// add second terminator
			$this->assertSame($dispatcher, $dispatcher->onTerminate(function ($request, $response) use ($requestMock, &$term2Calls) {
				$this->assertSame($requestMock, $request);
				$this->assertInstanceOf(ResponseInterface::class, $response);

				++$term2Calls;
			}));

			$dispatcher->dispatch($requestMock, false);

			$this->assertSame(1, $term1Calls);
			$this->assertSame(1, $term2Calls);

		}

		public function testDispatchWithOnceTerminateHandlers() {

			$term1Calls = 0;
			$term2Calls = 0;

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();


			$dispatcher = new Dispatcher();

			// add first terminator (multiple times)
			$this->assertSame($dispatcher, $dispatcher->onTerminate(function ($request, $response) use ($requestMock, &$term1Calls) {
				$this->assertSame($requestMock, $request);
				$this->assertInstanceOf(ResponseInterface::class, $response);

				++$term1Calls;
			}));

			// add second terminator (once)
			$this->assertSame($dispatcher, $dispatcher->onTerminate(function ($request, $response) use ($requestMock, &$term2Calls) {
				$this->assertSame($requestMock, $request);
				$this->assertInstanceOf(ResponseInterface::class, $response);

				++$term2Calls;
			}, true));

			$dispatcher->dispatch($requestMock, false);
			$dispatcher->dispatch($requestMock, false);

			$this->assertSame(2, $term1Calls);
			$this->assertSame(1, $term2Calls);

		}

		public function testDispatchWithTerminateHandlerAddedDuringDispatch() {
			$term1Calls = 0;

			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			/** @var ResponseInterface|MockObject $responseMock */
			$responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

			$dispatcher = new Dispatcher();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->exactly(2))
				->method('handle')
				->with($requestMock)
				->willReturnCallback(function() use ($responseMock, $requestMock, $dispatcher, &$term1Calls) {
					// only on first call
					if (!$term1Calls) {
						$self = Dispatcher::onTerminate(function ($request, $response) use ($requestMock, $responseMock, &$term1Calls) {
							$this->assertSame($requestMock, $request);
							$this->assertSame($responseMock, $response);

							++$term1Calls;
						}, true);

						$this->assertSame($dispatcher, $self);
					}

					return $responseMock;
				});


			$dispatcher->handler($handler);

			$dispatcher->dispatch($requestMock, false);
			$dispatcher->dispatch($requestMock, false);

			$this->assertSame(1, $term1Calls);

		}

		public function testDispatchSetsContentLength() {
			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			$dispatcher = new Dispatcher();

			$factory = new Psr17Factory();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->once())
				->method('handle')
				->with($requestMock)
				->willReturn($factory
					->createResponse(200)
					->withoutHeader('Content-Length')
					->withBody($factory->createStream('abc'))
				);


			$dispatcher->handler($handler);

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertEquals(3, $response->getHeaderLine('Content-Length'));
		}

		public function testDispatchDoesNotOverwriteContentLength() {
			/** @var ServerRequestInterface|MockObject $requestMock */
			$requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			$dispatcher = new Dispatcher();

			$factory = new Psr17Factory();

			/** @var RequestHandlerInterface|MockObject $handler */
			$handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
			$handler->expects($this->once())
				->method('handle')
				->with($requestMock)
				->willReturn($factory
					->createResponse(200)
					->withHeader('Content-Length', 100)
					->withBody($factory->createStream('abc'))
				);


			$dispatcher->handler($handler);

			$response = $dispatcher->dispatch($requestMock, false);

			$this->assertEquals(100, $response->getHeaderLine('Content-Length'));
		}


	}

	class DispatcherTest_CallableWrapper
	{

		protected $handler;



		/**
		 * ClosureMiddlewareTest_CallableWrapper constructor.
		 * @param $handler
		 * @param $return
		 */
		public function __construct($handler) {
			$this->handler = $handler;
		}


		public function callHandler() {
			$ret = call_user_func_array($this->handler, func_get_args());

			return $ret;
		}
	}