/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Mock } from "vitest";
import { describe, it, expect, beforeEach, vi } from "vitest";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { SelectionManager } from "../type";
import type { EventManager } from "./EventManager";
import { ListOptionsChangesObserver } from "./ListOptionsChangesObserver";
import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";

describe("ListOptionsChangesObserver", () => {
    let source_select_box: HTMLSelectElement,
        list_picker_element_attributes_updater: Mock,
        items_map_manager: ItemsMapManager,
        dropdown_content_renderer: DropdownContentRenderer,
        selection_manager: SelectionManager,
        event_manager: EventManager,
        list_options_changes_observer: ListOptionsChangesObserver;

    beforeEach(() => {
        source_select_box = document.createElement("select");
        list_picker_element_attributes_updater = vi.fn();
        dropdown_content_renderer = {
            renderAfterDependenciesUpdate: vi.fn(),
        } as unknown as DropdownContentRenderer;

        items_map_manager = {
            refreshItemsMap: vi.fn().mockReturnValue(Promise.resolve()),
        } as unknown as ItemsMapManager;

        selection_manager = {
            resetAfterDependenciesUpdate: vi.fn(),
        } as unknown as SelectionManager;

        event_manager = { attachItemListEvent: vi.fn() } as unknown as EventManager;

        list_options_changes_observer = new ListOptionsChangesObserver(
            source_select_box,
            list_picker_element_attributes_updater,
            items_map_manager,
            dropdown_content_renderer,
            selection_manager,
            event_manager
        );
    });

    it("should refresh the list-picker when options are added in the source <select>", async () => {
        list_options_changes_observer.startWatchingChanges();
        appendGroupedOptionsToSourceSelectBox(source_select_box);

        await new Promise(process.nextTick);

        await expect(items_map_manager.refreshItemsMap).toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).toHaveBeenCalled();
    });

    it("should refresh the list-picker when options are removed in the source <select>", async () => {
        appendSimpleOptionsToSourceSelectBox(source_select_box);
        list_options_changes_observer.startWatchingChanges();

        source_select_box.innerHTML = "";

        await new Promise(process.nextTick);

        await expect(items_map_manager.refreshItemsMap).toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).toHaveBeenCalled();
    });

    it("should refresh the list-picker when attribute disabled on children is added", async () => {
        appendSimpleOptionsToSourceSelectBox(source_select_box);
        list_options_changes_observer.startWatchingChanges();

        source_select_box.options[0].disabled = true;

        await new Promise(process.nextTick);

        await expect(items_map_manager.refreshItemsMap).toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).toHaveBeenCalled();
    });

    it("should refresh the list-picker when attribute disabled is added on source select", async () => {
        await new Promise<void>((done) => {
            list_options_changes_observer.startWatchingChanges();

            source_select_box.disabled = true;
            done();
        });

        expect(list_picker_element_attributes_updater).toHaveBeenCalled();
    });

    it("should not react otherwise", async () => {
        await new Promise<void>((done) => {
            appendSimpleOptionsToSourceSelectBox(source_select_box);
            list_options_changes_observer.startWatchingChanges();

            source_select_box.options[0].setAttribute("selected", "selected");
            done();
        });

        await expect(items_map_manager.refreshItemsMap).not.toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).not.toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).not.toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).not.toHaveBeenCalled();
    });
});
