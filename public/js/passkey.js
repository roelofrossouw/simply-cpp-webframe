/*
 * Passkeys for Auth\Passkey: the sign-in page's button and Your profile's Add a passkey. The options come from
 * passkey.php as JSON; sc-passkey.js turns them into the browser's call and its answer into JSON, which is posted back.
 *
 *   passkeySignIn(button, optionsUrl, postUrl)  - signs in: posts the answer to the login page as a page of its own
 *   passkeyAdd(button, optionsUrl, form)        - a new passkey: puts the answer in the form's passkey field and clicks
 *                                                 its submit button (which js/app.js posts into the popup)
 *
 * Copyright 2026 Roelof Rossouw. Licensed under the Apache License, Version 2.0.
 */
(function (root) {
    'use strict';

    // The options from passkey.php, or an error with the server's sentence
    async function options(url) {
        const response = await fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-by': 'sc-webframe'}});
        const json = await response.json().catch(function () { return {}; });
        if (!response.ok || json.error) {
            throw new Error(json.error || 'The passkey options couldn\'t be fetched.');
        }
        return json;
    }

    // What to tell the person when the browser gave no answer
    function problem(error) {
        if (error && error.name === 'NotAllowedError') {
            return 'No passkey was used. Try again, or sign in another way.';
        }
        if (error && error.name === 'InvalidStateError') {
            return 'This device has a passkey for your account already.';
        }
        return (error && error.message) || 'The passkey didn\'t work.';
    }

    function say(button, text) {
        let note = button.parentNode.querySelector('.passkey-problem');
        if (!note) {
            note = document.createElement('div');
            note.className = 'passkey-problem small text-danger mt-1';
            button.insertAdjacentElement('afterend', note);
        }
        note.textContent = text;
    }

    root.passkeySignIn = async function (button, optionsUrl, postUrl) {
        if (!root.scPasskey || !root.scPasskey.supported()) {
            say(button, 'This browser can\'t use passkeys.');
            return;
        }
        button.disabled = true;
        try {
            const answer = await root.scPasskey.get(await options(optionsUrl));
            // A page of its own: the login page signs in, then goes on to the site
            const form = document.createElement('form');
            form.method = 'post';
            form.action = postUrl;
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = 'passkey';
            field.value = JSON.stringify(answer);
            form.appendChild(field);
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            say(button, problem(error));
            button.disabled = false;
        }
    };

    root.passkeyAdd = async function (button, optionsUrl, formId) {
        if (!root.scPasskey || !root.scPasskey.supported()) {
            say(button, 'This browser can\'t use passkeys.');
            return;
        }
        button.disabled = true;
        try {
            const answer = await root.scPasskey.create(await options(optionsUrl));
            const form = document.getElementById(formId);
            form.querySelector('input[name="passkey"]').value = JSON.stringify(answer);
            form.querySelector('.passkey-save').click();
        } catch (error) {
            say(button, problem(error));
            button.disabled = false;
        }
    };
})(window);
