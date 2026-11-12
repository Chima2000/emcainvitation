const setupEventCreationForms = () => {
    document.querySelectorAll('form[data-event-form]').forEach((form) => {
        if (form.dataset.eventFormReady === '1') {
            return;
        }

        form.dataset.eventFormReady = '1';

        const organizationToggle = form.querySelector('[data-organization-host-toggle]');
        const organizationPanel = form.querySelector('[data-organization-host-panel]');
        const organizationInput = organizationPanel?.querySelector('[name="organization_name"]');
        const colorList = form.querySelector('[data-dress-code-color-list]');
        const colorTemplate = form.querySelector('[data-dress-code-color-template]');
        const colorAddButton = form.querySelector('[data-add-dress-code-color]');
        const previewCard = form.querySelector('[data-preview-card]');
        const templatePicker = form.querySelector('[name="invitation_template_id"]');
        const previewTemplates = form.dataset.previewTemplates ? JSON.parse(form.dataset.previewTemplates) : {};
        const previewDefault = form.dataset.previewDefault ? JSON.parse(form.dataset.previewDefault) : {};
        let nextColorIndex = colorList?.querySelectorAll('[data-dress-code-color-row]').length || 0;

        const readValue = (name) => form.querySelector(`[name="${name}"]`)?.value.trim() || '';
        const setCardText = (name, value, fallback = '') => {
            const field = previewCard?.querySelector(`[data-card-field="${name}"]`);

            if (field) {
                field.textContent = value || fallback;
            }
        };
        const updatePreview = () => {
            if (!previewCard) {
                return;
            }

            const selectedType = form.querySelector('[name="type"]');
            const selectedTypeLabel = selectedType?.selectedOptions[0]?.textContent.trim() || '';
            const selectedTemplateId = templatePicker?.value || '';
            const design = selectedTemplateId
                ? previewTemplates[selectedTemplateId]
                : { ...previewDefault, kicker: selectedTypeLabel, headline: null, message: null };

            if (design) {
                ['classic', 'elegant', 'modern', 'minimal'].forEach((layout) => {
                    previewCard.classList.toggle(`jp-card--${layout}`, design.layout === layout);
                });
                previewCard.style.setProperty('--jp-paper', design.paper);
                previewCard.style.setProperty('--jp-ink', design.ink);
                previewCard.style.setProperty('--jp-accent', design.accent);
                previewCard.style.setProperty('--jp-font', design.fontFamily);
                previewCard.style.setProperty('--jp-bg', design.backgroundUrl
                    ? `url("${encodeURI(design.backgroundUrl).replaceAll('"', '%22')}")`
                    : 'none');
            }

            const usesOrganizationName = Boolean(organizationToggle?.checked);
            const host = usesOrganizationName ? readValue('organization_name') : readValue('host_name');
            const partner = readValue('partner_name');
            const dateValue = readValue('event_date');
            const eventDate = dateValue ? new Date(dateValue) : null;
            const formattedDate = eventDate && !Number.isNaN(eventDate.getTime())
                ? new Intl.DateTimeFormat(document.documentElement.lang || 'en', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                }).format(eventDate)
                : dateValue || form.dataset.previewDateFallback;
            const headline = readValue('headline') || design?.headline || form.dataset.previewInviteHeadline;
            const message = readValue('message') || design?.message || '';
            const dressCode = readValue('dress_code');

            setCardText('kicker', design?.kicker || selectedTypeLabel);
            setCardText('title', readValue('title'), form.dataset.previewTitleFallback);
            setCardText('hosts', `${host}${partner ? ` & ${partner}` : ''}`);
            setCardText('headline', headline);
            setCardText('when', formattedDate);
            setCardText('venue', readValue('venue'), form.dataset.previewVenueFallback);

            const messageField = previewCard.querySelector('[data-card-field="message"]');
            if (messageField) {
                messageField.textContent = message;
                messageField.hidden = !message;
            }

            const dressCodeField = previewCard.querySelector('[data-card-field="dress_code"]');
            if (dressCodeField) {
                dressCodeField.textContent = dressCode ? `${form.dataset.previewDressPrefix}: ${dressCode}` : '';
                dressCodeField.hidden = !dressCode;
            }

            const colorContainer = previewCard.querySelector('[data-card-field="dress_code_colors"]');
            const colors = [...(colorList?.querySelectorAll('[data-dress-code-color-input]') || [])]
                .map((input) => input.value)
                .filter((color) => /^#[0-9a-f]{6}$/i.test(color));

            if (colorContainer) {
                colorContainer.replaceChildren(...colors.map((color) => {
                    const swatch = document.createElement('span');
                    swatch.className = 'jp-card__color-swatch';
                    swatch.style.backgroundColor = color;
                    swatch.title = color;
                    swatch.setAttribute('aria-label', color);

                    return swatch;
                }));
                colorContainer.hidden = colors.length === 0;
            }
        };
        const syncOrganizationName = () => {
            const enabled = Boolean(organizationToggle?.checked);

            if (organizationPanel) {
                organizationPanel.hidden = !enabled;
            }

            if (organizationInput) {
                organizationInput.disabled = !enabled;
                organizationInput.required = enabled;
            }

            updatePreview();
        };
        const attachColorRow = (row) => {
            const input = row.querySelector('[data-dress-code-color-input]');
            const label = row.querySelector('[data-dress-code-color-label]');

            input?.addEventListener('input', () => {
                if (label) {
                    label.textContent = input.value;
                }

                updatePreview();
            });
            row.querySelector('[data-remove-dress-code-color]')?.addEventListener('click', () => {
                row.remove();
                updatePreview();
            });
        };

        colorList?.querySelectorAll('[data-dress-code-color-row]').forEach(attachColorRow);
        colorAddButton?.addEventListener('click', () => {
            const row = colorTemplate?.content.firstElementChild?.cloneNode(true);
            const input = row?.querySelector('[data-dress-code-color-input]');

            if (!row || !input || !colorList) {
                return;
            }

            input.id = `${form.dataset.eventFieldPrefix || 'event-'}dress_code_color_${nextColorIndex++}`;
            colorList.append(row);
            attachColorRow(row);
            updatePreview();
        });

        organizationToggle?.addEventListener('change', syncOrganizationName);
        form.addEventListener('input', updatePreview);
        form.addEventListener('change', updatePreview);
        syncOrganizationName();
    });
};

