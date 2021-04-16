<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Wwwision\PrivateResources\Http\Middleware\Exception\FileNotFoundException;
use Wwwision\PrivateResources\Utility\ProtectedResourceUtility;

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
     * @param PersistentResource $resource Resource of the file to serve
     * @param HttpResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @param array $options
     * @return HttpResponseInterface
     * @throws FileNotFoundException
     */
    public function serve(PersistentResource $resource, HttpResponseInterface $httpResponse, array $options): HttpResponseInterface
    {
        $filePathAndName = ProtectedResourceUtility::getStoragePathAndFilenameByHash($resource->getSha1(), $options['basePath']);
        return $httpResponse->withHeader('X-Sendfile', $filePathAndName);
    }
}
