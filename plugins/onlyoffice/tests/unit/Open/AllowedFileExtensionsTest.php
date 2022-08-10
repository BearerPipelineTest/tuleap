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

use Tuleap\Test\PHPUnit\TestCase;

final class AllowedFileExtensionsTest extends TestCase
{
    /**
     * @dataProvider getExtensionsToTest
     */
    public function testIsFilenameAllowedToBeOpenInOnlyOffice(string $ext, bool $expected): void
    {
        self::assertEquals(
            $expected,
            AllowedFileExtensions::isFilenameAllowedToBeOpenInOnlyOffice("myfile.$ext"),
            "Mismatch for .{$ext} extension",
        );
    }

    public function getExtensionsToTest(): array
    {
        return [
            ['csv', true],
            ['doc', true],
            ['docm', true],
            ['docx', true],
            ['docxf', true],
            ['oform', true],
            ['dot', true],
            ['dotx', true],
            ['epub', true],
            ['htm', true],
            ['html', true],
            ['odp', true],
            ['ods', true],
            ['odt', true],
            ['otp', true],
            ['ots', true],
            ['ott', true],
            ['pdf', true],
            ['pot', true],
            ['potm', true],
            ['potx', true],
            ['pps', true],
            ['ppsm', true],
            ['ppsx', true],
            ['ppt', true],
            ['pptm', true],
            ['pptx', true],
            ['rtf', true],
            ['txt', true],
            ['xls', true],
            ['xlsm', true],
            ['xlsx', true],
            ['xlt', true],
            ['xltm', true],
            ['xltx', true],
            ['jpg', false],
            ['gif', false],
            ['md', false],
        ];
    }
}
