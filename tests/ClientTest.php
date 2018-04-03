<?php

namespace DelaneyMethod\Sharepoint\Test;

use PHPUnit\Framework\TestCase;
use DelaneyMethod\Sharepoint\Client;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use Spatie\Dropbox\Exceptions\BadRequest;
use DelaneyMethod\Sharepoint\UploadSessionCursor;

class ClientTest extends TestCase
{
	/** @test */
	public function it_can_be_instantiated()
	{
		$client = new Client('test_token');

		$this->assertInstanceOf(Client::class, $client);
	}

	protected static function getMethod($name)
	{
		$class = new \ReflectionClass('DelaneyMethod\Sharepoint\Client');

		$method = $class->getMethod($name);

		$method->setAccessible(true);

		return $method;
	}
}
