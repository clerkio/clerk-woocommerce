/**
 * Plugin Name: Clerk
 * Plugin URI: https://clerk.io/
 * Description: Clerk.io Turns More Browsers Into Buyers
 * Version: 4.1.7
 * Author: Clerk.io
 * Author URI: https://clerk.io
 *
 * Text Domain: clerk
 * Domain Path: /i18n/languages/
 * License: MIT
 *
 * @package clerkio/clerk-woocommerce
 * @file Handles front end content get.
 * Ajax handler.
 */

/**
 * Form Submission Shortcut Admin
 */

    const clerk_submit_admin_form = () => {
        document.querySelector('#submit').click();
    }
    const admin_form = document.querySelector('form#clerkAdminForm');
    if (admin_form) {
        admin_form.addEventListener(
            'submit',
            (e) => {
                collect_attributes();
            }
        );
        const lang_data_container = admin_form.querySelector('#multi-lang-data')
        if (lang_data_container && '1' !== lang_data_container.dataset?.isPll) {
            const lang_data = JSON.parse(lang_data_container.textContent);
            if (lang_data) {
                admin_form.querySelector('#import_url').value = lang_data['url']
            }
        }

        function remove_facet_line(data_value) {
            const admin_form = document.querySelector('form#clerkAdminForm');
            const elements = admin_form.querySelectorAll(`[data="${data_value}"]`);
            elements.forEach(
                el => {
                    el.remove();
                }
            );
        }

        const close_btn = admin_form.querySelector('.closebtn');
        if (close_btn) {
            close_btn.addEventListener(
                'click',
                (e) => {
                    admin_form.querySelector('.alert').remove();
                }
            )
        }
        const custom_facet_input = document.querySelector('#faceted_navigation_custom');
        if (custom_facet_input) {
            custom_facet_input.addEventListener(
                'keydown',
                (e) => {
                    if (e.keyCode == 13) {
                        e.preventDefault();
                        add_facet();
                    }
                }
            )
        }


        function add_facet() {
            let linescount = document.querySelectorAll('.facets_content .facets_lines').length;
            const custom_facet_input = document.querySelector('#faceted_navigation_custom');
            const facet_value = custom_facet_input.value;

            const facets_lines = document.createElement("div");
            facets_lines.setAttribute("class", "facets_lines");
            facets_lines.setAttribute("data", facet_value);

            const facet_td = document.createElement("div");

            const facet = document.createElement("input");
            facet.setAttribute("class", "facets_facet");
            facet.setAttribute("type", "text");
            facet.setAttribute("value", facet_value);
            facet.setAttribute("readonly", '');

            const title_td = document.createElement("div");
            const title = document.createElement("input");
            title.setAttribute("class", "facets_title");
            title.setAttribute("type", "text");
            title.setAttribute("value", '');

            const position_td = document.createElement("div");
            const position = document.createElement("input");
            position.setAttribute("class", "facets_position");
            position.setAttribute("type", "text");
            position.setAttribute("value", linescount + 1);

            const checkbox_td = document.createElement("div");

            const checkbox = document.createElement("input");
            checkbox.setAttribute("type", "checkbox");
            checkbox.setAttribute("class", "faceted_enabled");
            checkbox.setAttribute("value", "1");

            const remove = document.createElement("div");
            remove.setAttribute("class", "close");
            remove.setAttribute("onclick", `remove_facet_line( '${facet_value}' );`);

            facet_td.append(facet)
            facets_lines.append(facet_td);
            title_td.append(title);
            facets_lines.append(title_td);
            position_td.append(position);
            facets_lines.append(position_td);
            checkbox_td.append(checkbox);
            checkbox_td.append(remove);
            facets_lines.append(checkbox_td);

            document.querySelector('.facets_content').append(facets_lines);

            custom_facet_input.value = '';
        }


        function collect_attributes() {

            let attribute_reference = [];

            const facet_attributes_value_holder = document.querySelector('#faceted_navigation');
            const facet_slugs = document.querySelectorAll('input.facets_facet');
            const facet_titles = document.querySelectorAll('input.facets_title');
            const facet_position = document.querySelectorAll('input.facets_position');
            const facet_in_use = document.querySelectorAll('input.faceted_enabled');
            const facet_count = facet_slugs.length;
            for (let i = 0; i < facet_count; i++) {
                attribute_reference.push(
                    {
                        attribute: facet_slugs[i].value,
                        title: facet_titles[i].value,
                        position: facet_position[i].value,
                        checked: facet_in_use[i].checked ? facet_in_use[i].checked : false
                    }
                )
            }

            facet_attributes_value_holder.value = JSON.stringify(attribute_reference);

        }

        document.querySelector('#powerstep_custom_text_enabled').addEventListener(
            'click',
            function (e) {
                switch (e.target.checked) {
                    case true:
                        document.querySelector('#powerstep_custom_text_back').removeAttribute('disabled');
                        document.querySelector('#powerstep_custom_text_title').removeAttribute('disabled');
                        document.querySelector('#powerstep_custom_text_cart').removeAttribute('disabled');
                        break;
                    case false:
                        document.querySelector('#powerstep_custom_text_back').setAttribute('disabled', true);
                        document.querySelector('#powerstep_custom_text_title').setAttribute('disabled', true);
                        document.querySelector('#powerstep_custom_text_cart').setAttribute('disabled', true);
                        break;
                }
            }
        );
        const customPowerstepTexts = document.querySelector('#powerstep_custom_text_enabled').checked;
        if (!customPowerstepTexts) {
            document.querySelector('#powerstep_custom_text_back').setAttribute('disabled', true);
            document.querySelector('#powerstep_custom_text_title').setAttribute('disabled', true);
            document.querySelector('#powerstep_custom_text_cart').setAttribute('disabled', true);
        }

        function showPushAllPages() {
            const publicKey = document.querySelector('#public_key')?.value;
            const privateKey = document.querySelector('#private_key')?.value;
            const site = document.querySelector('#import_url')?.value;
            if (!publicKey || !privateKey || !site) {
                return;
            }
            const el = document.createElement('div');
            el.id = 'sync-pages-action';
            el.onclick = async function () {
                el.style.backgroundColor = '#ff5c28'
                el.style.color = '#1d2327'
                await pushAllPages(publicKey, privateKey, site);
            }
            el.textContent = 'Sync Pages';
            el.className = 'force-sync';
            document.querySelector('#realtime_updates_pages').parentNode.append(el)
        }

        async function pushAllPages(publicKey, privateKey, site) {
            const url = new URL(site);
            url.pathname += '/wp-json/clerk/page-rtu';
            url.searchParams.append('public_key', publicKey);
            url.searchParams.append('private_key', privateKey);
            const rsp = await fetch(url);
            const rsp_json = await rsp.json()
            if('page_count' in rsp_json){
                const btn = document.querySelector('#sync-pages-action')
                if(btn){
                    btn.removeAttribute('style');
                }
            }
        }

        function getAlternateSettingsJSON() {
            const hiddenLanguageDataRaw = document.querySelector('#hidden-lang-data').textContent;
            return hiddenLanguageDataRaw ? JSON.parse(hiddenLanguageDataRaw) : false;
        }

        function getAlternateSettingsValuesHTML(element, data) {
            const langs = data.languages;
            const id = element.id;

            let newElements;
            newElements = [];

            for (const lang of langs) {
                let options = data[lang];
                if (typeof options !== 'object' || (typeof options === 'object' && Array.isArray(options)) || (typeof options === 'object' && options === null)) {
                    options = {};
                }
                let newElement = element.cloneNode(true);
                newElement.className = 'clerk_hidden';
                newElement.removeAttribute('id');
                newElement.setAttribute('name', `clerk_options_${lang}[${id}]`)
                const newValue = options[id];
                if (newElement.tagName === 'INPUT' && newElement.type === 'checkbox') {
                    newElement.checked = !!newValue;
                }
                if (newElement.tagName === 'INPUT' && newElement.type === 'text' || newElement.tagName === 'TEXTAREA') {
                    newElement.value = newValue || '';
                }
                if (element.tagName === 'INPUT' && element.type === 'hidden' && element.classList.contains('faceted_navigation_holder')) {
                    if (newValue) {
                        newElement.value = newValue;
                    }
                }
                if (newElement.tagName === 'SELECT') {
                    if (newValue) {
                        newElement.innerHTML = `<option value="${newValue}" selected></option>`;
                    }
                }
                newElements.push(newElement);
            }
            return newElements;
        }


        const multiLangData = getAlternateSettingsJSON()
        if (multiLangData) {
            const clerkForms = document.querySelectorAll('#clerkAdminForm .form-table');
            for (const formWrapper of clerkForms) {
                const inputEls = formWrapper.querySelectorAll('input, select, textarea');
                for (const element of inputEls) {
                    const newElements = getAlternateSettingsValuesHTML(element, multiLangData);
                    formWrapper.append(...newElements);
                }
            }
        }

        // Fix Whitespace in additional scripts field.
        const additionalScriptsTextarea = document.querySelector('#clerk_additional_scripts_content');
        if (additionalScriptsTextarea) {
            additionalScriptsTextarea.value = additionalScriptsTextarea.value.trim();
        }

        const rtuPagesEnabled = document.querySelector('#realtime_updates_pages')
        if (rtuPagesEnabled) {
            if (rtuPagesEnabled.checked) {
                showPushAllPages();
            }
        }
    }




