<?php
/**
 * Copyright (c) Enalean, 2015 - Present.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

use FastRoute\RouteCollector;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\PendingElements\PendingDocumentsRetriever;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\DocmanLegacyController;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits\DocmanFilesUploadLimitsAdminController;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits\DocmanFilesUploadLimitsAdminSaveController;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits\DocumentFilesUploadLimitsSaver;
use Tuleap\Docman\DocmanWikisReferencingSameWikiPageRetriever;
use Tuleap\Docman\Download\DocmanFileDownloadController;
use Tuleap\Docman\Download\DocmanFileDownloadCORS;
use Tuleap\Docman\Download\DocmanFileDownloadResponseGenerator;
use Tuleap\Docman\ExternalLinks\DocmanHTTPControllerProxy;
use Tuleap\Docman\ExternalLinks\ExternalLinkParametersExtractor;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;
use Tuleap\Docman\FilenamePattern\FilenamePatternRetriever;
use Tuleap\Docman\LegacyRestoreDocumentsController;
use Tuleap\Docman\LegacySendMessageController;
use Tuleap\Docman\Metadata\Owner\AllOwnerRetriever;
use Tuleap\Docman\Metadata\Owner\OwnerDao;
use Tuleap\Docman\Metadata\Owner\OwnerRequestHandler;
use Tuleap\Docman\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UGroupsRetriever;
use Tuleap\Docman\Notifications\UgroupsToNotifyDao;
use Tuleap\Docman\Notifications\UgroupsToNotifyUpdater;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Docman\Notifications\UsersUpdater;
use Tuleap\Docman\PermissionsPerGroup\PermissionPerGroupDocmanServicePaneBuilder;
use Tuleap\Docman\Reference\CrossReferenceDocmanOrganizer;
use Tuleap\Docman\Reference\DocumentFromReferenceValueFinder;
use Tuleap\Docman\Reference\DocumentIconPresenterBuilder;
use Tuleap\Docman\REST\ResourcesInjector;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\REST\v1\Folders\ComputeFolderSizeVisitor;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollectionBuilder;
use Tuleap\Docman\Settings\ForbidWritersSettings;
use Tuleap\Docman\Settings\SettingsDAO;
use Tuleap\Docman\Upload\Document\DocumentBeingUploadedInformationProvider;
use Tuleap\Docman\Upload\Document\DocumentDataStore;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentUploadCanceler;
use Tuleap\Docman\Upload\Document\DocumentUploadCleaner;
use Tuleap\Docman\Upload\Document\DocumentUploadFinisher;
use Tuleap\Docman\Upload\UploadPathAllocatorBuilder;
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionBeingUploadedInformationProvider;
use Tuleap\Docman\Upload\Version\VersionDataStore;
use Tuleap\Docman\Upload\Version\VersionUploadCanceler;
use Tuleap\Docman\Upload\Version\VersionUploadCleaner;
use Tuleap\Docman\Upload\Version\VersionUploadFinisher;
use Tuleap\Docman\XML\Export\PermissionsExporter;
use Tuleap\Docman\XML\Export\PermissionsExporterDao;
use Tuleap\Docman\XML\Import\ImportPropertiesExtractor;
use Tuleap\Docman\XML\Import\ItemImporter;
use Tuleap\Docman\XML\Import\NodeImporter;
use Tuleap\Docman\XML\Import\PermissionsImporter;
use Tuleap\Docman\XML\Import\PostDoNothingImporter;
use Tuleap\Docman\XML\Import\PostFileImporter;
use Tuleap\Docman\XML\Import\PostFolderImporter;
use Tuleap\Docman\XML\Import\VersionImporter;
use Tuleap\Docman\XML\XMLExporter;
use Tuleap\Docman\XML\XMLImporter;
use Tuleap\Document\Config\Admin\FilesDownloadLimitsAdminController;
use Tuleap\Document\Config\Admin\FilesDownloadLimitsAdminSaveController;
use Tuleap\Document\Config\Admin\HistoryEnforcementAdminController;
use Tuleap\Document\Config\Admin\HistoryEnforcementAdminSaveController;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Document\Config\ModalDisplayer;
use Tuleap\Document\Config\Project\SearchColumnFilter;
use Tuleap\Document\Config\Project\SearchColumnsDao;
use Tuleap\Document\Config\Project\SearchCriteriaDao;
use Tuleap\Document\Config\Project\SearchCriteriaFilter;
use Tuleap\Document\Config\Project\SearchView;
use Tuleap\Document\Config\Project\UpdateSearchView;
use Tuleap\Document\DownloadFolderAsZip\DocumentFolderZipStreamer;
use Tuleap\Document\DownloadFolderAsZip\FolderSizeIsAllowedChecker;
use Tuleap\Document\DownloadFolderAsZip\ZipStreamerLoggingHelper;
use Tuleap\Document\DownloadFolderAsZip\ZipStreamMailNotificationSender;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\Document\PermissionDeniedDocumentMailSender;
use Tuleap\Document\Tree\DocumentTreeController;
use Tuleap\Document\Tree\DocumentTreeProjectExtractor;
use Tuleap\Document\Tree\ListOfSearchCriterionPresenterBuilder;
use Tuleap\Document\Tree\Search\ListOfSearchColumnDefinitionPresenterBuilder;
use Tuleap\Document\Tree\SwitchToOldUi;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\PaginationPresenter;
use Tuleap\Layout\TooltipJSON;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\ServerHostname;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;
use Tuleap\Upload\FileUploadController;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetPublicAreas;
use Tuleap\Widget\Event\GetUserWidgetList;
use Tuleap\Widget\Event\GetWidget;
use Tuleap\wiki\Events\GetItemsReferencingWikiPageCollectionEvent;
use User\XML\Import\IFindUserFromXMLReference;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../document/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class DocmanPlugin extends Plugin implements PluginWithConfigKeys
{
    public const TRUNCATED_SERVICE_NAME = 'Documents';
    public const SYSTEM_NATURE_NAME     = 'document';
    public const SERVICE_SHORTNAME      = 'docman';
    public const ITEM_MAPPING_KEY       = 'plugin_docman_item_mapping';

    public const ADMIN_BASE_URL = '/admin/document';

    #[ConfigKey("Max size for individual files in Document and Docman plugins (in bytes)")]
    public const PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING = 'plugin_docman_max_file_size';

    #[ConfigKey("Max number of files that can be uploaded with a drag'n drop in interface")]
    public const PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING = 'plugin_docman_max_number_of_files';

    /**
     * Store docman root items indexed by groupId
     *
     * @var array
     */
    private $rootItems = [];

    /**
     * Store controller instances
     *
     * @var Array
     */
    private $controller = [];

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-docman', __DIR__ . '/../site-content');
        bindtextdomain('tuleap-document', __DIR__ . '/../../document/site-content');

        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook('javascript_file');
        $this->addHook('logs_daily', 'logsDaily', false);
        $this->addHook('permission_get_name', 'permission_get_name', false);
        $this->addHook('permission_get_object_type', 'permission_get_object_type', false);
        $this->addHook('permission_get_object_name', 'permission_get_object_name', false);
        $this->addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change', false);
        $this->addHook(GetPublicAreas::NAME);
        $this->addHook(RegisterProjectCreationEvent::NAME);
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook('soap', 'soap', false);
        $this->addHook(GetWidget::NAME);
        $this->addHook(GetUserWidgetList::NAME);
        $this->addHook(GetProjectWidgetList::NAME);
        $this->addHook('codendi_daily_start', 'codendiDaily', false);
        $this->addHook('wiki_page_updated', 'wiki_page_updated', false);
        $this->addHook('wiki_before_content', 'wiki_before_content', false);
        $this->addHook(Event::WIKI_DISPLAY_REMOVE_BUTTON, 'wiki_display_remove_button', false);
        $this->addHook('isWikiPageReferenced', 'isWikiPageReferenced', false);
        $this->addHook('isWikiPageEditable', 'isWikiPageEditable', false);
        $this->addHook('userCanAccessWikiDocument', 'userCanAccessWikiDocument', false);
        $this->addHook('getPermsLabelForWiki', 'getPermsLabelForWiki', false);
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipContentEvent::NAME);
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipRepresentationEvent::NAME);
        $this->addHook('SystemEvent_PROJECT_RENAME', 'renameProject', false);
        $this->addHook('file_exists_in_data_dir', 'file_exists_in_data_dir', false);
        $this->addHook('webdav_root_for_service', 'webdav_root_for_service', false);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        // Stats plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project', false);
        $this->addHook('plugin_statistics_disk_usage_service_label', 'plugin_statistics_disk_usage_service_label', false);
        $this->addHook('plugin_statistics_color', 'plugin_statistics_color', false);

        $this->addHook('backend_system_purge_files', 'purgeFiles', false);
        $this->addHook('project_admin_remove_user', 'projectRemoveUser', false);

        $this->addHook('permission_request_information', 'permissionRequestInformation', false);

        $this->addHook('fill_project_history_sub_events', 'fillProjectHistorySubEvents', false);
        $this->addHook(ProjectStatusUpdate::NAME);
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);

        $this->addHook(GetReferenceEvent::NAME);
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);
        $this->addHook(SiteAdministrationAddOption::NAME);
    }

    public function getHooksAndCallbacks()
    {
        if (defined('STATISTICS_BASE_DIR')) {
            $this->addHook(Statistics_Event::FREQUENCE_STAT_ENTRIES);
            $this->addHook(Statistics_Event::FREQUENCE_STAT_SAMPLE);
        }

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(CollectRoutesEvent::NAME);

        $this->addHook(GetItemsReferencingWikiPageCollectionEvent::NAME);

        $this->addHook(StatisticsCollectionCollector::NAME);
        $this->addHook(ServiceEnableForXmlImportRetriever::NAME);

        $this->addHook(ExportXmlProject::NAME);
        $this->addHook(Event::IMPORT_XML_PROJECT);
        $this->addHook(PendingDocumentsRetriever::NAME);
        $this->addHook(CrossReferenceByNatureOrganizer::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname()
    {
        return self::SERVICE_SHORTNAME;
    }

    /** @see Event::SERVICE_CLASSNAMES */
    public function service_classnames(&$params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['classnames'][self::SERVICE_SHORTNAME] = \Tuleap\Docman\ServiceDocman::class;
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_ENTRIES
     */
    public function plugin_statistics_frequence_stat_entries($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['entries'][$this->getServiceShortname()] = 'Documents viewed';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_SAMPLE
     */
    public function plugin_statistics_frequence_stat_sample($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['character'] === $this->getServiceShortname()) {
            $params['sample'] = new Docman_Sample();
        }
    }

    public function permission_get_name($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['name']) {
            switch ($params['permission_type']) {
                case 'PLUGIN_DOCMAN_READ':
                    $params['name'] = dgettext('tuleap-docman', 'Document Reader');
                    break;
                case 'PLUGIN_DOCMAN_WRITE':
                    $params['name'] = dgettext('tuleap-docman', 'Document Writer');
                    break;
                case 'PLUGIN_DOCMAN_MANAGE':
                    $params['name'] = dgettext('tuleap-docman', 'Document Manager');
                    break;
                case 'PLUGIN_DOCMAN_ADMIN':
                    $params['name'] = dgettext('tuleap-docman', 'Document Administrator');
                    break;
                default:
                    break;
            }
        }
    }
    public function permission_get_object_type($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['object_type']) {
            if (in_array($params['permission_type'], ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'])) {
                $if   = new Docman_ItemFactory();
                $item = $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_type'] = $item instanceof \Docman_Folder ? 'folder' : 'document';
                }
            }
        }
    }
    public function permission_get_object_name($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['object_name']) {
            if (in_array($params['permission_type'], ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'])) {
                $if   = new Docman_ItemFactory();
                $item = $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_name'] = $item->getTitle();
                }
            }
        }
    }

    public $_cached_permission_user_allowed_to_change; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public function permission_user_allowed_to_change($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['allowed']) {
            if (! $this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'])) {
                    $docman = $this->getHTTPController();
                    switch ($params['permission_type']) {
                        case 'PLUGIN_DOCMAN_READ':
                        case 'PLUGIN_DOCMAN_WRITE':
                        case 'PLUGIN_DOCMAN_MANAGE':
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanManage($params['object_id']);
                            break;
                        default:
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanAdmin();
                            break;
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }
    public function &getPluginInfo()
    {
        if (! is_a($this->pluginInfo, 'DocmanPluginInfo')) {
            $this->pluginInfo = new DocmanPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssFile($params): void
    {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (
            $this->currentRequestIsForPlugin() ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('default-style.css') . '" />' . "\n";
        }
    }

    public function javascript_file($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->currentRequestIsForPlugin()) {
            $layout = $params['layout'];
            assert($layout instanceof \Layout);
            $layout->includeJavascriptFile($this->getAssets()->getFileURL('docman.js'));
            $layout->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/docman'
        );
    }

    public function logsDaily($params)
    {
        $project = $this->getProject($params['group_id']);
        if ($project->usesService($this->getServiceShortname())) {
            $controler = $this->getHTTPController();
            $controler->logsDaily($params);
        }
    }

    public function service_public_areas(GetPublicAreas $event) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $event->getProject();
        $service = $project->getService($this->getServiceShortname());
        if ($service instanceof \Tuleap\Docman\ServiceDocman) {
            $event->addArea(
                '<a href="' . $service->getUrl() . '">' .
                '<i class="dashboard-widget-content-projectpublicareas ' . Codendi_HTMLPurifier::instance()->purify($service->getIcon()) . '"></i>' .
                dgettext('tuleap-docman', 'Document Manager') .
                '</a>'
            );
        }
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $this->getHTTPController()->installDocman(
            $event->getMappingRegistry(),
            (int) $event->getJustCreatedProject()->getID(),
        );
    }

    public function service_is_used($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (isset($params['shortname']) && $params['shortname'] == $this->getServiceShortname()) {
            if (isset($params['is_used']) && $params['is_used']) {
                $this->getHTTPController()->installDocman(
                    null,
                    $params['group_id'],
                );
            }
        }
    }
    public function soap($arams)
    {
        require_once('soap.php');
    }

    public function widgetInstance(GetWidget $get_widget_event)
    {
        switch ($get_widget_event->getName()) {
            case 'plugin_docman_mydocman':
                $get_widget_event->setWidget(new Docman_Widget_MyDocman($this->getPluginPath()));
                break;
            case 'plugin_docman_my_embedded':
                $get_widget_event->setWidget(new Docman_Widget_MyEmbedded($this->getPluginPath()));
                break;
            case 'plugin_docman_project_embedded':
                $get_widget_event->setWidget(new Docman_Widget_ProjectEmbedded($this->getPluginPath()));
                break;
            case 'plugin_docman_mydocman_search':
                $get_widget_event->setWidget(new Docman_Widget_MyDocmanSearch($this->getPluginPath()));
                break;
            default:
                break;
        }
    }

    public function getUserWidgetList(GetUserWidgetList $event)
    {
        $event->addWidget('plugin_docman_mydocman');
        $event->addWidget('plugin_docman_mydocman_search');
        $event->addWidget('plugin_docman_my_embedded');
    }

    public function getProjectWidgetList(GetProjectWidgetList $event)
    {
        $event->addWidget('plugin_docman_project_embedded');
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets([
            'plugin_docman_mydocman',
            'plugin_docman_mydocman_search',
            'plugin_docman_my_embedded',
            'plugin_docman_project_embedded',
        ]);
    }

    /**
     * Hook: called by daily codendi script.
     */
    public function codendiDaily()
    {
        $controler = $this->getHTTPController();
        $controler->notifyFuturObsoleteDocuments();
        $reminder = new Docman_ApprovalTableReminder();
        $reminder->remindApprovers();

        $this->cleanUnusedResources();
    }

    public function process()
    {
        $request = HTTPRequest::instance();
        $user    = $request->getCurrentUser();
        $proxy   = new DocmanHTTPControllerProxy(
            new ExternalLinkParametersExtractor(),
            $this->getHTTPController(),
            $this->getItemDao()
        );

        $proxy->process($request, $user);
    }


    public function processSOAP($request)
    {
        return $this->getSOAPController($request)->process();
    }

    public function wiki_page_updated($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = new Docman_WikiRequest(['action' => 'wiki_page_updated',
            'wiki_page' => $params['wiki_page'],
            'diff_link' => $params['diff_link'],
            'group_id'  => $params['group_id'],
            'user'      => $params['user'],
            'version'   => $params['version'],
        ]);
        $this->getWikiController($request)->process();
    }

    public function wiki_before_content($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['action'] = 'wiki_before_content';
        $request          = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function wiki_display_remove_button($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['action'] = 'wiki_display_remove_button';
        $request          = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function isWikiPageReferenced($params)
    {
        $params['action'] = 'check_whether_wiki_page_is_referenced';
        $request          = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function isWikiPageEditable($params)
    {
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function userCanAccessWikiDocument($params)
    {
        $params['action'] = 'check_whether_user_can_access';
        $request          = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function getPermsLabelForWiki($params)
    {
        $params['action'] = 'getPermsLabelForWiki';
        $request          = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function referenceGetTooltipRepresentationEvent(Tuleap\Reference\ReferenceGetTooltipRepresentationEvent $event): void
    {
        if ($event->getReference()->getServiceShortName() !== 'docman') {
            return;
        }

        $finder = new DocumentFromReferenceValueFinder();
        $item   = $finder->findItem($event->getProject(), $event->getUser(), $event->getValue());
        if (! $item) {
            return;
        }

        $icon_presenter_builder = new DocumentIconPresenterBuilder();

        $renderer     = TemplateRendererFactory::build()->getRenderer(__DIR__);
        $tooltip_json = new TooltipJSON(
            $renderer->renderToString('tooltip-title', [
                'icon'  => $icon_presenter_builder->buildForItem($item),
                'title' => $item->getTitle(),
            ]),
            $item->getDescription(),
        );
        $event->setOutput($tooltip_json);
    }

    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        $tracker_organizer = new CrossReferenceDocmanOrganizer(
            ProjectManager::instance(),
            new DocumentFromReferenceValueFinder(),
            new DocumentIconPresenterBuilder(),
        );

        $tracker_organizer->organizeDocumentReferences($by_nature_organizer);
    }

    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event)
    {
        if ($event->getReference()->getServiceShortName() === 'docman') {
            $request    = new Codendi_Request([
                'id'       => $event->getValue(),
                'group_id' => $event->getProject()->getID(),
                'action'   => 'ajax_reference_tooltip',
            ]);
            $controller = $this->getHTTPController($request);
            ob_start();
            $controller->process();
            $event->setOutput(ob_get_clean());
        }
    }

    /**
     * Hook called when a project is being renamed
     * @param Array $params
     * @return bool
     */
    public function renameProject($params)
    {
        $docmanPath = $this->getPluginInfo()->getPropertyValueForName('docman_root') . '/';
        //Is this project using docman
        if (is_dir($docmanPath . $params['project']->getUnixName())) {
            $version = new Docman_VersionFactory();

            return $version->renameProject($docmanPath, $params['project'], $params['new_name']);
        }

        return true;
    }

    /**
     * Hook called before renaming project to check the name validity
     * @param Array $params
     */
    public function file_exists_in_data_dir($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $docmanPath = $this->getPluginInfo()->getPropertyValueForName('docman_root') . '/';
        $path       = $docmanPath . $params['new_name'];

        if (Backend::fileExists($path)) {
            $params['result'] = false;
            $params['error']  = dgettext('tuleap-docman', 'A directory already exists with this name under docman');
        }
    }

    /**
     * Hook to know if docman is activated for the given project
     * it returns the root item of that project
     *
     * @param Array $params
     */
    public function webdav_root_for_service($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project_id = (int) $params['project']->getId();
        if (! $params['project']->usesService('docman')) {
            return;
        }

        $pattern_retriever = new FilenamePatternRetriever(new SettingsDAO());
        if ($pattern_retriever->getPattern($project_id)->isEnforcedAndNonEmpty()) {
            return;
        }

        if (! isset($this->rootItems[$project_id])) {
            include_once 'Docman_ItemFactory.class.php';
            $docmanItemFactory            = new Docman_ItemFactory();
            $this->rootItems[$project_id] = $docmanItemFactory->getRoot($project_id);
        }

        $params['roots']['docman'] = $this->rootItems[$project_id];
    }

    /**
     * Hook to collect docman disk size usage per project
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_collect_project($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $row  = $params['project_row'];
        $root = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path = $root . '/' . strtolower($row['unix_group_name']);

        if (! isset($params['time_to_collect']['plugin_docman'])) {
            $params['time_to_collect']['plugin_docman'] = 0;
        }

        $params['DiskUsageManager']->storeForGroup(
            $params['collect_date'],
            $row['group_id'],
            'plugin_docman',
            $path,
            $params['time_to_collect']
        );
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_service_label($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['services']['plugin_docman'] = 'Docman';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     *
     * @param array $params
     */
    public function plugin_statistics_color($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['service'] == 'plugin_docman') {
            $params['color'] = 'royalblue';
        }
    }

    public function pendingDocumentsRetriever(PendingDocumentsRetriever $event): void
    {
        $request = HTTPRequest::instance();
        $limit   = 25;

        //return all pending versions for given group id
        $offsetVers = $request->getValidated('offsetVers', 'uint', 0);
        if (! $offsetVers || $offsetVers < 0) {
            $offsetVers = 0;
        }

        $version = new Docman_VersionFactory();
        $res     = $version->listPendingVersions($event->getProject()->getID(), $offsetVers, $limit);
        $html    = '';
        $html   .= '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . dgettext('tuleap-docman', 'Document Manager') . '</h1>
                </div>
                <section class="tlp-pane-section">
                    <h2 class="tlp-pane-subtitle">' . dgettext('tuleap-docman', 'Deleted versions') . '</h2>';
        if (isset($res) && $res) {
            $html .= $this->showPendingVersions($event->getToken(), $res['versions'], $event->getProject()->getID(), $res['nbVersions'], $offsetVers, $limit);
        } else {
            $html .= '<table class="tlp-table">
                <thead>
                    <tr>
                        <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Version label') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Version number') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            ' . dgettext('tuleap-docman', 'There are no pending versions.') . '
                        </td>
                    </tr>
                </tbody>
            </table>';
        }
        $html .= '</section>';

        $event->addPurifiedHTML($html);

        //return all pending items for given group id
        $offsetItem = $request->getValidated('offsetItem', 'uint', 0);
        if (! $offsetItem || $offsetItem < 0) {
            $offsetItem = 0;
        }
        $item  = new Docman_ItemFactory($event->getProject()->getID());
        $res   = $item->listPendingItems($event->getProject()->getID(), $offsetItem, $limit);
        $html  = '';
        $html .= '<section class="tlp-pane-section">
                <h2 class="tlp-pane-subtitle">' . dgettext('tuleap-docman', 'Deleted items') . '</h2>';
        if (isset($res) && $res) {
            $html .= $this->showPendingItems($event->getToken(), $res['items'], $event->getProject()->getID(), $res['nbItems'], $offsetItem, $limit);
        } else {
            $html .= '<table class="tlp-table">
                <thead>
                    <tr>
                        <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Item type') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Location') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Owner') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            No restorable items found
                        </td>
                    </tr>
                </tbody>
            </table>';
        }
        $html .= '</section>
            </div>
        </section>';

        $event->addPurifiedHTML($html);
    }

    public function showPendingVersions(CSRFSynchronizerToken $csrf_token, $versions, $groupId, $nbVersions, $offset, $limit)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $html  = '';
        $html .= '<table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Version label') . '</th>
                    <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Version number') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        $user_manager = UserManager::instance();
        $user         = $user_manager->getCurrentUser();

        if ($nbVersions > 0) {
            foreach ($versions as $row) {
                $historyUrl = $this->getPluginPath() . '/index.php?group_id=' . $groupId . '&id=' . $row['item_id'] . '&action=details&section=history';
                $purgeDate  = strtotime('+' . ForgeConfig::get('sys_file_deletion_delay') . ' day', $row['date']);
                $html      .= '<tr>' .
                '<td class="tlp-table-cell-numeric"><a href="' . $historyUrl . '">' . $row['item_id'] . '</a></td>' .
                '<td>' . $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $groupId) . '</td>' .
                '<td>' . $hp->purify($row['label']) . '</td>' .
                '<td class="tlp-table-cell-numeric">' . $row['number'] . '</td>' .
                '<td>' . DateHelper::relativeDateInlineContext((int) $row['date'], $user) . '</td>' .
                '<td>' . DateHelper::relativeDateInlineContext((int) $purgeDate, $user) . '</td>' .
                '<td class="tlp-table-cell-actions">
                        <form method="post" action="/plugins/docman/restore_documents.php" onsubmit="return confirm(\'Confirm restore of this version\')">
                            ' . $csrf_token->fetchHTMLInput() . '
                            <input type="hidden" name="id" value="' . $hp->purify($row['id']) . '">
                            <input type="hidden" name="item_id" value="' . $hp->purify($row['item_id']) . '">
                            <input type="hidden" name="group_id" value="' . $hp->purify($groupId) . '">
                            <input type="hidden" name="func" value="confirm_restore_version">
                            <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                                <i class="fas fa-redo tlp-button-icon"></i> Restore
                            </button>
                        </form>
                    </td>
                </tr>';
            }
            $html .= '</tbody>
                </table>';

            if ($offset > 0 || ($offset + $limit) < $nbVersions) {
                $pagination = new PaginationPresenter(
                    $limit,
                    $offset,
                    count($versions),
                    $nbVersions,
                    "/admin/show_pending_documents.php",
                    [
                        'group_id'   => $groupId,
                        'offsetItem' => ($offset + $limit),
                    ]
                );

                $html .= '<div class="siteadmin-projects-pending-doc-pagination">';
                $html .= TemplateRendererFactory::build()
                    ->getRenderer(__DIR__)
                    ->renderToString('pagination', $pagination);
                $html .= '</div>';
            }
        } else {
            $html .= '<tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            ' . dgettext('tuleap-docman', 'There are no pending versions.') . '
                        </td>
                    </tr>
                </tbody>
            </table>';
        }

        return $html;
    }

    public function showPendingItems(CSRFSynchronizerToken $csrf_token, $res, $groupId, $nbItems, $offset, $limit)
    {
        $hp          = Codendi_HTMLPurifier::instance();
        $itemFactory = new Docman_ItemFactory($groupId);
        $uh          = UserHelper::instance();

        $user_manager = UserManager::instance();
        $user         = $user_manager->getCurrentUser();

        $html  = '';
        $html .= '<table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Item type') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Location') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Owner') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        if ($nbItems > 0) {
            foreach ($res as $row) {
                $purgeDate = strtotime('+' . ForgeConfig::get('sys_file_deletion_delay') . ' day', $row['date']);
                $html     .= '<tr>' .
                '<td class="tlp-table-cell-numeric">' . $row['id'] . '</td>' .
                '<td>' . $itemFactory->getItemTypeAsText($row['item_type']) . '</td>' .
                '<td>' . $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $groupId) . '</td>' .
                '<td>' . $hp->purify($row['location']) . '</td>' .
                '<td>' . $hp->purify($uh->getDisplayNameFromUserId($row['user'])) . '</td>' .
                '<td>' . DateHelper::relativeDateInlineContext((int) $row['date'], $user) . '</td>' .
                '<td>' . DateHelper::relativeDateInlineContext((int) $purgeDate, $user) . '</td>' .
                '<td class="tlp-table-cell-actions">
                    <form method="post" action="/plugins/docman/restore_documents.php" onsubmit="return confirm(\'Confirm restore of this item\')">
                        ' . $csrf_token->fetchHTMLInput() . '
                        <input type="hidden" name="id" value="' . $hp->purify($row['id']) . '">
                        <input type="hidden" name="group_id" value="' . $hp->purify($groupId) . '">
                        <input type="hidden" name="func" value="confirm_restore_item">
                        <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                            <i class="fas fa-redo tlp-button-icon"></i> Restore
                        </button>
                    </form>
                    </td>
                </tr>';
            }
            $html .= '</tbody>
                </table>';

            if ($offset > 0 || ($offset + $limit) < $nbItems) {
                $pagination = new PaginationPresenter(
                    $limit,
                    $offset,
                    count($res),
                    $nbItems,
                    "/admin/show_pending_documents.php",
                    [
                        'group_id'   => $groupId,
                        'offsetItem' => ($offset + $limit),
                    ]
                );

                $html .= '<div class="siteadmin-projects-pending-doc-pagination">';
                $html .= TemplateRendererFactory::build()
                    ->getRenderer(__DIR__)
                    ->renderToString('pagination', $pagination);
                $html .= '</div>';
            }
        } else {
            $html .= '<tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            ' . dgettext('tuleap-docman', 'There are no pending items.') . '
                        </td>
                    </tr>
                </tbody>
            </table>';
        }

        return $html;
    }

    /**
     * Hook to purge deleted items if their agony ends today
     *
     * @param Array $params
     *
     * @return void
     */
    public function purgeFiles(array $params)
    {
        $itemFactory = new Docman_ItemFactory();
        $itemFactory->purgeDeletedItems($params['time']);

        $versionFactory = new Docman_VersionFactory();
        $versionFactory->purgeDeletedVersions($params['time']);

        $this->cleanUnusedResources();
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === Project::STATUS_DELETED) {
            $docmanItemFactory = new Docman_ItemFactory();
            $docmanItemFactory->deleteProjectTree((int) $event->project->getID());

            $this->cleanUnusedResources();
        }
    }

    /**
     * Function called when a user is removed from a project
     * If a user is removed from a private project, the
     * documents monitored by that user should be monitored no more.
     *
     * @param array $params
     *
     * @return void
     */
    public function projectRemoveUser($params)
    {
        $project_id = $params['group_id'];
        $user_id    = $params['user_id'];

        $project                                  = $this->getProject($project_id);
        $user                                     = $this->getUserManager()->getUserById($user_id);
        $notifications_for_project_member_cleaner = $this->getNotificationsForProjectMemberCleaner($project);
        $notifications_for_project_member_cleaner->cleanNotificationsAfterUserRemoval($project, $user);
    }

    /**
     * Display information about admin delegation
     *
     * @return void
     */
    public function permissionRequestInformation($params)
    {
        $params['notices'][] = dgettext('tuleap-docman', 'The selected group will not affect people notified for permission requests on documents service.<br> Only the <b>document managers</b> will be notified in that case.');
    }

    /**
     * Fill the list of subEvents related to docman in the project history interface
     *
     */
    public function fillProjectHistorySubEvents($params)
    {
        array_push(
            $params['subEvents']['event_permission'],
            'perm_reset_for_document',
            'perm_granted_for_document',
            'perm_reset_for_folder',
            'perm_granted_for_folder'
        );
    }

    protected function getWikiController($request)
    {
        return $this->getController('Docman_WikiController', $request);
    }

    protected function getHTTPController($request = null)
    {
        if ($request == null) {
            $request = HTTPRequest::instance();
        }
        return $this->getController('Docman_HTTPController', $request);
    }

    protected function getSOAPController($request)
    {
        return $this->getController('Docman_SOAPController', $request);
    }

    protected function getController($controller, $request)
    {
        if (! isset($this->controller[$controller])) {
            include_once $controller . '.class.php';
            $this->controller[$controller] = new $controller($this, $this->getPluginPath(), $this->getThemePath(), $request);
        } else {
            $this->controller[$controller]->setRequest($request);
        }
        return $this->controller[$controller];
    }

    public function proccess_system_check($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $docman_system_check = new Docman_SystemCheck(
            $this,
            new Docman_SystemCheckProjectRetriever(new Docman_SystemCheckDao()),
            BackendSystem::instance(),
            new PluginConfigChecker($params['logger']),
            $params['logger']
        );

        $docman_system_check->process();
    }

    public function services_truncated_emails($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $params['project'];
        if ($project->usesService('docman')) {
            $params['services'][] = dgettext('tuleap-docman', 'Documents');
        }
    }

    /**
     * @return Project
     */
    private function getProject($group_id)
    {
        return ProjectManager::instance()->getProject($group_id);
    }

    /**
     * @return MailBuilder
     */
    private function getMailBuilder()
    {
        return new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(
                UserManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new MailLogger()
            )
        );
    }

    public function getReference(GetReferenceEvent $event): void
    {
        $keyword       = $event->getKeyword();
        $reference_row = $this->getSystemDocmanReferenceByKeyword($keyword);

        if ($reference_row) {
            $docman_element_id   = $event->getValue();
            $docman_item_factory = new Docman_ItemFactory();
            $reference_factory   = new Docman_ReferenceFactory();

            $docman_item = $docman_item_factory->getItemFromDb($docman_element_id);

            if ($docman_item) {
                $reference = $reference_factory->getInstanceFromRowAndProjectId(
                    $reference_row,
                    $docman_item->getGroupId()
                );

                $event->setReference($reference);
            }
        }
    }

    private function getSystemDocmanReferenceByKeyword($keyword)
    {
        $dao    = new ReferenceDao();
        $result = $dao->searchSystemReferenceByNatureAndKeyword($keyword, self::SYSTEM_NATURE_NAME);

        if (! $result || $result->rowCount() < 1) {
            return null;
        }

        return $result->getRow();
    }

    public function project_admin_ugroup_deletion($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project_id = $params['group_id'];
        $ugroup     = $params['ugroup'];

        $ugroups_to_notify_dao = $this->getUGroupToNotifyDao();
        $ugroups_to_notify_dao->deleteByUgroupId($project_id, $ugroup->getId());
    }

    /** @see Event::PROJECT_ACCESS_CHANGE */
    public function projectAccessChange(array $params): void
    {
        $project_id = $params['project_id'];
        $old_access = $params['old_access'];
        $new_access = $params['access'];

        $updater = $this->getUgroupsToNotifyUpdater();
        $updater->updateProjectAccess($project_id, $old_access, $new_access);

        $project                                  = $this->getProject($project_id);
        $notifications_for_project_member_cleaner = $this->getNotificationsForProjectMemberCleaner($project);
        $notifications_for_project_member_cleaner->cleanNotificationsAfterProjectVisibilityChange($project, $new_access);
    }

    public function site_access_change($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $old_access = $params['old_value'];

        $updater = $this->getUgroupsToNotifyUpdater();
        $updater->updateSiteAccess($old_access);
    }

    private function getUgroupsToNotifyUpdater()
    {
        return new UgroupsToNotifyUpdater(
            $this->getUGroupToNotifyDao()
        );
    }

    private function getNotificationsForProjectMemberCleaner(Project $project)
    {
        return new NotificationsForProjectMemberCleaner(
            $this->getItemFactory($project->getID()),
            new Docman_NotificationsManager(
                $project,
                $this->getProvider($project),
                null,
                $this->getMailBuilder(),
                $this->getUsersToNotifyDao(),
                $this->getUsersNotificationRetriever(),
                $this->getUGroupsRetriever(),
                $this->getNotifiedPeopleRetriever(),
                $this->getUsersUpdater(),
                $this->getUGroupsUpdater()
            ),
            $this->getUserManager()
        );
    }

    private function getUsersToNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    private function getUGroupManager()
    {
        return new UGroupManager(
            new UGroupDao(),
            new EventManager(),
            new UGroupUserDao()
        );
    }

    private function getUGroupToNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    private function getUsersNotificationRetriever()
    {
        return new UsersRetriever(
            $this->getUsersToNotifyDao(),
            $this->getItemFactory()
        );
    }
    private function getUGroupsRetriever()
    {
        return new UGroupsRetriever($this->getUGroupToNotifyDao(), $this->getItemFactory());
    }

    private function getNotifiedPeopleRetriever()
    {
        return new NotifiedPeopleRetriever(
            $this->getUsersToNotifyDao(),
            $this->getUGroupToNotifyDao(),
            $this->getItemFactory(),
            $this->getUGroupManager()
        );
    }

    private function getItemFactory($project_id = null)
    {
        return new Docman_ItemFactory($project_id);
    }

    private function getUserManager()
    {
        return UserManager::instance();
    }

    private function getUGroupsUpdater()
    {
        return new UgroupsUpdater($this->getUGroupToNotifyDao());
    }

    private function getUsersUpdater()
    {
        return new UsersUpdater($this->getUsersToNotifyDao());
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(NavigationDropdownQuickLinksCollector $quick_links_collector)
    {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-docman', 'Document manager'),
                $this->getPluginPath() . '/?' . http_build_query(
                    [
                        'group_id' => $project->getID(),
                        'action'   => Docman_View_Admin_Permissions::IDENTIFIER,
                    ]
                )
            )
        );
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager = new UGroupManager();

        $service_pane_builder = new PermissionPerGroupDocmanServicePaneBuilder(
            new PermissionPerGroupUGroupRetriever(PermissionsManager::instance()),
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            $ugroup_manager
        );

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(dirname(PLUGIN_DOCMAN_BASE_DIR) . '/templates')
            ->renderToString(
                'project-admin-permission-per-group',
                $service_pane_builder->buildPresenter($event)
            );

        $project = $event->getProject();
        $service = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $rank_in_project = $service->getRank();
            $event->addPane($admin_permission_pane, $rank_in_project);
        }
    }

    /** @see Event::REST_RESOURCES */
    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /** @see \Event::REST_PROJECT_RESOURCES */
    public function rest_project_resources(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        ResourcesInjector::declareProjectResources($params['resources'], $params['project']);
    }

    public function routeUploadsDocmanFile(): FileUploadController
    {
        $document_ongoing_upload_dao = new DocumentOngoingUploadDAO();
        $root_path                   = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path_allocator              = (new UploadPathAllocatorBuilder())->getDocumentUploadPathAllocator();
        $user_manager                = UserManager::instance();
        $event_manager               = EventManager::instance();
        $current_user_provider       = new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication());

        return FileUploadController::build(
            new DocumentDataStore(
                new DocumentBeingUploadedInformationProvider(
                    $path_allocator,
                    $document_ongoing_upload_dao,
                    $this->getItemFactory(),
                    $current_user_provider,
                ),
                new FileBeingUploadedWriter(
                    $path_allocator,
                    DBFactory::getMainTuleapDBConnection()
                ),
                new FileBeingUploadedLocker(
                    $path_allocator
                ),
                new DocumentUploadFinisher(
                    BackendLogger::getDefaultLogger(),
                    $path_allocator,
                    $this->getItemFactory(),
                    new Docman_VersionFactory(),
                    $event_manager,
                    $document_ongoing_upload_dao,
                    $this->getItemDao(),
                    new Docman_FileStorage($root_path),
                    new Docman_MIMETypeDetector(),
                    $user_manager,
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    new DocmanItemsEventAdder($event_manager),
                    ProjectManager::instance()
                ),
                new DocumentUploadCanceler(
                    $path_allocator,
                    $document_ongoing_upload_dao
                )
            ),
            $current_user_provider,
        );
    }

    public function routeUploadsVersionFile(): FileUploadController
    {
        $root_path                = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path_allocator           = (new UploadPathAllocatorBuilder())->getVersionUploadPathAllocator();
        $version_to_upload_dao    = new DocumentOnGoingVersionToUploadDAO();
        $event_manager            = EventManager::instance();
        $approval_table_retriever = new ApprovalTableRetriever(
            new Docman_ApprovalTableFactoriesFactory(),
            new Docman_VersionFactory()
        );
        $current_user_provider    = new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication());
        return FileUploadController::build(
            new VersionDataStore(
                new VersionBeingUploadedInformationProvider(
                    $version_to_upload_dao,
                    $this->getItemFactory(),
                    $path_allocator,
                    $current_user_provider,
                ),
                new FileBeingUploadedWriter(
                    $path_allocator,
                    DBFactory::getMainTuleapDBConnection()
                ),
                new VersionUploadFinisher(
                    BackendLogger::getDefaultLogger(),
                    $path_allocator,
                    $this->getItemFactory(),
                    new Docman_VersionFactory(),
                    $event_manager,
                    $version_to_upload_dao,
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    new Docman_FileStorage($root_path),
                    new Docman_MIMETypeDetector(),
                    UserManager::instance(),
                    new DocmanItemsEventAdder($event_manager),
                    ProjectManager::instance(),
                    $this->getDocmanLockFactory(),
                    new ApprovalTableUpdater($approval_table_retriever, new Docman_ApprovalTableFactoriesFactory()),
                    $approval_table_retriever,
                    new ApprovalTableUpdateActionChecker($approval_table_retriever)
                ),
                new VersionUploadCanceler($path_allocator, $version_to_upload_dao),
                new FileBeingUploadedLocker($path_allocator)
            ),
            $current_user_provider,
        );
    }

    public function routeLegacyRestoreDocumentsController(): LegacyRestoreDocumentsController
    {
        return new LegacyRestoreDocumentsController($this);
    }

    public function routeLegacySendMessageController(): LegacySendMessageController
    {
        return new LegacySendMessageController(ProjectManager::instance());
    }

    public function routeLegacyController(): DocmanLegacyController
    {
        return new DocmanLegacyController(
            $this,
            new ExternalLinkParametersExtractor(),
            $this->getItemDao()
        );
    }

    public function routeFileDownload(): DocmanFileDownloadController
    {
        $response_factory             = HTTPFactoryBuilder::responseFactory();
        $rest_current_user_middleware = new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication());
        return new DocmanFileDownloadController(
            new SapiStreamEmitter(),
            new Docman_ItemFactory(),
            new DocmanFileDownloadResponseGenerator(
                new Docman_VersionFactory(),
                new BinaryFileResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory())
            ),
            $rest_current_user_middleware,
            BackendLogger::getDefaultLogger(),
            new SessionWriteCloseMiddleware(),
            $rest_current_user_middleware,
            new TuleapRESTCORSMiddleware(),
            new DocmanFileDownloadCORS($response_factory)
        );
    }

    public function routeGet(): DocumentTreeController
    {
        $history_enforcement_settings_builder = new HistoryEnforcementSettingsBuilder();
        $settings_DAO                         = new SettingsDAO();
        $filename_pattern_retriever           = new FilenamePatternRetriever($settings_DAO);

        return new DocumentTreeController(
            $this->getProjectExtractor(),
            $this->getPluginInfo(),
            new FileDownloadLimitsBuilder(),
            new ModalDisplayer(
                $filename_pattern_retriever,
                $history_enforcement_settings_builder->build()
            ),
            $filename_pattern_retriever,
            new ProjectFlagsBuilder(new ProjectFlagsDao()),
            new Docman_ItemDao(),
            new ListOfSearchCriterionPresenterBuilder(
                new SearchCriteriaDao(),
            ),
            new ListOfSearchColumnDefinitionPresenterBuilder(
                new SearchColumnCollectionBuilder(),
                new SearchColumnsDao(),
            ),
            new ForbidWritersSettings($settings_DAO)
        );
    }
    public function routeDownloadFolderAsZip(): DocumentFolderZipStreamer
    {
        return new DocumentFolderZipStreamer(
            new BinaryFileResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            $this->getProjectExtractor(),
            UserManager::instance(),
            new ZipStreamerLoggingHelper(),
            new ZipStreamMailNotificationSender(),
            new FolderSizeIsAllowedChecker(
                new ComputeFolderSizeVisitor(),
            ),
            new FileDownloadLimitsBuilder(),
            new SapiEmitter(),
            new SessionWriteCloseMiddleware(),
            new ServiceInstrumentationMiddleware('document')
        );
    }

    public function routeAdminSearch(): SearchView
    {
        $search_criteria_dao = new SearchCriteriaDao();
        return new SearchView(
            $this->getProjectExtractor(),
            new SearchColumnFilter(new SearchColumnCollectionBuilder(), new SearchColumnsDao()),
            new SearchCriteriaFilter(
                new ListOfSearchCriterionPresenterBuilder($search_criteria_dao),
                $search_criteria_dao
            )
        );
    }

    public function routeUpdateAdminSearch(): UpdateSearchView
    {
        return new UpdateSearchView(
            $this->getProjectExtractor(),
            new SearchColumnsDao(),
            new SearchCriteriaDao(),
        );
    }

    public function routeSendRequestMail(): PermissionDeniedDocumentMailSender
    {
        return new PermissionDeniedDocumentMailSender(
            new PlaceHolderBuilder(ProjectManager::instance()),
            new CSRFSynchronizerToken('plugin-document')
        );
    }

    public function routeGetDocumentSettingsNewUI(): FilesDownloadLimitsAdminController
    {
        return FilesDownloadLimitsAdminController::buildSelf();
    }

    public function routePostDocumentSettingsNewUI(): FilesDownloadLimitsAdminSaveController
    {
        return FilesDownloadLimitsAdminSaveController::buildSelf();
    }

    public function routeGetHistoryEnforcementSettingsNewUI(): HistoryEnforcementAdminController
    {
        return HistoryEnforcementAdminController::buildSelf();
    }

    public function routePostHistoryEnforcementSettingsNewUI(): HistoryEnforcementAdminSaveController
    {
        return HistoryEnforcementAdminSaveController::buildSelf();
    }

    public function routeGetOwners(): OwnerRequestHandler
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        return new OwnerRequestHandler(
            new AllOwnerRetriever(
                new OwnerDao(),
                $this->getUserManager(),
                UserHelper::instance()
            ),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            ),
            $this->getUserManager(),
            $this->getProjectExtractor(),
            $response_factory,
            $stream_factory,
            new JSONResponseBuilder($response_factory, $stream_factory),
            new SapiEmitter()
        );
    }

    private function getProjectExtractor(): DocumentTreeProjectExtractor
    {
        return new DocumentTreeProjectExtractor(ProjectManager::instance());
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $route_collector = $event->getRouteCollector();

        $route_collector->addRoute(['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'], '/uploads/docman/file/{id:\d+}', $this->getRouteHandler('routeUploadsDocmanFile'));
        $route_collector->addRoute(['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'], '/uploads/docman/version/{id:\d+}', $this->getRouteHandler('routeUploadsVersionFile'));

        $route_collector->addRoute(['GET'], self::ADMIN_BASE_URL . "/files-upload-limits", $this->getRouteHandler('routeGetDocumentSettings'));
        $route_collector->addRoute(['POST'], self::ADMIN_BASE_URL . "/files-upload-limits", $this->getRouteHandler('routePostDocumentSettings'));

        $route_collector->addGroup('/plugins/docman', function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/restore_documents.php', $this->getRouteHandler('routeLegacyRestoreDocumentsController'));
            $r->post('/sendmessage.php', $this->getRouteHandler('routeLegacySendMessageController'));
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));
            $r->get('/download/{file_id:\d+}[/{version_id:\d+}]', $this->getRouteHandler('routeFileDownload'));
        });

        $event->getRouteCollector()->addGroup('/plugins/document', function (RouteCollector $r) {
            $r->post(
                '/PermissionDeniedRequestMessage/{project_id:\d+}',
                $this->getRouteHandler('routeSendRequestMail')
            );
            $r->get(
                '/{project_name:[A-z0-9-]+}/folders/{folder_id:\d+}/download-folder-as-zip',
                $this->getRouteHandler('routeDownloadFolderAsZip')
            );
            $r->get(
                '/{project_name:[A-z0-9-]+}/admin-search',
                $this->getRouteHandler('routeAdminSearch')
            );
            $r->post(
                '/{project_name:[A-z0-9-]+}/admin-search',
                $this->getRouteHandler('routeUpdateAdminSearch')
            );
            $r->get('/{project_name:[A-z0-9-]+}/owners', $this->getRouteHandler('routeGetOwners'));
            $r->get('/{project_name:[A-z0-9-]+}/[{vue-routing:.*}]', $this->getRouteHandler('routeGet'));
        });

        $event->getRouteCollector()->addGroup(self::ADMIN_BASE_URL, function (RouteCollector $r) {
            $r->get('/files-download-limits', $this->getRouteHandler('routeGetDocumentSettingsNewUI'));
            $r->post('/files-download-limits', $this->getRouteHandler('routePostDocumentSettingsNewUI'));
            $r->get('/history-enforcement', $this->getRouteHandler('routeGetHistoryEnforcementSettingsNewUI'));
            $r->post('/history-enforcement', $this->getRouteHandler('routePostHistoryEnforcementSettingsNewUI'));
        });
    }

    public function routeGetDocumentSettings(): DocmanFilesUploadLimitsAdminController
    {
        return new DocmanFilesUploadLimitsAdminController(
            new AdminPageRenderer(),
        );
    }

    public function routePostDocumentSettings(): DocmanFilesUploadLimitsAdminSaveController
    {
        return new DocmanFilesUploadLimitsAdminSaveController(
            new DocumentFilesUploadLimitsSaver(
                new ConfigDao()
            )
        );
    }

    private function cleanUnusedResources()
    {
        $this->cleanUnusedDocumentResources();
        $this->cleanUnusedVersionResources();
    }

    private function cleanUnusedDocumentResources(): void
    {
        $cleaner = new DocumentUploadCleaner(
            (new UploadPathAllocatorBuilder())->getDocumentUploadPathAllocator(),
            new DocumentOngoingUploadDAO()
        );
        $cleaner->deleteDanglingDocumentToUpload(new DateTimeImmutable());
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-docman', 'Document'),
                self::ADMIN_BASE_URL . '/files-upload-limits'
            )
        );
    }

    private function cleanUnusedVersionResources(): void
    {
        $cleaner = new VersionUploadCleaner(
            (new UploadPathAllocatorBuilder())->getVersionUploadPathAllocator(),
            new DocumentOnGoingVersionToUploadDAO()
        );
        $cleaner->deleteDanglingVersionToUpload(new DateTimeImmutable());
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(self::class);
        $event->addConfigClass(SwitchToOldUi::class);
    }

    public function getItemsReferencingWikiPageCollectionEvent(GetItemsReferencingWikiPageCollectionEvent $event): void
    {
        $wiki_page = $event->getWikiPage();

        $wikis_retriever = new DocmanWikisReferencingSameWikiPageRetriever(
            $this->getItemFactory($wiki_page->getGid()),
            Docman_PermissionsManager::instance($wiki_page->getGid())
        );

        $event->addItemsReferencingWikiPage(
            $wikis_retriever->retrieveWikiDocuments($wiki_page, $event->getUser())
        );
    }

    private function getDocmanLockFactory(): Docman_LockFactory
    {
        return new Docman_LockFactory(new Docman_LockDao(), new Docman_Log());
    }

    private function getProvider(Project $project): ILinkUrlProvider
    {
        return new DocumentLinkProvider(ServerHostname::HTTPSUrl(), $project);
    }

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $collector->addStatistics(
            dgettext('tuleap-document', 'Documents'),
            $this->getItemDao()->countDocument(),
            $this->getItemDao()->countDocumentAfter($collector->getTimestamp())
        );
    }

    private function getItemDao(): Docman_ItemDao
    {
        return new Docman_ItemDao();
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
        if (! $event->shouldExportAllData()) {
            return;
        }

        $project = $event->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $ugroup_retriever                   = new UGroupRetrieverWithLegacy(new UGroupManager());
        $project_ugroups_id_indexed_by_name = $ugroup_retriever->getProjectUgroupIds($project);

        $archive = $event->getArchive();
        $archive->addEmptyDir('documents');

        $export = new XMLExporter(
            $event->getLogger(),
            Docman_ItemFactory::instance($project->getGroupId()),
            new Docman_VersionFactory(),
            UserManager::instance(),
            $event->getUserXMLExporter(),
            new PermissionsExporter(
                new PermissionsExporterDao(),
                $project_ugroups_id_indexed_by_name
            )
        );
        $export->export($project, $event->getIntoXml(), $archive);
    }

    /** @see Event::IMPORT_XML_PROJECT */
    public function importXmlProject(array $params): void
    {
        if (empty($params['xml_content']->docman)) {
            return;
        }
        $root_path = $this->getPluginInfo()->getPropertyValueForName('docman_root');

        $logger = new WrapperLogger($params['logger'], 'docman');
        $logger->info('Start import');

        $user_finder = $params['user_finder'];
        assert($user_finder instanceof IFindUserFromXMLReference);

        $current_user = UserManager::instance()->getCurrentUser();
        assert($current_user !== null);

        $project             = $params['project'];
        $docman_item_factory = Docman_ItemFactory::instance($project->getGroupId());
        $current_date        = new DateTimeImmutable();
        $xml_importer        = new XMLImporter(
            $docman_item_factory,
            $project,
            $logger,
            new NodeImporter(
                new ItemImporter(
                    new PermissionsImporter(
                        $logger,
                        PermissionsManager::instance(),
                        new UGroupRetrieverWithLegacy(new UGroupManager()),
                        $project
                    ),
                    $docman_item_factory
                ),
                new PostFileImporter(
                    new VersionImporter(
                        $user_finder,
                        new Docman_VersionFactory(),
                        new Docman_FileStorage($root_path),
                        $project,
                        $params['extraction_path'],
                        $current_date,
                        $current_user
                    ),
                    $logger
                ),
                new PostFolderImporter(),
                new PostDoNothingImporter(),
                $logger,
                new ImportPropertiesExtractor($current_date, $current_user, $user_finder)
            ),
            new XML_RNGValidator()
        );
        $xml_importer->import($params['xml_content']->docman);

        $logger->info('Import completed');
    }
}
