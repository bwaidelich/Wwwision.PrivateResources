<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

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
     * @param HttpResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @return void
     */
    public function serve($filePathAndName, HttpResponseInterface $httpResponse)
    {
        $httpResponse->withHeader('X-Sendfile', $filePathAndName);
    }
}
