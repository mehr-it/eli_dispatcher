<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 22.03.19
	 * Time: 01:12
	 */

	namespace MehrItEliDispatcherTest\Cases\Unit\Handlers;


	use MehrIt\EliDispatcher\Handlers\RedirectHandler;
	use MehrItEliDispatcherTest\Cases\Unit\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Message\UriInterface;

	class RedirectHandlerTest extends TestCase
	{

		public function testFullUrl() {

			/** @var ServerRequestInterface|MockObject $request */
			$request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			$handler = new RedirectHandler('https://www.test.de', 302);

			$response = $handler->handle($request);

			$this->assertSame(302, $response->getStatusCode());
			$this->assertNotEmpty($response->getReasonPhrase());
			$this->assertCount(1, $response->getHeaders());
			$this->assertSame('https://www.test.de', $response->getHeaderLine('location'));
		}

		public function testRelativeUrl() {

			/** @var UriInterface|MockObject $uri */
			$uri = $this->getMockBuilder(UriInterface::class)->getMock();
			$uri->method('getAuthority')
				->willReturn('https://www.test.de');

			/** @var ServerRequestInterface|MockObject $request */
			$request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
			$request->method('getUri')
				->willReturn($uri);


			$handler = new RedirectHandler('/absolute/path/to/dir', 302);

			$response = $handler->handle($request);

			$this->assertSame(302, $response->getStatusCode());
			$this->assertNotEmpty($response->getReasonPhrase());
			$this->assertCount(1, $response->getHeaders());
			$this->assertSame('https://www.test.de/absolute/path/to/dir', $response->getHeaderLine('location'));
		}

		public function testUrlWithoutScheme() {

			/** @var UriInterface|MockObject $uri */
			$uri = $this->getMockBuilder(UriInterface::class)->getMock();
			$uri->method('getScheme')
				->willReturn('https');

			/** @var ServerRequestInterface|MockObject $request */
			$request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
			$request->method('getUri')
				->willReturn($uri);


			$handler = new RedirectHandler('//www.test.de/absolute/path/to/dir', 302);

			$response = $handler->handle($request);

			$this->assertSame(302, $response->getStatusCode());
			$this->assertNotEmpty($response->getReasonPhrase());
			$this->assertCount(1, $response->getHeaders());
			$this->assertSame('https://www.test.de/absolute/path/to/dir', $response->getHeaderLine('location'));
		}

	}