<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference\ByNature\FRS;

use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use ProjectManager;
use Tuleap\Reference\CrossReferenceSectionPresenter;

class CrossReferenceFRSOrganizer
{
    /**
     * @var \FRSPackageFactory
     */
    private $package_factory;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var \FRSReleaseFactory
     */
    private $release_factory;

    public function __construct(
        \FRSPackageFactory $package_factory,
        \FRSReleaseFactory $release_factory,
        ProjectManager $project_manager
    ) {
        $this->package_factory = $package_factory;
        $this->project_manager = $project_manager;
        $this->release_factory = $release_factory;
    }

    public function organizeFRSReleaseReference(
        CrossReferencePresenter $cross_reference_presenter,
        CrossReferenceByNatureOrganizer $by_nature_organizer
    ): void {
        $project    = $this->project_manager->getProject($cross_reference_presenter->target_gid);
        $release_id = (int) $cross_reference_presenter->target_value;
        $release    = $this->release_factory->getFRSReleaseFromDb($release_id);

        if (! $release) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }

        if ($release->isHidden() || $release->getPackage()->isHidden()) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }

        $user_can_read_package = $this->package_factory->userCanRead(
            $cross_reference_presenter->target_gid,
            $release->getPackageID(),
            $by_nature_organizer->getCurrentUser()->getId()
        );

        if (! $user_can_read_package) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }

        $user_can_read_release = $this->release_factory->userCanRead(
            $cross_reference_presenter->target_gid,
            $release->getPackageID(),
            $release_id,
            $by_nature_organizer->getCurrentUser()->getId()
        );

        if (! $user_can_read_release) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);
            return;
        }
        $by_nature_organizer->moveCrossReferenceToSection(
            $project,
            $cross_reference_presenter,
            CrossReferenceSectionPresenter::UNLABELLED
        );
    }
}
