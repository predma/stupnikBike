@extends('admin.layout')

@section('content')
    <div class="card">
        <div class="table-head" style="padding: 0 0 18px 0;">
            <div>
                <h2>{{ $title }}</h2>
                <div class="muted">{{ $subtitle }}</div>
            </div>
            <a class="btn secondary" href="{{ $backUrl }}">Natrag</a>
        </div>

        @if ($errors->any())
            <div class="card" style="margin-bottom: 18px; border-color: rgba(251, 113, 133, 0.35); background: rgba(127, 29, 29, 0.22);">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form id="reservation-form" method="POST" action="{{ $action }}">
            @csrf
            @if (($method ?? 'POST') !== 'POST')
                @method($method)
            @endif

            <input type="hidden" name="starts_at" id="starts_at" value="{{ old('starts_at', $values['starts_at'] ?? '') }}">
            <input type="hidden" name="ends_at" id="ends_at" value="{{ old('ends_at', $values['ends_at'] ?? '') }}">

            <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:16px;">
                <div class="field">
                    <label class="muted" for="user_id">Tko naručuje <span style="color:#fda4af;">*</span></label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Odaberi korisnika</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('user_id', $values['user_id'] ?? '') === (string) $user->id)>
                                {{ $user->name }} · {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="muted" for="reservation_number">Broj rezervacije</label>
                    <input id="reservation_number" name="reservation_number" type="text" value="{{ old('reservation_number', $values['reservation_number'] ?? '') }}" placeholder="Automatski ako ostane prazno">
                </div>

                <div class="field">
                    <label class="muted" for="bike_id">Koji bicikl <span style="color:#fda4af;">*</span></label>
                    <select id="bike_id" name="bike_id" required>
                        <option value="">Odaberi bicikl</option>
                        @foreach ($bikes as $bike)
                            <option
                                value="{{ $bike->id }}"
                                data-stock="{{ $bike->stock_quantity }}"
                                @selected((string) old('bike_id', $values['bike_id'] ?? '') === (string) $bike->id)
                            >
                                {{ $bike->code }} · {{ $bike->name }} {{ $bike->size ? '(' . $bike->size . ')' : '' }} · lager {{ $bike->stock_quantity }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="muted" for="quantity">Koliko bicikala <span style="color:#fda4af;">*</span></label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity', $values['quantity'] ?? 1) }}" required>
                    <div id="quantity-help" class="muted" style="font-size: 12px;">Odaberi bicikl i datum za provjeru lagera.</div>
                </div>

                <div class="field">
                    <label class="muted" for="reservation_date">Za kada <span style="color:#fda4af;">*</span></label>
                    <input id="reservation_date" name="reservation_date" type="date" value="{{ old('reservation_date', $values['reservation_date'] ?? now()->toDateString()) }}" required>
                </div>

                <div class="field" id="slot-field" style="display:none;">
                    <label class="muted" for="slot">Termin <span style="color:#fda4af;">*</span></label>
                    <select id="slot" name="slot"></select>
                </div>

                <div class="field">
                    <label class="muted" for="station_id">Stanica</label>
                    <select id="station_id" name="station_id">
                        <option value="">Bez stanice</option>
                        @foreach ($stations as $station)
                            <option value="{{ $station->id }}" @selected((string) old('station_id', $values['station_id'] ?? '') === (string) $station->id)>
                                {{ $station->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="muted" for="status">Status <span style="color:#fda4af;">*</span></label>
                    <select id="status" name="status" required>
                        @foreach (['confirmed' => 'Confirmed', 'pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $values['status'] ?? 'confirmed') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="muted" for="payment_status">Plaćanje <span style="color:#fda4af;">*</span></label>
                    <select id="payment_status" name="payment_status" required>
                        <option value="unpaid" @selected(old('payment_status', $values['payment_status'] ?? 'unpaid') === 'unpaid')>Neplaćeno</option>
                        <option value="paid" @selected(old('payment_status', $values['payment_status'] ?? '') === 'paid')>Plaćeno</option>
                    </select>
                </div>

                <div class="field">
                    <label class="muted" for="payment_method">Način plaćanja</label>
                    <input id="payment_method" name="payment_method" type="text" value="{{ old('payment_method', $values['payment_method'] ?? '') }}" placeholder="Kartica, gotovina, virman...">
                </div>

                <div class="field">
                    <label class="muted" for="total_price">Ukupna cijena</label>
                    <input id="total_price" name="total_price" type="number" min="0" step="0.01" value="{{ old('total_price', $values['total_price'] ?? 0) }}" readonly>
                    <div id="price-help" class="muted" style="font-size: 12px;">Cijena se računa iz cjenika.</div>
                </div>
            </div>

            <div id="availability-panel" class="card" style="margin-top: 18px; background: rgba(15, 23, 42, 0.55);">
                <div class="muted" id="availability-summary">Odaberi bicikl i datum.</div>
                <div id="day-options" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;"></div>
            </div>

            <div class="field" style="margin-top: 18px;">
                <label class="muted" for="notes">Napomena</label>
                <textarea id="notes" name="notes" rows="4">{{ old('notes', $values['notes'] ?? '') }}</textarea>
            </div>

            <div style="margin-top: 22px;">
                <button id="save-button" class="btn" type="submit">Spremi rezervaciju</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const form = document.getElementById('reservation-form');
            const bike = document.getElementById('bike_id');
            const date = document.getElementById('reservation_date');
            const slotField = document.getElementById('slot-field');
            const slot = document.getElementById('slot');
            const quantity = document.getElementById('quantity');
            const help = document.getElementById('quantity-help');
            const summary = document.getElementById('availability-summary');
            const days = document.getElementById('day-options');
            const startsAt = document.getElementById('starts_at');
            const endsAt = document.getElementById('ends_at');
            const totalPrice = document.getElementById('total_price');
            const priceHelp = document.getElementById('price-help');
            const saveButton = document.getElementById('save-button');
            const url = @json(route('admin.reservations.availability'));
            const reservationId = @json($reservation?->id);
            let currentAvailability = null;
            let currentMax = 0;

            const pad = (value) => String(value).padStart(2, '0');

            function setHiddenTimes() {
                if (!date.value) {
                    return;
                }

                if (currentAvailability?.setting?.mode === 'hourly' && slot.value) {
                    const selected = currentAvailability.slots.find((item) => `${item.start}-${item.end}` === slot.value);
                    if (selected) {
                        startsAt.value = `${date.value}T${selected.start}`;
                        endsAt.value = `${date.value}T${selected.end}`;
                    }
                    return;
                }

                startsAt.value = `${date.value}T00:00`;
                endsAt.value = `${date.value}T23:59`;
            }

            function applyMax(max) {
                currentMax = Math.max(0, Number(max || 0));
                quantity.max = String(currentMax);
                const hasPrice = updatePrice();

                if (currentMax <= 0) {
                    saveButton.disabled = true;
                    help.textContent = 'Nema slobodnih bicikala za odabrani datum/termin.';
                    return;
                }

                if (Number(quantity.value || 0) > currentMax) {
                    quantity.value = String(currentMax);
                }

                saveButton.disabled = !hasPrice;
                help.textContent = `Maksimalno slobodno: ${currentMax}.`;
            }

            function updatePrice() {
                const pricing = currentAvailability?.pricing;
                const count = Math.max(1, Number(quantity.value || 1));

                if (!pricing?.has_price) {
                    totalPrice.value = '0.00';
                    priceHelp.textContent = 'Za ovaj bicikl i datum nema definiranog cjenika.';
                    return false;
                }

                const value = Number(pricing.price || 0) * count;
                totalPrice.value = value.toFixed(2);
                priceHelp.textContent = `Cijena: ${Number(pricing.price).toFixed(2)} EUR × ${count}.`;
                return true;
            }

            function renderAvailability(data) {
                currentAvailability = data;
                const mode = data.setting.mode === 'hourly' ? 'satna rezervacija' : 'dnevna rezervacija';
                const price = data.pricing?.has_price ? `${Number(data.pricing.price).toFixed(2)} EUR` : 'cjenik nije definiran';
                summary.textContent = `Lager: ${data.stock_quantity}. Za ${data.selected_date} dostupno: ${data.max_quantity}. Model: ${mode}. Cijena: ${price}.`;

                days.innerHTML = '';
                data.days.forEach((day) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn secondary';
                    button.style.padding = '8px 10px';
                    button.style.opacity = day.available ? '1' : '0.45';
                    button.textContent = `${day.date} (${day.available_units})`;
                    button.disabled = !day.available;
                    button.addEventListener('click', () => {
                        date.value = day.date;
                        loadAvailability();
                    });
                    days.appendChild(button);
                });

                slot.innerHTML = '';
                if (data.setting.mode === 'hourly') {
                    slotField.style.display = '';
                    data.slots.forEach((item) => {
                        const option = document.createElement('option');
                        option.value = `${item.start}-${item.end}`;
                        option.textContent = `${item.start}-${item.end} · slobodno ${item.available_units}`;
                        option.disabled = !item.available;
                        option.dataset.max = item.available_units;
                        slot.appendChild(option);
                    });
                    const selected = slot.selectedOptions[0];
                    applyMax(selected ? selected.dataset.max : 0);
                } else {
                    slotField.style.display = 'none';
                    applyMax(data.max_quantity);
                }

                setHiddenTimes();
            }

            async function loadAvailability() {
                if (!bike.value || !date.value) {
                    return;
                }

                saveButton.disabled = true;
                summary.textContent = 'Provjeravam dostupnost...';
                const params = new URLSearchParams({
                    bike_id: bike.value,
                    date: date.value
                });

                if (reservationId) {
                    params.set('reservation_id', reservationId);
                }

                const response = await fetch(`${url}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Dostupnost nije učitana.');
                }

                renderAvailability(payload.data);
            }

            bike.addEventListener('change', loadAvailability);
            date.addEventListener('change', loadAvailability);
            slot.addEventListener('change', () => {
                const selected = slot.selectedOptions[0];
                applyMax(selected ? selected.dataset.max : 0);
                setHiddenTimes();
            });
            quantity.addEventListener('input', () => {
                if (Number(quantity.value || 0) > currentMax) {
                    quantity.value = String(currentMax);
                }
                saveButton.disabled = currentMax <= 0 || !updatePrice();
            });

            form.addEventListener('submit', async (event) => {
                if (form.dataset.checked === '1') {
                    return;
                }

                event.preventDefault();
                try {
                    await loadAvailability();
                    const requested = Number(quantity.value || 0);
                    if (requested < 1 || requested > currentMax) {
                        help.textContent = `Količina mora biti između 1 i ${currentMax}.`;
                        return;
                    }
                    setHiddenTimes();
                    form.dataset.checked = '1';
                    form.submit();
                } catch (error) {
                    summary.textContent = error instanceof Error ? error.message : 'Provjera nije prošla.';
                    saveButton.disabled = false;
                }
            });

            if (bike.value && date.value) {
                loadAvailability().catch((error) => {
                    summary.textContent = error instanceof Error ? error.message : 'Dostupnost nije učitana.';
                    saveButton.disabled = false;
                });
            }
        })();
    </script>
@endsection
