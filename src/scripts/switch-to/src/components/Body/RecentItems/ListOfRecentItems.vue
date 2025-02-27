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
        class="switch-to-recent-items"
        data-test="switch-to-recent-items"
        v-if="should_be_displayed"
    >
        <template v-if="has_history">
            <h2
                class="tlp-modal-subtitle switch-to-modal-body-title"
                id="switch-to-modal-recent-items-title"
                v-translate
            >
                Recent items
            </h2>
            <nav
                class="switch-to-recent-items-list"
                aria-labelledby="switch-to-modal-recent-items-title"
                v-if="has_filtered_history"
            >
                <recent-items-entry
                    v-for="entry of filtered_history.entries"
                    v-bind:key="entry.html_url"
                    v-bind:entry="entry"
                    v-bind:has_programmatically_focus="hasProgrammaticallyFocus(entry)"
                />
            </nav>
            <p class="switch-to-modal-no-matching-history" v-else>
                <translate>You didn't visit recently any items matching your query.</translate>
            </p>
        </template>
        <recent-items-error-state v-if="is_history_in_error" />
        <recent-items-loading-state v-else-if="is_loading_history" />
        <recent-items-empty-state v-else-if="has_no_history" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import RecentItemsEmptyState from "./RecentItemsEmptyState.vue";
import RecentItemsLoadingState from "./RecentItemsLoadingState.vue";
import RecentItemsEntry from "./RecentItemsEntry.vue";
import type { UserHistory, UserHistoryEntry } from "../../../type";
import RecentItemsErrorState from "./RecentItemsErrorState.vue";
import { useSwitchToStore } from "../../../stores";

@Component({
    components: {
        RecentItemsErrorState,
        RecentItemsEmptyState,
        RecentItemsLoadingState,
        RecentItemsEntry,
    },
})
export default class ListOfRecentItems extends Vue {
    get is_loading_history(): boolean {
        return useSwitchToStore().is_loading_history;
    }

    get is_history_loaded(): boolean {
        return useSwitchToStore().is_history_loaded;
    }

    get is_history_in_error(): boolean {
        return useSwitchToStore().is_history_in_error;
    }

    get history(): UserHistory {
        return useSwitchToStore().history;
    }

    get filtered_history(): UserHistory {
        return useSwitchToStore().filtered_history;
    }

    get filter_value(): string {
        return useSwitchToStore().filter_value;
    }

    hasProgrammaticallyFocus(entry: UserHistoryEntry): boolean {
        return entry === useSwitchToStore().programmatically_focused_element;
    }

    get has_no_history(): boolean {
        if (!this.is_history_loaded) {
            return false;
        }

        return this.history.entries.length === 0;
    }

    get has_history(): boolean {
        if (this.is_history_in_error) {
            return false;
        }

        if (!this.is_history_loaded) {
            return false;
        }

        return this.history.entries.length > 0;
    }

    get has_filtered_history(): boolean {
        if (!this.is_history_loaded) {
            return false;
        }

        return this.filtered_history.entries.length > 0;
    }

    get should_be_displayed(): boolean {
        return this.filter_value === "" || this.has_filtered_history;
    }
}
</script>
