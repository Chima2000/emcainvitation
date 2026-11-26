import jsQR from 'jsqr';

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
        const templatePicker = form.querySelector('[data-template-picker]');
        const templateSearch = form.querySelector('[data-template-select-search]');
        const templateMenu = form.querySelector('[data-template-results]');
        const templateToggle = form.querySelector('[data-template-menu-toggle]');
        const templateEmpty = form.querySelector('[data-template-select-empty]');
        let templateMenuOpen = false;
        let templateQuery = '';
        const selectedTemplateId = () => form.querySelector('[name="invitation_template_id"]:checked')?.value
            || form.querySelector('[name="invitation_template_id"]')?.value
            || '';
        const previewTemplates = form.dataset.previewTemplates ? JSON.parse(form.dataset.previewTemplates) : {};
        const previewDefault = form.dataset.previewDefault ? JSON.parse(form.dataset.previewDefault) : {};
        let nextColorIndex = colorList?.querySelectorAll('[data-dress-code-color-row]').length || 0;

        const readValue = (name) => form.querySelector(`[name="${name}"]`)?.value.trim() || '';
        const setCardText = (name, value, hideWhenEmpty = false) => {
            const field = previewCard?.querySelector(`[data-card-field="${name}"]`);

            if (!field) {
                return;
            }

            field.textContent = value;

            if (hideWhenEmpty) {
                field.hidden = value === '';
            }
        };
        const updatePreview = () => {
            if (!previewCard) {
                return;
            }

            const selectedType = form.querySelector('[name="type"]');
            const selectedTypeLabel = selectedType?.selectedOptions[0]?.textContent.trim() || '';
            const templateId = selectedTemplateId();
            const design = templateId
                ? previewTemplates[templateId]
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

                const photo = previewCard.querySelector('[data-card-photo]');
                const showPhoto = Boolean(design.portraitSlot && design.portraitUrl);
                previewCard.classList.toggle('jp-card--has-photo', showPhoto);
                [...previewCard.classList].filter((name) => name === 'jp-card--scene' || name.startsWith('jp-card--scene-')).forEach((name) => previewCard.classList.remove(name));

                if (design.scene) {
                    previewCard.classList.add('jp-card--scene', `jp-card--scene-${design.scene}`);
                }

                if (photo) {
                    photo.hidden = !showPhoto;
                    photo.classList.remove('jp-card__photo--arch', 'jp-card__photo--circle', 'jp-card__photo--frame');
                    photo.classList.add(`jp-card__photo--${design.portraitSlot?.shape || 'frame'}`);
                    ['top', 'left', 'width', 'height', 'radius', 'clip'].forEach((key) => {
                        const value = design.portraitSlot?.box?.[key];

                        if (value) {
                            photo.style.setProperty(`--photo-${key}`, value);
                        } else {
                            photo.style.removeProperty(`--photo-${key}`);
                        }
                    });

                    if (design.portraitSlot?.box?.copy) {
                        previewCard.style.setProperty('--photo-copy', design.portraitSlot.box.copy);
                    } else {
                        previewCard.style.removeProperty('--photo-copy');
                    }

                    if (showPhoto) {
                        photo.src = design.portraitUrl;
                    }
                }
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
                : '';
            const headline = readValue('headline') || design?.headline || form.dataset.previewInviteHeadline;
            const message = readValue('message') || design?.message || '';
            const dressCode = readValue('dress_code');
            const weddingLine = (name, pattern) => {
                const value = readValue(name);

                return value && pattern ? pattern.replace('__VALUE__', value) : '';
            };

            setCardText('kicker', design?.kicker || selectedTypeLabel);
            setCardText('title', readValue('title'), true);
            setCardText('hosts', `${host}${partner ? ` & ${partner}` : ''}`);
            setCardText('headline', headline);
            setCardText('when', formattedDate, true);
            setCardText('venue', readValue('venue'), true);

            const linesHost = previewCard.querySelector('[data-card-lines]');

            if (linesHost) {
                const type = form.querySelector('[name="type"]')?.value;
                const lines = [];

                if (type === 'wedding') {
                    const couple = [readValue('bride_name'), readValue('groom_name')].filter(Boolean).join(' & ');

                    if (couple) {
                        lines.push(couple);
                    }

                    [
                        weddingLine('bride_family', form.dataset.previewBrideFamily),
                        weddingLine('groom_family', form.dataset.previewGroomFamily),
                        weddingLine('ceremony_place', form.dataset.previewVows),
                        weddingLine('reception_venue', form.dataset.previewReception),
                        weddingLine('reception_time', form.dataset.previewReceptionTime),
                        weddingLine('arrive_by', form.dataset.previewArrive),
                    ].filter(Boolean).forEach((line) => lines.push(line));
                }

                linesHost.replaceChildren(...lines.map((text) => {
                    const line = document.createElement('p');
                    line.textContent = text;

                    return line;
                }));
            }

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

        const renderTemplateMenu = () => {
            let visibleCount = 0;

            form.querySelectorAll('[data-template-choice]').forEach((choice) => {
                const haystack = choice.dataset.templateName ?? '';
                const matches = templateQuery === '' || haystack.includes(templateQuery);

                choice.hidden = !matches;

                if (matches) {
                    visibleCount += 1;
                }
            });

            const noneChoice = form.querySelector('[data-template-none]');

            if (noneChoice) {
                noneChoice.hidden = templateQuery !== '';
            }

            if (templateMenu) {
                templateMenu.hidden = !templateMenuOpen;
            }

            templateSearch?.setAttribute('aria-expanded', templateMenuOpen ? 'true' : 'false');
            templateToggle?.setAttribute('aria-expanded', templateMenuOpen ? 'true' : 'false');

            if (templateEmpty) {
                templateEmpty.hidden = !templateMenuOpen || templateQuery === '' || visibleCount > 0;
            }
        };
        const openTemplateMenu = (query) => {
            templateQuery = query;
            templateMenuOpen = true;
            renderTemplateMenu();
        };
        const closeTemplateMenu = () => {
            templateQuery = '';
            templateMenuOpen = false;
            renderTemplateMenu();
        };

        organizationToggle?.addEventListener('change', syncOrganizationName);
        templateToggle?.addEventListener('click', () => {
            if (templateMenuOpen) {
                closeTemplateMenu();

                return;
            }

            openTemplateMenu('');
            templateSearch?.focus();
            templateSearch?.select();
        });
        templateSearch?.addEventListener('focus', () => {
            templateSearch.select();
        });
        templateSearch?.addEventListener('input', () => {
            const query = templateSearch.value.trim().toLowerCase();

            if (query === '') {
                const none = form.querySelector('[data-template-none] input');

                if (none) {
                    none.checked = true;
                }

                closeTemplateMenu();
                updatePreview();

                return;
            }

            openTemplateMenu(query);
        });
        templateMenu?.addEventListener('change', (event) => {
            const choice = event.target.closest('[data-template-choice], [data-template-none]');
            const label = choice?.querySelector('span')?.textContent.trim() ?? '';

            if (templateSearch) {
                templateSearch.value = event.target.value === '' ? '' : label;
            }

            closeTemplateMenu();
            updatePreview();
        });
        document.addEventListener('click', (event) => {
            if (!templateMenuOpen || templatePicker?.contains(event.target)) {
                return;
            }

            closeTemplateMenu();
        });
        form.addEventListener('input', updatePreview);
        form.addEventListener('change', updatePreview);
        syncOrganizationName();
        closeTemplateMenu();
    });
};

