<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { geocodingService } from '@/util/geocodingSupport';
import { MapPin, Search, Loader2, Check, ExternalLink, Copy, ChevronDown, ChevronUp } from '@lucide/vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger, Skeleton, SearchableSelect } from '@/components/ui';

const props = defineProps<{
    show: boolean;
    clipper: any;
    initialNotes: string | null | undefined;
    initialLocation: string | null | undefined;
}>();

const emit = defineEmits(['close']);

// --- UI State ---
const isEditing = ref(false);
const searchQuery = ref('');
const isSearching = ref(false);
const searchResults = ref<any[]>([]);
const selectedCoords = ref<string | null>(props.initialLocation as string | null);
const readableLocation = ref<string | null>(null);
const isFetchingReadable = ref(false);
const isInitialLoading = ref(false);

// --- Copy TO state ---
const isCopyingTo = ref(false);
const isFetchingList = ref(false);
const collectedList = ref<any[]>([]);
const copySearch = ref('');
const copySelectedIds = ref<string[]>([]);
const copyFields = ref({ notes: true, location_bought: true });
const isCopyingToProcessing = ref(false);

// --- Copy FROM state (in edit mode) ---
const isCopyFromOpen = ref(false);
const copyFromSelectedId = ref<string | null>(null);
const copyFromFields = ref({ notes: true, location_bought: true });
const copyFromSearch = ref('');

const form = useForm({
    notes: props.initialNotes ?? '',
    location_bought: props.initialLocation ?? '',
});

// --- Collected list (shared between copy-to and copy-from) ---
const fetchCollectedList = async () => {
    if (collectedList.value.length) return;
    isFetchingList.value = true;
    try {
        const res = await fetch(route('collection.list'), {
            headers: { Accept: 'application/json' },
        });
        collectedList.value = await res.json();
    } finally {
        isFetchingList.value = false;
    }
};

// List excluding the current clipper
const otherCollected = computed(() => collectedList.value.filter((item) => item.clipper_id !== props.clipper.id));

// Series ID of the currently viewed clipper (used to pin its series first)
const currentSeriesId = computed(
    () => collectedList.value.find((item) => item.clipper_id === props.clipper.id)?.series_id ?? null,
);

const buildGroups = (items: any[]) => {
    const groups: Record<string, { series_id: string; series_name: string; items: any[] }> = {};
    for (const item of items) {
        if (!groups[item.series_id]) {
            groups[item.series_id] = { series_id: item.series_id, series_name: item.series_name, items: [] };
        }
        groups[item.series_id].items.push(item);
    }
    return Object.values(groups).sort((a, b) => {
        if (a.series_id === currentSeriesId.value) return -1;
        if (b.series_id === currentSeriesId.value) return 1;
        return 0;
    });
};

// Filter by series name, sort alphabetically, then group with the current series pinned to the top
const filterSortGroup = (items: any[], search: string) => {
    const term = search.toLowerCase();
    const filtered = term ? items.filter((item) => item.series_name.toLowerCase().includes(term)) : items;
    const sorted = [...filtered].sort((a, b) => a.series_name.localeCompare(b.series_name));
    return buildGroups(sorted);
};

// Filtered + grouped by series for copy-to picker
const filteredGrouped = computed(() => filterSortGroup(otherCollected.value, copySearch.value));

// Copy FROM: source data for the selected clipper
const copyFromSource = computed(
    () => collectedList.value.find((item) => item.clipper_id === copyFromSelectedId.value) ?? null,
);

// Copy FROM: filtered + grouped for the source select
const copyFromGrouped = computed(() => filterSortGroup(otherCollected.value, copyFromSearch.value));

// Copy FROM: groups reshaped for the SearchableSelect component
const copyFromSelectGroups = computed(() =>
    copyFromGrouped.value.map((group) => ({ label: group.series_name, items: group.items })),
);

const copyFromSelectedLabel = computed(() =>
    copyFromSource.value ? `#${copyFromSource.value.series_number} — ${copyFromSource.value.series_name}` : null,
);

const toggleSelectAllInSeries = (items: any[]) => {
    const ids = items.map((i) => i.clipper_id);
    const allSelected = ids.every((id) => copySelectedIds.value.includes(id));
    if (allSelected) {
        copySelectedIds.value = copySelectedIds.value.filter((id) => !ids.includes(id));
    } else {
        copySelectedIds.value = [...new Set([...copySelectedIds.value, ...ids])];
    }
};

