<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  -->

<template>
    <div class="document-quick-look-document-action">
        <button
            type="button"
            class="tlp-button-primary tlp-button-small document-quick-look-action-button-margin"
            v-on:click="wikiPageRedirect"
            data-test="go-to-the-wiki-page"
        >
            <i class="fas fa-long-arrow-alt-right tlp-button-icon"></i>
            <translate>Go to the wiki page</translate>
        </button>
        <drop-down-quick-look v-bind:item="item" />
        <div class="document-header-spacer"></div>
        <quick-look-delete-button v-bind:item="item" />
    </div>
</template>

<script setup lang="ts">
import DropDownQuickLook from "../Folder/DropDown/DropDownQuickLook.vue";
import QuickLookDeleteButton from "../Folder/ActionsQuickLookButton/QuickLookDeleteButton.vue";
import { useState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../store/configuration";
import type { Item } from "../../type";

const { project_id } = useState<Pick<ConfigurationState, "project_id">>("configuration", [
    "project_id",
]);

const props = defineProps<{ item: Item }>();

function wikiPageRedirect(): void {
    window.location.assign(
        encodeURI(`/plugins/docman/?group_id=${project_id.value}&action=show&id=${props.item.id}`)
    );
}
</script>
