<?php
namespace Wwwision\PrivateResources\Http\FileServeStrategy;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * Contract for a strategy that allows for serving files outside of the public folder structure
 */
interface FileServeStrategyInterface
{

    /**
     * @param PersistentResource $resource Resource of the file to serve
     * @param HttpResponse $httpResponse The current HTTP response (allows setting headers, ...)
     * @param array $options
     * @return void
     */
    public function serve(PersistentResource $resource, HttpResponse $httpResponse, $options);
}
