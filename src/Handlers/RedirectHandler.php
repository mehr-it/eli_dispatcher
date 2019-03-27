<?php
	/**
	 * Created by PhpStorm.
	 * User: chris
	 * Date: 20.03.19
	 * Time: 01:27
	 */

	namespace MehrIt\EliDispatcher\Handlers;


	use Nyholm\Psr7\Factory\Psr17Factory;
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\RequestHandlerInterface;

	class RedirectHandler implements RequestHandlerInterface
	{
		protected $location;
		protected $statusCode;

		/**
		 * Creates a new instance
		 * @param string $location The location header
		 * @param int $statusCode The HTTP status code
		 */
		public function __construct(string $location, int $statusCode = 301) {
			$this->location   = $location;
			$this->statusCode = $statusCode;
		}

		/**
		 * @inheritDoc
		 */
		public function handle(ServerRequestInterface $request): ResponseInterface {
			$location   = $this->location;
			$statusCode = $this->statusCode;

			// here we create full URLs when location is an absolute path or omits the scheme
			if (substr($location, 0, 2) === '//')
				$location = $request->getUri()->getScheme() . ":{$location}";
			else if (substr($location, 0, 1) === '/')
				$location = $request->getUri()->getAuthority() . $location;

			// create response
			return (new Psr17Factory())->createResponse()
				->withStatus($statusCode)
				->withHeader('location', $location);
		}

	}