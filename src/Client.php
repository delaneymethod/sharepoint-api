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

	protected $appId;
	
	protected $verify;
	
	protected $siteName;
	
	protected $sitesUri;
	
	protected $appSecret;
	
	protected $folderPath;
	
	protected $redirectUrl;
	
	protected $refreshToken;

	protected $maxChunkSize;

	protected $documentsUri;
	
	protected $sharepointUrl;
	
	protected $maxUploadChunkRetries;

	/**
	 * @param array				$config	
	 * @param GuzzleClient|null $client
	 * @param int				$maxChunkSize Set max chunk size per request (determines when to switch from "one shot upload" to upload session and defines chunk size for uploads via session).
	 * @param int				$maxUploadChunkRetries How many times to retry an upload session start or append after RequestException.
	 */
	public function __construct(array $config, GuzzleClient $client = null, int $maxChunkSize = self::MAX_CHUNK_SIZE, int $maxUploadChunkRetries = 0)
	{
		$this->sitesUri = 'sites';
		
		$this->appId = $config['app_id'];
		
		$this->verify = $config['verify'];
		
		$this->sharepointUrl = $config['url'];
		
		$this->siteName = $config['site_name'];
		
		$this->appSecret = $config['app_secret'];
		
		$this->documentsUri = 'Shared%20Documents';
		
		$this->redirectUrl = $config['redirect_url'];
		
		$this->refreshToken = '1234';
		
		$this->client = $client ?? new GuzzleClient([
			'verify' => $config['verify'],
			'headers' => [
				'Authorization' => "Bearer {$this->refreshToken}",
				'Accept' => 'application/json;odata=verbose',
			],
		]);

		$this->maxChunkSize = ($maxChunkSize < self::MAX_CHUNK_SIZE ? ($maxChunkSize > 1 ? $maxChunkSize : 1) : self::MAX_CHUNK_SIZE);
		
		$this->maxUploadChunkRetries = $maxUploadChunkRetries;
		
		$this->folderPath = DIRECTORY_SEPARATOR.$this->sitesUri.DIRECTORY_SEPARATOR.$this->siteName.DIRECTORY_SEPARATOR.$this->documentsUri;
	}
	
	/**
	 * Copy a file or folder to a different location.
	 */
	public function copy(string $fromPath, string $toPath) : array
	{
		/*
		$parameters = [
			'from_path' => $this->normalizePath($fromPath),
			'to_path' => $this->normalizePath($toPath),
		];
		
		return $this->rpcEndpointRequest('files/copy_v2', $parameters);
		*/
	}
	
	/**
	 * Create a folder at a given path.
	 */
	public function createFolder(string $path) : array
	{
		/*
		$parameters = [
			'path' => $this->normalizePath($path),
		];
		$object = $this->rpcEndpointRequest('files/create_folder', $parameters);
		$object['.tag'] = 'folder';
		return $object;
		*/
	}
	
	/**
	 * Delete the file or folder at a given path.
	 *
	 * If the path is a folder, all its contents will be deleted too.
	 * A successful response indicates that the file or folder was deleted.
	 */
	public function delete(string $path) : array
	{
		/*
		$parameters = [
			'path' => $this->normalizePath($path),
		];
		return $this->rpcEndpointRequest('files/delete', $parameters);
		*/
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
		/*
		$arguments = [
			'path' => $this->normalizePath($path),
		];
		$response = $this->contentEndpointRequest('files/download', $arguments);
		return StreamWrapper::getResource($response->getBody());
		*/
		
	}
	
	/**
	 * Returns the metadata for a file or folder.
	 *
	 * Note: Metadata for the root folder is unsupported.
	 */
	public function getMetadata(string $path) : array
	{
		/*
		$parameters = [
			'path' => $this->normalizePath($path),
		];
		return $this->rpcEndpointRequest('files/get_metadata', $parameters);
		*/
	}
	
	/**
	 * Get a thumbnail for an image.
	 *
	 * This method currently supports files with the following file extensions:
	 * jpg, jpeg, png, tiff, tif, gif and bmp.
	 *
	 * Photos that are larger than 20MB in size won't be converted to a thumbnail.
	 */
	public function getThumbnail(string $path, string $format = 'jpeg', string $size = 'w64h64') : string
	{
		/*
		$arguments = [
			'path' => $this->normalizePath($path),
			'format' => $format,
			'size' => $size,
		];
		$response = $this->contentEndpointRequest('files/get_thumbnail', $arguments);
		return (string) $response->getBody();
		*/
	}
	
	/**
	 * Starts returning the contents of a folder.
	 *
	 * If the result's ListFolderResult.has_more field is true, call
	 * list_folder/continue with the returned ListFolderResult.cursor to retrieve more entries.
	 *
	 * Note: auth.RateLimitError may be returned if multiple list_folder or list_folder/continue calls
	 * with same parameters are made simultaneously by same API app for same user. If your app implements
	 * retry logic, please hold off the retry until the previous request finishes.
	 */
	public function listFolder(string $path = '', bool $recursive = false) : array
	{
		/*
		$parameters = [
			'path' => $this->normalizePath($path),
			'recursive' => $recursive,
		];
		return $this->rpcEndpointRequest('files/list_folder', $parameters);
		*/
	}
	
	/**
	 * Once a cursor has been retrieved from list_folder, use this to paginate through all files and
	 * retrieve updates to the folder, following the same rules as documented for list_folder.
	 */
	public function listFolderContinue(string $cursor = '') : array
	{
		// return $this->rpcEndpointRequest('files/list_folder/continue', compact('cursor'));
	}
	
	/**
	 * Move a file or folder to a different location in the user's Dropbox.
	 *
	 * If the source path is a folder all its contents will be moved.
	 */
	public function move(string $fromPath, string $toPath) : array
	{
		/*
		$parameters = [
			'from_path' => $this->normalizePath($fromPath),
			'to_path' => $this->normalizePath($toPath),
		];
		return $this->rpcEndpointRequest('files/move_v2', $parameters);
		*/
	}
	
	/**
	 * The file should be uploaded in chunks if it size exceeds the 150 MB threshold
	 * or if the resource size could not be determined (eg. a popen() stream).
	 *
	 * @param string|resource $contents
	 *
	 * @return bool
	 */
	protected function shouldUploadChunked($contents) : bool
	{
		/*
		$size = is_string($contents) ? strlen($contents) : fstat($contents)['size'];
		if ($this->isPipe($contents)) {
			return true;
		}
		if ($size === null) {
			return true;
		}
		return $size > $this->maxChunkSize;
		*/
	}
	
	/**
	 * Check if the contents is a pipe stream (not seekable, no size defined).
	 *
	 * @param string|resource $contents
	 *
	 * @return bool
	 */
	protected function isPipe($contents) : bool
	{
		// return is_resource($contents) ? (fstat($contents)['mode'] & 010000) != 0 : false;
	}
	
	/**
	 * Create a new file with the contents provided in the request.
	 *
	 * Do not use this to upload a file larger than 150 MB. Instead, create an upload session with upload_session/start.
	 *
	 * @param string $path
	 * @param string|resource $contents
	 * @param string $mode
	 *
	 * @return array
	 */
	public function upload(string $path, $contents, $mode = 'add') : array
	{
		/*
		if ($this->shouldUploadChunked($contents)) {
			return $this->uploadChunked($path, $contents, $mode);
		}
		$arguments = [
			'path' => $this->normalizePath($path),
			'mode' => $mode,
		];
		$response = $this->contentEndpointRequest('files/upload', $arguments, $contents);
		$metadata = json_decode($response->getBody(), true);
		$metadata['.tag'] = 'file';
		return $metadata;
		*/
	}
	
	/**
	 * Upload file split in chunks. This allows uploading large files, since
	 *
	 * The chunk size will affect directly the memory usage, so be careful.
	 * Large chunks tends to speed up the upload, while smaller optimizes memory usage.
	 *
	 * @param string		  $path
	 * @param string|resource $contents
	 * @param string		  $mode
	 * @param int			  $chunkSize
	 *
	 * @return array
	 */
	public function uploadChunked(string $path, $contents, $mode = 'add', $chunkSize = null) : array
	{
		/*
		if ($chunkSize === null || $chunkSize > $this->maxChunkSize) {
			$chunkSize = $this->maxChunkSize;
		}
		$stream = Psr7\stream_for($contents);
		$cursor = $this->uploadChunk(self::UPLOAD_SESSION_START, $stream, $chunkSize, null);
		while (! $stream->eof()) {
			$cursor = $this->uploadChunk(self::UPLOAD_SESSION_APPEND, $stream, $chunkSize, $cursor);
		}
		return $this->uploadSessionFinish('', $cursor, $path, $mode);
		*/
	}
	
	/**
	 * @param int		  $type
	 * @param Psr7\Stream $stream
	 * @param int		  $chunkSize
	 * @param \DelaneyMethod\Sharepoint\UploadSessionCursor|null $cursor
	 * @return \DelaneyMethod\Sharepoint\UploadSessionCursor
	 * @throws Exception
	 */
	protected function uploadChunk($type, &$stream, $chunkSize, $cursor = null) : UploadSessionCursor
	{
		/*
		$maximumTries = $stream->isSeekable() ? $this->maxUploadChunkRetries : 0;
		$pos = $stream->tell();
		$tries = 0;
		tryUpload:
		try {
			$tries++;
			$chunkStream = new Psr7\LimitStream($stream, $chunkSize, $stream->tell());
			if ($type === self::UPLOAD_SESSION_START) {
				return $this->uploadSessionStart($chunkStream);
			}
			if ($type === self::UPLOAD_SESSION_APPEND && $cursor !== null) {
				return $this->uploadSessionAppend($chunkStream, $cursor);
			}
			throw new Exception('Invalid type');
		} catch (RequestException $exception) {
			if ($tries < $maximumTries) {
				// rewind
				$stream->seek($pos, SEEK_SET);
				goto tryUpload;
			}
			throw $exception;
		}
		*/
	}
	
	/**
	 * Upload sessions allow you to upload a single file in one or more requests,
	 * for example where the size of the file is greater than 150 MB.
	 * This call starts a new upload session with the given data.
	 *
	 * @param string|StreamInterface $contents
	 * @param bool	 $close
	 *
	 * @return UploadSessionCursor
	 */
	public function uploadSessionStart($contents, bool $close = false) : UploadSessionCursor
	{
		/*
		$arguments = compact('close');
		$response = json_decode(
			$this->contentEndpointRequest('files/upload_session/start', $arguments, $contents)->getBody(),
			true
		);
		return new UploadSessionCursor($response['session_id'], ($contents instanceof StreamInterface ? $contents->tell() : strlen($contents)));
		*/
	}
	
	/**
	 * Append more data to an upload session.
	 * When the parameter close is set, this call will close the session.
	 * A single request should not upload more than 150 MB.
	 *
	 * @param string|StreamInterface $contents
	 * @param UploadSessionCursor $cursor
	 * @param bool				  $close
	 *
	 * @return \DelaneyMethod\Sharepoint\UploadSessionCursor
	 */
	public function uploadSessionAppend($contents, UploadSessionCursor $cursor, bool $close = false) : UploadSessionCursor
	{
		/*
		$arguments = compact('cursor', 'close');
		$pos = $contents instanceof StreamInterface ? $contents->tell() : 0;
		$this->contentEndpointRequest('files/upload_session/append_v2', $arguments, $contents);
		$cursor->offset += $contents instanceof StreamInterface ? ($contents->tell() - $pos) : strlen($contents);
		return $cursor;
		*/
	}
	
	/**
	 * Finish an upload session and save the uploaded data to the given file path.
	 * A single request should not upload more than 150 MB.
	 *
	 * @param string|StreamInterface			  $contents
	 * @param \DelaneyMethod\Sharepoint\UploadSessionCursor $cursor
	 * @param string							  $path
	 * @param string|array						  $mode
	 * @param bool								  $autorename
	 * @param bool								  $mute
	 *
	 * @return array
	 */
	public function uploadSessionFinish($contents, UploadSessionCursor $cursor, string $path, $mode = 'add', $autorename = false, $mute = false) : array
	{
		/*
		$arguments = compact('cursor');
		$arguments['commit'] = compact('path', 'mode', 'autorename', 'mute');
		$response = $this->contentEndpointRequest(
			'files/upload_session/finish',
			$arguments,
			($contents == '') ? null : $contents
		);
		$metadata = json_decode($response->getBody(), true);
		$metadata['.tag'] = 'file';
		return $metadata;
		*/
	}
	
	protected function normalizePath(string $path) : string
	{
		if (preg_match("/^id:.*|^rev:.*|^(ns:[0-9]+(\/.*)?)/", $path) === 1) {
			return $path;
		}
		
		$path = trim($path, '/');
		
		return ($path === '') ? '' : '/'.$path;
	}
	
	/**
	 * @param string $endpoint
	 * @param array $arguments
	 * @param string|resource|StreamInterface $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \Exception
	 */
	public function contentEndpointRequest(string $endpoint, array $arguments, $body = '') : ResponseInterface
	{
		/*
		$headers = ['Dropbox-API-Arg' => json_encode($arguments)];
		if ($body !== '') {
			$headers['Content-Type'] = 'application/octet-stream';
		}
		try {
			$response = $this->client->post("https://content.dropboxapi.com/2/{$endpoint}", [
				'headers' => $headers,
				'body' => $body,
			]);
		} catch (ClientException $exception) {
			throw $this->determineException($exception);
		}
		return $response;
		*/
	}
	
	public function rpcEndpointRequest(string $endpoint, array $parameters = null) : array
	{
		/*
		try {
			$options = [];
			if ($parameters) {
				$options['json'] = $parameters;
			}
			$response = $this->client->post("https://api.dropboxapi.com/2/{$endpoint}", $options);
		} catch (ClientException $exception) {
			throw $this->determineException($exception);
		}
		$response = json_decode($response->getBody(), true);
		return $response ?? [];
		*/
	}
	
	protected function determineException(ClientException $exception) : Exception
	{
		/*
		if (in_array($exception->getResponse()->getStatusCode(), [400, 409])) {
			return new BadRequest($exception->getResponse());
		}
		return $exception;
		*/
	}
}
