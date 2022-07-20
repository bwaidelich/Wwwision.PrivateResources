Wwwision.PrivateResources
=========================

This is a Flow package that allows for protecting persistent resources from unauthorized access.

By default Flow publishes persistent resources to the ``_Resources/Persistent`` folder inside the web root making them
accessible to browsers and other clients (unless using a different ``PublishingTarget``, see below).
That means that even files served from a protected area of your web application will be accessible for anyone knowing
the internal filename of that resource. This is not a big deal usually, because the filename is determined by a hash
over the actual file *contents* - so if you know the hash, you most probably know the file content anyways.
But in some cases you need more control over the served files or want to prevent direct links to files being shared.
In these cases this package might be of help:

It provides a new *Resource Publishing Target*, named ``ProtectedResourceTarget`` that, in contrast to other targets,
won't copy file contents to a public directory (or CDN) upon publishing. Instead it will return a signed URL that will
only work for users with *the same privileges as the current user*.
That means, if an image is rendered to an authenticated user, the image URL will only resolve for users with *the same
roles*, for other users it will return an HTTP status of ``403 Forbidden``.

Disclaimer:
-----------

With this package user's can't easily share URLs to protected resources as they will only work for users with the same
roles. However, resources will still be downloaded obviously and users can share them otherwise.
Furthermore serving private resources consumes more time and memory because every hit triggers a PHP request.
Conclusion: This package is only useful in very rare cases ;)

Version:
--------

The table below provides an overview of the available versions.

Note: The releases version 3.4.0 is not compatible with Flow < 6.

| Branch / Release   | Supported Flow version |
| ------------------ | ---------------------- |
| 3.x, 4.x           | 4.1, 5.x               |
| 3.4, 5.x           | 6.x                    |
| 6.x, master        | 7.x, 8.x               |

How-To:
-------

* Install the package to ``Packages/Application/Wwwision.PrivateResources`` (e.g. via ``composer require wwwision/privateresources``)
* Configure it (see below)
* Done

Concept:
--------

