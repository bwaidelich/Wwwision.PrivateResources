<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the TYPO3 Flow package "Wwwision.PrivateResources".  *
 *                                                                             */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Response as HttpResponse;

/**
 * A file serve strategy that uses the custom "X-accel-Redirect" header to let Nginx servers handle the file download.
 *
 * Note: This requires a properly configured Nginx server, see http://wiki.nginx.org/X-accel
 *
 * @Flow\Scope("singleton")
 */
class XAccelRedirectStrategy implements FileServeStrategyInterface {

	/**
	 * @param string $filePathAndName Absolute path to the file to serve
	 * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
	 * @return void
	 */
	public function serve($filePathAndName, HttpResponse $httpResponse) {
		$httpResponse->setHeader('X-Accel-Redirect', $filePathAndName);
	}

}