const submitCopyTo = () => {
    if (!copySelectedIds.value.length) return;

    const fields = Object.entries(copyFields.value)
        .filter(([, v]) => v)
        .map(([k]) => k);

    if (!fields.length) return;

    isCopyingToProcessing.value = true;
    router.post(
        route('collection.copy-to', props.clipper.id),
        { clipper_ids: copySelectedIds.value, fields },
        {
            preserveScroll: true,
            onSuccess: () => {
                isCopyingTo.value = false;
                copySelectedIds.value = [];
                emit('close');
            },
            onFinish: () => {
                isCopyingToProcessing.value = false;
            },
        },
    );
};

const applyFromSource = () => {
    if (!copyFromSource.value) return;

    if (copyFromFields.value.notes) {
        form.notes = copyFromSource.value.notes ?? '';
    }
    if (copyFromFields.value.location_bought) {
        form.location_bought = copyFromSource.value.location_bought ?? '';
        selectedCoords.value = copyFromSource.value.location_bought ?? null;
        if (copyFromSource.value.location_bought) {
            fetchReadableLocation(copyFromSource.value.location_bought);
        } else {
            readableLocation.value = null;
        }
    }

    isCopyFromOpen.value = false;
    copyFromSelectedId.value = null;
};

// --- Search Logic ---
let debounceTimeout: ReturnType<typeof setTimeout>;

const handleSearchInput = () => {
    clearTimeout(debounceTimeout);

    if (!searchQuery.value || searchQuery.value.length < 3) {
        searchResults.value = [];
        return;
    }

    debounceTimeout = setTimeout(async () => {
        isSearching.value = true;
        try {
            searchResults.value = await geocodingService.search(searchQuery.value);
        } finally {
            isSearching.value = false;
        }
    }, 500);
};

const selectLocation = (result: any) => {
    const coords = `${result.lat}, ${result.lon}`;
    form.location_bought = coords;
    selectedCoords.value = coords;
    searchQuery.value = result.display_name;
    searchResults.value = [];
    fetchReadableLocation(coords);
};

const fetchReadableLocation = async (coords: string | null | undefined) => {
    if (!coords) {
        readableLocation.value = null;
        isInitialLoading.value = false;
        return;
    }

    const [lat, lon] = coords.split(',').map((c) => c.trim());
    if (!lat || !lon) {
        readableLocation.value = null;
        isInitialLoading.value = false;
        return;
    }

    isFetchingReadable.value = true;
    try {
        const data = await geocodingService.reverse(lat, lon);
        if (data && data.display_name) {
            readableLocation.value = data.display_name;
        } else {
            readableLocation.value = null;
        }
    } catch (e) {
        console.error('Failed to fetch readable location:', e);
        readableLocation.value = null;
    } finally {
        isFetchingReadable.value = false;
        isInitialLoading.value = false;
    }
};

// --- Lifecycle ---
watch(
    () => props.show,
    (val) => {
        if (val) {
            isEditing.value = false;
            isCopyingTo.value = false;
            isCopyFromOpen.value = false;
            copyFromSelectedId.value = null;
            copySelectedIds.value = [];
            copySearch.value = '';
            copyFromSearch.value = '';
            collectedList.value = [];
            form.notes = props.initialNotes ?? '';
            form.location_bought = props.initialLocation ?? '';
            searchQuery.value = '';
            selectedCoords.value = props.initialLocation as string | null;

            if (props.initialLocation) {
                isInitialLoading.value = true;
                fetchReadableLocation(props.initialLocation as string | null);
            } else {
                isInitialLoading.value = false;
                readableLocation.value = null;
            }
        }
    },
);

