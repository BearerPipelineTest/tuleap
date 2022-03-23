/*
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { CurrentArtifactIdentifier } from "src/domain/CurrentArtifactIdentifier";
import type { ArtifactCrossReference } from "../../domain/ArtifactCrossReference";
import type { TrackerShortname } from "../../domain/TrackerShortname";

export const TrackerArtifactCrossReferenceProxy = {
    fromArtifactIdentifierAndTracker: (
        current_artifact_identifier: CurrentArtifactIdentifier | null,
        tracker_shortname: TrackerShortname
    ): ArtifactCrossReference | null => {
        if (current_artifact_identifier === null) {
            return null;
        }

        return { ref: `${tracker_shortname.shortname} #${current_artifact_identifier.id}` };
    },
};
