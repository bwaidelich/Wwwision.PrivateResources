<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;

/**
 * A file serve strategy that uses the custom "X-Sendfile" header to let Apache servers handle the file download.
 *
 * Note: This needs the "mod_xsendfile" Apache module to be installed and configured, see https://tn123.org/mod_xsendfile/
 *
 * @Flow\Scope("singleton")
 */
class XSendfileStrategy implements FileServeStrategyInterface
{

    /**
     * @param string $filePathAndName Absolute path to the file to serve
     * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
     * @return void
     */
    public function serve($filePathAndName, HttpResponse $httpResponse)
    {
        $httpResponse->setHeader('X-Sendfile', $filePathAndName);
    }
}