const initPage = () => {
    setupEventCreationForms();

    document.querySelectorAll('[data-qr]').forEach((node) => {
        if (!window.QRCode || !node.dataset.qr || node.dataset.qrReady === '1') {
            return;
        }

        node.dataset.qrReady = '1';

        new window.QRCode(node, {
            text: node.dataset.qr,
            width: 144,
            height: 144,
            colorDark: '#16171a',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.M,
        });
    });

    {
        const manualValue = '__manual__';
        const locationForms = [...document.querySelectorAll('[data-location-form]')];
        let locationsPromise;

        const loadLocations = (url) => {
            locationsPromise ??= fetch(url, { headers: { Accept: 'application/json' } }).then((response) => {
                if (!response.ok) {
                    throw new Error(`Tanzania location data request failed with status ${response.status}.`);
                }

                return response.json();
            });

            return locationsPromise;
        };

        const setupLocationFields = async (form) => {
            const fields = form.querySelector('[data-location-fields]');

            if (!fields) {
                return;
            }

            const country = fields.querySelector('[data-location-country]');
            const region = fields.querySelector('[data-location-region]');
            const district = fields.querySelector('[data-location-district]');
            const ward = fields.querySelector('[data-location-ward]');
            const manualPanels = fields.querySelectorAll('[data-location-manual-panel]');
            const required = fields.dataset.locationRequired === 'true';
            const manualLabel = fields.dataset.locationManualLabel;
            const showManualField = (name, select) => {
                const panel = fields.querySelector(`[data-location-manual-panel="${name}"]`);
                const input = fields.querySelector(`[data-location-manual="${name}"]`);
                const isManual = select.value === manualValue;

                panel?.classList.toggle('d-none', !isManual);

                if (input) {
                    input.required = required && isManual;
                }
            };
            const setOptions = (select, placeholder, values, current) => {
                select.replaceChildren(new Option(placeholder, ''));

                values.forEach((value) => select.add(new Option(value, value)));
                select.add(new Option(manualLabel, manualValue));

                select.value = values.includes(current) ? current : current ? manualValue : '';

                return select.value;
            };
            const setCountry = () => {
                const current = country.dataset.current;
                const selectedCountry = current === 'Tanzania' ? 'Tanzania' : current ? manualValue : '';

                country.value = selectedCountry;
                showManualField('country', country);

                const manualCountry = fields.querySelector('[data-location-manual="country"]');

                if (manualCountry && selectedCountry === manualValue && !manualCountry.value) {
                    manualCountry.value = current;
                }
            };
            const setRegion = (data, current = '') => {
                const regions = country.value === 'Tanzania' ? Object.keys(data) : [];
                const selected = setOptions(region, 'Choose a region', regions, current);
                showManualField('region', region);

                return selected;
            };
            const setDistrict = (data, current = '') => {
                const districts = country.value === 'Tanzania' && region.value !== manualValue && region.value
                    ? Object.keys(data[region.value]?.districts || {})
                    : [];
                const selected = setOptions(district, 'Choose a district', districts, current);
                showManualField('district', district);

                return selected;
            };
            const setWard = (data, current = '') => {
                const wards = country.value === 'Tanzania' && region.value !== manualValue && district.value !== manualValue
                    ? data[region.value]?.districts?.[district.value]?.wards || []
                    : [];

                setOptions(ward, 'Choose a ward', wards, current);
                showManualField('ward', ward);
            };

            setCountry();

            country.addEventListener('change', async () => {
                showManualField('country', country);

                try {
                    const data = await loadLocations(form.dataset.locationUrl);
                    setRegion(data);
                    setDistrict(data);
                    setWard(data);
                } catch (error) {
                    console.error(error);
                }
            });

            region.addEventListener('change', async () => {
                showManualField('region', region);

                try {
                    const data = await loadLocations(form.dataset.locationUrl);
                    setDistrict(data);
                    setWard(data);
                } catch (error) {
                    console.error(error);
                }
            });

            district.addEventListener('change', async () => {
                showManualField('district', district);

                try {
                    const data = await loadLocations(form.dataset.locationUrl);
                    setWard(data);
                } catch (error) {
                    console.error(error);
                }
            });

            ward.addEventListener('change', () => {
                showManualField('ward', ward);
            });

            try {
                const data = await loadLocations(form.dataset.locationUrl);
                const selectedRegion = setRegion(data, region.dataset.current);
                const selectedDistrict = setDistrict(data, district.dataset.current);

                setWard(data, ward.dataset.current);
                region.dataset.current = selectedRegion;
                district.dataset.current = selectedDistrict;
            } catch (error) {
                console.error(error);
                country.value = manualValue;
                region.replaceChildren(new Option(manualLabel, manualValue));
                district.replaceChildren(new Option(manualLabel, manualValue));
                ward.replaceChildren(new Option(manualLabel, manualValue));
                region.value = manualValue;
                district.value = manualValue;
                ward.value = manualValue;
                manualPanels.forEach((panel) => panel.classList.remove('d-none'));

                const countryManual = fields.querySelector('[data-location-manual="country"]');

                if (countryManual && !countryManual.value) {
                    countryManual.value = country.dataset.current === 'Tanzania' ? 'Tanzania' : '';
                }

                fields.querySelector('[data-location-error]')?.classList.remove('d-none');
                manualPanels.forEach((panel) => {
                    const input = panel.querySelector('input');

                    if (input) {
                        input.required = required;
                    }
                });
            }
        };

        locationForms.forEach((form) => {
            if (form.dataset.locationReady === '1') {
                return;
            }

            form.dataset.locationReady = '1';
            setupLocationFields(form);
        });

        document.querySelectorAll('form[data-multistep]').forEach((form) => {
            const panels = [...form.querySelectorAll('[data-step-panel]')];

            if (panels.length < 2 || form.dataset.multistepReady === '1') {
                return;
            }

            form.dataset.multistepReady = '1';

            const indicators = [...form.querySelectorAll('[data-step-indicator]')];
            const previousButtons = [...form.querySelectorAll('[data-step-back]')];
            const submitButton = form.querySelector('[data-step-submit]');
            let currentStep = Number(form.dataset.startStep || 1);

            const showStep = (step) => {
                currentStep = Math.min(Math.max(step, 1), panels.length);

                panels.forEach((panel) => {
                    const active = Number(panel.dataset.stepPanel) === currentStep;

                    panel.hidden = !active;
                    panel.querySelectorAll('[data-step-required]').forEach((input) => {
                        input.required = active;
                    });
                });

                indicators.forEach((indicator) => {
                    const active = Number(indicator.dataset.stepIndicator) === currentStep;

                    indicator.classList.toggle('bg-primary', active);
                    indicator.classList.toggle('bg-light', !active);
                    indicator.classList.toggle('text-dark', !active);
                    indicator.classList.toggle('border', !active);
                });

                previousButtons.forEach((button) => {
                    button.hidden = currentStep === 1;
                });

                if (submitButton) {
                    submitButton.hidden = currentStep !== panels.length;
                }
            };

            form.querySelectorAll('[data-step-next]').forEach((button) => {
                button.addEventListener('click', () => {
                    const activePanel = panels.find((panel) => Number(panel.dataset.stepPanel) === currentStep);
                    const invalidInput = [...activePanel.querySelectorAll('input, select, textarea')]
                        .find((input) => !input.checkValidity());

                    if (invalidInput) {
                        invalidInput.reportValidity();

                        return;
                    }

                    showStep(currentStep + 1);
                });
            });

            previousButtons.forEach((button) => {
                button.addEventListener('click', () => showStep(currentStep - 1));
            });

            showStep(currentStep);
        });
    }

    const start = document.querySelector('[data-scanner-start]');
    const video = document.querySelector('[data-scanner]');
    const input = document.querySelector('[data-check-in-code]');

    if (!start || !video || !input || start.dataset.scannerReady === '1') {
        return;
    }

    start.dataset.scannerReady = '1';
    start.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            window.alert(start.dataset.scannerUnsupported || 'This browser cannot scan codes. Type the card number instead.');

            return;
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
        });

        video.srcObject = stream;
        video.classList.remove('hidden', 'd-none');
        await video.play();

        const detector = new window.BarcodeDetector({ formats: ['qr_code'] });
        const timer = window.setInterval(async () => {
            const codes = await detector.detect(video);

            if (codes.length === 0) {
                return;
            }

            window.clearInterval(timer);
            stream.getTracks().forEach((track) => track.stop());
            input.value = codes[0].rawValue;
            input.form?.requestSubmit();
        }, 700);
    });
};

document.addEventListener('DOMContentLoaded', initPage);
document.addEventListener('emca:page-load', initPage);

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');

    if (!button) {
        return;
    }

    await navigator.clipboard.writeText(button.dataset.copy);
    const original = button.textContent;
    button.textContent = button.dataset.copied || 'Copied';
    window.setTimeout(() => {
        button.textContent = original;
    }, 1600);
});
