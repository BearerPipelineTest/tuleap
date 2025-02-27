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
    <div
        class="switch-to-projects-project"
        v-on:keydown="changeFocus"
        data-test="switch-to-projects-project"
    >
        <a
            v-bind:href="project.project_uri"
            class="switch-to-projects-project-link"
            ref="project_link"
            data-test="project-link"
        >
            <i
                class="fas fa-fw switch-to-projects-project-icon"
                v-bind:class="project_privacy_icon"
            ></i>
            <span class="switch-to-projects-project-label">
                <span v-if="project.icon" class="switch-to-projects-project-label-icon">
                    {{ project.icon }}
                </span>
                <word-highlighter
                    v-bind:highlight-class="'tlp-mark-on-dark-background'"
                    v-bind:query="filter_value"
                >
                    {{ project.project_name }}
                </word-highlighter>
            </span>
        </a>
        <a
            v-if="project.is_current_user_admin"
            v-bind:href="project.project_config_uri"
            class="switch-to-projects-project-admin-icon"
            v-bind:title="admin_title"
            data-test="switch-to-projects-project-admin-icon"
        >
            <i class="fa fa-cog" aria-hidden="true"></i>
        </a>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { Project } from "../../../type";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import { getProjectPrivacyIcon } from "@tuleap/project-privacy-helper";
import { sprintf } from "sprintf-js";
import WordHighlighter from "vue-word-highlighter";
import { useSwitchToStore } from "../../../stores";

@Component({
    components: { WordHighlighter },
})
export default class ProjectLink extends Vue {
    @Prop({ required: true })
    private readonly project!: Project;

    @Prop({ required: true })
    private readonly has_programmatically_focus!: boolean;

    get filter_value(): string {
        return useSwitchToStore().filter_value;
    }

    @Watch("has_programmatically_focus")
    forceFocus(): void {
        if (!this.has_programmatically_focus) {
            return;
        }

        const link = this.$refs.project_link;
        if (link instanceof HTMLAnchorElement) {
            link.focus();
        }
    }

    changeFocus(event: KeyboardEvent): void {
        switch (event.key) {
            case "ArrowUp":
            case "ArrowRight":
            case "ArrowDown":
            case "ArrowLeft":
                event.preventDefault();
                useSwitchToStore().changeFocusFromProject({
                    project: this.project,
                    key: event.key,
                });
                break;
            default:
        }
    }

    goToAdmin(event: Event): void {
        event.preventDefault();

        window.location.href = this.project.project_config_uri;
    }

    get project_privacy_icon(): string {
        const privacy: ProjectPrivacy = {
            project_is_public: this.project.is_public,
            project_is_private: this.project.is_private,
            project_is_private_incl_restricted: this.project.is_private_incl_restricted,
            project_is_public_incl_restricted: this.project.is_public_incl_restricted,
            are_restricted_users_allowed: useSwitchToStore().are_restricted_users_allowed,
            explanation_text: "",
            privacy_title: "",
        };

        return getProjectPrivacyIcon(privacy);
    }

    get admin_title(): string {
        return sprintf(
            this.$gettext("Go to project administration of %s"),
            this.project.project_name
        );
    }
}
</script>
