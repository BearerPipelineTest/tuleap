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
    <input
        id="switch-to-filter"
        type="search"
        name="words"
        v-bind:placeholder="placeholder"
        v-bind:value="filter_value"
        v-on:keyup="update"
        autocomplete="off"
        data-test="switch-to-filter"
    />
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { Modal } from "tlp";
import { EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { useSwitchToStore } from "../../stores";

@Component
export default class SwitchToFilter extends Vue {
    @Prop({ required: true })
    private readonly modal!: Modal | null;

    mounted(): void {
        this.listenToHideModalEvent();
    }

    @Watch("modal")
    listenToHideModalEvent(): void {
        if (this.modal) {
            this.modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, this.clearInput);
        }
    }

    beforeDestroy(): void {
        if (this.modal) {
            this.modal.removeEventListener(EVENT_TLP_MODAL_HIDDEN, this.clearInput);
        }
    }

    clearInput(): void {
        const store = useSwitchToStore();
        if (store.filter_value !== "") {
            store.updateFilterValue("");
        }
    }

    update(event: KeyboardEvent): void {
        if (event.key === "Escape") {
            if (this.modal) {
                this.modal.hide();
            }
            this.clearInput();
            return;
        }

        if (event.target instanceof HTMLInputElement) {
            const store = useSwitchToStore();
            store.updateFilterValue(event.target.value);
        }
    }

    get filter_value(): string {
        return useSwitchToStore().filter_value;
    }

    get placeholder(): string {
        return this.$gettext("Project, recent item, …");
    }
}
</script>
