<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-form-element document-search-criterion document-search-criterion-owner">
        <label class="tlp-label" v-bind:for="id">{{ criterion.label }}</label>
        <select class="tlp-input" v-bind:id="id" ref="owner_input" v-bind:data-test="id">
            <option
                selected
                v-if="currently_selected_user"
                v-bind:value="currently_selected_user.id"
            >
                {{ currently_selected_user.display_name }}
            </option>
            <slot />
        </select>
    </div>
</template>

<script setup lang="ts">
import type { Select2Plugin } from "tlp";
import type { SearchCriterionOwner } from "../../../type";
import { computed, onMounted, ref } from "vue";
import { autocomplete_users_for_select2 } from "@tuleap/autocomplete-for-select2";
import type { RestUser } from "../../../api/rest-querier";
import { retrieveSelectedOwner } from "../../../helpers/owner/retrieve-selected-owner";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";

const props = defineProps<{ criterion: SearchCriterionOwner; value: string }>();

const { project_name } = useState<Pick<ConfigurationState, "project_name">>("configuration", [
    "project_name",
]);

const owner_input = ref<InstanceType<typeof HTMLElement>>();
const select2_people_picker = ref<(Select2Plugin & { trigger(event: string): void }) | undefined>();
const currently_selected_user = ref<RestUser | undefined>();

const emit = defineEmits<{
    (e: "input", value: string): void;
}>();

onMounted(async (): Promise<void> => {
    const people_picker_element = owner_input.value;
    if (!(people_picker_element instanceof HTMLElement)) {
        return;
    }

    currently_selected_user.value = await retrieveSelectedOwner(props.value);

    if (select2_people_picker.value) {
        select2_people_picker.value.trigger("change");
    }

    const configuration = {
        data: currently_selected_user.value,
        codendiUserOnly: true,
        use_tuleap_id: true,
        ajax: {
            url: `/plugins/document/${encodeURIComponent(project_name.value)}/owners`,
            dataType: "json",
            delay: 250,
            data: (params: Record<string, unknown>) => ({
                name: params.term,
                page: 1,
            }),
        },
    };
    const options = {
        ...configuration,
        multiple: false,
    };

    select2_people_picker.value = autocomplete_users_for_select2(people_picker_element, options)
        .trigger("change")
        .on("select2:select", (e: { params: { data: RestUser } }): void => {
            const data = e.params.data;
            emit("input", data.username);
        })
        .on("select2:clear", (): void => {
            emit("input", "");
        });
});

const id = computed((): string => {
    return "document-criterion-owner-" + props.criterion.name;
});
</script>
