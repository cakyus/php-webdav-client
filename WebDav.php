<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 **/

/**
 *
 * References
 *
 *  - https://docs.nextcloud.com/server/12.0/developer_manual/client_apis/WebDAV/index.html
 **/

class WebDav {

	protected $_remoteUrl;

	protected $_httpResponseCode;
	protected $_httpResponseHeader;
	protected $_httpResponseText;

	protected $_httpRequestCookie;
	protected $_httpRequestAuthorization;

	public function __construct() {
		$this->_httpResponseHeader = array();
	}

	public function connect($location, $username, $password) {

		$header = array();
		$this->_httpRequestAuthorization = 'Basic '.base64_encode($username.':'.$password);

		$this->sendHttpRequest('HEAD', $location, null, $header);

		if ($this->_httpResponseCode != 200 ) {
			throw new \Exception('Unexpected response code. '.$this->_httpResponseCode);
		}

		$this->_remoteUrl = $location;
	}

	public function getFolderItemCollection($folderPath) {

		$itemCollection = array();
		$remotePath = parse_url($this->_remoteUrl, PHP_URL_PATH);
		$remotePath .= $folderPath;

		$location = $this->_remoteUrl.$folderPath;
		$this->sendHttpRequest('PROPFIND', $location);

		$doc = new \DOMDocument;
		@$doc->loadXML($this->_httpResponseText);
		$nodeList = $doc->getElementsByTagName('href');

		for ($i = 1; $i < $nodeList->length; $i++) {
			$nodePath = $nodeList->item($i)->textContent;
			if (substr($nodePath, 0, strlen($remotePath)) == $remotePath) {
				$nodePath = substr($nodePath, strlen($remotePath));
			}
			if ($nodePath == '') {
				continue;
			}
			$itemCollection[] = $nodePath;
		}

		return $itemCollection;
	}

	/**
	 * Copy a local file to the remote host
	 **/

	public function uploadFile($localFile, $remoteFile) {

		if (is_file($localFile) == false) {
			throw new \Exception("File not found. '$localFile'");
		}

		$httpRequestText = file_get_contents($localFile);
		$httpRequestUrl = $this->_remoteUrl.'/'.$remoteFile;

		$this->sendHttpRequest('PUT', $httpRequestUrl, $httpRequestText);
	}

	/**
	 * Delete file on remote host
	 **/

	public function deleteFile($remoteFile) {
		$httpRequestUrl = $this->_remoteUrl.'/'.$remoteFile;
		$this->sendHttpRequest('DELETE', $httpRequestUrl);
	}

	protected function sendHttpRequest($pMethod, $pUrl, $pData=null, $pHeader=null, $pOption=null) {

		$this->_httpResponseCode = null;
		$this->_httpResponseHeader = array();
		$this->_httpResponseText = null;

		$option = array(
			'http' => array(
				'method' => $pMethod,
			)
		);

		$header = array();
		$header[] = array('Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
		$header[] = array('Accept-Languange' => 'en-US,en;q=0.5');
		$header[] = array('Authorization' => $this->_httpRequestAuthorization);

		// additional header

		if (is_null($pHeader) == false) {
			foreach ($pHeader as $pHeaderItem) {
				$header[] = $pHeaderItem;
			}
		}

		// cookie

		if (is_null($this->_httpRequestCookie) == false) {
			$cookieItem = array();
			foreach ($this->_httpRequestCookie as $cookieName => $cookieValue) {
				$cookieItem[] = $cookieName.'='.$cookieValue;
			}
			$headerCookieValue = implode('; ', $cookieItem);
			$header[] = array('Cookie' => $headerCookieValue);
		}

		$header[] = array('Cache-Control' => 'no-cache');
		$header[] = array('Upgrade-Insecure-Requests' => '1');
		$header[] = array('Pragma' => 'no-cache');

		// post data

		if (is_null($pData) == false) {
			// assume binary data
			$header[] = array('Content-Type' => 'application/octet-stream');
			$header[] = array('Content-Length' => strlen($pData));
			$option['http']['content'] = $pData;
		}

		// http request header

		$headerLine = array();
		foreach ($header as $headerItem) {
			foreach ($headerItem as $headerName => $headerValue) {
				$headerLine[] = $headerName.': '.$headerValue;
			}
		}
		$headerText = implode("\r\n", $headerLine)."\r\n";

		$option['http']['header'] = $headerText;

		$context = stream_context_create($option);
		$responseText = file_get_contents($pUrl, false, $context);
		$this->_httpResponseText = $responseText;

		// response header

		foreach ($http_response_header as $responseHeader) {

			if (preg_match("/^([^:]+): (.+)$/", $responseHeader, $responseMatch) == false) {

				// HTTP/1.0 200 OK
				if (preg_match("/^HTTP\/[0-9]\.[0-9] ([0-9]+)/", $responseHeader, $codeMatch)) {
					$this->_httpResponseCode = (int) $codeMatch[1];
				}

				continue;
			}

			$headerName = $responseMatch[1];
			$headerValue = $responseMatch[2];

			$this->_httpResponseHeader[] = array($headerName => $headerValue);

			// Handle cookie
			if ($headerName == 'Set-Cookie') {
				if (preg_match("/^([^=]+)=([^;]+)/", $headerValue, $cookieMatch)) {
					$this->_httpRequestCookie[$cookieMatch[1]] = $cookieMatch[2];
				}
			}
		}

		return TRUE;
	}
}
