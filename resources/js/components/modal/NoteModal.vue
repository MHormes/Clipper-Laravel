<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { geocodingService } from '@/util/geocodingSupport';
import { MapPin, Search, Loader2, Check, ExternalLink } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
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

const form = useForm({
    notes: props.initialNotes ?? '',
    location_bought: props.initialLocation ?? '',
});

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
    searchQuery.value = result.display_name; // Show the full name in the input
    searchResults.value = []; // Close dropdown
    fetchReadableLocation(coords);
};

const fetchReadableLocation = async (coords: string | null | undefined) => {
    if (!coords) {
        readableLocation.value = null;
        return;
    }

    const [lat, lon] = coords.split(',').map(c => c.trim());
    if (!lat || !lon) {
        readableLocation.value = null;
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
    }
};

// --- Lifecycle ---
watch(() => props.show, (val) => {
    if (val) {
        isEditing.value = false;
        form.notes = props.initialNotes ?? '';
        form.location_bought = props.initialLocation ?? '';
        searchQuery.value = '';
        selectedCoords.value = props.initialLocation as string | null;
        fetchReadableLocation(props.initialLocation as string | null);
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
                    Clipper Details
                </h3>

                <div v-if="!isEditing" class="space-y-6">
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
                    <button @click="isEditing = true"
                        class="w-full py-3 bg-primary text-button-content rounded-xl font-bold uppercase text-sm hover:bg-primary hover:text-button-content!  transition-all shadow-lg shadow-primary/20">
                        Edit Details
                    </button>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-4">
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
                                class="w-full text-left px-4 py-3 text-[11px] leading-tight text-primary-content hover:bg-primary hover:text-button-content!  transition-colors border-b border-border-color last:border-0"
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
                            class="flex-1 bg-primary text-button-content py-3 rounded-xl font-black uppercase text-xs hover:bg-primary hover:text-button-content!  transition-all disabled:opacity-50">
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
