<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 22.03.19
	 * Time: 01:01
	 */

	namespace MehrItEliDispatcherTest\Cases\Unit\Handlers;


	use MehrIt\EliDispatcher\Handlers\StatusCodeHandler;
	use MehrItEliDispatcherTest\Cases\Unit\TestCase;
	use PHPUnit\Framework\MockObject\MockObject;
	use Psr\Http\Message\ServerRequestInterface;

	class StatusCodeHandlerTest extends TestCase
	{


		public function testWithoutHeaders() {

			/** @var ServerRequestInterface|MockObject $request */
			$request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			$handler = new StatusCodeHandler(403);

			$response = $handler->handle($request);

			$this->assertSame(403, $response->getStatusCode());
			$this->assertNotEmpty($response->getReasonPhrase());
			$this->assertEmpty($response->getHeaders());
		}

		public function testWithHeaders() {

			$headers = [
				'X-USER-HEADER' => 'my value',
				'Content-Type'  => 'text/text'
			];

			/** @var ServerRequestInterface|MockObject $request */
			$request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

			$handler = new StatusCodeHandler(401, $headers);

			$response = $handler->handle($request);

			$this->assertSame(401, $response->getStatusCode());
			$this->assertNotEmpty($response->getReasonPhrase());
			$this->assertCount(2, $response->getHeaders());
			$this->assertSame('my value', $response->getHeaderLine('x-user-header'));
			$this->assertSame('text/text', $response->getHeaderLine('Content-Type'));
		}


	}