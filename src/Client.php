<?php

namespace DelaneyMethod\Sharepoint;

use Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use DelaneyMethod\Sharepoint\Exceptions\BadRequest;

class Client
{
	const THUMBNAIL_FORMAT_JPEG = 'jpeg';
	const THUMBNAIL_FORMAT_PNG = 'png';

	const THUMBNAIL_SIZE_XS = 'w32h32';
	const THUMBNAIL_SIZE_S = 'w64h64';
	const THUMBNAIL_SIZE_M = 'w128h128';
	const THUMBNAIL_SIZE_L = 'w640h480';
	const THUMBNAIL_SIZE_XL = 'w1024h768';

	const MAX_CHUNK_SIZE = 1024 * 1024 * 150;

	const UPLOAD_SESSION_START = 0;
	const UPLOAD_SESSION_APPEND = 1;

	/** @var string */
	protected $accessToken;

	/** @var \GuzzleHttp\Client */
	protected $client;

	/** @var int */
	protected $maxChunkSize;

	/** @var int */
	protected $maxUploadChunkRetries;

	/**
	 * @param string			$accessToken
	 * @param GuzzleClient|null $client
	 * @param int				$maxChunkSize Set max chunk size per request (determines when to switch from "one shot upload" to upload session and defines chunk size for uploads via session).
	 * @param int				$maxUploadChunkRetries How many times to retry an upload session start or append after RequestException.
	 */
	public function __construct(string $accessToken, GuzzleClient $client = null, int $maxChunkSize = self::MAX_CHUNK_SIZE, int $maxUploadChunkRetries = 0)
	{
		$this->accessToken = $accessToken;
		
		$this->client = $client ?? new GuzzleClient([
			'headers' => [
				'Authorization' => "Bearer {$this->accessToken}",
				'Accept' => 'application/json;odata=verbose',
			],
		]);

		$this->maxChunkSize = ($maxChunkSize < self::MAX_CHUNK_SIZE ? ($maxChunkSize > 1 ? $maxChunkSize : 1) : self::MAX_CHUNK_SIZE);
		
		$this->maxUploadChunkRetries = $maxUploadChunkRetries;
	}
}
