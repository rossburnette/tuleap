/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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

import { post } from "tlp-fetch";
import { Upload } from "tus-js-client";
import Gettext from "node-gettext";
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import { addInstance } from "./consistentUploadedFilesBeforeSubmitChecker.js";

let gettext_provider;

async function getGettextProvider(options) {
    if (typeof gettext_provider !== "undefined") {
        return gettext_provider;
    }

    gettext_provider = new Gettext();
    if (options.language === "fr_FR") {
        try {
            const french_translations = await import(/* webpackChunkName: "rich-text-editor-fr" */ "./po/fr.po");
            gettext_provider.addTranslations(
                options.language,
                "rich-text-editor",
                french_translations
            );
        } catch (exception) {
            // will be en_US if translations cannot be loaded
        }
    }

    gettext_provider.setLocale(options.language);
    gettext_provider.setTextDomain("rich-text-editor");

    return gettext_provider;
}

function initiateUploadImage(ckeditor_instance, options, element) {
    if (!isUploadEnabled(element)) {
        ckeditor_instance.on("paste", disablePasteOfImages);
        return;
    }

    const form = element.form;
    const field_name = element.dataset.uploadFieldName;
    const max_size_upload = parseInt(element.dataset.uploadMaxSize, 10);

    informUsersThatTheyCanPasteImagesInEditor(element, options);

    let nb_being_uploaded = 0;
    const submit_buttons = form.querySelectorAll(".hidden-artifact-submit-button button");
    ckeditor_instance.on("fileUploadRequest", fileUploadRequest, null, null, 4);
    addInstance(form, ckeditor_instance, field_name);

    async function fileUploadRequest(evt) {
        const loader = evt.data.fileLoader;
        evt.stop();

        if (loader.file.size > max_size_upload) {
            loader.message = sprintf(
                (await getGettextProvider(options)).gettext(
                    "You are not allowed to upload files bigger than %s."
                ),
                prettyKibibytes(max_size_upload)
            );
            loader.changeStatus("error");
            return;
        }

        try {
            const response = await post(loader.uploadUrl, {
                headers: { "content-type": "application/json" },
                body: JSON.stringify({
                    name: loader.fileName,
                    file_size: loader.file.size,
                    file_type: loader.file.type
                })
            });

            const { id, upload_href, download_href } = await response.json();

            if (!upload_href) {
                onSuccess(loader, id, download_href);
                return;
            }

            const uploader = new Upload(loader.file, {
                uploadUrl: upload_href,
                retryDelays: [0, 1000, 3000, 5000],
                metadata: {
                    filename: loader.file.name,
                    filetype: loader.file.type
                },
                onProgress: (bytes_sent, bytes_total) => {
                    loader.uploadTotal = bytes_total;
                    loader.uploaded = bytes_sent;
                    loader.update();
                },
                onSuccess: () => {
                    onSuccess(loader, id, download_href);
                    enableFormSubmit();
                },
                onError: ({ originalRequest }) => {
                    onError(loader, originalRequest);
                    enableFormSubmit();
                }
            });

            disableFormSubmit();
            uploader.start();
        } catch (exception) {
            enableFormSubmit();
            loader.message = (await getGettextProvider(options)).gettext(
                "Unable to upload the file"
            );
            if (typeof exception.response === "undefined") {
                loader.changeStatus("error");
                return;
            }

            try {
                const json = await exception.response.json();
                if (json.hasOwnProperty("error")) {
                    loader.message = json.error.message;

                    if (json.error.hasOwnProperty("i18n_error_message")) {
                        loader.message = json.error.i18n_error_message;
                    }
                }
            } finally {
                loader.changeStatus("error");
            }
        }
    }

    function preventFormSubmissionListener(event) {
        event.preventDefault();
        event.stopPropagation();
    }

    function disableFormSubmit() {
        ++nb_being_uploaded;
        for (const button of submit_buttons) {
            button.disabled = true;
        }
        form.addEventListener("submit", preventFormSubmissionListener);
    }

    function enableFormSubmit() {
        --nb_being_uploaded;
        if (nb_being_uploaded > 0) {
            return;
        }
        for (const button of submit_buttons) {
            button.disabled = false;
        }
        form.removeEventListener("submit", preventFormSubmissionListener);
    }

    function onSuccess(loader, id, download_href) {
        loader.responseData = {
            // ckeditor uploadImage widget inserts real size of the image as inline style
            // which causes strange rendering for big images in the artifact view once
            // the artifact is updated.
            // Using blank width & height inhibits this behavior.
            // See https://github.com/ckeditor/ckeditor-dev/blob/4.11.1/plugins/uploadimage/plugin.js#L84-L86
            width: " ",
            height: " "
        };
        loader.uploaded = 1;
        loader.fileName = loader.file.name;
        loader.url = download_href;
        loader.changeStatus("uploaded");

        const hidden_field = document.createElement("input");
        hidden_field.type = "hidden";
        hidden_field.name = field_name;
        hidden_field.value = id;
        hidden_field.dataset.url = download_href;
        form.appendChild(hidden_field);

        ckeditor_instance.fire("change");
    }

    function onError(loader, originalRequest) {
        loader.message = loader.lang.filetools["httpError" + originalRequest.status];
        if (!loader.message) {
            loader.message = loader.lang.filetools.httpError.replace("%1", originalRequest.status);
        }
        loader.changeStatus("error");
    }

    async function disablePasteOfImages(evt) {
        const doc = new DOMParser().parseFromString(evt.data.dataValue, "text/html");
        for (const img of doc.querySelectorAll("img")) {
            if (img.src.match(/^data:/i)) {
                evt.data.dataValue = "";
                evt.cancel();
                ckeditor_instance.showNotification(
                    (await getGettextProvider(options)).gettext(
                        "You are not allowed to paste images here"
                    ),
                    "warning"
                );
                return;
            }
        }
    }
}

function getUploadImageOptions(element) {
    if (!isUploadEnabled(element)) {
        return {};
    }

    return {
        extraPlugins: "uploadimage",
        uploadUrl: element.dataset.uploadUrl
    };
}

function isUploadEnabled(element) {
    return document.body.querySelector("[data-upload-is-enabled]") && element.dataset.uploadUrl;
}

async function informUsersThatTheyCanPasteImagesInEditor(element, options) {
    if (typeof element.dataset.helpId === "undefined") {
        return;
    }
    const help_block = document.getElementById(element.dataset.helpId);
    if (!help_block) {
        return;
    }

    const p = document.createElement("p");
    p.innerText = (await getGettextProvider(options)).gettext(
        "You can drag 'n drop or paste image directly in the editor."
    );
    help_block.appendChild(p);
}

export { getUploadImageOptions, initiateUploadImage };
