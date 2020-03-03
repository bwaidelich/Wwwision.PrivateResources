<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Wwwision\PrivateResources\Utility\ProtectedResourceUtility;

/**
 * A file serve strategy that uses the custom "X-accel-Redirect" header to let Nginx servers handle the file download.
 *
 * Note: This requires a properly configured nginx server,
 * see https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/
 *
 * @Flow\Scope("singleton")
 */
class XAccelRedirectStrategy implements FileServeStrategyInterface
{

    /**
     * @param PersistentResource $resource Resource of the file to serve
     * @param HttpResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @param array $options
     * @return HttpResponseInterface
     */
    public function serve(PersistentResource $resource, HttpResponseInterface $httpResponse, array $options): HttpResponseInterface
    {
        $filePathAndName = ProtectedResourceUtility::getStoragePathAndFilenameByHash($resource->getSha1(), $options['basePath']);

        /** @var HttpResponseInterface $response */
        $response = $httpResponse->withHeader('X-Accel-Redirect', $filePathAndName);
        return $response;
    }
}
