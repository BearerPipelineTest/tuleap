<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element" v-if="is_item_a_folder">
        <label class="tlp-label tlp-checkbox">
            <input
                type="checkbox"
                v-bind:value="value"
                v-on:input="$emit('input', $event.target.checked)"
            />
            <translate>Apply same permissions to all sub-items of this folder</translate>
        </label>
    </div>
</template>
<script>
import { isFolder } from "../../../helpers/type-check-helper";

export default {
    name: "PermissionsUpdateFolderSubItems",
    props: {
        item: Object,
        value: Boolean,
    },
    computed: {
        is_item_a_folder() {
            return isFolder(this.item);
        },
    },
};
</script>
