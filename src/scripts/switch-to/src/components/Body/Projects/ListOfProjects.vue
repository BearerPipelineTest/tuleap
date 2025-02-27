<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="switch-to-projects-container" v-if="should_be_displayed">
        <template v-if="has_projects">
            <h2
                class="tlp-modal-subtitle switch-to-modal-body-title"
                id="switch-to-modal-projects-title"
                v-translate
            >
                My projects
            </h2>
            <nav
                class="switch-to-projects"
                aria-labelledby="switch-to-modal-projects-title"
                v-if="has_filtered_projects"
            >
                <project-link
                    v-for="project of filtered_projects"
                    v-bind:key="project.project_uri"
                    v-bind:project="project"
                    v-bind:has_programmatically_focus="hasProgrammaticallyFocus(project)"
                />
            </nav>
            <trove-cat-link
                class="switch-to-projects-softwaremap"
                v-if="should_softwaremap_link_be_displayed"
            >
                {{ trove_cat_label }}
            </trove-cat-link>
        </template>
        <projects-empty-state v-else />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import ProjectLink from "./ProjectLink.vue";
import type { Project, UserHistoryEntry } from "../../../type";
import ProjectsEmptyState from "./ProjectsEmptyState.vue";
import TroveCatLink from "../TroveCatLink.vue";
import { useSwitchToStore } from "../../../stores";

@Component({
    components: { TroveCatLink, ProjectLink, ProjectsEmptyState },
})
export default class ListOfProjects extends Vue {
    get projects(): Project[] {
        return useSwitchToStore().projects;
    }

    get filtered_projects(): Project[] {
        return useSwitchToStore().filtered_projects;
    }

    get programmatically_focused_element(): Project | UserHistoryEntry | null {
        return useSwitchToStore().programmatically_focused_element;
    }

    get filter_value(): string {
        return useSwitchToStore().filter_value;
    }

    hasProgrammaticallyFocus(project: Project): boolean {
        return project === this.programmatically_focused_element;
    }

    get trove_cat_label(): string {
        return this.$gettext("Browse all…");
    }

    get has_projects(): boolean {
        return this.projects.length > 0;
    }

    get has_filtered_projects(): boolean {
        return this.filtered_projects.length > 0;
    }

    get should_be_displayed(): boolean {
        return this.filter_value === "" || this.has_filtered_projects;
    }

    get should_softwaremap_link_be_displayed(): boolean {
        return this.filter_value === "";
    }
}
</script>
