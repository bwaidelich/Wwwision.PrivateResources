<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

/**
 * A file serve strategy that outputs the given file using PHPs readfile function
 *
 * @Flow\Scope("singleton")
 */
class ReadfileStrategy implements FileServeStrategyInterface
{

    /**
     * @param string $filePathAndName Absolute path to the file to serve
     * @param HttpResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @return HttpResponseInterface
     */
    public function serve($filePathAndName, HttpResponseInterface $httpResponse): HttpResponseInterface
    {
        /** @var HttpResponseInterface $response */
        $response = $httpResponse->withBody(stream_for($filePathAndName));
        return $response;
    }
}