const submit = () => {
    form.patch(route('collection.update', props.clipper.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            emit('close');
        },
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div
            class="w-full max-w-md overflow-hidden rounded-3xl border border-border-color bg-primary-background shadow-xl"
        >
            <div class="p-8">
                <h3 class="mb-6 text-xl font-black tracking-tight text-primary-content uppercase">
                    {{ isCopyingTo ? 'Copy to Others' : 'Clipper Details' }}
                </h3>

                <!-- Loading skeleton -->
                <div v-if="isInitialLoading" class="space-y-6">
                    <div>
                        <Skeleton class="mb-2 h-3 w-20" />
                        <Skeleton class="h-16 w-full rounded-xl" />
                    </div>
                    <div>
                        <Skeleton class="mb-2 h-3 w-32" />
                        <div class="space-y-2">
                            <Skeleton class="h-20 w-full rounded-xl" />
                            <Skeleton class="h-3 w-24" />
                        </div>
                    </div>
                    <Skeleton class="h-12 w-full rounded-xl" />
                </div>

                <!-- Copy TO panel -->
                <div v-else-if="isCopyingTo" class="space-y-4">
                    <!-- Field toggles -->
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                            >Fields to copy</span
                        >
                        <div class="mt-2 flex gap-3">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" v-model="copyFields.notes" class="h-4 w-4 accent-primary" />
                                <span class="text-sm font-bold text-primary-content">Notes</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input
                                    type="checkbox"
                                    v-model="copyFields.location_bought"
                                    class="h-4 w-4 accent-primary"
                                />
                                <span class="text-sm font-bold text-primary-content">Location</span>
                            </label>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <div class="relative">
                            <input
                                v-model="copySearch"
                                type="text"
                                class="w-full rounded-xl border border-border-color bg-component-background p-3 pl-10 text-sm text-primary-content placeholder:text-muted-content/50 focus:border-primary focus:ring-primary"
                                placeholder="Filter by series name..."
                            />
                            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-content" />
                        </div>
                    </div>

                    <!-- Clipper list -->
                    <div v-if="isFetchingList" class="flex justify-center py-6">
                        <Loader2 class="h-6 w-6 animate-spin text-primary" />
                    </div>

                    <div v-else-if="filteredGrouped.length === 0" class="py-6 text-center text-sm text-muted-content">
                        No other collected clippers found.
                    </div>

                    <div v-else class="max-h-64 space-y-4 overflow-y-auto pr-1">
                        <div v-for="group in filteredGrouped" :key="group.series_name">
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-[10px] font-black tracking-widest text-muted-content uppercase">{{
                                    group.series_name
                                }}</span>
                                <button
                                    type="button"
                                    @click="toggleSelectAllInSeries(group.items)"
                                    class="text-[9px] font-black tracking-wider text-primary uppercase hover:underline"
                                >
                                    {{
                                        group.items.every((i) => copySelectedIds.includes(i.clipper_id))
                                            ? 'Deselect all'
                                            : 'Select all'
                                    }}
                                </button>
                            </div>
                            <div class="space-y-1">
                                <label
                                    v-for="item in group.items"
                                    :key="item.clipper_id"
                                    class="flex cursor-pointer items-center gap-3 rounded-xl bg-component-background p-2.5 transition-colors hover:bg-hover-overlay"
                                >
                                    <input
                                        type="checkbox"
                                        :value="item.clipper_id"
                                        v-model="copySelectedIds"
                                        class="h-4 w-4 shrink-0 accent-primary"
                                    />
                                    <span class="text-sm font-bold text-primary-content"
                                        >#{{ item.series_number }}</span
                                    >
                                    <span
                                        v-if="item.notes || item.location_bought"
                                        class="truncate text-[10px] text-muted-content"
                                    >
                                        {{ item.notes ? 'Has notes' : ''
                                        }}{{ item.notes && item.location_bought ? ' · ' : ''
                                        }}{{ item.location_bought ? 'Has location' : '' }}
                                    </span>
                                    <span v-else class="text-[10px] text-muted-content/50">No info yet</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Copy action -->
                    <div class="flex gap-3 pt-2">
                        <button
                            type="button"
                            @click="submitCopyTo"
                            :disabled="
                                isCopyingToProcessing ||
                                copySelectedIds.length === 0 ||
                                (!copyFields.notes && !copyFields.location_bought)
                            "
                            class="flex-1 rounded-xl bg-primary py-3 text-xs font-black text-button-content uppercase transition-all hover:bg-primary hover:text-button-content! disabled:opacity-50"
                        >
                            {{
                                isCopyingToProcessing
                                    ? 'Copying...'
                                    : `Copy to ${copySelectedIds.length}
                            clipper${copySelectedIds.length === 1 ? '' : 's'}`
                            }}
                        </button>
                        <button
                            type="button"
                            @click="isCopyingTo = false"
                            class="bg-muted/10 hover:bg-muted/20 flex-1 rounded-xl border border-border-color py-3 text-xs font-black text-muted-content uppercase transition-all"
                        >
                            Back
                        </button>
                    </div>
                </div>

                <!-- View mode -->
                <div v-else-if="!isEditing" class="space-y-6">
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                            >My Notes</span
                        >
                        <div class="mt-2 rounded-xl bg-component-background p-3">
                            <p class="font-medium whitespace-pre-wrap text-primary-content">
                                {{ initialNotes || 'No notes added yet...' }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-black tracking-widest text-muted-content uppercase"
                            >Location Found</span
                        >
                        <div class="mt-2 flex flex-col gap-2">
                            <div class="rounded-xl bg-component-background p-3">
                                <div class="flex items-start gap-2">
                                    <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    <div class="flex flex-col">
                                        <TooltipProvider v-if="readableLocation" :delay-duration="300">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <p
                                                        class="line-clamp-2 cursor-help leading-tight font-medium text-primary-content"
                                                    >
                                                        {{ readableLocation }}
                                                    </p>
                                                </TooltipTrigger>
                                                <TooltipContent
                                                    side="bottom"
                                                    class="max-w-[280px] border-border-color bg-primary-background text-primary-content shadow-2xl"
                                                >
                                                    <p class="font-bold">{{ readableLocation }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <p
                                            class="font-medium tracking-tighter text-primary-content uppercase"
                                            :class="{ 'mt-1 text-[10px] text-success': readableLocation }"
                                        >
                                            {{ initialLocation || 'No location set...' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <a
                                v-if="initialLocation"
                                :href="`https://www.openstreetmap.org/?mlat=${initialLocation.split(',')[0].trim()}&mlon=${initialLocation.split(',')[1].trim()}#map=17`"
                                target="_blank"
                                class="flex items-center gap-1 text-[10px] font-bold tracking-tighter text-primary uppercase hover:underline"
                            >
                                <ExternalLink class="h-3 w-3" /> View on OpenStreetMap
                            </a>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button
                            @click="isEditing = true"
                            class="w-full rounded-xl bg-primary py-3 text-sm font-bold text-button-content uppercase shadow-lg shadow-primary/20 transition-all hover:bg-primary hover:text-button-content!"
                        >
                            Edit Details
                        </button>
                        <button
                            @click="
                                isCopyingTo = true;
                                fetchCollectedList();
                            "
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-border-color bg-component-background py-3 text-sm font-bold text-muted-content uppercase transition-all hover:text-primary-content"
                        >
                            <Copy class="h-4 w-4" /> Copy to Other Clippers
                        </button>
                    </div>
                </div>

                <!-- Edit form -->
                <form v-else @submit.prevent="submit" class="space-y-4">
                    <!-- Copy FROM panel -->
                    <div class="border-b border-border-color pb-2">
                        <button
                            type="button"
                            @click="
                                isCopyFromOpen = !isCopyFromOpen;
                                if (isCopyFromOpen) fetchCollectedList();
                            "
                            class="flex items-center gap-1.5 text-[10px] font-black tracking-widest text-primary uppercase hover:underline"
                        >
                            <component :is="isCopyFromOpen ? ChevronUp : ChevronDown" class="h-3 w-3" />
                            Copy from another clipper
                        </button>

                        <div v-if="isCopyFromOpen" class="mt-3 space-y-3">
                            <div v-if="isFetchingList" class="flex justify-center py-3">
                                <Loader2 class="h-5 w-5 animate-spin text-primary" />
                            </div>

                            <template v-else>
                                <!-- Source picker -->
                                <div>
                                    <label
                                        class="mb-1 block text-[10px] font-black tracking-widest text-muted-content uppercase"
                                        >Select source clipper</label
                                    >
                                    <SearchableSelect
                                        v-model="copyFromSelectedId"
                                        v-model:search="copyFromSearch"
                                        :groups="copyFromSelectGroups"
                                        :item-value="(item) => item.clipper_id"
                                        :selected-label="copyFromSelectedLabel"
                                        placeholder="Choose a clipper..."
                                        search-placeholder="Search series..."
                                        empty-text="No matching clippers."
                                    >
                                        <template #item="{ item }">
                                            #{{ item.series_number
                                            }}{{ item.notes || item.location_bought ? ' — has info' : ' — no info' }}
                                        </template>
                                    </SearchableSelect>
                                </div>

                                <!-- Field toggles -->
                                <div v-if="copyFromSelectedId" class="flex gap-3">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="checkbox"
                                            v-model="copyFromFields.notes"
                                            class="h-4 w-4 accent-primary"
                                        />
                                        <span class="text-sm font-bold text-primary-content">Notes</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="checkbox"
                                            v-model="copyFromFields.location_bought"
                                            class="h-4 w-4 accent-primary"
                                        />
                                        <span class="text-sm font-bold text-primary-content">Location</span>
                                    </label>
                                </div>

                                <!-- Source preview -->
                                <div
                                    v-if="copyFromSource"
                                    class="space-y-1 rounded-xl bg-component-background p-3 text-[11px] text-muted-content"
                                >
                                    <p v-if="copyFromSource.notes">
                                        <span class="font-black uppercase">Notes:</span> {{ copyFromSource.notes }}
                                    </p>
                                    <p v-if="copyFromSource.location_bought">
                                        <span class="font-black uppercase">Location:</span>
                                        {{ copyFromSource.location_bought }}
                                    </p>
                                    <p v-if="!copyFromSource.notes && !copyFromSource.location_bought" class="italic">
                                        This clipper has no info to copy.
                                    </p>
                                </div>

                                <button
                                    v-if="copyFromSelectedId"
                                    type="button"
                                    @click="applyFromSource"
                                    :disabled="!copyFromFields.notes && !copyFromFields.location_bought"
                                    class="w-full rounded-xl bg-primary py-2.5 text-xs font-black text-button-content uppercase transition-all hover:bg-primary hover:text-button-content! disabled:opacity-50"
                                >
                                    Apply to form
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-[10px] font-black tracking-widest text-muted-content uppercase"
                            >Notes</label
                        >
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-xl border border-border-color bg-component-background p-3 text-sm text-primary-content placeholder:text-muted-content/50 focus:border-primary focus:ring-primary"
                            placeholder="Who did you get it from? Any funny memories?"
                        ></textarea>
                    </div>

                    <div class="relative">
                        <label class="mb-1 block text-[10px] font-black tracking-widest text-muted-content uppercase"
                            >Search Location (OSM)</label
                        >
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                @input="handleSearchInput"
                                type="text"
                                class="w-full rounded-xl border border-border-color bg-component-background p-3 pl-10 text-sm text-primary-content placeholder:text-muted-content/50 focus:border-primary focus:ring-primary"
                                placeholder="Search city, shop or street..."
                            />
                            <div class="absolute top-1/2 left-3 -translate-y-1/2">
                                <Loader2 v-if="isSearching" class="h-4 w-4 animate-spin text-primary" />
                                <Search v-else class="h-4 w-4 text-muted-content" />
                            </div>
                        </div>

                        <div
                            v-if="searchResults.length > 0"
                            class="absolute z-10 mt-2 max-h-40 w-full overflow-hidden overflow-y-auto rounded-xl border border-border-color bg-component-background bg-primary-background shadow-2xl"
                        >
                            <button
                                v-for="result in searchResults"
                                :key="result.place_id"
                                type="button"
                                @click="selectLocation(result)"
                                class="w-full border-b border-border-color px-4 py-3 text-left text-[11px] leading-tight text-primary-content transition-colors last:border-0 hover:bg-primary hover:text-button-content!"
                            >
                                {{ result.display_name }}
                            </button>
                        </div>

                        <div
                            v-if="selectedCoords"
                            class="mt-2 flex items-center gap-1.5 text-[10px] font-black tracking-wider text-success uppercase"
                        >
                            <Check class="h-3 w-3" />
                            Coords Linked: {{ selectedCoords }}
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 rounded-xl bg-primary py-3 text-xs font-black text-button-content uppercase transition-all hover:bg-primary hover:text-button-content! disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                        <button
                            type="button"
                            @click="isEditing = false"
                            class="bg-muted/10 hover:bg-muted/20 flex-1 rounded-xl border border-border-color py-3 text-xs font-black text-muted-content uppercase transition-all"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <button
                @click="$emit('close')"
                class="w-full border-t border-border-color bg-component-background py-4 text-[10px] font-black tracking-widest text-muted-content uppercase transition-all hover:bg-muted-background hover:text-primary-content"
            >
                Close Modal
            </button>
        </div>
    </div>
</template>