const isMobileDevice = () => {
    if (typeof navigator.userAgentData?.mobile === 'boolean') {
        return navigator.userAgentData.mobile;
    }

    const shortestSide = Math.min(window.screen.width, window.screen.height);

    return window.matchMedia('(pointer: coarse)').matches && shortestSide <= 900;
};

const doorWaitMs = 3000;
const doorMessageMs = 4000;
let doorCheckStartedAt = 0;

const showDoorLoading = () => {
    const card = document.querySelector('[data-door-card]');
    const body = card?.querySelector('.card-body');

    if (!body || body.querySelector('[data-door-loading]')) {
        return;
    }

    const loading = document.createElement('div');
    loading.dataset.doorLoading = '1';
    loading.setAttribute('role', 'status');
    loading.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span></span>';
    loading.lastElementChild.textContent = card.dataset.doorChecking || 'Checking the card…';
    body.prepend(loading);
};

const setDoorBusy = (busy) => {
    document.querySelectorAll('[data-door-card] button, [data-door-card] input').forEach((control) => {
        control.disabled = busy;
    });
};

const revealDoorResult = (result) => {
    document.querySelector('[data-door-loading]')?.remove();
    result.dataset.doorReady = '1';
    setDoorBusy(false);
    doorCheckStartedAt = 0;

    if (result.dataset.doorResult === 'denied' && isMobileDevice() && typeof navigator.vibrate === 'function') {
        navigator.vibrate([280, 120, 280, 120, 360]);
    }

    window.setTimeout(() => {
        result.style.transition = 'opacity 0.2s ease';
        result.style.opacity = '0';
        window.setTimeout(() => result.remove(), 200);
    }, doorMessageMs);
    showScannerVerdict(result.dataset.doorResult);
};

