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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { createApp } from "vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { createPinia } from "pinia";

import { createInitializedRouter } from "./router/router";

import GitlabGroupLinkAppComponent from "./components/GitlabGroupLinkAppComponent.vue";
import { useRootStore } from "./stores/root";

export async function init(mount_point: HTMLElement): Promise<void> {
    const app = createApp(GitlabGroupLinkAppComponent);
    const pinia = createPinia();

    app.use(createInitializedRouter(getDatasetItemFromMountPoint("currentProjectUnixName")));

    app.use(
        await initVueGettext(createGettext, (locale: string) => {
            return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
        })
    );

    app.use(pinia);
    useRootStore().setCurrentProject({
        public_name: getDatasetItemFromMountPoint("currentProjectName"),
    });

    app.mount(mount_point);

    function getDatasetItemFromMountPoint(item_name: string): string {
        const item = mount_point.dataset[item_name];
        if (!item) {
            throw new Error(`Missing item ${item_name} in dataset`);
        }
        return item;
    }
}
