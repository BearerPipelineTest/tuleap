<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\OnlyOffice\Administration\CheckOnlyOfficeIsAvailable;
use Tuleap\Project\ProjectByIDFactory;

class DocmanFileLastVersionToOnlyOfficeDocumentTransformer implements TransformDocmanFileLastVersionToOnlyOfficeDocument
{
    public function __construct(
        private CheckOnlyOfficeIsAvailable $check_only_office_is_available,
        private ProjectByIDFactory $project_factory,
    ) {
    }

    /**
     * @psalm-return Ok<OnlyOfficeDocument>|Err<Fault>
     */
    public function transformToOnlyOfficeDocument(DocmanFileLastVersion $file_last_version): Ok|Err
    {
        $project = $this->project_factory->getProjectById((int) $file_last_version->item->getGroupId());
        if (! $this->check_only_office_is_available->isOnlyOfficeIntegrationAvailableForProject($project)) {
            return Result::err(
                Fault::fromMessage(
                    sprintf('ONLYOFFICE is not available for project %s', $project->getUnixNameMixedCase())
                )
            );
        }

        $filename = $file_last_version->version->getFilename();
        if (! AllowedFileExtensions::isFilenameAllowedToBeOpenInOnlyOffice($filename)) {
            return Result::err(
                Fault::fromMessage(
                    sprintf('Item #%d cannot be opened with ONLYOFFICE', $file_last_version->item->getId())
                )
            );
        }

        return Result::ok(
            new OnlyOfficeDocument(
                $project,
                $file_last_version->item,
                (int) $file_last_version->version->getId(),
                $filename
            )
        );
    }
}
