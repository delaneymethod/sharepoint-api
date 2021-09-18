<?php

namespace DelaneyMethod\Sharepoint\Test;

use DelaneyMethod\Sharepoint\OnPremiseClient;
use PHPUnit\Framework\TestCase;

class OnPremiseClientTest extends TestCase {
    /**
     * @var OnPremiseClient $client
     */
    private $client = null;

    public function setUp(): void {
        $this->client = new OnPremiseClient([
            'siteName' => 'YOUR_TEAM_SITE_NAME',
            'siteUrl' => 'https://YOUR_SITE.sharepoint.com',
            'publicUrl' => 'https://YOUR_SITE.sharepoint.com/:i:/r/sites/YOUR_TEAM_SITE_NAME/Shared%20Documents',
            'client' => [
                'verify' => false
            ]
        ]);
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
