<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use GuzzleHttp\Psr7\Utils;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

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

        return $httpResponse->withBody(Utils::streamFor($stream));
    }

}
