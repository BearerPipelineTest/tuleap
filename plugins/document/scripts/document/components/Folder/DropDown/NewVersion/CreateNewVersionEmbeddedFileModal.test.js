/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as tlp from "tlp";
import CreateNewVersionEmbeddedFileModal from "./CreateNewVersionEmbeddedFileModal.vue";
import emitter from "../../../../helpers/emitter";

jest.mock("tlp");

describe("CreateNewVersionEmbeddedFileModal", () => {
    const add_event_listener = jest.fn();
    const modal_show = jest.fn();
    const remove_backdrop = jest.fn();

    function getWrapper(prop) {
        const state = {
            error: { has_modal_error: false },
        };
        const store_option = { state };
        const store = createStoreMock(store_option);

        store.dispatch.mockImplementation((action) => {
            if (action === "loadDocument") {
                return Promise.resolve({
                    id: 12,
                    title: "Dacia",
                    embedded_file_properties: {
                        content: "VROOM VROOM",
                    },
                });
            }
        });
        return shallowMount(CreateNewVersionEmbeddedFileModal, {
            localVue,
            propsData: {
                ...prop,
            },
            mocks: { $store: store },
        });
    }

    beforeEach(() => {
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return {
                addEventListener: add_event_listener,
                show: modal_show,
                removeBackdrop: remove_backdrop,
            };
        });
    });

    it("Updates the version title", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        expect(wrapper.vm.$data.version.title).toBe("");
        emitter.emit("update-version-title", "A title");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.version.title).toBe("A title");
    });

    it("Updates the changelog", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        expect(wrapper.vm.$data.version.changelog).toBe("");
        emitter.emit("update-changelog-property", "A changelog");

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.version.changelog).toBe("A changelog");
    });

    it("Updates the lock", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.version.is_file_locked).toBe(true);
        emitter.emit("update-lock", false);

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.version.is_file_locked).toBe(false);
    });

    it("should not retrieve the document content if there is content when the component is mounted", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: { content: "Time or ..." } },
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.embedded_item.embedded_file_properties.content).toBe("Time or ...");
    });

    it("should retrieve the document content if there is no content when the component is mounted", async () => {
        const wrapper = getWrapper({
            item: { id: 12, title: "Dacia", embedded_file_properties: {} },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$data.embedded_item.embedded_file_properties.content).toBe("VROOM VROOM");
    });
});
