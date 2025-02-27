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

namespace Tuleap\OnlyOffice\Administration;

use Psr\Log\LoggerInterface;

final class OnlyOfficeAvailabilityChecker implements CheckOnlyOfficeIsAvailable
{
    public function __construct(
        private \PluginManager $plugin_manager,
        private \onlyofficePlugin $onlyoffice_plugin,
        private LoggerInterface $logger,
    ) {
    }

    public function isOnlyOfficeIntegrationAvailableForProject(\Project $project): bool
    {
        if (! \ForgeConfig::get(OnlyOfficeDocumentServerSettings::URL, '')) {
            $this->logger->debug(
                sprintf('Settings %s does not seem to be defined', OnlyOfficeDocumentServerSettings::URL)
            );

            return false;
        }

        if (! \ForgeConfig::exists(OnlyOfficeDocumentServerSettings::SECRET)) {
            $this->logger->debug(
                sprintf('Settings %s does not seem to be defined', OnlyOfficeDocumentServerSettings::SECRET)
            );

            return false;
        }

        if (! $project->usesService(\DocmanPlugin::SERVICE_SHORTNAME)) {
            return false;
        }

        if (! $this->plugin_manager->isPluginAllowedForProject($this->onlyoffice_plugin, (int) $project->getID())) {
            return false;
        }

        return true;
    }
}
