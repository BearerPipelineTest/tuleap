<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div>
        <fake-caret v-bind:item="item" />
        <i class="fa fa-fw document-folder-content-icon" v-bind:class="ICON_WIKI"></i>
        <a
            v-bind:href="wiki_url"
            class="document-folder-subitem-link"
            draggable="false"
            data-test="wiki-document-link"
        >
            {{ item.title }}
        </a>
    </div>
</template>

<script setup lang="ts">
import FakeCaret from "./FakeCaret.vue";
import { ICON_WIKI } from "../../../constants";
import type { Item } from "../../../type";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { computed } from "vue";

const props = defineProps<{ item: Item }>();

const { project_id } = useNamespacedState<Pick<ConfigurationState, "project_id">>("configuration", [
    "project_id",
]);

const wiki_url = computed((): string => {
    return `/plugins/docman/?group_id=${project_id.value}&action=show&id=${props.item.id}`;
});
</script>
