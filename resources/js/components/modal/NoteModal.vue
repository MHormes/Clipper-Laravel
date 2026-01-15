<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { geocodingService } from '@/util/geocodingSupport';
import { MapPin, Search, Loader2, Check, ExternalLink } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    clipper: any;
    initialNotes: string | null;
    initialLocation: string | null;
}>();

const emit = defineEmits(['close']);

// --- UI State ---
const isEditing = ref(false);
const searchQuery = ref('');
const isSearching = ref(false);
const searchResults = ref<any[]>([]);
const selectedCoords = ref<string | null>(props.initialLocation);

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
};

// --- Lifecycle ---
watch(() => props.show, (val) => {
    if (val) {
        isEditing.value = false;
        form.notes = props.initialNotes ?? '';
        form.location_bought = props.initialLocation ?? '';
        searchQuery.value = '';
        selectedCoords.value = props.initialLocation;
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
        <div class="bg-white dark:bg-[#161615] rounded-3xl w-full max-w-md overflow-hidden shadow-xl border border-sidebar-border">
            <div class="p-8">
                <h3 class="text-xl font-black uppercase tracking-tight text-foreground mb-6">
                    Clipper Details
                </h3>

                <div v-if="!isEditing" class="space-y-6">
                    <div>
                        <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">My Notes</span>
                        <p class="mt-1 text-foreground font-medium whitespace-pre-wrap">
                            {{ initialNotes || 'No notes added yet...' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Location Found</span>
                        <div class="mt-1 flex flex-col gap-2">
                            <p class="text-foreground font-medium flex items-center gap-2">
                                <MapPin class="w-4 h-4 text-orange-600" />
                                {{ initialLocation || 'No location set...' }}
                            </p>
                            
                            <a v-if="initialLocation" 
                               :href="`https://www.openstreetmap.org/?mlat=${initialLocation.split(',')[0].trim()}&mlon=${initialLocation.split(',')[1].trim()}#map=17`"
                               target="_blank"
                               class="text-[10px] font-bold text-orange-600 uppercase flex items-center gap-1 hover:underline tracking-tighter"
                            >
                                <ExternalLink class="w-3 h-3" /> View on OpenStreetMap
                            </a>
                        </div>
                    </div>
                    <button @click="isEditing = true" 
                        class="w-full py-3 bg-orange-600 text-white rounded-xl font-bold uppercase text-sm hover:bg-orange-700 transition-all shadow-lg shadow-orange-600/20">
                        Edit Details
                    </button>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="3" 
                            class="p-3 w-full bg-transparent border-sidebar-border rounded-xl focus:ring-orange-500 focus:border-orange-500 text-foreground text-sm placeholder:text-muted-foreground/50" 
                            placeholder="Who did you get it from? Any funny memories?"></textarea>
                    </div>
                    
                    <div class="relative">
                        <label class="block text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1">Search Location (OSM)</label>
                        <div class="relative">
                            <input 
                                v-model="searchQuery" 
                                @input="handleSearchInput"
                                type="text" 
                                class="p-3 pl-10 w-full bg-transparent border-sidebar-border rounded-xl focus:ring-orange-500 focus:border-orange-500 text-foreground text-sm placeholder:text-muted-foreground/50" 
                                placeholder="Search city, shop or street..." 
                            />
                            <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                <Loader2 v-if="isSearching" class="w-4 h-4 text-orange-600 animate-spin" />
                                <Search v-else class="w-4 h-4 text-muted-foreground" />
                            </div>
                        </div>

                        <div v-if="searchResults.length > 0" class="absolute z-10 w-full mt-2 bg-white dark:bg-[#1c1c1b] border border-sidebar-border rounded-xl shadow-2xl overflow-hidden max-h-40 overflow-y-auto">
                            <button 
                                v-for="result in searchResults" 
                                :key="result.place_id" 
                                type="button"
                                @click="selectLocation(result)"
                                class="w-full text-left px-4 py-3 text-[11px] leading-tight text-foreground hover:bg-orange-600 hover:text-white transition-colors border-b border-sidebar-border last:border-0"
                            >
                                {{ result.display_name }}
                            </button>
                        </div>

                        <div v-if="selectedCoords" class="mt-2 flex items-center gap-1.5 text-[10px] font-black uppercase text-green-500 tracking-wider">
                            <Check class="w-3 h-3" />
                            Coords Linked: {{ selectedCoords }}
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" 
                            class="flex-1 bg-orange-600 text-white py-3 rounded-xl font-black uppercase text-xs hover:bg-orange-700 transition-all disabled:opacity-50">
                            Save Changes
                        </button>
                        <button type="button" @click="isEditing = false" 
                            class="flex-1 bg-muted/10 text-muted-foreground py-3 rounded-xl font-black uppercase text-xs hover:bg-muted/20 border border-sidebar-border transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
            
            <button @click="$emit('close')" 
                class="w-full py-4 bg-muted/5 text-muted-foreground text-[10px] font-black uppercase tracking-widest border-t border-sidebar-border hover:bg-muted/10 hover:text-foreground transition-all">
                Close Modal
            </button>
        </div>
    </div>
</template>