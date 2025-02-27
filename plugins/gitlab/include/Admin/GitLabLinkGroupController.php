<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Admin;

use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class GitLabLinkGroupController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private ProjectByUnixNameFactory $project_manager,
        private EventDispatcherInterface $event_manager,
        private JavascriptAssetGeneric $assets,
        private HeaderRenderer $header_renderer,
        private Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        private GitPermissionsManager $git_permissions_manager,
        private TemplateRenderer $renderer,
    ) {
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        $user = $request->getCurrentUser();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new ForbiddenException(dgettext("tuleap-hudson_git", 'User is not Git administrator.'));
        }

        $layout->addJavascriptAsset($this->assets);

        $this->header_renderer->renderServiceAdministrationHeader(
            $request,
            $user,
            $project
        );

        $event = new GitAdminGetExternalPanePresenters($project, GitLabLinkGroupTabPresenter::PANE_NAME);
        $this->event_manager->dispatch($event);

        $this->renderer->renderToPage(
            'git-administration-gitlab-link-group',
            new GitLabLinkGroupPanePresenter(
                $project,
                ! empty($this->mirror_data_mapper->fetchAllForProject($project)),
                $event->getExternalPanePresenters(),
            )
        );

        $layout->footer([]);
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        return $project;
    }
}
