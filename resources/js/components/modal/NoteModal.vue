<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { geocodingService } from '@/util/geocodingSupport';
import { MapPin, Search, Loader2, Check, ExternalLink, Copy, ChevronDown, ChevronUp } from '@lucide/vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
    Skeleton,
} from '@/components/ui';

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

const form = useForm({
    notes: props.initialNotes ?? '',
    location_bought: props.initialLocation ?? '',
});

// --- Collected list (shared between copy-to and copy-from) ---
const fetchCollectedList = async () => {
    if (collectedList.value.length) return;
    isFetchingList.value = true;
    try {
        const res = await fetch(route('collection.list'));
        collectedList.value = await res.json();
    } finally {
        isFetchingList.value = false;
    }
};

// List excluding the current clipper
const otherCollected = computed(() =>
    collectedList.value.filter(item => item.clipper_id !== props.clipper.id)
);

// Series ID of the currently viewed clipper (used to pin its series first)
const currentSeriesId = computed(() =>
    collectedList.value.find(item => item.clipper_id === props.clipper.id)?.series_id ?? null
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

// Filtered + grouped by series for copy-to picker
const filteredGrouped = computed(() => {
    const search = copySearch.value.toLowerCase();
    const filtered = search
        ? otherCollected.value.filter(item => item.series_name.toLowerCase().includes(search))
        : otherCollected.value;
    return buildGroups(filtered);
});

// Copy FROM: source data for the selected clipper
const copyFromSource = computed(() =>
    collectedList.value.find(item => item.clipper_id === copyFromSelectedId.value) ?? null
);

// Copy FROM: grouped for the combobox select
const copyFromGrouped = computed(() => buildGroups(otherCollected.value));

const toggleSelectAllInSeries = (items: any[]) => {
    const ids = items.map(i => i.clipper_id);
    const allSelected = ids.every(id => copySelectedIds.value.includes(id));
    if (allSelected) {
        copySelectedIds.value = copySelectedIds.value.filter(id => !ids.includes(id));
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
        }
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

    const [lat, lon] = coords.split(',').map(c => c.trim());
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
watch(() => props.show, (val) => {
    if (val) {
        isEditing.value = false;
        isCopyingTo.value = false;
        isCopyFromOpen.value = false;
        copyFromSelectedId.value = null;
        copySelectedIds.value = [];
        copySearch.value = '';
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
});

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
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-primary-background rounded-3xl w-full max-w-md overflow-hidden shadow-xl border border-border-color">
            <div class="p-8">
                <h3 class="text-xl font-black uppercase tracking-tight text-primary-content mb-6">
                    {{ isCopyingTo ? 'Copy to Others' : 'Clipper Details' }}
                </h3>

                <!-- Loading skeleton -->
                <div v-if="isInitialLoading" class="space-y-6">
                    <div>
                        <Skeleton class="h-3 w-20 mb-2" />
                        <Skeleton class="h-16 w-full rounded-xl" />
                    </div>
                    <div>
                        <Skeleton class="h-3 w-32 mb-2" />
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
                        <span class="text-[10px] font-black text-muted-content uppercase tracking-widest">Fields to copy</span>
                        <div class="flex gap-3 mt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="copyFields.notes" class="accent-primary w-4 h-4" />
                                <span class="text-sm font-bold text-primary-content">Notes</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="copyFields.location_bought" class="accent-primary w-4 h-4" />
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
                                class="p-3 pl-10 w-full bg-component-background border border-border-color rounded-xl text-primary-content text-sm placeholder:text-muted-content/50 focus:ring-primary focus:border-primary"
                                placeholder="Filter by series name..."
                            />
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-content" />
                        </div>
                    </div>

                    <!-- Clipper list -->
                    <div v-if="isFetchingList" class="flex justify-center py-6">
                        <Loader2 class="w-6 h-6 text-primary animate-spin" />
                    </div>

                    <div v-else-if="filteredGrouped.length === 0" class="text-center py-6 text-sm text-muted-content">
                        No other collected clippers found.
                    </div>

                    <div v-else class="max-h-64 overflow-y-auto space-y-4 pr-1">
                        <div v-for="group in filteredGrouped" :key="group.series_name">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-black text-muted-content uppercase tracking-widest">{{ group.series_name }}</span>
                                <button
                                    type="button"
                                    @click="toggleSelectAllInSeries(group.items)"
                                    class="text-[9px] font-black text-primary uppercase tracking-wider hover:underline"
                                >
                                    {{ group.items.every(i => copySelectedIds.includes(i.clipper_id)) ? 'Deselect all' : 'Select all' }}
                                </button>
                            </div>
                            <div class="space-y-1">
                                <label
                                    v-for="item in group.items"
                                    :key="item.clipper_id"
                                    class="flex items-center gap-3 p-2.5 rounded-xl bg-component-background cursor-pointer hover:bg-hover-overlay transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        :value="item.clipper_id"
                                        v-model="copySelectedIds"
                                        class="accent-primary w-4 h-4 shrink-0"
                                    />
                                    <span class="text-sm font-bold text-primary-content">#{{ item.series_number }}</span>
                                    <span v-if="item.notes || item.location_bought" class="text-[10px] text-muted-content truncate">
                                        {{ item.notes ? 'Has notes' : '' }}{{ item.notes && item.location_bought ? ' · ' : '' }}{{ item.location_bought ? 'Has location' : '' }}
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
                            :disabled="isCopyingToProcessing || copySelectedIds.length === 0 || (!copyFields.notes && !copyFields.location_bought)"
                            class="flex-1 bg-primary text-button-content py-3 rounded-xl font-black uppercase text-xs hover:bg-primary hover:text-button-content! transition-all disabled:opacity-50"
                        >
                            {{ isCopyingToProcessing ? 'Copying...' : `Copy to ${copySelectedIds.length} clipper${copySelectedIds.length === 1 ? '' : 's'}` }}
                        </button>
                        <button
                            type="button"
                            @click="isCopyingTo = false"
                            class="flex-1 bg-muted/10 text-muted-content py-3 rounded-xl font-black uppercase text-xs hover:bg-muted/20 border border-border-color transition-all"
                        >
                            Back
                        </button>
                    </div>
                </div>

                <!-- View mode -->
                <div v-else-if="!isEditing" class="space-y-6">
                    <div>
                        <span class="text-[10px] font-black text-muted-content uppercase tracking-widest">My Notes</span>
                        <div class="mt-2 rounded-xl bg-component-background p-3">
                            <p class="text-primary-content font-medium whitespace-pre-wrap">
                                {{ initialNotes || 'No notes added yet...' }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-black text-muted-content uppercase tracking-widest">Location Found</span>
                        <div class="mt-2 flex flex-col gap-2">
                            <div class="rounded-xl bg-component-background p-3">
                                <div class="flex items-start gap-2">
                                    <MapPin class="w-4 h-4 text-primary mt-0.5 shrink-0" />
                                    <div class="flex flex-col">
                                        <TooltipProvider v-if="readableLocation" :delay-duration="300">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <p class="text-primary-content font-medium leading-tight line-clamp-2 cursor-help">
                                                        {{ readableLocation }}
                                                    </p>
                                                </TooltipTrigger>
                                                <TooltipContent side="bottom" class="max-w-[280px] bg-primary-background text-primary-content border-border-color shadow-2xl">
                                                    <p class="font-bold">{{ readableLocation }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <p class="text-primary-content font-medium uppercase tracking-tighter" :class="{ 'text-[10px] text-success mt-1': readableLocation }">
                                            {{ initialLocation || 'No location set...' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <a v-if="initialLocation"
                               :href="`https://www.openstreetmap.org/?mlat=${initialLocation.split(',')[0].trim()}&mlon=${initialLocation.split(',')[1].trim()}#map=17`"
                               target="_blank"
                               class="text-[10px] font-bold text-primary uppercase flex items-center gap-1 hover:underline tracking-tighter"
                            >
                                <ExternalLink class="w-3 h-3" /> View on OpenStreetMap
                            </a>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button @click="isEditing = true"
                            class="w-full py-3 bg-primary text-button-content rounded-xl font-bold uppercase text-sm hover:bg-primary hover:text-button-content! transition-all shadow-lg shadow-primary/20">
                            Edit Details
                        </button>
                        <button
                            @click="isCopyingTo = true; fetchCollectedList()"
                            class="w-full py-3 bg-component-background text-muted-content rounded-xl font-bold uppercase text-sm border border-border-color hover:text-primary-content transition-all flex items-center justify-center gap-2"
                        >
                            <Copy class="w-4 h-4" /> Copy to Other Clippers
                        </button>
                    </div>
                </div>

                <!-- Edit form -->
                <form v-else @submit.prevent="submit" class="space-y-4">
                    <!-- Copy FROM panel -->
                    <div class="pb-2 border-b border-border-color">
                        <button
                            type="button"
                            @click="isCopyFromOpen = !isCopyFromOpen; if (isCopyFromOpen) fetchCollectedList()"
                            class="flex items-center gap-1.5 text-[10px] font-black text-primary uppercase tracking-widest hover:underline"
                        >
                            <component :is="isCopyFromOpen ? ChevronUp : ChevronDown" class="w-3 h-3" />
                            Fill from another clipper
                        </button>

                        <div v-if="isCopyFromOpen" class="mt-3 space-y-3">
                            <div v-if="isFetchingList" class="flex justify-center py-3">
                                <Loader2 class="w-5 h-5 text-primary animate-spin" />
                            </div>

                            <template v-else>
                                <!-- Source picker -->
                                <div>
                                    <label class="block text-[10px] font-black text-muted-content uppercase tracking-widest mb-1">Select source clipper</label>
                                    <select
                                        v-model="copyFromSelectedId"
                                        class="w-full p-3 bg-component-background border border-border-color rounded-xl text-primary-content text-sm focus:ring-primary focus:border-primary"
                                    >
                                        <option value="" disabled selected>Choose a clipper...</option>
                                        <optgroup
                                            v-for="group in copyFromGrouped"
                                            :key="group.series_name"
                                            :label="group.series_name"
                                        >
                                            <option
                                                v-for="item in group.items"
                                                :key="item.clipper_id"
                                                :value="item.clipper_id"
                                            >
                                                #{{ item.series_number }}{{ item.notes || item.location_bought ? ' — has info' : ' — no info' }}
                                            </option>
                                        </optgroup>
                                    </select>
                                </div>

                                <!-- Field toggles -->
                                <div v-if="copyFromSelectedId" class="flex gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" v-model="copyFromFields.notes" class="accent-primary w-4 h-4" />
                                        <span class="text-sm font-bold text-primary-content">Notes</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" v-model="copyFromFields.location_bought" class="accent-primary w-4 h-4" />
                                        <span class="text-sm font-bold text-primary-content">Location</span>
                                    </label>
                                </div>

                                <!-- Source preview -->
                                <div v-if="copyFromSource" class="rounded-xl bg-component-background p-3 space-y-1 text-[11px] text-muted-content">
                                    <p v-if="copyFromSource.notes"><span class="font-black uppercase">Notes:</span> {{ copyFromSource.notes }}</p>
                                    <p v-if="copyFromSource.location_bought"><span class="font-black uppercase">Location:</span> {{ copyFromSource.location_bought }}</p>
                                    <p v-if="!copyFromSource.notes && !copyFromSource.location_bought" class="italic">This clipper has no info to copy.</p>
                                </div>

                                <button
                                    v-if="copyFromSelectedId"
                                    type="button"
                                    @click="applyFromSource"
                                    :disabled="!copyFromFields.notes && !copyFromFields.location_bought"
                                    class="w-full py-2.5 bg-primary text-button-content rounded-xl font-black uppercase text-xs hover:bg-primary hover:text-button-content! transition-all disabled:opacity-50"
                                >
                                    Apply to form
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-muted-content uppercase tracking-widest mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="3"
                            class="p-3 w-full bg-component-background border border-border-color rounded-xl focus:ring-primary focus:border-primary text-primary-content text-sm placeholder:text-muted-content/50"
                            placeholder="Who did you get it from? Any funny memories?"></textarea>
                    </div>

                    <div class="relative">
                        <label class="block text-[10px] font-black text-muted-content uppercase tracking-widest mb-1">Search Location (OSM)</label>
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                @input="handleSearchInput"
                                type="text"
                                class="p-3 pl-10 w-full bg-component-background border border-border-color rounded-xl focus:ring-primary focus:border-primary text-primary-content text-sm placeholder:text-muted-content/50"
                                placeholder="Search city, shop or street..."
                            />
                            <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                <Loader2 v-if="isSearching" class="w-4 h-4 text-primary animate-spin" />
                                <Search v-else class="w-4 h-4 text-muted-content" />
                            </div>
                        </div>

                        <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-2 bg-primary-background bg-component-background border border-border-color rounded-xl shadow-2xl overflow-hidden max-h-40 overflow-y-auto">
                            <button
                                v-for="result in searchResults"
                                :key="result.place_id"
                                type="button"
                                @click="selectLocation(result)"
                                class="w-full text-left px-4 py-3 text-[11px] leading-tight text-primary-content hover:bg-primary hover:text-button-content! transition-colors border-b border-border-color last:border-0"
                            >
                                {{ result.display_name }}
                            </button>
                        </div>

                        <div v-if="selectedCoords" class="mt-2 flex items-center gap-1.5 text-[10px] font-black uppercase text-success tracking-wider">
                            <Check class="w-3 h-3" />
                            Coords Linked: {{ selectedCoords }}
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 bg-primary text-button-content py-3 rounded-xl font-black uppercase text-xs hover:bg-primary hover:text-button-content! transition-all disabled:opacity-50">
                            Save Changes
                        </button>
                        <button type="button" @click="isEditing = false"
                            class="flex-1 bg-muted/10 text-muted-content py-3 rounded-xl font-black uppercase text-xs hover:bg-muted/20 border border-border-color transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <button @click="$emit('close')"
                class="w-full py-4 bg-component-background text-muted-content text-[10px] font-black uppercase tracking-widest border-t border-border-color hover:bg-muted-background hover:text-primary-content transition-all">
                Close Modal
            </button>
        </div>
    </div>
</template>
