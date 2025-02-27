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

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\MediawikiStandalone\Configuration\LocalSettingsInstantiator;
use Tuleap\Queue\EnqueueTaskInterface;

final class SiteAccessHandler
{
    public function __construct(
        private LocalSettingsInstantiator $local_settings_instantiator,
        private EnqueueTaskInterface $enqueue_task,
    ) {
    }

    /**
     * @param \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED $site_access
     */
    public function process(string $site_access): void
    {
        $this->local_settings_instantiator->instantiateLocalSettings($site_access);
        $this->enqueue_task->enqueue(LogUsersOutInstanceTask::logsOutUserOnAllInstances());
    }
}
