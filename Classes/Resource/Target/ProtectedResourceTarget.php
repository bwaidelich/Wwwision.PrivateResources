<?php
namespace Wwwision\PrivateResources\Resource\Target;

/*                                                                            *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".  *
 *                                                                            */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\Target\TargetInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Utility\Now;
use Neos\Flow\Http\Request as HttpRequest;

/**
 * A resource target that does not publish resources directly.
 * Instead it generates a token link that is bound to the current client.
 * The token can be consumed by the ProtectedResourceComponent HTTP component
 */
class ProtectedResourceTarget implements TargetInterface
{

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Name which identifies this publishing target
     *
     * @var string
     */
    protected $name;

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var Now
     */
    protected $now;

    /**
     * @var HttpRequest
     */
    protected $httpRequest;

    /**
     * @param string $name Name of this target instance, according to the resource settings
     * @param array $options Options for this target
     */
    public function __construct($name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Returns the name of this target instance
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param CollectionInterface $collection The collection to publish
     * @return void
     */
    public function publishCollection(CollectionInterface $collection)
    {
        // publishing is not required for protected resources
    }

    /**
     * @param PersistentResource $resource The resource to publish
     * @param CollectionInterface $collection The collection the given resource belongs to
     * @return void
     */
    public function publishResource(PersistentResource $resource, CollectionInterface $collection)
    {
        // publishing is not required for protected resources
    }

    /**
     * @param PersistentResource $resource The resource to unpublish
     * @return void
     */
    public function unpublishResource(PersistentResource $resource)
    {
        // publishing is not required for protected resources
    }

    /**
     * Returns the web accessible URI pointing to the given static resource
     *
     * @param string $relativePathAndFilename Relative path and filename of the static resource
     * @return string The URI
     */
    public function getPublicStaticResourceUri($relativePathAndFilename)
    {
        throw new \BadMethodCallException('Protected static resources are not supported yet', 1421241070);
    }

    /**
     * Returns the web accessible URI pointing to the specified persistent resource
     *
     * @param PersistentResource $resource Resource object
     * @return string The URI
     * @throws \Exception
     */
    public function getPublicPersistentResourceUri(PersistentResource $resource)
    {
        $resourceData = [
            'resourceIdentifier' => $resource->getSha1()
        ];
        if ($this->shouldIncludeSecurityContext()) {
            $resourceData['securityContextHash'] = $this->securityContext->getContextHash();
        } elseif (!empty($this->options['tokenLifetime'])) {
            $expirationDateTime = clone $this->now;
            $expirationDateTime = $expirationDateTime->modify(sprintf('+%d seconds', $this->options['tokenLifetime']));
            $resourceData['expirationDateTime'] = $expirationDateTime->format(\DateTime::ATOM);
        }
        $encodedResourceData = base64_encode(json_encode($resourceData));
        $signedResourceData = $this->hashService->appendHmac($encodedResourceData);
        return $this->detectResourcesBaseUri() . '?__protectedResource=' . $signedResourceData;
    }

    /**
     * @return boolean
     */
    protected function shouldIncludeSecurityContext()
    {
        if (!isset($this->options['whitelistRoles'])) {
            return true;
        }
        foreach ($this->options['whitelistRoles'] as $roleIdentifier) {
            if ($this->securityContext->hasRole($roleIdentifier)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Detects and returns the website's base URI
     *
     * @return string The website's base URI
     */
    protected function detectResourcesBaseUri()
    {
        $uri = '';
        $request = $this->getHttpRequest();
        if ($request instanceof HttpRequest) {
            $uri = $request->getBaseUri();
        }
        return (string)$uri;
    }

    /**
     * @return HttpRequest
     */
    protected function getHttpRequest()
    {
        if ($this->httpRequest === null) {
            $requestHandler = $this->bootstrap->getActiveRequestHandler();
            if (!$requestHandler instanceof HttpRequestHandlerInterface) {
                return null;
            }
            $this->httpRequest = $requestHandler->getHttpRequest();
        }
        return $this->httpRequest;
    }
}
