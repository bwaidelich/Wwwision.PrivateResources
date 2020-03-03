<?php
namespace Wwwision\PrivateResources\Http\Component;

/*                                                                             *
 * This script belongs to the Neos Flow package "Wwwision.PrivateResources".   *
 *                                                                             */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception as SecurityException;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\InvalidHashException;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Utility\Now;
use Neos\Utility\Files;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;
use Wwwision\PrivateResources\Http\Component\Exception\FileNotFoundException;
use Wwwision\PrivateResources\Http\FileServeStrategy\FileServeStrategyInterface;
use Neos\Flow\Http\Component\ComponentChain;

/**
 * A HTTP Component that checks for the request argument "__protectedResource" and outputs the requested resource if the client tokens match
 */
class ProtectedResourceComponent implements ComponentInterface
{

    /**
     * @Flow\Inject
     * @var HashService
     */
    protected $hashService;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param ComponentContext $componentContext
     * @return void
     * @throws FileNotFoundException|AccessDeniedException|FlowException
     */
    public function handle(ComponentContext $componentContext)
    {
        /** @var HttpRequestInterface $httpRequest */
        $httpRequest = $componentContext->getHttpRequest();
        $queryParams = $httpRequest->getQueryParams();

        if (!array_key_exists('__protectedResource', $queryParams) || $queryParams['__protectedResource'] === '' || $queryParams['__protectedResource'] === false) {
            return;
        }
        try {
            $encodedResourceData = $this->hashService->validateAndStripHmac($queryParams['__protectedResource']);
        } catch (InvalidHashException $exception) {
            throw new AccessDeniedException('Invalid HMAC!', 1421241393, $exception);
        }
        $tokenData = json_decode(base64_decode($encodedResourceData), true);

        $this->verifyExpiration($tokenData, $httpRequest);
        $this->verifySecurityContext($tokenData, $httpRequest);

        $resource = $this->resourceManager->getResourceBySha1($tokenData['resourceIdentifier']);
        if ($resource === null) {
            throw new FileNotFoundException(sprintf('Unknown resource!%sCould not find resource with identifier "%s"',
                chr(10), $tokenData['resourceIdentifier']), 1429621743);
        }

        if (!isset($this->options['serveStrategy'])) {
            throw new FlowException('No "serveStrategy" configured!', 1429704107);
        }
        $fileServeStrategy = $this->objectManager->get($this->options['serveStrategy']);
        if (!$fileServeStrategy instanceof FileServeStrategyInterface) {
            throw new FlowException(sprintf('The class "%s" does not implement the FileServeStrategyInterface',
                get_class($fileServeStrategy)), 1429704284);
        }
        /** @var ResponseInterface $httpResponse */
        $httpResponse = $componentContext->getHttpResponse()
            ->withHeader('Content-Type', $resource->getMediaType())
            ->withHeader('Content-Disposition', 'attachment;filename="' . $resource->getFilename() . '"')
            ->withHeader('Content-Length', $resource->getFileSize());

        $this->emitResourceServed($resource, $httpRequest);

        $httpResponse = $fileServeStrategy->serve($resource, $httpResponse, $this->options);
        $componentContext->replaceHttpResponse($httpResponse);
        $componentContext->setParameter(ComponentChain::class, 'cancel', true);
    }

    /**
     * Checks whether the token is expired
     *
     * @param array $tokenData
     * @param HttpRequestInterface $httpRequest
     * @return void
     * @throws AccessDeniedException
     */
    protected function verifyExpiration(array $tokenData, HttpRequestInterface $httpRequest)
    {
        if (!isset($tokenData['expirationDateTime'])) {
            return;
        }
        $expirationDateTime = \DateTime::createFromFormat(\DateTime::ATOM, $tokenData['expirationDateTime']);
        if ($this->now instanceof DependencyProxy) {
            $this->now->_activateDependency();
        }
        if ($expirationDateTime < $this->now) {
            $this->emitAccessDenied($tokenData, $httpRequest);
            throw new AccessDeniedException(sprintf('Token expired!%sThis token expired at "%s"', chr(10), $expirationDateTime->format(\DateTime::ATOM)), 1429697439);
        }
    }

    /**
     * Checks whether the currently authenticated user is allowed to access the resource
     *
     * @param array $tokenData
     * @param HttpRequestInterface $httpRequest
     * @return void
     * @throws AccessDeniedException | SecurityException | NoSuchRoleException
     */
    protected function verifySecurityContext(array $tokenData, HttpRequestInterface $httpRequest)
    {
        if (!isset($tokenData['securityContextHash']) && !isset($tokenData['privilegedRole'])) {
            return;
        }
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $this->securityContext->setRequest($actionRequest);
        if (isset($tokenData['privilegedRole'])) {
            if ($this->securityContext->hasRole($tokenData['privilegedRole'])) {
                return;
            }
            $authenticatedRoleIdentifiers = array_map(static function(Role $role) { return $role->getIdentifier(); }, $this->securityContext->getRoles());
            $this->emitAccessDenied($tokenData, $httpRequest);
            throw new AccessDeniedException(sprintf('Access denied!%sThis request is signed for a role "%s", but only the following roles are authenticated: %s', chr(10), $tokenData['privilegedRole'], implode(', ', $authenticatedRoleIdentifiers)), 1565856716);
        }

        if ($tokenData['securityContextHash'] !== $this->securityContext->getContextHash()) {
            $this->emitAccessDenied($tokenData, $httpRequest);
            $this->emitInvalidSecurityContextHash($tokenData, $httpRequest);
            throw new AccessDeniedException(sprintf('Invalid security hash!%sThis request is signed for a security context hash of "%s", but the current hash is "%s"', chr(10), $tokenData['securityContextHash'], $this->securityContext->getContextHash()), 1429705633);
        }
    }

    /**
     * Signals that all persistAll() has been executed successfully.
     *
     * @Flow\Signal
     * @param PersistentResource $resource the resource that has been served
     * @param HttpRequestInterface $httpRequest the current HTTP request
     * @return void
     */
    protected function emitResourceServed(PersistentResource $resource, HttpRequestInterface $httpRequest)
    {
    }

    /**
     * Signals that the token verification failed
     *
     * @Flow\Signal
     * @param array $tokenData the token data
     * @param HttpRequestInterface $httpRequest the current HTTP request
     * @return void
     */
    protected function emitAccessDenied(array $tokenData, HttpRequestInterface $httpRequest)
    {
    }

    /**
     * Signals that the security context hash verification failed
     *
     * @Flow\Signal
     * @deprecated use "accessDenied" signal instead
     * @param array $tokenData the token data
     * @param HttpRequestInterface $httpRequest the current HTTP request
     * @return void
     */
     protected function emitInvalidSecurityContextHash(array $tokenData, HttpRequestInterface $httpRequest)
     {
     }
}
