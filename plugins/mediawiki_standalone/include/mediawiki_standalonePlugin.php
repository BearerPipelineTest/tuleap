<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\MediawikiStandalone\Configuration\GenerateLocalSettingsCommand;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsFactory;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsInstantiator;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsPersistToPHPFile;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiAsyncUpdateProcessor;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandProcessFactory;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiNewOAuth2AppBuilder;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiOAuth2AppSecretGeneratorDBStore;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiSharedSecretGeneratorForgeConfigStore;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiInstallAndUpdateScriptCaller;
use Tuleap\MediawikiStandalone\Configuration\MustachePHPString\PHPStringMustacheRenderer;
use Tuleap\MediawikiStandalone\Configuration\ProjectMediaWikiServiceDAO;
use Tuleap\MediawikiStandalone\Configuration\UpdateMediaWikiTask;
use Tuleap\MediawikiStandalone\Instance\InstanceManagement;
use Tuleap\MediawikiStandalone\Instance\LogUsersOutInstanceTask;
use Tuleap\MediawikiStandalone\Instance\MediawikiHTTPClientFactory;
use Tuleap\MediawikiStandalone\Instance\ProjectRenameHandler;
use Tuleap\MediawikiStandalone\Instance\ProjectStatusHandler;
use Tuleap\MediawikiStandalone\OAuth2\MediawikiStandaloneOAuth2ConsentChecker;
use Tuleap\MediawikiStandalone\OAuth2\RejectAuthorizationRequiringConsent;
use Tuleap\MediawikiStandalone\REST\MediawikiStandaloneResourcesInjector;
use Tuleap\MediawikiStandalone\REST\OAuth2\OAuth2MediawikiStandaloneReadScope;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsageDao;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\MediawikiStandalone\Service\ServiceActivationEvent;
use Tuleap\MediawikiStandalone\Service\ServiceActivationHandler;
use Tuleap\MediawikiStandalone\Service\ServiceAvailabilityHandler;
use Tuleap\MediawikiStandalone\Service\ServiceAvailabilityProjectServiceBeforeAvailabilityEvent;
use Tuleap\MediawikiStandalone\Service\ServiceAvailabilityServiceDisabledCollectorEvent;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\AppFactory;
use Tuleap\OAuth2ServerCore\App\AppMatchingClientIDFilterAppTypeRetriever;
use Tuleap\OAuth2ServerCore\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PromptParameterValuesExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectProfileScope;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\PluginsAdministration\LifecycleHookCommand\PluginExecuteUpdateHookEvent;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Service\PluginAddMissingServiceTrait;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Queue\EnqueueTask;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Templating\TemplateCache;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ProjectReadScope;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../mediawiki/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class mediawiki_standalonePlugin extends Plugin implements PluginWithService, PluginWithConfigKeys
{
    use PluginAddMissingServiceTrait;

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-mediawiki_standalone', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone'),
                    dgettext('tuleap-mediawiki_standalone', 'Standalone MediaWiki instances integration with Tuleap')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['mediawiki'];
    }

    public function getInstallRequirements(): array
    {
        return [new \Tuleap\Plugin\MandatoryAsyncWorkerSetupPluginInstallRequirement(new \Tuleap\Queue\WorkerAvailability())];
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(OAuth2ScopeBuilderCollector::NAME);
        $this->addHook(CLICommandsCollector::NAME);
        $this->addHook(PluginExecuteUpdateHookEvent::NAME);
        $this->addHook(WorkerEvent::NAME);
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook('project_admin_remove_user', 'projectUserMemberRemoved');
        $this->addHook(Event::SITE_ACCESS_CHANGE);
        $this->addHook(ProjectStatusUpdate::NAME);
        $this->addHook(RegisterProjectCreationEvent::NAME);
        $this->addHook(Event::PROJECT_RENAME);
        $this->addHook(Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED);

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname(): string
    {
        return MediawikiStandaloneService::SERVICE_SHORTNAME;
    }

    protected function getServiceClass(): string
    {
        return MediawikiStandaloneService::class;
    }

    public function postEnable(): void
    {
        parent::postEnable();
        (new EnqueueTask())->enqueue(new UpdateMediaWikiTask());
    }

    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = $this->getServiceClass();
    }

    /**
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        (new ServiceActivationHandler(new EnqueueTask()))->handle(
            ServiceActivationEvent::fromServiceIsUsedEvent(
                $params,
                ProjectManager::instance()
            )
        );
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        (new ServiceActivationHandler(new EnqueueTask()))->handle(
            ServiceActivationEvent::fromRegisterProjectCreationEvent($event)
        );
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        (new ServiceAvailabilityHandler(new MediawikiFlavorUsageDao()))->handle(new ServiceAvailabilityProjectServiceBeforeAvailabilityEvent($event));
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        (new ServiceAvailabilityHandler(new MediawikiFlavorUsageDao()))->handle(new ServiceAvailabilityServiceDisabledCollectorEvent($event));
    }

    /**
     * @see Event::PROJECT_ACCESS_CHANGE
     * @param array{project_id: int} $params
     */
    public function projectAccessChange(array $params): void
    {
        $task = LogUsersOutInstanceTask::logsOutUserOfAProjectFromItsID(
            $params['project_id'],
            ProjectManager::instance()
        );

        if ($task !== null) {
            (new EnqueueTask())->enqueue($task);
        }
    }

    /**
     * @param array{group_id: int|string, user_id: int|string} $params
     */
    public function projectUserMemberRemoved(array $params): void
    {
        $task = LogUsersOutInstanceTask::logsSpecificUserOutOfAProjectFromItsID(
            (int) $params['group_id'],
            ProjectManager::instance(),
            (int) $params['user_id']
        );

        if ($task !== null) {
            (new EnqueueTask())->enqueue($task);
        }
    }

    /**
     * @see Event::SITE_ACCESS_CHANGE
     * @param array{old_value: \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED, new_value: \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED} $params
     */
    public function siteAccessChange(array $params): void
    {
        (new \Tuleap\MediawikiStandalone\Instance\SiteAccessHandler(
            $this->buildLocalSettingsInstantiator(),
            new EnqueueTask()
        ))->process($params['new_value']);
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        (new ProjectStatusHandler(new EnqueueTask()))->handle($event->project, $event->status);
    }

    public function workerEvent(WorkerEvent $event): void
    {
        $logger = $this->getBackendLogger();
        (new InstanceManagement(
            $logger,
            new MediawikiHTTPClientFactory(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            ProjectManager::instance(),
        ))->process($event);
        (new MediaWikiAsyncUpdateProcessor($this->buildUpdateScriptCaller($logger)))->process($event);
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(MediawikiHTTPClientFactory::class);
    }

    /**
     * @see         Event::PROJECT_RENAME
     *
     * @psalm-param array{group_id: string|int, new_name: string} $params
     */
    public function projectRename(array $params): void
    {
        (new ProjectRenameHandler(
            new EnqueueTask(),
            ProjectManager::instance(),
        ))->handle((int) $params['group_id'], $params['new_name']);
    }

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    public function restResources(array $params): void
    {
        $injector = new MediawikiStandaloneResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED
     *
     * @psalm-param array{allowed_services: string[]} $params
     */
    public function getServicesAllowedForRestricted(array &$params): void
    {
        $params['allowed_services'][] = $this->getServiceShortname();
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $route_collector = $event->getRouteCollector();

        $route_collector->addRoute(['GET', 'POST'], '/mediawiki_standalone/oauth2_authorize', $this->getRouteHandler('routeAuthorizationEndpoint'));
    }

    public function routeAuthorizationEndpoint(): \Tuleap\Request\DispatchableWithRequest
    {
        $response_factory           = HTTPFactoryBuilder::responseFactory();
        $stream_factory             = HTTPFactoryBuilder::streamFactory();
        $uri_factory                = HTTPFactoryBuilder::URIFactory();
        $redirect_uri_builder       = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
        $url_redirect               = new \URLRedirect(\EventManager::instance());
        $scope_builder              = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new OAuth2ScopeBuilderCollector())
        );
        $authorization_code_creator = new OAuth2AuthorizationCodeCreator(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new SplitTokenVerificationStringHasher(),
            new OAuth2AuthorizationCodeDAO(),
            new OAuth2ScopeSaver(new OAuth2AuthorizationCodeScopeDAO()),
            new DateInterval('PT1M'),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );

        $logger = \Tuleap\OAuth2ServerCore\OAuth2ServerRoutes::getOAuth2ServerLogger();
        return new AuthorizationEndpointController(
            new RejectAuthorizationRequiringConsent(
                new AuthorizationCodeResponseFactory(
                    $response_factory,
                    $authorization_code_creator,
                    $redirect_uri_builder,
                    $url_redirect,
                    $uri_factory
                ),
                $logger
            ),
            \UserManager::instance(),
            new AppFactory(
                new AppMatchingClientIDFilterAppTypeRetriever(
                    new AppDao(),
                    MediawikiStandaloneService::SERVICE_SHORTNAME
                ),
                \ProjectManager::instance()
            ),
            new ScopeExtractor($scope_builder),
            new AuthorizationCodeResponseFactory(
                $response_factory,
                $authorization_code_creator,
                $redirect_uri_builder,
                $url_redirect,
                $uri_factory
            ),
            new PKCEInformationExtractor(),
            new PromptParameterValuesExtractor(),
            new MediawikiStandaloneOAuth2ConsentChecker(self::allowedOAuth2Scopes()),
            $logger,
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(MediawikiStandaloneService::SERVICE_SHORTNAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware()
        );
    }

    /**
     * @return non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    private static function allowedOAuth2Scopes(): array
    {
        return [
            OAuth2SignInScope::fromItself(),
            OpenIDConnectEmailScope::fromItself(),
            OpenIDConnectProfileScope::fromItself(),
            OAuth2ProjectReadScope::fromItself(),
            OAuth2MediawikiStandaloneReadScope::fromItself(),
        ];
    }

    public function collectOAuth2ScopeBuilder(OAuth2ScopeBuilderCollector $collector): void
    {
        $collector->addOAuth2ScopeBuilder(
            new AuthenticationScopeBuilderFromClassNames(
                OAuth2MediawikiStandaloneReadScope::class
            )
        );
    }

    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        $collector->addCommand(
            GenerateLocalSettingsCommand::NAME,
            function (): GenerateLocalSettingsCommand {
                return new GenerateLocalSettingsCommand($this->buildLocalSettingsInstantiator());
            }
        );
    }

    public function executeUpdateHook(PluginExecuteUpdateHookEvent $event): void
    {
        $logger = new BrokerLogger(
            [
                new TruncateLevelLogger($event->logger, \Psr\Log\LogLevel::INFO),
                $this->getBackendLogger(),
            ]
        );
        $logger->info('Execute MediaWiki update script');
        $this->buildUpdateScriptCaller($logger)->runInstallAndUpdate();
    }

    private function buildUpdateScriptCaller(\Psr\Log\LoggerInterface $logger): MediaWikiInstallAndUpdateScriptCaller
    {
        return new MediaWikiInstallAndUpdateScriptCaller(
            new MediaWikiManagementCommandProcessFactory($logger, $this->buildSettingDirectoryPath()),
            $this->buildLocalSettingsInstantiator(),
            new ProjectMediaWikiServiceDAO(),
            $logger
        );
    }

    private function buildLocalSettingsInstantiator(): LocalSettingsInstantiator
    {
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $hasher               = new SplitTokenVerificationStringHasher();
        return new LocalSettingsInstantiator(
            new LocalSettingsFactory(
                new MediaWikiOAuth2AppSecretGeneratorDBStore(
                    $transaction_executor,
                    new AppDao(),
                    new MediaWikiNewOAuth2AppBuilder($hasher),
                    $hasher,
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret())
                ),
                new MediaWikiSharedSecretGeneratorForgeConfigStore(new ConfigDao())
            ),
            new LocalSettingsPersistToPHPFile(
                $this->buildSettingDirectoryPath(),
                new PHPStringMustacheRenderer(new TemplateCache(), __DIR__ . '/../templates/')
            ),
            $transaction_executor
        );
    }

    private function buildSettingDirectoryPath(): string
    {
        return ForgeConfig::get('sys_custompluginsroot') . '/' . $this->getName();
    }
}
