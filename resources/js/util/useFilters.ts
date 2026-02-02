// @/composables/useFilters.ts
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

export function useFilters(routeName: string, initialFilters: any) {
    const search = ref(initialFilters?.search || '');
    const sortCol = ref(initialFilters?.sortCol || '');
    const sortDir = ref(initialFilters?.sortDir || '');
    const filter = ref(initialFilters?.filter || 'all');
    const type = ref(initialFilters?.type || 'all');

    // Watch for internal prop changes (Inertia partial reloads)
    watch(() => initialFilters, (newFilters) => {
        if (newFilters) {
            search.value = newFilters.search || '';
            sortCol.value = newFilters.sortCol || '';
            sortDir.value = newFilters.sortDir || '';
            filter.value = newFilters.filter || 'all';
            type.value = newFilters.type || 'all';
        }
    }, { deep: true });

    const isFiltered = computed(() => search.value !== '' || sortCol.value !== '' || sortDir.value !== '' || filter.value !== 'all' || type.value !== 'all');

    const updateResults = () => {
        router.get(route(routeName), {
            search: search.value,
            sortCol: sortCol.value,
            sortDir: sortDir.value,
            filter: filter.value,
            type: type.value
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const resetFilters = () => {
        search.value = '';
        sortCol.value = '';
        sortDir.value = '';
        filter.value = 'all';
        type.value = 'all';
        updateResults();
    };

    let timeout: any = null;
    watch(search, () => {
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(updateResults, 300);
    });

    watch([filter, type], () => {
        updateResults();
    });

    const toggleSort = (column: string) => {
        if (sortCol.value !== column) {
            sortCol.value = column;
            sortDir.value = 'asc';
        } else if (sortDir.value === 'asc') {
            sortDir.value = 'desc';
        } else {
            sortCol.value = '';
            sortDir.value = '';
        }
        updateResults();
    };

    return { search, sortCol, sortDir, filter, type, isFiltered, resetFilters, toggleSort, updateResults };
}