<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the TYPO3 Flow package "Wwwision.PrivateResources".  *
 *                                                                             */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Response as HttpResponse;

/**
 * A file serve strategy that outputs the given file using PHPs readfile function
 *
 * Note: This mechanism is discouraged for large files because it consumes a lot of memory
 *
 * @Flow\Scope("singleton")
 */
class ReadfileStrategy implements FileServeStrategyInterface {

	/**
	 * @param string $filePathAndName Absolute path to the file to serve
	 * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
	 * @return void
	 */
	public function serve($filePathAndName, HttpResponse $httpResponse) {
		$httpResponse->sendHeaders();
		readfile($filePathAndName);
		exit;
	}

}