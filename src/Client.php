<?php

namespace DelaneyMethod\Sharepoint;

use Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\{ClientException, RequestException};

class Client
{
	protected $verify;
	
	protected $siteUrl;
	
	protected $siteName;
	
	protected $clientId;
	
	protected $publicUrl;
	
	protected $folderPath;
	
	protected $accessToken;
	
	protected $requestBody;
	
	protected $clientSecret;
	
	protected $requestHeaders;
	
	public function __construct(string $siteName, string $siteUrl, string $publicUrl, string $clientId, string $clientSecret, bool $verify, string $accessToken = null)
	{
		$this->verify = $verify;
		
		$this->siteUrl = $siteUrl;
		
		$this->siteName = $siteName;
		
		$this->clientId = $clientId;
		
		$this->publicUrl = $publicUrl;
		
		$this->accessToken = $accessToken;
		
		$this->clientSecret = $clientSecret;
		
		$this->folderPath = '/sites/'.$this->siteName.'/Shared%20Documents';
		
		$this->client = new GuzzleClient([
			'verify' => $this->verify,
		]);
		
		$this->requestBody = [];
		
		$this->requestHeaders = [
			'Accept' => 'application/json;odata=verbose',
			'Authorization' => 'Bearer '.$this->accessToken,
		];
	}
	
	/**
	 * Create a folder at a given path.
	 */
	public function createFolder($path) : bool
	{
		$path = $this->normalizePath($path);
		
		$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/folders';
		
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
		
		$response = $this->send('POST', $requestUrl, $options);
		
		return $response->getStatusCode() === 200 ? true : false;
	}
	
	/**
	 * Delete the file or folder at a given path.
	 *
	 * If the path is a folder, all its contents will be deleted too.
	 * A successful response indicates that the file or folder was deleted.
	 */
	public function delete(string $path) : bool
	{
		$path = $this->normalizePath($path);
			
		$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFolderByServerRelativeUrl(\''.$this->folderPath.$path.'\')';
		
		$this->requestHeaders['IF-MATCH'] = 'etag';
			
		$this->requestHeaders['X-HTTP-Method'] = 'DELETE';
				
		$options = [
			'headers' => $this->requestHeaders,
		];
			
		$response = $this->send('POST', $requestUrl, $options);
		
		return $response->getStatusCode() === 200 ? true : false;
	}
	
	/**
	 * Returns the metadata for a file or folder.
	 *
	 * Note: Metadata for the root folder is unsupported.
	 */
	public function getMetadata(string $path, array $mimeType) : array
	{
		$path = $this->normalizePath($path);
		
		// If plain/text, its a folder
		if (substr($mimeType['mimetype'], 0, 4) === 'text') {
			$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFolderByServerRelativeUrl(\''.$this->folderPath.$path.'\')';
		} else {
			$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFileByServerRelativeUrl(\''.$this->folderPath.$path.'\')';
		}
		
		$options = [
			'headers' => $this->requestHeaders,
		];
			
		$response = $this->send('GET', $requestUrl, $options);
		
		$metadata = json_decode($response->getBody())->d ?? [];
		
		// Making sure we convert the object to an array
		if (!is_array($metadata)) {
			$metadata = json_decode(json_encode($metadata), true);
		}
		
		return $metadata;
	}
	
	/**
	 * Returns the contents of a folder.
	 */
	public function listFolder(string $path, bool $recursive = false) : array
	{
		$path = $this->normalizePath($path);
			
		$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFolderByServerRelativeUrl(\''.$this->folderPath.$path.'\')/Folders';
		
		$options = [
			'headers' => $this->requestHeaders,
		];
			
		$response = $this->send('GET', $requestUrl, $options);
			
		$folders = json_decode($response->getBody())->d ?? ['results' => []];
			
		// Making sure we convert the object to an array
		if (!is_array($folders)) {
			$folders = json_decode(json_encode($folders), true);
		}
			
		return $folders['results'];
	}
	
	/**
	 * Copy a file or folder to a different location.
	 *
	 * https://msdn.microsoft.com/en-us/library/office/jj247198%28v=office.15%29.aspx
	 */
	public function copy(string $fromPath, string $toPath, array $mimeType) : bool
	{
		$fromPath = $this->normalizePath($fromPath);
		
		$toPath = $this->normalizePath($toPath);
		
		// If plain/text, its a folder
		if (substr($mimeType['mimetype'], 0, 4) === 'text') {
			$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFolderByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/copyTo(strNewUrl=\''.$this->folderPath.$toPath.'\', bOverWrite=true)';
		} else {
			$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFileByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/copyTo(strNewUrl=\''.$this->folderPath.$toPath.'\', bOverWrite=true)';
		}
		
		$options = [
			'headers' => $this->requestHeaders,
		];
			
		$response = $this->send('POST', $requestUrl, $options);
		
		return $response->getStatusCode() === 200 ? true : false;
	}
	
	/**
	 * Move a file or folder to a different location.
	 *
	 * If the source path is a folder all its contents will be moved.
	 */
	public function move(string $fromPath, string $toPath, array $mimeType) : bool
	{
		$fromPath = $this->normalizePath($fromPath);
		
		$toPath = $this->normalizePath($toPath);
		
		// If plain/text, its a folder
		if (substr($mimeType['mimetype'], 0, 4) === 'text') {
			$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFolderByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/moveTo(newUrl=\''.$this->folderPath.$toPath.'\')';
		} else {
			$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFileByServerRelativeUrl(\''.$this->folderPath.$fromPath.'\')/moveTo(newUrl=\''.$this->folderPath.$toPath.'\', flags=1)';
		}
		
		$options = [
			'headers' => $this->requestHeaders,
		];
			
		$response = $this->send('POST', $requestUrl, $options);
		
		return $response->getStatusCode() === 200 ? true : false;
	}
	
	/**
	 * Create a new file with the contents provided in the request.
	 *
	 * @param string $path
	 * @param string|resource $contents
	 *
	 * @return bool
	 */
	public function upload(string $path, $contents) : array
	{
		$path = trim($path, '/');
		
		$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFolderByServerRelativeUrl(\''.$this->folderPath.'\')/Files/add(url=\''.$path.'\', overwrite=true)';
		
		$options = [
			'headers' => $this->requestHeaders,
			'body' => $contents,
		];
		
		$response = $this->send('POST', $requestUrl, $options);
		
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
	 */
	public function download(string $path)
	{
		// To get specific file data pass in the value you want e.g. /Name 
		$requestUrl = $this->siteUrl.'/sites/'.$this->siteName.'/_api/Web/GetFileByServerRelativeUrl(\''.$this->folderPath.$path.'\')';
		
		$options = [
			'headers' => $this->requestHeaders,
		];
		
		$response = $this->send('GET', $requestUrl, $options);
		
		return StreamWrapper::getResource($response->getBody());
	}
	
	protected function normalizePath(string $path) : string
	{
		if (preg_match("/^id:.*|^rev:.*|^(ns:[0-9]+(\/.*)?)/", $path) === 1) {
			return $path;
		}
		
		$path = trim($path, '/');
		
		return ($path === '') ? '' : '/'.$path;
	}
	
	private function send(string $method, string $url, array $options)
	{
		try {
			return $this->client->request($method, $url, $options);
		} catch (RequestException $requestException) {
			throw new Exception(Psr7\str($requestException->getResponse()));
		} catch (ClientException $clientException) {
			throw new Exception(Psr7\str($clientException->getResponse()));
		}
	}
}
