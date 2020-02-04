<?php

namespace DelaneyMethod\Sharepoint\Test;

use DelaneyMethod\Sharepoint\OnPremiseClient;
use PHPUnit\Framework\TestCase;

class OnPremiseClientTest extends TestCase {
    /**
     * @var OnPremiseClient $client
     */
    private $client = null;

    public function setUp() {
        $this->client = new OnPremiseClient();
    }

    public function testCreateFolder()
    {
        $folders = $this->client->createFolder('Test folder');
        $this->assertNotEmpty($folders);
    }


    public function testListFolder()
    {
        $folders = $this->client->listFolder('');
        $this->assertNotEmpty($folders);
    }

}