const showScannerVerdict = (kind) => {
    const stage = document.querySelector('[data-scanner-stage]');
    const verdict = document.querySelector('[data-scanner-verdict]');
    const icon = verdict?.querySelector('[data-scanner-icon]');

    if (!stage || !verdict || !icon || (kind !== 'verified' && kind !== 'denied')) {
        return;
    }

    const line = document.querySelector('[data-scanner-line]');

    if (line) {
        line.hidden = true;
    }
    const video = stage.querySelector('[data-scanner]');

    if (video) {
        video.hidden = true;
    }

    icon.className = kind === 'verified' ? 'fi fi-rr-check' : 'fi fi-rr-cross';
    verdict.dataset.scannerVerdict = kind;
    verdict.hidden = false;
    stage.hidden = false;

    window.setTimeout(() => {
        verdict.hidden = true;
        stage.hidden = true;
    }, 3000);
};

const settleDoorResult = () => {
    const result = document.querySelector('[data-door-result]:not([data-door-holding])');

    if (!result) {
        if (doorCheckStartedAt) {
            document.querySelector('[data-door-loading]')?.remove();
            setDoorBusy(false);
            doorCheckStartedAt = 0;
        }

        return;
    }

    result.dataset.doorHolding = '1';
    showDoorLoading();
    setDoorBusy(true);

    const started = doorCheckStartedAt || Date.now();
    const wait = Math.max(0, doorWaitMs - (Date.now() - started));

    window.setTimeout(() => revealDoorResult(result), wait);
};

const setupDoorArrivals = () => {
    document.querySelectorAll('[data-door-arrival-list]').forEach((list) => {
        if (list.dataset.doorArrivalReady === '1') {
            return;
        }

        list.dataset.doorArrivalReady = '1';

        const items = [...list.querySelectorAll('[data-door-arrival]')];
        const pager = list.parentElement?.querySelector('[data-door-arrival-pager]');
        const previous = pager?.querySelector('[data-door-arrival-prev]');
        const next = pager?.querySelector('[data-door-arrival-next]');
        const pageSize = 5;
        let page = 0;
        const lastPage = Math.max(0, Math.ceil(items.length / pageSize) - 1);

        const showPage = () => {
            items.forEach((item, index) => {
                item.hidden = index < page * pageSize || index >= (page + 1) * pageSize;
            });

            if (previous) {
                previous.hidden = page === 0;
            }

            if (next) {
                next.hidden = page >= lastPage;
            }
        };

        previous?.addEventListener('click', () => {
            page = Math.max(0, page - 1);
            showPage();
        });

        next?.addEventListener('click', () => {
            page = Math.min(lastPage, page + 1);
            showPage();
        });

        showPage();
    });
};

const setupGuestRows = () => {
    document.querySelectorAll('[data-guest-row-list]').forEach((list) => {
        if (list.dataset.guestRowReady === '1') {
            return;
        }

        list.dataset.guestRowReady = '1';

        const items = [...list.querySelectorAll('[data-guest-row]')];
        const pager = list.closest('[data-guest-list]')?.querySelector('[data-guest-row-pager]');
        const previous = pager?.querySelector('[data-guest-row-prev]');
        const next = pager?.querySelector('[data-guest-row-next]');
        const pageSize = 5;
        let page = 0;
        const lastPage = Math.max(0, Math.ceil(items.length / pageSize) - 1);

        const showPage = () => {
            items.forEach((item, index) => {
                item.hidden = index < page * pageSize || index >= (page + 1) * pageSize;
            });

            if (previous) {
                previous.hidden = page === 0;
            }

            if (next) {
                next.hidden = page >= lastPage;
            }
        };

        previous?.addEventListener('click', () => {
            page = Math.max(0, page - 1);
            showPage();
        });

        next?.addEventListener('click', () => {
            page = Math.min(lastPage, page + 1);
            showPage();
        });

        showPage();
    });
};

