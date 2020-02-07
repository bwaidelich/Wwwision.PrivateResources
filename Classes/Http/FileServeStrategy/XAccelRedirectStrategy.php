<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\ResourceManagement\PersistentResource;
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
     * @param PersistentResource $resource Absolute path to the file to serve
     * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
     * @return void
     */
    public function serve(PersistentResource $resource, HttpResponse $httpResponse, $options)
    {
        $filePathAndName = ProtectedResourceUtility::getStoragePathAndFilenameByHash($resource->getSha1(), $options['basePath']);
        $httpResponse->setHeader('X-Accel-Redirect', $filePathAndName);
    }
}
