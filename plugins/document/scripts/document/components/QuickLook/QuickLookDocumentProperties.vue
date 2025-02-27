<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  -->

<template>
    <section class="tlp-pane-section document-quick-look-properties">
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label for="document-id" class="tlp-label" v-translate>Id</label>
                <p id="document-id">#{{ item.id }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-owner" class="tlp-label" v-translate>Owner</label>
                <p id="document-owner">
                    <user-badge v-bind:user="item.owner" />
                </p>
            </div>
            <template v-if="is_document">
                <quick-look-document-additional-properties
                    v-for="property in properties_right_column"
                    v-bind:property="property"
                    v-bind:key="property.name"
                    data-test="properties-right-list"
                />
            </template>
        </div>
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label class="tlp-label" v-translate>Creation</label>
                <tlp-relative-date
                    v-bind:date="item.creation_date"
                    v-bind:absolute-date="getFormattedDate(item.creation_date)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                >
                    {{ getFormattedDate(item.creation_date) }}
                </tlp-relative-date>
            </div>
            <div class="tlp-property">
                <label class="tlp-label" v-translate>Last update date</label>
                <tlp-relative-date
                    v-bind:date="item.last_update_date"
                    v-bind:absolute-date="getFormattedDate(item.last_update_date)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                >
                    {{ getFormattedDate(item.last_update_date) }}
                </tlp-relative-date>
            </div>
            <div
                class="tlp-property"
                v-if="has_an_approval_table"
                data-test="docman-item-approval-table-status-badge"
            >
                <label for="document-approval-table-status" class="tlp-label">
                    {{ $gettext("Approval table status") }}
                </label>
                <approval-badge
                    id="document-approval-table-status"
                    v-bind:item="item"
                    v-bind:is-in-folder-content-row="false"
                />
            </div>
            <div v-if="is_file" class="tlp-property">
                <label for="document-file-size" class="tlp-label">
                    {{ $gettext("File size") }}
                </label>
                <p id="document-file-size" data-test="docman-file-size">
                    {{ file_size_in_mega_bytes }}
                </p>
            </div>
            <template v-if="is_document">
                <quick-look-document-additional-properties
                    v-for="property in properties_left_column"
                    v-bind:property="property"
                    v-bind:key="property.name"
                    data-test="properties-left-list"
                />
            </template>
        </div>
    </section>
</template>

<script setup lang="ts">
import prettyBytes from "pretty-kibibytes";
import { formatDateUsingPreferredUserFormat } from "../../helpers/date-formatter";
import UserBadge from "../User/UserBadge.vue";
import QuickLookDocumentAdditionalProperties from "./QuickLookDocumentAdditionalProperties.vue";
import ApprovalBadge from "../Folder/ApprovalTables/ApprovalBadge.vue";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { isFile, isFolder } from "../../helpers/type-check-helper";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../store/configuration";
import type { Item, Property } from "../../type";
import { computed } from "vue";
import { hasAnApprovalTable } from "../../helpers/approval-table-helper";

const { date_time_format, relative_dates_display, user_locale } = useState<
    Pick<ConfigurationState, "date_time_format" | "relative_dates_display" | "user_locale">
>("configuration", ["date_time_format", "relative_dates_display", "user_locale"]);

const props = defineProps<{ item: Item }>();

const get_custom_properties = computed((): Array<Property> => {
    const hardcoded_properties = ["title", "description", "owner", "create_date", "update_date"];

    return props.item.properties.filter(
        ({ short_name }) => !hardcoded_properties.includes(short_name)
    );
});

const properties_right_column = computed((): Array<Property> => {
    const length = get_custom_properties.value.length;

    return get_custom_properties.value.slice(0, Math.ceil(length / 2));
});
const properties_left_column = computed((): Array<Property> => {
    const length = get_custom_properties.value.length;

    return get_custom_properties.value.slice(Math.ceil(length / 2), length);
});
const file_size_in_mega_bytes = computed((): string => {
    const item = props.item;
    if (!isFile(item) || !item.file_properties) {
        return prettyBytes(0);
    }
    return prettyBytes(item.file_properties.file_size);
});
const has_an_approval_table = computed((): boolean => {
    return hasAnApprovalTable(props.item);
});

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_dates_display.value);
});
const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_dates_display.value, "right");
});

const is_file = computed((): boolean => {
    return isFile(props.item);
});

const is_document = computed((): boolean => {
    return !isFolder(props.item);
});

function getFormattedDate(date: string): string {
    return formatDateUsingPreferredUserFormat(date, date_time_format.value);
}
</script>