const initPage = () => {
    settleDoorResult();
    setupDoorArrivals();
    setupGuestRows();
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
    const stage = document.querySelector('[data-scanner-stage]');
    const line = document.querySelector('[data-scanner-line]');
    const input = document.querySelector('[data-check-in-code]');
    const status = document.querySelector('[data-scanner-status]');
    const startLabel = start?.querySelector('[data-scanner-label]');

    if (!start || !video || !input || start.dataset.scannerReady === '1') {
        return;
    }

    start.dataset.scannerReady = '1';

    const setStartLabel = (label) => {
        if (startLabel) {
            startLabel.textContent = label;

            return;
        }

        start.textContent = label;
    };
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });
    let stream = null;
    let timer = 0;
    let detector = null;
    let submitted = false;

    input.form?.addEventListener('submit', () => {
        window.setTimeout(() => {
            input.form?.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
            });
        }, 0);
    });

    const setStatus = (message) => {
        if (!status) {
            return;
        }

        status.hidden = message === '';
        status.textContent = message;
    };
    const stopScanner = () => {
        window.clearInterval(timer);
        timer = 0;
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        video.pause();
        video.srcObject = null;
        video.hidden = true;

        if (line) {
            line.hidden = true;
        }

        if (stage && stage.querySelector('[data-scanner-verdict]')?.hidden !== false) {
            stage.hidden = true;
        }

        start.dataset.scannerActive = 'false';
        start.setAttribute('aria-pressed', 'false');
        setStartLabel(start.dataset.scannerStartLabel || start.textContent);
        setStatus('');
    };
    const finishScan = (value) => {
        if (submitted) {
            return;
        }

        submitted = true;
        const code = String(value ?? '').trim();

        if (code === '') {
            submitted = false;

            return;
        }

        stopScanner();
        input.value = code;
        input.form?.requestSubmit();
    };
    const readFrame = async () => {
        if (submitted || !stream || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) {
            return;
        }

        if (detector) {
            try {
                const codes = await detector.detect(video);

                if (codes.length > 0) {
                    finishScan(codes[0].rawValue);

                    return;
                }
            } catch {
                detector = null;
            }
        }

        const width = video.videoWidth;
        const height = video.videoHeight;

        if (!width || !height || !context) {
            return;
        }

        const scale = Math.min(1, 480 / width);
        canvas.width = Math.floor(width * scale);
        canvas.height = Math.floor(height * scale);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const image = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' });

        if (code?.data) {
            finishScan(code.data);
        }
    };

    start.addEventListener('click', async () => {
        if (stream) {
            stopScanner();

            return;
        }

        submitted = false;

        if (stage) {
            stage.hidden = false;
        }

        setStatus('');

        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            setStatus(start.dataset.scannerInsecure || 'Open this site with https to use the camera, or type the card number.');

            return;
        }

        const openCamera = async (videoConstraints) => navigator.mediaDevices.getUserMedia({
            audio: false,
            video: videoConstraints,
        });

        try {
            stream = await openCamera({ facingMode: { ideal: 'environment' } });
        } catch {
            try {
                stream = await openCamera(true);
            } catch {
                setStatus(start.dataset.scannerDenied || 'Allow the camera, or type the card number.');

                return;
            }
        }

        if ('BarcodeDetector' in window) {
            try {
                const formats = typeof window.BarcodeDetector.getSupportedFormats === 'function'
                    ? await window.BarcodeDetector.getSupportedFormats()
                    : ['qr_code'];

                if (formats.includes('qr_code')) {
                    detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                }
            } catch {
                detector = null;
            }
        }

        if (!start.dataset.scannerStartLabel) {
            start.dataset.scannerStartLabel = start.textContent.trim();
        }

        video.srcObject = stream;
        video.hidden = false;

        if (stage) {
            stage.hidden = false;
        }

        if (line) {
            line.hidden = false;
        }

        const verdict = stage?.querySelector('[data-scanner-verdict]');

        if (verdict) {
            verdict.hidden = true;
        }

        try {
            await video.play();
        } catch {
            stopScanner();

            if (stage) {
                stage.hidden = false;
            }

            setStatus(start.dataset.scannerDenied || 'Allow the camera, or type the card number.');

            return;
        }

        start.dataset.scannerActive = 'true';
        start.setAttribute('aria-pressed', 'true');
        setStartLabel(start.dataset.scannerStopLabel || 'Stop scanning');
        setStatus(start.dataset.scannerLooking || 'Point the camera at the invitation code.');
        timer = window.setInterval(() => {
            readFrame();
        }, 300);
    });
};

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.querySelector('[data-check-in-code]')) {
        return;
    }

    doorCheckStartedAt = Date.now();
    showDoorLoading();
    window.setTimeout(() => {
        if (doorCheckStartedAt && !document.querySelector('[data-door-result]')) {
            document.querySelector('[data-door-loading]')?.remove();
            setDoorBusy(false);
            doorCheckStartedAt = 0;
        }
    }, 8000);
}, true);

document.addEventListener('DOMContentLoaded', initPage);
document.addEventListener('emca:page-load', initPage);

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-door-refresh]')) {
        return;
    }

    window.location.reload();
});

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
