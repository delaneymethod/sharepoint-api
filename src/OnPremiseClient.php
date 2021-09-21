<?php


namespace DelaneyMethod\Sharepoint;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\StreamWrapper;

class OnPremiseClient extends Client {

    protected $digestTimeout = null;
    protected $client = null;
    protected $config = [
        'siteName' => '',
        'siteUrl' => '',
        'publicUrl' => '',
        'client' => [
            'verify' => false
        ]
    ];

    /**
     * OnPremiseClient constructor.
     *
     * @param array $config
     */
    public function __construct(array $config) {
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }

        extract($this->config);
        parent::__construct( $this->config['siteName'] ?? '', $this->config['siteUrl'] ?? '', $this->config['publicUrl'] ?? '', '', '', $this->config['client']['verify'] ?? false, '');

        $this->folderPath = '/Shared%20Documents';

        $this->client = new GuzzleClient($this->config['client']);

        $this->digestTimeout = new \DateTime();

        foreach($this->folderPathExcludeList as &$excludedPath) {
            $excludedPath = $this->normalizePath($excludedPath);
        }
    }

    /**
     * Create a folder at a given path.
     *
     * @param string $path
     *
     * @return bool
     * @throws \Exception
     */
    public function createFolder($path) : bool
    {
        $path = $this->normalizePath($path);

        // Check if the path contains folders we dont want to create
        if (in_array($path, $this->folderPathExcludeList)) {
            return true;
        }

        $this->folderPath = '/Shared%20Documents';

        $action = 'folders';

        $this->requestHeaders['Content-Type'] = 'application/json;odata=verbose';

        $folderAttributes = [
            '__metadata' => [
                'type' => 'SP.Folder',
            ],
            'ServerRelativeUrl' => $this->folderPath.$path,
        ];

        $options = [
            'headers' => $this->requestHeaders,
            'body' => json_encode($folderAttributes)
        ];

        $response = $this->send('POST', $action, $options);

        return $response->getStatusCode() === 200 ? true : false;
    }

    /**
     * Delete the file or folder at a given path.
     * If the path is a folder, all its contents will be deleted too.
     * A successful response indicates that the file or folder was deleted.
     *
     * @param string $path
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(string $path) : bool
    {
        $path = $this->normalizePath($path);

        // Check if the path contains folders we dont want to delete
        if ($this->isAllowedFolder($path)) {
            return true;
        }

        $action = 'GetFolderByServerRelativeUrl(\''.$this->folderPath.$path.'\')';

        $this->requestHeaders['IF-MATCH'] = 'etag';

        $this->requestHeaders['X-HTTP-Method'] = 'DELETE';

        $options = [
            'headers' => $this->requestHeaders,
        ];

        $response = $this->send('POST', $action, $options);

        return $response->getStatusCode() === 200 ? true : false;
    }

    /**
     * Returns the contents of a folder.
     *
     * @param string $path Path after $this->folderPath
     * @param bool $recursive
     *
     * @return array
     * @throws \Exception
     */
    public function listFolder(string $path, bool $recursive = false) : array
    {
        $path = $this->normalizePath($path);

        $action = 'GetFolderByServerRelativeUrl(\''.$this->folderPath.$path.'\')/Folders';

        $options = [
            'headers' => $this->requestHeaders,
        ];

        $response = $this->send('GET', $action, $options);

        $folders = json_decode($response->getBody())->d ?? ['results' => []];

        // Making sure we convert the object to an array
        if (!is_array($folders)) {
            $folders = json_decode(json_encode($folders), true);
        }

        return $folders['results'];
    }

    /**
     * Copy a file or folder to a different location.
     * https://msdn.microsoft.com/en-us/library/office/jj247198%28v=office.15%29.aspx
     *
     * @param string $fromPath
     * @param string $toPath
     * @param array $mimeType
     *
     * @return bool
     * @throws \Exception
     */
    public function copy(string $fromPath, string $toPath, array $mimeType) : bool
    {
        $fromPath = $this->normalizePath($fromPath);

        $toPath = $this->normalizePath($toPath);

        // If plain/text, its a folder
        if (substr($mimeType['mimetype'], 0, 4) === 'text') {
            $action = 'GetFolderByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/copyTo(strNewUrl=\''.$this->folderPath.$toPath.'\', bOverWrite=true)';
        } else {
            $action = 'GetFileByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/copyTo(strNewUrl=\''.$this->folderPath.$toPath.'\', bOverWrite=true)';
        }

        $options = [
            'headers' => $this->requestHeaders,
        ];

        $response = $this->send('POST', $action, $options);

        return $response->getStatusCode() === 200 ? true : false;
    }

    /**
     * Move a file or folder to a different location.
     * If the source path is a folder all its contents will be moved.
     *
     * @param string $fromPath
     * @param string $toPath
     * @param array $mimeType
     *
     * @return bool
     * @throws \Exception
     */
    public function move(string $fromPath, string $toPath, array $mimeType) : bool
    {
        $fromPath = $this->normalizePath($fromPath);

        $toPath = $this->normalizePath($toPath);

        // If plain/text, its a folder
        if (substr($mimeType['mimetype'], 0, 4) === 'text') {
            $action = 'GetFolderByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/moveTo(newUrl=\''.$this->folderPath.$toPath.'\')';
        } else {
            $action = 'GetFileByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/moveTo(newUrl=\''.$this->folderPath.$toPath.'\', flags=1)';
        }

        $options = [
            'headers' => $this->requestHeaders,
        ];

        $response = $this->send('POST', $action, $options);

        return $response->getStatusCode() === 200 ? true : false;
    }

    /**
     * Create a new file with the contents provided in the request.
     *
     * @param string $path
     * @param string|resource $contents
     *
     * @return bool
     * @throws \Exception
     */
    public function upload(string $path, $contents) : array
    {
        $path = trim($path, '/');

        // Split the path so we can grab the filename and folder path
        $segments = explode('/', $path);

        // Filename will be last item
        $path = end($segments);

        // Remove filename from segments so we're left with the folder path
        array_pop($segments);

        // Update the folder path
        $folderPath = implode('/', $segments);

        $this->folderPath = $this->folderPath.'/'.$folderPath;

        $action = 'GetFolderByServerRelativeUrl(\''.$this->folderPath.'\')/Files/add(url=\''.$path.'\',overwrite=true)';

        $options = [
            'headers' => $this->requestHeaders,
            'body' => $contents,
        ];

        $response = $this->send('POST', $action, $options);

        $file = json_decode($response->getBody())->d ?? [];

        // Making sure we convert the object to an array
        if (!is_array($file)) {
            $file = json_decode(json_encode($file), true);
        }

        return $file;
    }

    /**
     * Download a file.
     *
     * @param string $path
     *
     * @return resource
     * @throws \Exception
     */
    public function download(string $path)
    {
        // To get specific file data pass in the value you want e.g. /Name
        $action = 'GetFileByServerRelativeUrl(\''.$this->folderPath.$path.'\')';

        $options = [
            'headers' => $this->requestHeaders,
        ];

        $response = $this->send('GET', $action, $options);

        return StreamWrapper::getResource($response->getBody());
    }

    public function getRequestDigest()
    {
        $digest = null;
        $options = [
            'headers' => $this->requestHeaders
        ];
        $context = $this->client->request('POST', $this->siteUrl . '/_api/contextinfo', $options);
        $context = json_decode($context->getBody()) ?? null;

        if ($context && $this->digestTimeout->diff(new \DateTime())->invert === 0) {
            $digest = $context->d->GetContextWebInformation->FormDigestValue;
            $this->digestTimeout->add(new \DateInterval('PT'. $context->d->GetContextWebInformation->FormDigestTimeoutSeconds.'S'));
            $this->requestHeaders['X-RequestDigest'] = $digest;
        }

        return $digest ?? $this->requestHeaders['X-RequestDigest'];
    }

    private function send(string $method, string $action, array $options)
    {
        try {
            if (in_array($method, ['POST', 'PUT'])) {
                $options['headers']['X-RequestDigest'] = $this->getRequestDigest();
            }
            return $this->client->request($method, $this->siteUrl . '/_api/Web/' . $action, $options);
        } catch (RequestException $requestException) {
            throw new \Exception(Psr7\Message::toString($requestException->getResponse()));
        }
    }

    private function isAllowedFolder(string $path) {
        return !in_array($path, $this->folderPathExcludeList);
    }
}
