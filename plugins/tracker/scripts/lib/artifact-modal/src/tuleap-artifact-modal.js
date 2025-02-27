/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import angular from "angular";
import "angular-gettext";
import angular_tlp from "@tuleap/angular-tlp";
import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";

import angular_custom_elements_module from "angular-custom-elements";

import "@tuleap/tlp-relative-date";
import { STRUCTURAL_FIELDS } from "@tuleap/plugin-tracker-constants";
import { setCatalog } from "./gettext-catalog";

import ArtifactModalService from "./tuleap-artifact-modal-service.js";
import ArtifactModalController from "./tuleap-artifact-modal-controller.js";
import StaticOpenListFieldDirective from "./fields/open-list-field/static-open-list-field-directive.js";
import UgroupsOpenListFieldDirective from "./fields/open-list-field/ugroups-open-list-field-directive.js";
import UsersOpenListFieldDirective from "./fields/open-list-field/users-open-list-field-directive.js";
import AwkwardCreationFields from "./model/awkward-creation-fields-constant.js";
import HighlightDirective from "./tuleap-highlight/highlight-directive.js";
import ListPickerDirective from "./fields/list-picker-field/list-picker-field-directive.js";
import ListPickerMultipleDirective from "./fields/list-picker-multiple-field/list-picker-mulitple-field-directive.js";

import "./fields/computed-field/ComputedField";
import "./fields/float-field/FloatField";
import "./fields/int-field/IntField";
import "./fields/radio-buttons-field/RadioButtonsField";
import "./fields/string-field/StringField";
import "./fields/text-field/TextField";
import "./adapters/UI/fields/link-field/LinkField";
import "./adapters/UI/fields/date-field/DateField";
import "./adapters/UI/fields/burndown-field/BurndownField";
import "./adapters/UI/fields/artifact-id-field/ArtifactIdField";
import "./adapters/UI/fields/priority-field/PriorityField";
import "./adapters/UI/fields/cross-references-field/CrossReferencesField";
import "./adapters/UI/fields/user-avatar-field/UserAvatarField";
import "./adapters/UI/fields/date-readonly-field/DateReadonlyField";
import "./adapters/UI/fields/file-field/FileField";
import "./adapters/UI/fields/permission-field/PermissionField";
import "./adapters/UI/fields/checkbox-field/CheckboxField";
import "./followups/FollowupEditor";
import "./adapters/UI/feedback/ModalFeedback";
import "./adapters/UI/footer/FileUploadQuota";

export default angular
    .module("tuleap.artifact-modal", ["gettext", angular_tlp, angular_custom_elements_module])
    .run([
        "gettextCatalog",
        function (gettextCatalog) {
            [fr_FR, pt_BR].forEach((translations_catalog) => {
                for (const [language, strings] of Object.entries(translations_catalog)) {
                    const short_language = language.split("_")[0];
                    gettextCatalog.setStrings(short_language, strings);
                    setCatalog(gettextCatalog);
                }
            });
        },
    ])
    .constant("TuleapArtifactModalAwkwardCreationFields", AwkwardCreationFields)
    .constant("TuleapArtifactModalStructuralFields", STRUCTURAL_FIELDS)
    .controller("TuleapArtifactModalController", ArtifactModalController)
    .directive("tuleapArtifactModalStaticOpenListField", StaticOpenListFieldDirective)
    .directive("tuleapArtifactModalUgroupsOpenListField", UgroupsOpenListFieldDirective)
    .directive("tuleapArtifactModalUsersOpenListField", UsersOpenListFieldDirective)
    .directive("tuleapHighlightDirective", HighlightDirective)
    .directive("tuleapArtifactModalListPickerField", ListPickerDirective)
    .directive("tuleapArtifactModalListPickerMultipleField", ListPickerMultipleDirective)
    .service("NewTuleapArtifactModalService", ArtifactModalService)
    .value("TuleapArtifactModalLoading", {
        loading: false,
    }).name;
