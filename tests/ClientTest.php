<?php

namespace DelaneyMethod\Sharepoint\Test;

use PHPUnit\Framework\TestCase;
use DelaneyMethod\Sharepoint\Client;

class ClientTest extends TestCase
{
	/** @test */
	public function it_can_be_instantiated()
	{
		$client = new Client('YOUR_TEAM_SITE_NAME', 'https://YOUR_SITE.sharepoint.com',
            'https://YOUR_SITE.sharepoint.com/:i:/r/sites/YOUR_TEAM_SITE_NAME/Shared%20Documents', 'YOUR_CLIENT_ID',
            'YOUR_CLIENT_SECRET', false, 'YOUR_ACCESS_TOKEN');

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
