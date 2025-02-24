<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Action;

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiAction as OpenApiSwaggerUiAction;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment as TwigEnvironment;

/**
 * Displays the documentation.
 *
 * @deprecated please refer to ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiAction for further changes
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SwaggerUiAction
{
    private $resourceNameCollectionFactory;
    /** @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface */
    private $resourceMetadataFactory;
    private $normalizer;
    private $twig;
    private $urlGenerator;
    private $title;
    private $description;
    private $version;
    private $showWebby;
    private $formats;
    private $oauthEnabled;
    private $oauthClientId;
    private $oauthClientSecret;
    private $oauthType;
    private $oauthFlow;
    private $oauthTokenUrl;
    private $oauthAuthorizationUrl;
    private $oauthScopes;
    private $formatsProvider;
    private $swaggerUiEnabled;
    private $reDocEnabled;
    private $graphqlEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;
    private $swaggerVersions;
    private $swaggerUiAction;
    private $assetPackage;
    private $swaggerUiExtraConfiguration;

    /**
     * @param int[]      $swaggerVersions
     * @param mixed|null $assetPackage
     * @param mixed      $formats
     * @param mixed      $oauthEnabled
     * @param mixed      $oauthClientId
     * @param mixed      $oauthClientSecret
     * @param mixed      $oauthType
     * @param mixed      $oauthFlow
     * @param mixed      $oauthTokenUrl
     * @param mixed      $oauthAuthorizationUrl
     * @param mixed      $oauthScopes
     * @param mixed      $resourceMetadataFactory
     */
    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory, NormalizerInterface $normalizer, ?TwigEnvironment $twig, UrlGeneratorInterface $urlGenerator, string $title = '', string $description = '', string $version = '', $formats = [], $oauthEnabled = false, $oauthClientId = '', $oauthClientSecret = '', $oauthType = '', $oauthFlow = '', $oauthTokenUrl = '', $oauthAuthorizationUrl = '', $oauthScopes = [], bool $showWebby = true, bool $swaggerUiEnabled = false, bool $reDocEnabled = false, bool $graphqlEnabled = false, bool $graphiQlEnabled = false, bool $graphQlPlaygroundEnabled = false, array $swaggerVersions = [2, 3], OpenApiSwaggerUiAction $swaggerUiAction = null, $assetPackage = null, array $swaggerUiExtraConfiguration = [])
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->normalizer = $normalizer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->title = $title;
        $this->showWebby = $showWebby;
        $this->description = $description;
        $this->version = $version;
        $this->oauthEnabled = $oauthEnabled;
        $this->oauthClientId = $oauthClientId;
        $this->oauthClientSecret = $oauthClientSecret;
        $this->oauthType = $oauthType;
        $this->oauthFlow = $oauthFlow;
        $this->oauthTokenUrl = $oauthTokenUrl;
        $this->oauthAuthorizationUrl = $oauthAuthorizationUrl;
        $this->oauthScopes = $oauthScopes;
        $this->swaggerUiEnabled = $swaggerUiEnabled;
        $this->reDocEnabled = $reDocEnabled;
        $this->graphqlEnabled = $graphqlEnabled;
        $this->graphiQlEnabled = $graphiQlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->swaggerVersions = $swaggerVersions;
        $this->swaggerUiAction = $swaggerUiAction;
        $this->swaggerUiExtraConfiguration = $swaggerUiExtraConfiguration;
        $this->assetPackage = $assetPackage;

        if (null === $this->twig) {
            throw new \RuntimeException('The documentation cannot be displayed since the Twig bundle is not installed. Try running "composer require symfony/twig-bundle".');
        }

        if (null === $this->swaggerUiAction) {
            @trigger_error(sprintf('The use of "%s" is deprecated since API Platform 2.6, use "%s" instead.', __CLASS__, OpenApiSwaggerUiAction::class), \E_USER_DEPRECATED);
        }

        if (\is_array($formats)) {
            $this->formats = $formats;

            return;
        }

        @trigger_error(sprintf('Passing an array or an instance of "%s" as 5th parameter of the constructor of "%s" is deprecated since API Platform 2.5, pass an array instead', FormatsProviderInterface::class, __CLASS__), \E_USER_DEPRECATED);
        $this->formatsProvider = $formats;
    }

    public function __invoke(Request $request)
    {
        if ($this->swaggerUiAction) {
            return $this->swaggerUiAction->__invoke($request);
        }

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        // BC check to be removed in 3.0
        if (null === $this->formatsProvider) {
            $formats = $attributes ? $this
                ->resourceMetadataFactory
                ->create($attributes['resource_class'])
                ->getOperationAttribute($attributes, 'output_formats', [], true) : $this->formats;
        } else {
            $formats = $this->formatsProvider->getFormatsFromAttributes($attributes);
        }

        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version);

        return new Response($this->twig->render('@ApiPlatform/SwaggerUi/index.html.twig', $this->getContext($request, $documentation) + ['formats' => $formats]));
    }

    /**
     * Gets the base Twig context.
     */
    private function getContext(Request $request, Documentation $documentation): array
    {
        $context = [
            'title' => $this->title,
            'description' => $this->description,
            'showWebby' => $this->showWebby,
            'swaggerUiEnabled' => $this->swaggerUiEnabled,
            'reDocEnabled' => $this->reDocEnabled,
            'graphqlEnabled' => $this->graphqlEnabled,
            'graphiQlEnabled' => $this->graphiQlEnabled,
            'graphQlPlaygroundEnabled' => $this->graphQlPlaygroundEnabled,
            'assetPackage' => $this->assetPackage,
        ];

        $swaggerContext = ['spec_version' => $request->query->getInt('spec_version', $this->swaggerVersions[0] ?? 2)];
        if ('' !== $baseUrl = $request->getBaseUrl()) {
            $swaggerContext['base_url'] = $baseUrl;
        }

        $swaggerData = [
            'url' => $this->urlGenerator->generate('api_doc', ['format' => 'json']),
            'spec' => $this->normalizer->normalize($documentation, 'json', $swaggerContext),
            'extraConfiguration' => $this->swaggerUiExtraConfiguration,
        ];

        $swaggerData['oauth'] = [
            'enabled' => $this->oauthEnabled,
            'clientId' => $this->oauthClientId,
            'clientSecret' => $this->oauthClientSecret,
            'type' => $this->oauthType,
            'flow' => $this->oauthFlow,
            'tokenUrl' => $this->oauthTokenUrl,
            'authorizationUrl' => $this->oauthAuthorizationUrl,
            'scopes' => $this->oauthScopes,
        ];

        if ($request->isMethodSafe() && null !== $resourceClass = $request->attributes->get('_api_resource_class')) {
            $swaggerData['id'] = $request->attributes->get('id');
            $swaggerData['queryParameters'] = $request->query->all();

            $metadata = $this->resourceMetadataFactory->create($resourceClass);
            $swaggerData['shortName'] = $metadata instanceof ResourceMetadata ? $metadata->getShortName() : $metadata[0]->getShortName();

            if (null !== $collectionOperationName = $request->attributes->get('_api_collection_operation_name')) {
                $swaggerData['operationId'] = sprintf('%s%sCollection', $collectionOperationName, ucfirst($swaggerData['shortName']));
            } elseif (null !== $itemOperationName = $request->attributes->get('_api_item_operation_name')) {
                $swaggerData['operationId'] = sprintf('%s%sItem', $itemOperationName, ucfirst($swaggerData['shortName']));
            } elseif (null !== $subresourceOperationContext = $request->attributes->get('_api_subresource_context')) {
                $swaggerData['operationId'] = $subresourceOperationContext['operationId'];
            }

            [$swaggerData['path'], $swaggerData['method']] = $this->getPathAndMethod($swaggerData);
        }

        return $context + ['swagger_data' => $swaggerData];
    }

    private function getPathAndMethod(array $swaggerData): array
    {
        foreach ($swaggerData['spec']['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if ($operation['operationId'] === $swaggerData['operationId']) {
                    return [$path, $method];
                }
            }
        }

        throw new RuntimeException(sprintf('The operation "%s" cannot be found in the Swagger specification.', $swaggerData['operationId']));
    }
}
