<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;

require_once __DIR__ . '/../../../../bootstrap.php';

final class ChangesCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ChangesChecker
     */
    private $checker;

    private $old_value;

    protected function setUp(): void
    {
        parent::setUp();

        $this->old_value = Mockery::mock(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact::class);
        $this->checker   = new ChangesChecker();
    }

    public function testShouldBeTrueIfPermissionsOnArtifactAreNowUsed(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("0");

        $new_values = [
            'use_artifact_permissions' => "1",
            "u_groups" => [3],
        ];

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueIfUgroupsSelectedForPermissionsOnArtifactChanged(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("1");
        $this->old_value->shouldReceive('getPerms')->andReturn([2]);

        $new_values = [
            'use_artifact_permissions' => "1",
            "u_groups" => [3],
        ];

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueIfNoUgroupsSelectedForPermissionsOnArtifactChanged(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("1");
        $this->old_value->shouldReceive('getPerms')->andReturn([2]);

        $new_values = [
            'use_artifact_permissions' => "1",
        ];

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueIfUgroupsSelectedForPermissionsOnArtifactChangedButNoUgroupInLastChangeset(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("1");
        $this->old_value->shouldReceive('getPerms')->andReturn([]);

        $new_values = [
            'use_artifact_permissions' => "1",
            "u_groups" => [3],
        ];

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeFalseIfNothingChanged(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("1");
        $this->old_value->shouldReceive('getPerms')->andReturn([3]);

        $new_values = [
            'use_artifact_permissions' => "1",
            "u_groups" => [3],
        ];

        $this->assertFalse($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeTrueWhenOldValuesAreOnNewValuesWithOtherNewValues(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("1");
        $this->old_value->shouldReceive('getPerms')->andReturn([3]);

        $new_values = [
            'use_artifact_permissions' => "1",
            "u_groups" => [3, 4],
        ];

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_values));
    }

    public function testShouldBeFalseIfStillNotUsed(): void
    {
        $this->old_value->shouldReceive('getUsed')->andReturn("0");
        $this->old_value->shouldReceive('getPerms')->andReturn([]);

        $new_values = [
            'use_artifact_permissions' => "0",
        ];

        $this->assertFalse($this->checker->hasChanges($this->old_value, $new_values));
    }
}
