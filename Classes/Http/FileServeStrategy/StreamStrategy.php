<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * A file serve strategy that streams the given resource
 *
 * @Flow\Scope("singleton")
 */
class StreamStrategy implements FileServeStrategyInterface
{

    /**
     * @param PersistentResource $resource Absolute path to the file to serve
     * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
     * @return void
     */
    public function serve(PersistentResource $resource, HttpResponse $httpResponse, $options)
    {
        $httpResponse->sendHeaders();
        // Ensure no output buffer is used so the file contents won't be loaded into the RAM
        // BTW: This does not work with xdebug enabled! (any output will be buffered by xdebug)
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        $stream = $resource->getStream();
        while ($buffer = fread($stream, 4096)) {
            echo $buffer;
        }
        fclose($stream);
        exit;
    }
}
