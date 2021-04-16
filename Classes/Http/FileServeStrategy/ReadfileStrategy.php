<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use GuzzleHttp\Psr7\Utils;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Wwwision\PrivateResources\Http\Middleware\Exception\FileNotFoundException;
use Wwwision\PrivateResources\Utility\ProtectedResourceUtility;

/**
 * A file serve strategy that outputs the given file using PHPs readfile function
 *
 * @Flow\Scope("singleton")
 */
class ReadfileStrategy implements FileServeStrategyInterface
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

        return $httpResponse->withBody(Utils::streamFor(fopen($filePathAndName, 'rb')));
    }
}
