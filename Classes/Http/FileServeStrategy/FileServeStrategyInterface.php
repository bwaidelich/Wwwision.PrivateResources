<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Http\Response as HttpResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Contract for a strategy that allows for serving files outside of the public folder structure
 */
interface FileServeStrategyInterface
{

    /**
     * @param string $filePathAndName Absolute path to the file to serve
     * @param ResponseInterface $httpResponse The current HTTP response (allows setting headers, ...)
     * @return ResponseInterface
     */
    public function serve($filePathAndName, ResponseInterface $httpResponse): ResponseInterface;
}
