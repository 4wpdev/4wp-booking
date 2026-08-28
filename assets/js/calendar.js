(function () {
	'use strict';

	if (!window.forwpBooking) {
		return;
	}

	var cfg = window.forwpBooking;

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function qsa(root, sel) {
		return Array.prototype.slice.call(root.querySelectorAll(sel));
	}

	function pad(n) {
		return n < 10 ? '0' + n : String(n);
	}

	function isoDate(d) {
		return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
	}

	function dateLocale() {
		return cfg.locale || undefined;
	}

	function monthLabel(year, month) {
		return new Date(year, month, 1).toLocaleString(dateLocale(), {
			month: 'long',
			year: 'numeric',
		});
	}

	function formatDayHeading(iso) {
		var parts = String(iso || '').split('-');
		if (parts.length !== 3) {
			return iso;
		}
		var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
		if (isNaN(d.getTime())) {
			return iso;
		}
		return d.toLocaleDateString(dateLocale(), {
			weekday: 'short',
			day: 'numeric',
		});
	}

	function formatDateLabel(iso) {
		var parts = String(iso || '').split('-');
		if (parts.length !== 3) {
			return iso;
		}
		var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
		if (isNaN(d.getTime())) {
			return iso;
		}
		return d.toLocaleDateString(dateLocale(), {
			weekday: 'short',
			day: 'numeric',
			month: 'long',
		});
	}

	function initials(label) {
		var parts = String(label || '').trim().split(/\s+/).filter(Boolean);
		var a = parts[0] ? parts[0].charAt(0) : '';
		var b = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
		return (a + b).toUpperCase();
	}

	function setEmpty(box, message) {
		if (!box) {
			return;
		}
		box.innerHTML = '';
		var p = document.createElement('p');
		p.className = 'forwp-booking__empty';
		p.textContent = message;
		box.appendChild(p);
	}

	function specialistLabel(item) {
		return (item && (item.specialist_label || item.label)) || '';
	}

	function serviceLabel(item) {
		return (item && item.service_label) || '';
	}

	function displayLabel(item) {
		var doctor = specialistLabel(item);
		var service = serviceLabel(item);
		if (doctor && service) {
			return doctor + ' · ' + service;
		}
		return doctor || service;
	}

	function uniqueRows(items, keyFn, labelFn) {
		var seen = {};
		var out = [];
		items.forEach(function (item) {
			var key = keyFn(item);
			if (!key || seen[key]) {
				return;
			}
			seen[key] = true;
			out.push({ key: key, label: labelFn(item), item: item });
		});
		return out;
	}

	function fillSummary(root, doctor, date, time) {
		var doctorEl = qs(root, '[data-forwp-summary-doctor]');
		var dateEl = qs(root, '[data-forwp-summary-date]');
		var timeEl = qs(root, '[data-forwp-summary-time]');
		if (doctorEl) {
			doctorEl.textContent = doctor || '';
			var doctorItem = doctorEl.closest('.forwp-booking__summary-item');
			if (doctorItem) {
				doctorItem.hidden = !doctor;
			}
		}
		if (dateEl) {
			dateEl.textContent = formatDateLabel(date);
		}
		if (timeEl) {
			timeEl.textContent = time || '';
		}
	}

	function request(path, options) {
		options = options || {};
		return fetch(cfg.restUrl + path, {
			method: options.method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce,
			},
			credentials: 'same-origin',
			body: options.body ? JSON.stringify(options.body) : undefined,
		}).then(function (res) {
			return res.json().then(function (json) {
				if (!res.ok) {
					var msg = json && json.message ? json.message : cfg.strings.error;
					throw new Error(msg);
				}
				return json;
			});
		});
	}

	function showStatus(root, message, isError) {
		var el = qs(root, '[data-forwp-status]');
		if (!el) {
			return;
		}
		if (!message) {
			el.hidden = true;
			el.textContent = '';
			return;
		}
		el.hidden = false;
		el.textContent = message;
		el.classList.toggle('is-error', !!isError);
	}

	function fieldWrap(form, name) {
		return qs(form, '[data-field="' + name + '"]');
	}

	function setFieldError(wrap, message) {
		if (!wrap) {
			return;
		}
		var input = qs(wrap, 'input, textarea');
		var err = qs(wrap, '[data-error]');
		wrap.classList.add('is-invalid');
		if (input) {
			input.setAttribute('aria-invalid', 'true');
		}
		if (err) {
			err.hidden = false;
			err.textContent = message;
		}
	}

	function clearFieldError(wrap) {
		if (!wrap) {
			return;
		}
		var input = qs(wrap, 'input, textarea');
		var err = qs(wrap, '[data-error]');
		wrap.classList.remove('is-invalid');
		if (input) {
			input.removeAttribute('aria-invalid');
		}
		if (err) {
			err.hidden = true;
			err.textContent = '';
		}
	}

	function validateName(value) {
		var v = String(value || '').trim();
		if (!v) {
			return cfg.strings.errorRequired;
		}
		if (v.length < 2) {
			return cfg.strings.errorName;
		}
		return '';
	}

	function validatePhone(value) {
		var v = String(value || '').trim();
		if (!v) {
			return cfg.strings.errorRequired;
		}
		if (!/^[+\d][\d\s\-()]{8,19}$/.test(v)) {
			return cfg.strings.errorPhone;
		}
		var digits = v.replace(/\D/g, '');
		if (digits.length < 10 || digits.length > 15) {
			return cfg.strings.errorPhone;
		}
		return '';
	}

	function validateEmail(value) {
		var v = String(value || '').trim();
		if (!v) {
			return '';
		}
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) {
			return cfg.strings.errorEmail;
		}
		return '';
	}

	function validateForm(form) {
		var firstInvalid = null;
		var checks = [
			{ name: 'first_name', fn: validateName },
			{ name: 'last_name', fn: validateName },
			{ name: 'phone', fn: validatePhone },
			{ name: 'email', fn: validateEmail },
		];
		checks.forEach(function (check) {
			var wrap = fieldWrap(form, check.name);
			var input = wrap ? qs(wrap, 'input') : form[check.name];
			var message = check.fn(input ? input.value : '');
			if (message) {
				setFieldError(wrap, message);
				if (!firstInvalid && input) {
					firstInvalid = input;
				}
			} else {
				clearFieldError(wrap);
			}
		});
		if (firstInvalid) {
			firstInvalid.focus();
			return false;
		}
		return true;
	}

	function setStep(root, name) {
		var chooser = qs(root, '[data-forwp-chooser]');
		var inChooser = name === 'offerings' || name === 'calendar';
		if (chooser) {
			chooser.hidden = !inChooser;
		}
		qsa(root, '[data-forwp-step]').forEach(function (step) {
			var on = step.getAttribute('data-forwp-step') === name;
			step.hidden = !on;
			step.classList.toggle('is-active', on);
		});
	}

	function isMobileLayout() {
		return window.matchMedia('(max-width: 859px)').matches;
	}

	function scrollToBlock(el) {
		if (!isMobileLayout() || !el) {
			return;
		}
		window.requestAnimationFrame(function () {
			el.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	}

	function init(root) {
		var state = {
			provider: root.getAttribute('data-forwp-provider') || '',
			offerings: [],
			slots: [],
			dates: {},
			offering: null,
			serviceId: '',
			date: '',
			time: '',
			year: new Date().getFullYear(),
			month: new Date().getMonth(),
		};

		qsa(root, '[data-forwp-back]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				setStep(root, btn.getAttribute('data-forwp-back'));
			});
		});

		var weekdays = qs(root, '[data-forwp-weekdays]');
		if (weekdays) {
			weekdays.innerHTML = (cfg.strings.weekdays || []).map(function (d) {
				return '<span>' + d + '</span>';
			}).join('');
		}

		function isAdvanced() {
			return root.getAttribute('data-forwp-template') === 'advanced';
		}

		function currentFlow() {
			return root.getAttribute('data-forwp-flow') || 'staff';
		}

		function isDateFlow() {
			return currentFlow() === 'date';
		}

		function isServiceFlow() {
			return currentFlow() === 'service';
		}

		function realServices() {
			return state.offerings.filter(function (item) {
				return item.service_id && item.service_id !== 'default' && serviceLabel(item);
			});
		}

		function serviceRows() {
			return uniqueRows(
				realServices(),
				function (item) {
					return String(item.service_id);
				},
				serviceLabel
			);
		}

		function doctorRows(serviceId) {
			var source = state.offerings.filter(function (item) {
				if (!item.specialist_id) {
					return false;
				}
				if (serviceId) {
					return String(item.service_id) === String(serviceId);
				}
				return true;
			});
			return uniqueRows(
				source,
				function (item) {
					return String(item.specialist_id);
				},
				specialistLabel
			);
		}

		function offeringFor(specialistId, serviceId) {
			var match = null;
			state.offerings.forEach(function (item) {
				if (String(item.specialist_id) !== String(specialistId)) {
					return;
				}
				if (serviceId && String(item.service_id) === String(serviceId)) {
					match = item;
				}
			});
			if (match) {
				return match;
			}
			state.offerings.forEach(function (item) {
				if (!match && String(item.specialist_id) === String(specialistId)) {
					match = item;
				}
			});
			return match;
		}

		function setListTitle(text) {
			var el = qs(root, '[data-forwp-list-title]');
			if (el) {
				el.textContent = text || '';
			}
		}

		function syncTimesTitle() {
			var el = qs(root, '[data-forwp-times-title]');
			if (!el) {
				return;
			}
			el.textContent = state.date ? formatDayHeading(state.date) : cfg.strings.selectDate;
		}

		function syncIdentity() {
			var name = qs(root, '[data-forwp-selected-name]');
			var av = qs(root, '[data-forwp-selected-initials]');
			var label = displayLabel(state.offering);
			if (name) {
				name.textContent = label;
			}
			if (av) {
				av.textContent = initials(label);
			}
		}

		function paintButtons(box, rows, className, selectedKey, onPick) {
			box.innerHTML = '';
			if (!rows.length) {
				return false;
			}
			rows.forEach(function (row) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = className;
				if (selectedKey && row.key === selectedKey) {
					btn.classList.add('is-selected');
				}
				var avatar = document.createElement('span');
				avatar.className =
					className.indexOf('doctor') !== -1
						? 'forwp-booking__avatar forwp-booking__avatar--sm'
						: 'forwp-booking__avatar';
				avatar.setAttribute('aria-hidden', 'true');
				avatar.textContent = initials(row.label);
				var name = document.createElement('span');
				name.className =
					className.indexOf('doctor') !== -1
						? 'forwp-booking__doctor-name'
						: 'forwp-booking__offering-name';
				name.textContent = row.label;
				btn.appendChild(avatar);
				btn.appendChild(name);
				btn.addEventListener('click', function () {
					onPick(row);
				});
				box.appendChild(btn);
			});
			return true;
		}

		function renderAsideList() {
			var box = qs(root, '[data-forwp-doctors]');
			if (!box) {
				return;
			}
			var identity = qs(root, '.forwp-booking__identity');
			if (isDateFlow()) {
				box.hidden = true;
				box.innerHTML = '';
				if (identity) {
					identity.hidden = true;
				}
				return;
			}
			if (isServiceFlow() && !state.serviceId) {
				setListTitle(cfg.strings.selectService);
				var services = serviceRows();
				box.hidden = false;
				if (!services.length) {
					setEmpty(box, cfg.strings.noServices || cfg.strings.noOfferings);
				} else {
					paintButtons(box, services, 'forwp-booking__doctor', '', function (row) {
						pickService(row.key, true);
					});
				}
				if (identity) {
					identity.hidden = true;
				}
				return;
			}
			var doctors = doctorRows(state.serviceId || '');
			setListTitle(cfg.strings.selectDoctor);
			if (!doctors.length) {
				box.hidden = false;
				setEmpty(box, cfg.strings.noOfferings);
				if (identity) {
					identity.hidden = true;
				}
				return;
			}
			if (doctors.length === 1 && isAdvanced() && state.offering) {
				box.hidden = true;
				box.innerHTML = '';
				if (identity) {
					identity.hidden = false;
				}
				return;
			}
			box.hidden = false;
			paintButtons(
				box,
				doctors,
				'forwp-booking__doctor',
				state.offering ? String(state.offering.specialist_id) : '',
				function (row) {
					pickDoctor(row.key, true);
				}
			);
			if (identity) {
				identity.hidden = doctors.length > 1;
			}
		}

		function calendarReady() {
			if (isAdvanced()) {
				return true;
			}
			return isDateFlow() || !!state.offering || !!state.serviceId;
		}

		function usesCombinedAvailability() {
			return !state.offering;
		}

		function syncCalendarVisibility() {
			if (!isAdvanced()) {
				return;
			}
			root.setAttribute('data-forwp-calendar', calendarReady() ? 'on' : 'off');
		}

		function selectedServiceName() {
			var id = String(state.serviceId || '');
			if (!id) {
				return '';
			}
			var found = '';
			serviceRows().forEach(function (row) {
				if (row.key === id) {
					found = row.label;
				}
			});
			return found || serviceLabel(state.offering);
		}

		function syncPickedService() {
			var chip = qs(root, '[data-forwp-picked-service]');
			if (!chip) {
				return;
			}
			var label = selectedServiceName();
			var show = isServiceFlow() && !!label;
			chip.hidden = !show;
			var text = qs(chip, '[data-forwp-picked-service-label]');
			if (text) {
				text.textContent = label;
			}
			var av = qs(chip, '[data-forwp-picked-service-initials]');
			if (av) {
				av.textContent = initials(label);
			}
		}

		function clearPickedService() {
			state.offering = null;
			state.serviceId = '';
			state.date = '';
			state.time = '';
			state.slots = [];
			state.dates = {};
			if (!isAdvanced()) {
				setStep(root, 'offerings');
			}
			renderCurrentList();
			syncChip();
			syncPickedService();
			syncCalendarVisibility();
			refreshCalendarShell();
		}

		function pickService(serviceId, fromUser) {
			state.serviceId = serviceId;
			state.offering = null;
			state.date = '';
			state.time = '';
			var doctors = doctorRows(serviceId);
			if (doctors.length === 1) {
				pickDoctor(doctors[0].key, fromUser);
				return;
			}
			if (isAdvanced()) {
				syncIdentity();
				renderAsideList();
				syncPickedService();
				syncCalendarVisibility();
				loadMonth();
				if (fromUser) {
					scrollToBlock(qs(root, '.forwp-booking__month') || root);
				}
				return;
			}
			setListTitle(cfg.strings.selectDoctor);
			renderOfferings();
			syncChip();
			syncPickedService();
			if (fromUser) {
				scrollToBlock(qs(root, '[data-forwp-step="offerings"]') || root);
			}
		}

		function pickDoctor(specialistId, fromUser) {
			var item = offeringFor(specialistId, state.serviceId || '');
			if (!item) {
				return;
			}
			selectOffering(item, fromUser);
		}

		function selectOffering(item, fromUser) {
			state.offering = item;
			if (item && item.service_id && item.service_id !== 'default') {
				state.serviceId = String(item.service_id);
			}
			state.date = '';
			state.time = '';
			syncIdentity();
			renderAsideList();
			syncChip();
			syncPickedService();
			syncTimesTitle();
			syncCalendarVisibility();
			if (!isAdvanced()) {
				setStep(root, 'calendar');
			}
			loadMonth();
			if (fromUser) {
				scrollToBlock(qs(root, '.forwp-booking__month') || root);
			}
		}

		function pickFirstAvailableDate() {
			if (state.date && state.dates[state.date]) {
				return;
			}
			var keys = Object.keys(state.dates).sort();
			state.date = keys.length ? keys[0] : '';
		}

		function syncTabs() {
			var current = currentFlow();
			qsa(root, '[data-forwp-tab]').forEach(function (btn) {
				var on = btn.getAttribute('data-forwp-tab') === current;
				btn.classList.toggle('is-active', on);
				btn.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			var lead = qs(root, '[data-forwp-calendar-lead]');
			if (lead) {
				lead.hidden = !isDateFlow();
			}
		}

		function syncChip() {
			var chip = qs(root, '[data-forwp-clear-doctor]');
			if (!chip) {
				return;
			}
			var labelEl = qs(root, '[data-forwp-offering-label]');
			var chipInitials = qs(root, '[data-forwp-offering-initials]');
			var text = '';
			if (isServiceFlow() && state.serviceId && !state.offering) {
				var services = serviceRows();
				services.forEach(function (row) {
					if (row.key === String(state.serviceId)) {
						text = row.label;
					}
				});
			} else if (state.offering) {
				text = displayLabel(state.offering);
			}
			var show = !isDateFlow() && !!text;
			chip.hidden = !show;
			if (labelEl) {
				labelEl.textContent = text;
			}
			if (chipInitials) {
				chipInitials.textContent = initials(text);
			}
		}

		function refreshCalendarShell() {
			if (!qs(root, '[data-forwp-days]')) {
				return;
			}
			renderCalendar();
			renderSlots();
			syncTimesTitle();
		}

		function setTab(tab) {
			var next = tab === 'date' || tab === 'service' ? tab : 'staff';
			root.setAttribute('data-forwp-flow', next);
			state.offering = null;
			state.serviceId = '';
			state.date = '';
			state.time = '';
			state.slots = [];
			state.dates = {};
			syncTabs();
			syncChip();
			syncPickedService();
			syncCalendarVisibility();
			if (next === 'date') {
				if (!isAdvanced()) {
					setStep(root, 'calendar');
				}
				syncIdentity();
				renderAsideList();
				loadMonth();
			} else if (isAdvanced()) {
				renderCurrentList();
				if (calendarReady()) {
					loadMonth();
				} else {
					refreshCalendarShell();
				}
			} else {
				setStep(root, 'offerings');
				renderCurrentList();
			}
		}

		qsa(root, '[data-forwp-tab]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				setTab(btn.getAttribute('data-forwp-tab'));
			});
		});

		var pickedService = qs(root, '[data-forwp-picked-service]');
		if (pickedService) {
			pickedService.addEventListener('click', function () {
				clearPickedService();
			});
		}

		var clearDoctor = qs(root, '[data-forwp-clear-doctor]');
		if (clearDoctor) {
			clearDoctor.addEventListener('click', function () {
				if (isServiceFlow()) {
					state.offering = null;
					state.serviceId = '';
					state.date = '';
					state.time = '';
					setStep(root, 'offerings');
					renderCurrentList();
					syncChip();
					return;
				}
				setTab('staff');
			});
		}

		function renderCurrentList() {
			if (isAdvanced()) {
				renderAsideList();
				syncIdentity();
				syncPickedService();
				syncCalendarVisibility();
				return;
			}
			renderOfferings();
			syncChip();
			syncPickedService();
		}

		function loadOfferings() {
			showStatus(root, cfg.strings.loading, false);
			return request('offerings?provider=' + encodeURIComponent(state.provider))
				.then(function (data) {
					showStatus(root, '', false);
					state.offerings = data.offerings || [];
					renderCurrentList();
				})
				.catch(function (err) {
					showStatus(root, err.message || cfg.strings.error, true);
				});
		}

		function renderOfferings() {
			var box = qs(root, '[data-forwp-offerings]');
			if (!box) {
				return;
			}
			if (isServiceFlow() && !state.serviceId) {
				setListTitle(cfg.strings.selectService);
				var services = serviceRows();
				if (!services.length) {
					setEmpty(box, cfg.strings.noServices || cfg.strings.noOfferings);
					return;
				}
				paintButtons(box, services, 'forwp-booking__offering', '', function (row) {
					pickService(row.key, true);
				});
				return;
			}
			var doctors = doctorRows(state.serviceId || '');
			setListTitle(cfg.strings.selectDoctor);
			if (!doctors.length) {
				setEmpty(box, cfg.strings.noOfferings);
				return;
			}
			paintButtons(box, doctors, 'forwp-booking__offering', '', function (row) {
				pickDoctor(row.key, true);
			});
		}

		function loadMonth() {
			if (!calendarReady()) {
				return;
			}
			var from = isoDate(new Date(state.year, state.month, 1));
			var to = isoDate(new Date(state.year, state.month + 1, 0));
			var path;
			if (usesCombinedAvailability()) {
				path =
					'availability?provider=' +
					encodeURIComponent(state.provider) +
					'&from=' +
					from +
					'&to=' +
					to;
				if (state.serviceId) {
					path += '&service_id=' + encodeURIComponent(state.serviceId);
				}
			} else {
				path =
					'slots?provider=' +
					encodeURIComponent(state.provider) +
					'&offering_id=' +
					encodeURIComponent(state.offering.id) +
					'&from=' +
					from +
					'&to=' +
					to;
			}
			showStatus(root, cfg.strings.loading, false);
			request(path)
				.then(function (data) {
					showStatus(root, '', false);
					state.slots = data.slots || [];
					state.dates = {};
					state.slots.forEach(function (slot) {
						state.dates[slot.date] = true;
					});
					if (isAdvanced()) {
						pickFirstAvailableDate();
					}
					renderCalendar();
					renderSlots();
					syncTimesTitle();
				})
				.catch(function (err) {
					showStatus(root, err.message || cfg.strings.error, true);
				});
		}

		function renderCalendar() {
			qs(root, '[data-forwp-month-label]').textContent = monthLabel(state.year, state.month);
			var grid = qs(root, '[data-forwp-days]');
			grid.innerHTML = '';
			var first = new Date(state.year, state.month, 1);
			var startPad = (first.getDay() + 6) % 7;
			var daysInMonth = new Date(state.year, state.month + 1, 0).getDate();
			var i;
			for (i = 0; i < startPad; i += 1) {
				var empty = document.createElement('span');
				grid.appendChild(empty);
			}
			for (i = 1; i <= daysInMonth; i += 1) {
				var date = isoDate(new Date(state.year, state.month, i));
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'forwp-booking__day';
				btn.textContent = String(i);
				var available = !!state.dates[date];
				if (!available) {
					btn.classList.add('is-muted');
					btn.disabled = true;
				} else {
					btn.classList.add('is-available');
				}
				if (state.date === date) {
					btn.classList.add('is-selected');
				}
				btn.addEventListener('click', function (picked) {
					return function () {
						state.date = picked;
						state.time = '';
						renderCalendar();
						renderSlots();
						syncTimesTitle();
						scrollToBlock(
							qs(root, '.forwp-booking__times') ||
								qs(root, '[data-forwp-slots]') ||
								root
						);
					};
				}(date));
				grid.appendChild(btn);
			}
		}

		function renderSlots() {
			var box = qs(root, '[data-forwp-slots]');
			box.innerHTML = '';
			if (!state.slots.length) {
				setEmpty(box, cfg.strings.noMonthSlots || cfg.strings.noSlots);
				return;
			}
			if (!state.date) {
				setEmpty(box, cfg.strings.selectDate);
				return;
			}
			var daySlots = state.slots.filter(function (slot) {
				return slot.date === state.date;
			});
			daySlots.sort(function (a, b) {
				if (a.time_start === b.time_start) {
					return String(a.label || '').localeCompare(String(b.label || ''));
				}
				return a.time_start < b.time_start ? -1 : 1;
			});
			if (!daySlots.length) {
				setEmpty(box, cfg.strings.noSlots);
				return;
			}
			daySlots.forEach(function (slot) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'forwp-booking__slot';
				var time = document.createElement('span');
				time.className = 'forwp-booking__slot-time';
				time.textContent = slot.time_start;
				btn.appendChild(time);
				if (slot.label && usesCombinedAvailability()) {
					var meta = document.createElement('span');
					meta.className = 'forwp-booking__slot-meta';
					meta.textContent = slot.label;
					btn.appendChild(meta);
				}
				if (state.time === slot.time_start && (!slot.offering_id || (state.offering && state.offering.id === slot.offering_id))) {
					btn.classList.add('is-selected');
				}
				btn.addEventListener('click', function () {
					state.time = slot.time_start;
					if (slot.offering_id) {
						var match = null;
						state.offerings.forEach(function (item) {
							if (String(item.id) === String(slot.offering_id)) {
								match = item;
							}
						});
						state.offering = match || {
							id: slot.offering_id,
							label: slot.label || '',
							specialist_label: slot.label || '',
						};
					}
					fillSummary(
						root,
						displayLabel(state.offering),
						state.date,
						slot.time_start
					);
					setStep(root, 'details');
				});
				box.appendChild(btn);
			});
		}

		qs(root, '[data-forwp-prev-month]').addEventListener('click', function () {
			state.month -= 1;
			if (state.month < 0) {
				state.month = 11;
				state.year -= 1;
			}
			loadMonth();
		});
		qs(root, '[data-forwp-next-month]').addEventListener('click', function () {
			state.month += 1;
			if (state.month > 11) {
				state.month = 0;
				state.year += 1;
			}
			loadMonth();
		});

		var form = qs(root, '[data-forwp-form]');
		qsa(form, '.forwp-booking__field input, .forwp-booking__field textarea').forEach(function (input) {
			input.addEventListener('input', function () {
				clearFieldError(input.closest('[data-field]'));
			});
		});
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (!validateForm(form)) {
				return;
			}
			var submit = qs(form, '.forwp-booking__submit');
			submit.disabled = true;
			request('book', {
				method: 'POST',
				body: {
					provider: state.provider,
					offering_id: state.offering.id,
					date: state.date,
					time_start: state.time,
					first_name: form.first_name.value.trim(),
					last_name: form.last_name.value.trim(),
					phone: form.phone.value.trim(),
					email: form.email.value.trim(),
					comment: form.comment.value.trim(),
					website: form.website.value,
				},
			})
				.then(function (data) {
					qs(root, '[data-forwp-done]').textContent = data.message || cfg.strings.success;
					setStep(root, 'done');
					showStatus(root, '', false);
				})
				.catch(function (err) {
					showStatus(root, err.message || cfg.strings.error, true);
				})
				.then(function () {
					submit.disabled = false;
				});
		});

		syncTabs();
		syncChip();
		syncPickedService();
		syncCalendarVisibility();
		if (isAdvanced()) {
			setStep(root, 'calendar');
			loadOfferings().then(function () {
				if (calendarReady()) {
					loadMonth();
				} else {
					syncCalendarVisibility();
				}
			});
		} else if (isDateFlow()) {
			setStep(root, 'calendar');
			loadMonth();
			loadOfferings();
		} else {
			loadOfferings();
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		qsa(document, '[data-forwp-booking]').forEach(init);
	});
})();
