<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Wwwision\PrivateResources\Utility\ProtectedResourceUtility;

use function GuzzleHttp\Psr7\stream_for;

/**
 * A file serve strategy that streams the given resource
 *
 * @Flow\Scope("singleton")
 */
class StreamStrategy implements FileServeStrategyInterface
{

    /**
     * @param PersistentResource $resource Resource of the file to serve
     * @param HttpResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @param array $options
     * @return HttpResponseInterface
     */
    public function serve(PersistentResource $resource, HttpResponseInterface $httpResponse, array $options): HttpResponseInterface
    {
        $stream = $resource->getStream();

        /** @var HttpResponseInterface $response */
        $response = $httpResponse->withBody(stream_for($stream));
        return $response;
    }

}
