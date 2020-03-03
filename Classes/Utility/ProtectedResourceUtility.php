<?php
namespace Wwwision\PrivateResources\Utility;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Files;
use Wwwision\PrivateResources\Http\Component\Exception\FileNotFoundException;

/**
 * A HTTP Component that checks for the request argument "__protectedResource" and outputs the requested resource if the client tokens match
 */
class ProtectedResourceUtility
{

    /**
     *
     * @param string $sha1Hash
     * @param string $basePath
     * @return string
     * @throws FileNotFoundException
     */
    public static function getStoragePathAndFilenameByHash($sha1Hash, $basePath)
    {
        // TODO there should be a better way to determine the absolute path of the resource? Resource::createTemporaryLocalCopy() is too expensive
        $resourcePathAndFilename = Files::concatenatePaths(
            [
                $basePath,
                $sha1Hash[0],
                $sha1Hash[1],
                $sha1Hash[2],
                $sha1Hash[3],
                $sha1Hash
            ]
        );
        if (!is_file($resourcePathAndFilename)) {
            throw new FileNotFoundException(
                sprintf(
                    'File not found!%sThe file "%s" does not exist',
                    chr(10),
                    $resourcePathAndFilename
                ), 1429702284
            );
        }
        return $resourcePathAndFilename;
    }
}
