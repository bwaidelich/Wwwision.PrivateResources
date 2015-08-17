<?php
namespace Wwwision\PrivateResources\Resource\Target;

/*                                                                             *
 * This script belongs to the TYPO3 Flow package "Wwwision.PrivateResources".  *
 *                                                                             */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Http\HttpRequestHandlerInterface;
use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Resource\Collection;
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\Target\Exception;
use TYPO3\Flow\Resource\Target\TargetInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Utility\Now;

/**
 * A resource target that does not publish resources directly.
 * Instead it generates a token link that is bound to the current client.
 * The token can be consumed by the ProtectedResourceComponent HTTP component
 */
class ProtectedResourceTarget implements TargetInterface {

	/**
	 * @var array
	 */
	protected $options = array();

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
	public function __construct($name, array $options = array()) {
		$this->name = $name;
		$this->options = $options;
	}

	/**
	 * Returns the name of this target instance
	 *
	 * @return string
	 */
	public function getName() {
		return $this->getName();
	}

	/**
	 * @param Collection $collection The collection to publish
	 * @return void
	 */
	public function publishCollection(Collection $collection) {
		// publishing is not required for protected resources
	}

	/**
	 * @param Resource $resource The resource to publish
	 * @param CollectionInterface $collection The collection the given resource belongs to
	 * @return void
	 */
	public function publishResource(Resource $resource, CollectionInterface $collection) {
		// publishing is not required for protected resources
	}

	/**
	 * @param Resource $resource The resource to unpublish
	 * @return void
	 */
	public function unpublishResource(Resource $resource) {
		// publishing is not required for protected resources
	}

	/**
	 * Returns the web accessible URI pointing to the given static resource
	 *
	 * @param string $relativePathAndFilename Relative path and filename of the static resource
	 * @return string The URI
	 */
	public function getPublicStaticResourceUri($relativePathAndFilename) {
		throw new \BadMethodCallException('Protected static resources are not supported yet', 1421241070);
	}

	/**
	 * Returns the web accessible URI pointing to the specified persistent resource
	 *
	 * @param Resource $resource Resource object
	 * @return string The URI
	 * @throws Exception
	 */
	public function getPublicPersistentResourceUri(Resource $resource) {
		$resourceData = array(
			'securityContextHash' => $this->securityContext->getContextHash(),
			'resourceIdentifier' => $resource->getSha1(),
		);
		if (!empty($this->options['tokenLifetime'])) {
			$expirationDateTime = clone $this->now;
			$expirationDateTime = $expirationDateTime->modify(sprintf('+%d seconds', $this->options['tokenLifetime']));
			$resourceData['expirationDateTime'] = $expirationDateTime->format(\DateTime::ISO8601);
		}
		$encodedResourceData = base64_encode(json_encode($resourceData));
		$signedResourceData = $this->hashService->appendHmac($encodedResourceData);
		return $this->detectResourcesBaseUri() . '?__protectedResource=' . $signedResourceData;
	}

	/**
	 * Detects and returns the website's base URI
	 *
	 * @return string The website's base URI
	 */
	protected function detectResourcesBaseUri() {
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
	protected function getHttpRequest() {
		if ($this->httpRequest === NULL) {
			$requestHandler = $this->bootstrap->getActiveRequestHandler();
			if (!$requestHandler instanceof HttpRequestHandlerInterface) {
				return NULL;
			}
			$this->httpRequest = $requestHandler->getHttpRequest();
		}
		return $this->httpRequest;
	}

}