<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlannedIterations;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;

class DisplayPlanIterationsPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuilds(): void
    {
        $presenter = DisplayPlanIterationsPresenter::fromPlannedIterations(
            PlannedIterations::build(
                BuildProgramFlagsStub::withDefaults(),
                ProgramIdentifierBuilder::build()
            )
        );

        self::assertEquals('[{"label":"Top Secret","description":"For authorized eyes only"}]', $presenter->program_flags);
    }
}
