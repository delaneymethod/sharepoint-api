<?php

namespace DelaneyMethod\Sharepoint\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
	/**
	 * The sharepoint error code supplied in the response.
	 *
	 * @var string|null
	 */
	public $sharepointCode;

	public function __construct(ResponseInterface $response)
	{
		$body = json_decode($response->getBody(), true);

		if (isset($body['error'])) {
			$this->sharepointCode = $body['error'];
		}

		parent::__construct($body['error_summary']);
	}
}
