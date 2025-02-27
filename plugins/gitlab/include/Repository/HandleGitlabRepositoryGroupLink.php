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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use Project;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Gitlab\Group\GitlabGroupAlreadyExistsException;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;

interface HandleGitlabRepositoryGroupLink
{
    /**
     * @param GitlabProject[] $gitlab_projects
     *
     * @throws GitlabGroupAlreadyExistsException
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    public function integrateGitlabRepositoriesInProject(
        Credentials $credentials,
        array $gitlab_projects,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
        GitlabGroupApiDataRepresentation $api_data_representation,
    ): GitlabGroupRepresentation;
}
