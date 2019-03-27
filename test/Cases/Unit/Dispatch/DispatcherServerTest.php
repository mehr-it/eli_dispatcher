<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 23.03.19
	 * Time: 00:04
	 */

	namespace MehrItEliDispatcherTest\Cases\Unit\Dispatch;


	use Guzzle\Http\Client;
	use Guzzle\Http\Exception\ClientErrorResponseException;
	use MehrItEliDispatcherTest\Cases\Unit\TestCase;
	use Symfony\Component\Process\Process;

	class DispatcherServerTest extends TestCase
	{


		/** @var Process */
		protected static $process;

		protected static $server = 'localhost:8080';

		public static function setUpBeforeClass() {
			$docRoot = __DIR__ . '/../../../server';

			self::$process = new Process("php -S " . self::$server . " -t \"{$docRoot}\"");
			self::$process->start();
			usleep(100000); // wait for server to get going
		}

		public static function tearDownAfterClass() {
			self::$process->stop();
		}

		protected function url($path) {
			return 'http://' . self::$server . $path;
		}



		public function testDispatchDefaultHandler() {

			try {
				(new Client())
					->get($this->url('/dispatch_default_handler.php'))
					->send();

				$this->assertFalse(true, 'Expected exception to be thrown');
			}
			catch (ClientErrorResponseException $ex) {
				$this->assertSame(404, $ex->getResponse()->getStatusCode());
			}
		}

		public function testDispatchHandlerOnly() {

			$response = (new Client())
				->get($this->url('/dispatch_handler_only.php'))
				->send()
			;

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('test body', (string)$response->getBody());
		}

		public function testDispatchWithMiddleware_passToHandler() {

			$response = (new Client())
				->get($this->url('/dispatch_with_middleware.php'), ['X-PASS-TO-HANDLER' => 'yes'])
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('from handler', (string)$response->getBody());
			$this->assertSame('my value', (string)$response->getHeader('X-ADDED-HEADER'));
		}

		public function testDispatchWithMiddleware_handledByMiddleware() {

			$response = (new Client())
				->get($this->url('/dispatch_with_middleware.php'), ['X-PASS-TO-HANDLER' => 'no'])
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('from middleware', (string)$response->getBody());
			$this->assertSame('my value', (string)$response->getHeader('X-ADDED-HEADER'));
		}

		public function testDispatchWithMultipleMiddleware() {

			$response = (new Client())
				->get($this->url('/dispatch_with_multiple_middleware.php'))
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('from middleware 2', (string)$response->getBody());
			$this->assertSame('m2m1', (string)$response->getHeader('X-ADDED-HEADER'));
		}

		public function testDispatchRequestPassed() {

			$response = (new Client())
				->get($this->url('/dispatch_passed_request.php'))
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('GET http://test.de/ test value', (string)$response->getBody());
		}

		public function testDispatchNoEmit() {

			$response = (new Client())
				->get($this->url('/dispatch_no_emit.php'))
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('after emit', (string)$response->getBody());
		}

		public function testDispatchTerminateHandlers() {
			$response = (new Client())
				->get($this->url('/dispatch_terminate_handlers.php'))
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$path = (string)$response->getBody();

			try {
				// at this point, only handle should be written yet
				$this->assertSame(':handle', file_get_contents($path));

				// wait some time
				sleep(1);

				// now the terminate handlers should also have written to the file
				$this->assertSame(':handle:terminate1:terminate2', file_get_contents($path));
			}
			finally {
				unlink($path);
			}

		}

		public function testDispatchBootScript() {

			$response = (new Client())
				->get($this->url('/dispatch_boot_script.php'))
				->send();

			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame('test body from boot script handler', (string)$response->getBody());
		}
	}