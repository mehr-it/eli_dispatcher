<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 21.03.19
	 * Time: 23:10
	 */

	namespace MehrIt\EliDispatcher\Handlers;


	use Nyholm\Psr7\Factory\Psr17Factory;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	class StatusCodeHandler implements RequestHandlerInterface
	{
		protected $statusCode;
		protected $headers;

		/**
		 * Creates a new instance
		 * @param int $statusCode The status code
		 * @param array $headers The headers
		 */
		public function __construct(int $statusCode, array $headers = []) {
			$this->statusCode = $statusCode;
			$this->headers    = $headers;
		}

		/**
		 * @inheritdoc
		 */
		public function handle(ServerRequestInterface $request): ResponseInterface {
			$response =  (new Psr17Factory())
				->createResponse()
				->withStatus($this->statusCode, null);

			foreach($this->headers as $name => $value) {
				$response = $response->withHeader($name, $value);
			}

			return $response;
		}
	}
