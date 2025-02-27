/*
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

import { errAsync } from "neverthrow";
import { ResultAsync } from "neverthrow";
import type { RetrieveLinkTypes } from "./RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "./RetrieveLinkedArtifactsByType";
import type { LinkedArtifact } from "./LinkedArtifact";
import type { RetrieveAllLinkedArtifacts } from "./RetrieveAllLinkedArtifacts";
import type { CurrentArtifactIdentifier } from "../../CurrentArtifactIdentifier";
import type { Fault } from "@tuleap/fault";
import { NoLinksInCreationModeFault } from "./NoLinksInCreationModeFault";
import type { AddLinkedArtifactCollection } from "./AddLinkedArtifactCollection";
import type { LinkType } from "./LinkType";

export const LinksRetriever = (
    types_retriever: RetrieveLinkTypes,
    artifacts_retriever: RetrieveLinkedArtifactsByType,
    links_adder: AddLinkedArtifactCollection
): RetrieveAllLinkedArtifacts => ({
    getLinkedArtifacts(
        current_artifact_identifier: CurrentArtifactIdentifier | null
    ): ResultAsync<readonly LinkedArtifact[], Fault> {
        if (current_artifact_identifier === null) {
            return errAsync(NoLinksInCreationModeFault());
        }
        return types_retriever
            .getAllLinkTypes(current_artifact_identifier)
            .andThen((link_types) => {
                const promises = link_types.map((type: LinkType) =>
                    artifacts_retriever.getLinkedArtifactsByLinkType(
                        current_artifact_identifier,
                        type
                    )
                );
                return ResultAsync.combine(promises).map((collections) => {
                    const all_links = collections.flat();
                    links_adder.addLinkedArtifacts(all_links);
                    return all_links;
                });
            });
    },
});
