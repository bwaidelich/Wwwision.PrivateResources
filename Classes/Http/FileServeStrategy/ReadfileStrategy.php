<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;

/**
 * A file serve strategy that outputs the given file using PHPs readfile function
 *
 * @Flow\Scope("singleton")
 */
class ReadfileStrategy implements FileServeStrategyInterface
{

    /**
     * @param string $filePathAndName Absolute path to the file to serve
     * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
     * @return void
     */
    public function serve($filePathAndName, HttpResponse $httpResponse)
    {
        $httpResponse->sendHeaders();
        // Ensure no output buffer is used so the file contents won't be loaded into the RAM
        // BTW: This does not work with xdebug enabled! (any output will be buffered by xdebug)
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        readfile($filePathAndName);
        exit;
    }
}
