@php
    $areas = [
        ['title' => 'Kigali City', 'detail' => 'Amahoro, Kigali'],
        ['title' => 'Nyarutarama', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Kimironko', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Kacyiru', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Remera', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Gikondo', 'detail' => 'Kicukiro, Kigali'],
        ['title' => 'Nyamirambo', 'detail' => 'Nyarugenge, Kigali'],
        ['title' => 'Kicukiro', 'detail' => 'Kicukiro, Kigali'],
    ];
@endphp

<div
    x-data="locationPicker(@js($areas))"
    x-cloak
    @open-location-modal.window="openModal()"
    class="contents"
>
    {{-- Confirm location modal --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[80] flex items-center justify-center bg-munch-deep/50 p-4 backdrop-blur-[2px]"
        @keydown.escape.window="if(open && !editing) confirmHere()"
    >
        <div
            x-show="open && !editing"
            x-transition
            class="w-full max-w-sm rounded-[28px] bg-white px-6 py-8 text-center shadow-2xl"
            @click.outside="confirmHere()"
        >
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center">
                <img src="{{ asset('images/brand/icon.svg') }}" alt="MunchBox" class="h-14 w-14">
            </div>

            <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-box/10">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-box">
                    <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
            </div>

            <h2 class="text-2xl font-extrabold text-ink">Still delivering here?</h2>

            <div class="mt-5 rounded-2xl bg-[#F5F5F5] px-4 py-4 text-left">
                <p class="font-extrabold text-ink" x-text="location.title"></p>
                <p class="mt-1 text-sm text-ink/55" x-text="location.detail"></p>
            </div>

            <button type="button" class="btn-primary mt-6 w-full !rounded-full !py-3.5 text-base" @click="confirmHere()">
                Yes, deliver here
            </button>

            <button type="button" class="mt-4 text-sm font-bold text-ink hover:text-box" @click="editing = true">
                Use a different address
            </button>
        </div>

        {{-- Choose different address --}}
        <div
            x-show="open && editing"
            x-transition
            class="w-full max-w-sm rounded-[28px] bg-white px-6 py-8 shadow-2xl"
            @click.outside="editing = false"
        >
            <div class="mb-5 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-box/10">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-box">
                        <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 class="text-2xl font-extrabold text-ink">Choose delivery area</h2>
                <p class="mt-1 text-sm text-ink/55">We deliver across Kigali</p>
            </div>

            <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                <template x-for="area in areas" :key="area.title">
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 rounded-2xl border px-4 py-3 text-left transition"
                        :class="location.title === area.title ? 'border-box bg-box/5' : 'border-munch/10 hover:border-box/40'"
                        @click="selectArea(area)"
                    >
                        <span class="mt-0.5 text-box">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.145 16.978 16.978 0 002.592-1.932c1.76-1.644 3.793-4.29 3.793-7.778A7 7 0 0010 1a7 7 0 00-7 7c0 3.488 2.033 6.134 3.793 7.778a16.978 16.978 0 002.873 2.077 5.741 5.741 0 00.281.145l.018.008.006.003zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>
                            <span class="block font-bold text-ink" x-text="area.title"></span>
                            <span class="block text-sm text-ink/55" x-text="area.detail"></span>
                        </span>
                    </button>
                </template>
            </div>

            <div class="mt-5 space-y-3">
                <div>
                    <label class="label-field">Street / landmark (optional)</label>
                    <input type="text" class="input-field" placeholder="Near Amahoro Stadium" x-model="customStreet">
                </div>
                <button type="button" class="btn-primary w-full !rounded-full !py-3.5" @click="confirmHere()">
                    Deliver here
                </button>
                <button type="button" class="w-full text-sm font-bold text-ink/60 hover:text-munch" @click="editing = false">
                    Back
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function locationPicker(areas) {
    const STORAGE_KEY = 'munchbox_delivery_location';
    const CONFIRMED_KEY = 'munchbox_location_confirmed';
    const defaultLocation = areas[0] || { title: 'Kigali City', detail: 'Amahoro, Kigali' };

    return {
        open: false,
        editing: false,
        areas,
        location: defaultLocation,
        customStreet: '',
        init() {
            try {
                const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
                if (saved?.title) {
                    this.location = saved;
                    this.customStreet = saved.street || '';
                }
            } catch (e) {}

            const confirmed = localStorage.getItem(CONFIRMED_KEY) === '1';
            if (!confirmed) {
                this.open = true;
            }

            this.syncUi();
            window.addEventListener('storage', () => this.syncUi());
        },
        openModal() {
            this.editing = false;
            this.open = true;
        },
        selectArea(area) {
            this.location = { ...area };
        },
        confirmHere() {
            const payload = {
                title: this.location.title,
                detail: this.location.detail,
                street: this.customStreet || '',
                city: 'Kigali',
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
            localStorage.setItem(CONFIRMED_KEY, '1');
            this.location = payload;
            this.open = false;
            this.editing = false;
            this.syncUi();
            window.dispatchEvent(new CustomEvent('location-updated', { detail: payload }));
        },
        syncUi() {
            const label = this.location.street
                ? `${this.location.title} · ${this.location.street}`
                : `${this.location.title}`;
            document.querySelectorAll('[data-location-label]').forEach((el) => {
                el.textContent = label;
            });
            document.querySelectorAll('[data-location-detail]').forEach((el) => {
                el.textContent = this.location.detail;
            });
        },
    }
}
</script>
