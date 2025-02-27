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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\DB\DBTransactionExecutor;

final class LocalSettingsInstantiator
{
    public function __construct(
        private LocalSettingsRepresentationBuilder $representation_builder,
        private LocalSettingsPersist $local_settings_persistor,
        private DBTransactionExecutor $transaction_executor,
    ) {
    }

    /**
     * @param \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED $site_access
     */
    public function instantiateLocalSettings(string $site_access): void
    {
        $this->transaction_executor->execute(
            fn () => $this->local_settings_persistor->persist(
                $this->representation_builder->generateTuleapLocalSettingsRepresentation($site_access)
            ),
        );
    }
}