This package provides a custom [Resource Publishing Target](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/ResourceManagement.html#target)
that prevents resources from being published to the accessible file system. Instead it generates a token link that, when requested, invokes a
[HTTP Middleware](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Http.html#middlewares-chain) that in turn serves the
corresponding resource if the current user is allowed to access it.

Configuration:
--------------

### Publishing Target ###

First of all, you'll have to activate the ``ProtectedResourceTarget`` in your ``Settings.yaml``.
You can either create a new *resource collection*:

```yaml
Neos:
  Flow:
    resource:
      collections:
        'protectedResources':
          storage: 'defaultPersistentResourcesStorage'
          target: 'protectedResourcesTarget'
```

You then can use this feature by uploading resources to the "protectedResources" collection, e.g. with help of Fluid
and the ``UploadViewHelper``:

```html
<f:form.upload name="file" collection="protectedResources" />
```

If you want to enable this feature *globally* for persistent resources, just override the target of the existing
"persistent" collection:

```yaml
Neos:
  Flow:
    resource:
      collections:
        'persistent':
          target: 'protectedResourcesTarget'
```

**NOTE:** Serving protected resources will have a negative effect on performance and memory consumption - only activate
this globally, if you really want to protect *all* persistent resources.

#### Token Lifetime ####

By default a token never expires.
You can change that with the ``tokenLifetime`` option:

```yaml
Neos:
  Flow:
    resource:
      targets:
        'protectedResourcesTarget':
          targetOptions:
            tokenLifetime: 86400
```

With this configuration, tokens will expire after 86400 seconds (= one day).

**NOTE:** This option is only considered for "whitelisted" tokens that are not bound to a role or security context hash

**NOTE:** If the publishing of the resource is cached, this might lead to broken resources (e.g. if you use this within
Neos CMS within a Node Type with a cache lifetime larger than the tokenLifetime).

#### Whitelist Roles ####

Sometimes you want to prevent resources from being protected if a certain role is authenticated. With the ``whitelistRoles``
option you can disable the token enforcement for individual roles (The token will still be generated but it won't be
verified any longer if one of the specified roles is authenticated):

```yaml
Neos:
  Flow:
    resource:
      targets:
        'protectedResourcesTarget':
          targetOptions:
            whitelistRoles: ['Your.Package:SomeRole']
```

By default, the ``Neos.Neos:Editor`` role is whitelisted, so that this package can be used within Neos CMS without
caching issues.

#### Privileged Role ####

If no whitelisted role is active (see above) the token is bound to the Flow Security Context. That means that only
requests that have the same Security Context Hash are privileged to access the resource.
The Security Context Hash is bound to the currently authenticated roles and the [Global AOP Objects](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Security.html#content-security-entityprivilege).

Alternatively a role can be configured that should always have access to all resources of a given Resource Target:

```yaml
Neos:
  Flow:
    resource:
      targets:
        'protectedResourcesTarget':
          targetOptions:
            privilegedRole: 'Your.Package:SomeRole'
```

**NOTE:** With this option it's allowed to create the resource URLs asynchronous and/or via CLI because it doesn't require an initialized Security Context

### HTTP Middleware ###

The actual serving of protected files is done using a ``HTTP Middleware`` that will be triggered even before the regular
routing kicks in.
This ``ProtectedResourceMiddleware`` is already configured and if it comes across an HTTP requests with an
"__protectedResource" argument it will validate the hash and output the requested file, if valid.

By default it uses PHPs ``readfile()`` function to stream a local file from its inaccessible location to the client, but
this has some drawbacks because it has to pipe the whole file through the PHP process consuming a lot of memory,
especially for larger files.

To support external cloud storage use the `StreamStrategy` described further below.

To improve performance and memory footprint you can therefore configure the middleware to use different strategies to
serve the file:

#### X-Sendfile (Apache) ####

mod_xsendfile is a small **Apache2** module that processes X-SENDFILE headers registered by the original output handler (see
https://tn123.org/mod_xsendfile/).

Assuming you have the Apache module installed and configured to access files within the ``Data/Persistent/Resources``
directory of your installation, you can activate the ``XSendfileStrategy`` with the following settings:

```yaml
Wwwision:
  PrivateResources:
    middleware:
      serveStrategy: 'Wwwision\PrivateResources\Http\FileServeStrategy\XSendfileStrategy'
```

Instead of using ``readfile()`` to serve the file, the HTTP Middleware will then send an `X-Sendfile` header pointing to
the internal file, letting Apache take care of the download.

#### X-Accel-Redirect (Nginx) ####

Similar to the ``X-Sendfile`` mechanism, the ``X-Accel-Redirect`` allows for internal redirection to a location in
**Nginx** environments (see http://wiki.nginx.org/X-accel).

It can be activated with:

```yaml
Wwwision:
  PrivateResources:
    middleware:
      serveStrategy: 'Wwwision\PrivateResources\Http\FileServeStrategy\XAccelRedirectStrategy'
```

#### Stream ####

This strategy uses the read-only stream offered by the ResourceManager. It should be compatible with all
StorageInterfaces and supports external storage (e.g. AWS S3, Google Cloud Storage GCS). But this has some
drawbacks because it has to pipe the whole file through the PHP process consuming a lot of memory and CPU time.

It can be activated with:

```yaml
Wwwision:
  PrivateResources:
    middleware:
      serveStrategy: 'Wwwision\PrivateResources\Http\FileServeStrategy\StreamStrategy'
```

#### Custom strategy ####

You can create your own strategy for serving files, implementing the ``FileServeStrategyInterface``.
With this you could for example realize protected CDN resources.

Signals
-------

The HTTP Middleware triggers a signal whenever a protected resource is being accessed (see Flow documentation regarding
more details about **Signals and Slots**).
You can use that signal to count file downloads for example:

```php
/**
 * @param Bootstrap $bootstrap The current bootstrap
 * @return void
 */
public function boot(Bootstrap $bootstrap) {
	$dispatcher = $bootstrap->getSignalSlotDispatcher();
	$dispatcher->connect(ProtectedResourceMiddleware::class, 'resourceServed', function(Resource $resource, HttpRequest $httpRequest) {
		// increase counter for the given $resource
	});
}
```

The following signals are emitted:

* `ProtectedResourceMiddleware:resourceServed(PersistentResource $resource, HttpRequest $httpRequest)` whenever a private resource is served
* `ProtectedResourceMiddleware:accessDenied(array $tokenData, HttpRequest $httpRequest)`

The following signal is still emitted for backwards compatibility, but is deprecated in favor of `accessDenied`:
* `ProtectedResourceMiddleware:invalidSecurityContextHash(array $tokenData, HttpRequest $httpRequest)`

Known issues and limitations
----------------------------

* This package works well with [Neos CMS](https://www.neos.io), but Neos currently doesn't offer a way to select a *resource collection*
  when uploading files or working with the Media Module. You can, however, activate protected resources globally (see
  above) or create custom editors for your protected file uploads
* Private resources currently only work for **persistent** resources. **Static** resources are not yet covered
