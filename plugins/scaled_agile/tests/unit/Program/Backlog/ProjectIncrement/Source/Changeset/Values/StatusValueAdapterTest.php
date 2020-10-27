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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldData;

final class StatusValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldData
     */
    private $status_field_data;

    /**
     * @var \Tracker_FormElement_Field_Selectbox
     */
    private $status_field;
    protected function setUp(): void
    {
        $this->status_field       = new \Tracker_FormElement_Field_Selectbox(
            1,
            10,
            null,
            'status',
            'status',
            '',
            true,
            null,
            true,
            true,
            1
        );
        $this->status_field_data = new FieldData($this->status_field);
    }

    public function testItThrowsWhenStatusValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->status_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new StatusValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->status_field_data, $source_changeset);
    }

    public function testItBuildStatusValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $bind_values = new Tracker_FormElement_Field_List_Bind_StaticValue(1, "Planned", "planned", 1, false);
        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class);
        $changset_value->shouldReceive('getListValues')->once()->andReturn([$bind_values]);

        $source_changeset->shouldReceive('getValue')->with($this->status_field)->andReturn($changset_value);

        $adapter = new StatusValueAdapter();

        $expected_data = new StatusValueData([$bind_values]);

        $data = $adapter->build($this->status_field_data, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }
}
