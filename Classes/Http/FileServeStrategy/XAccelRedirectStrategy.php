<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

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
     * @param string $filePathAndName Absolute path to the file to serve
     * @param HttpResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @return HttpResponseInterface
     */
    public function serve($filePathAndName, HttpResponseInterface $httpResponse): HttpResponseInterface
    {
        /** @var HttpResponseInterface $response */
        $response = $httpResponse->withHeader('X-Accel-Redirect', $filePathAndName);
        return $response;
    }
}
