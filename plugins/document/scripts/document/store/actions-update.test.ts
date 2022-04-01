/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

import type { NewVersionFromEmptyInformation } from "./actions-update";
import {
    createNewEmbeddedFileVersionFromModal,
    createNewFileVersion,
    createNewFileVersionFromModal,
    createNewLinkVersionFromModal,
    createNewVersionFromEmpty,
    createNewWikiVersionFromModal,
} from "./actions-update";
import * as rest_querier from "../api/rest-querier";
import * as upload_file from "./actions-helpers/upload-file";
import type { ActionContext } from "vuex";
import type { Embedded, Empty, Folder, ItemFile, Link, RootState, Wiki } from "../type";
import type { ConfigurationState } from "./configuration";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK } from "../constants";
import type { Upload } from "tus-js-client";

describe("actions-update", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        const project_id = "101";
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            state: {
                configuration: { project_id } as ConfigurationState,
                current_folder_ascendant_hierarchy: [],
            } as unknown as RootState,
        } as unknown as ActionContext<RootState, RootState>;
    });

    describe("createNewFileVersion", () => {
        let createNewVersion: jest.SpyInstance, uploadVersion: jest.SpyInstance;

        beforeEach(() => {
            createNewVersion = jest.spyOn(rest_querier, "createNewVersion");
            uploadVersion = jest.spyOn(upload_file, "uploadVersion");
        });

        it("does not trigger any upload if the file is empty", async () => {
            const dropped_file = { name: "filename.txt", size: 0, type: "text/plain" } as File;
            const item = {} as ItemFile;

            createNewVersion.mockReturnValue(Promise.resolve());

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).not.toHaveBeenCalled();
        });

        it("uploads a new version of the file and releases the edition lock", async () => {
            const item = {
                id: 45,
                lock_info: null,
                title: "Electronic document management for dummies.pdf",
            } as ItemFile;
            const NO_LOCK = false;

            context.state.folder_content = [{ id: 45 } as Folder];
            const dropped_file = { name: "filename.txt", size: 123, type: "text/plain" } as File;

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.mockReturnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.mockReturnValue(uploader);

            await createNewFileVersion(context, [item, dropped_file]);

            expect(uploadVersion).toHaveBeenCalled();
            expect(createNewVersion).toHaveBeenCalledWith(
                item,
                "Electronic document management for dummies.pdf",
                "",
                dropped_file,
                NO_LOCK,
                null
            );
        });
    });

    describe("createNewFileVersionFromModal", () => {
        let createNewVersion: jest.SpyInstance, uploadVersion: jest.SpyInstance;

        beforeEach(() => {
            createNewVersion = jest.spyOn(rest_querier, "createNewVersion");
            uploadVersion = jest.spyOn(upload_file, "uploadVersion");
        });

        it("uploads a new version of a file", async () => {
            const item = { id: 45 } as ItemFile;
            context.state.folder_content = [{ id: 45 } as ItemFile];
            const updated_file = { name: "filename.txt", size: 123, type: "text/plain" } as File;

            const new_version = { upload_href: "/uploads/docman/version/42" };
            createNewVersion.mockReturnValue(Promise.resolve(new_version));

            const uploader = {};
            uploadVersion.mockReturnValue(uploader);

            await createNewFileVersionFromModal(context, [
                item,
                updated_file,
                "My new version",
                "Changed the version because...",
                true,
                null,
            ]);

            expect(createNewVersion).toHaveBeenCalled();
            expect(uploadVersion).toHaveBeenCalled();
        });

        it("handles error when there is a problem with the version creation", async () => {
            const item = { id: 45 } as ItemFile;
            context.state.folder_content = [{ id: 45 } as ItemFile];
            const update_fail = {} as File;

            createNewVersion.mockImplementation(() => {
                throw new Error("An error occurred");
            });

            await createNewFileVersionFromModal(context, [
                item,
                update_fail,
                "My new version",
                "Changed the version because...",
                false,
                null,
            ]);
            expect(createNewVersion).toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleErrorsForModal",
                expect.anything()
            );
            expect(uploadVersion).not.toHaveBeenCalled();
        });

        it("handles error when there is an error 400 with the version creation", async () => {
            const item = { id: 45 } as ItemFile;
            context.state.folder_content = [{ id: 45 } as ItemFile];
            const update_fail = {} as File;

            mockFetchError(createNewVersion, {
                status: 400,
            });

            await createNewFileVersionFromModal(context, [
                item,
                update_fail,
                "My new version",
                "Changed the version because...",
                false,
                null,
            ]);

            expect(createNewVersion).toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleErrorsForModal",
                expect.anything()
            );
            expect(uploadVersion).not.toHaveBeenCalled();
        });
    });

    describe("createNewEmbeddedFileVersionFromModal", () => {
        let postEmbeddedFile: jest.SpyInstance;

        beforeEach(() => {
            postEmbeddedFile = jest.spyOn(rest_querier, "postEmbeddedFile");
        });

        it("updates an embedded file", async () => {
            const item = { id: 45 } as Embedded;
            context.state.folder_content = [{ id: 45 } as Embedded];

            await createNewEmbeddedFileVersionFromModal(context, [
                item,
                "<h1>Hello world!</h1>",
                "My new version",
                "Changed the version because...",
                true,
                null,
            ]);

            expect(postEmbeddedFile).toHaveBeenCalled();
        });
        it("handles error when there is a problem with the update", async () => {
            const item = { id: 45 } as Embedded;
            context.state.folder_content = [{ id: 45 } as Embedded];

            postEmbeddedFile.mockImplementation(() => {
                throw new Error("nope");
            });

            await createNewEmbeddedFileVersionFromModal(context, [
                item,
                "<h1>Hello world!</h1>",
                "My new version",
                "Changed the version because...",
                true,
                null,
            ]);
            expect(postEmbeddedFile).toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleErrorsForModal",
                expect.anything()
            );
        });
    });

    describe("createNewWikiVersionFromModal", () => {
        let postWiki: jest.SpyInstance;

        beforeEach(() => {
            postWiki = jest.spyOn(rest_querier, "postWiki");
        });

        it("updates a wiki page name", async () => {
            const item = { id: 45 } as Wiki;
            context.state.folder_content = [{ id: 45 } as Wiki];

            await createNewWikiVersionFromModal(context, [
                item,
                "kinky wiki",
                "NSFW",
                "Changed title to NSFW",
                true,
            ]);

            expect(postWiki).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 } as Wiki;
            context.state.folder_content = [{ id: 45 } as Wiki];

            postWiki.mockImplementation(() => {
                throw new Error("nope");
            });

            await createNewWikiVersionFromModal(context, [
                item,
                "kinky wiki",
                "NSFW",
                "Changed title to NSFW",
                true,
            ]);

            expect(postWiki).toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleErrorsForModal",
                expect.anything()
            );
        });
    });

    describe("createNewLinkVersionFromModal", () => {
        let postLinkVersion: jest.SpyInstance;

        beforeEach(() => {
            postLinkVersion = jest.spyOn(rest_querier, "postLinkVersion");
        });

        it("updates a link url", async () => {
            const item = { id: 45 } as Link;
            context.state.folder_content = [{ id: 45 } as Link];

            await createNewLinkVersionFromModal(context, [
                item,
                "https://moogle.fr",
                "My new version",
                "Changed the version because...",
                true,
                null,
            ]);

            expect(postLinkVersion).toHaveBeenCalled();
        });
        it("throws an error when there is a problem with the update", async () => {
            const item = { id: 45 } as Link;
            context.state.folder_content = [{ id: 45 } as Link];

            postLinkVersion.mockImplementation(() => {
                throw new Error("nope");
            });

            await createNewLinkVersionFromModal(context, [
                item,
                "https://moogle.fr",
                "My new version",
                "Changed the version because...",
                true,
                null,
            ]);

            expect(postLinkVersion).toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleErrorsForModal",
                expect.anything()
            );
        });
    });

    describe("createNewVersionFromEmpty -", () => {
        let context: ActionContext<RootState, RootState>,
            postNewLinkVersionFromEmpty: jest.SpyInstance,
            postNewEmbeddedFileVersionFromEmpty: jest.SpyInstance,
            postNewFileVersionFromEmpty: jest.SpyInstance;

        beforeEach(() => {
            context = {
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    folder_content: [{ id: 123, type: TYPE_EMPTY } as Empty],
                } as unknown as RootState,
            } as unknown as ActionContext<RootState, RootState>;

            postNewLinkVersionFromEmpty = jest.spyOn(rest_querier, "postNewLinkVersionFromEmpty");
            postNewEmbeddedFileVersionFromEmpty = jest.spyOn(
                rest_querier,
                "postNewEmbeddedFileVersionFromEmpty"
            );
            postNewFileVersionFromEmpty = jest.spyOn(rest_querier, "postNewFileVersionFromEmpty");
        });

        it("should update the empty document to link document", async () => {
            const item_to_update = {
                link_properties: {
                    link_url: "https://example.test",
                },
            } as NewVersionFromEmptyInformation;
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            } as Empty;

            const updated_item = {
                id: 123,
                type: TYPE_LINK,
            } as Link;
            jest.spyOn(rest_querier, "getItem").mockResolvedValue(updated_item);
            postNewLinkVersionFromEmpty.mockReturnValue(Promise.resolve());

            await createNewVersionFromEmpty(context, [TYPE_LINK, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).toHaveBeenCalled();
            expect(postNewEmbeddedFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewFileVersionFromEmpty).not.toHaveBeenCalled();

            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );

            expect(context.commit).toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });

        it("should update the empty document to embedded_file document", async () => {
            const item_to_update = {
                embedded_properties: {
                    content: "content",
                },
            } as NewVersionFromEmptyInformation;
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            } as Empty;

            const updated_item = {
                id: 123,
                type: TYPE_EMBEDDED,
            } as Embedded;

            jest.spyOn(rest_querier, "getItem").mockResolvedValue(updated_item);
            postNewEmbeddedFileVersionFromEmpty.mockReturnValue(Promise.resolve());

            await createNewVersionFromEmpty(context, [TYPE_EMBEDDED, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewEmbeddedFileVersionFromEmpty).toHaveBeenCalled();
            expect(postNewFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );

            expect(context.commit).toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });

        it("should update the empty document to file document", async () => {
            const item_to_update = {
                file_properties: {
                    file: { name: "toto.gif" } as File,
                },
            } as NewVersionFromEmptyInformation;
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            } as Empty;

            const updated_item = {
                id: 123,
                type: TYPE_FILE,
            } as ItemFile;
            const uploadVersionFromEmpty = jest
                .spyOn(upload_file, "uploadVersionFromEmpty")
                .mockReturnValue({} as Upload);
            postNewFileVersionFromEmpty.mockReturnValue(Promise.resolve());
            jest.spyOn(rest_querier, "getItem").mockResolvedValue(updated_item);

            await createNewVersionFromEmpty(context, [TYPE_FILE, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewEmbeddedFileVersionFromEmpty).not.toHaveBeenCalled();
            expect(postNewFileVersionFromEmpty).toHaveBeenCalled();
            expect(uploadVersionFromEmpty).toHaveBeenCalled();
            expect(context.commit).toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );

            expect(context.commit).toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });

        it("should failed the update", async () => {
            const item_to_update = {
                link_properties: {
                    link_url: "https://example.test",
                },
            } as NewVersionFromEmptyInformation;
            const item = {
                id: 123,
                type: TYPE_EMPTY,
            } as Empty;

            const updated_item = {
                id: 123,
                type: TYPE_LINK,
            } as Link;

            const getItem = jest.spyOn(rest_querier, "getItem");
            postNewLinkVersionFromEmpty.mockImplementation(() => {
                throw new Error("Failed to update");
            });

            await createNewVersionFromEmpty(context, [TYPE_LINK, item, item_to_update]);

            expect(postNewLinkVersionFromEmpty).toHaveBeenCalled();
            expect(context.dispatch).toHaveBeenCalledWith(
                "error/handleErrorsForModal",
                expect.anything()
            );
            expect(getItem).not.toHaveBeenCalled();
            expect(context.commit).not.toHaveBeenCalledWith(
                "removeItemFromFolderContent",
                updated_item
            );
            expect(context.commit).not.toHaveBeenCalledWith(
                "addJustCreatedItemToFolderContent",
                updated_item
            );
            expect(context.commit).not.toHaveBeenCalledWith(
                "updateCurrentItemForQuickLokDisplay",
                updated_item
            );
        });
    });
});
