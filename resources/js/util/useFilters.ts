// @/composables/useFilters.ts
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

export function useFilters(routeName: string, initialFilters: any) {
    const search = ref(initialFilters?.search || '');
    const sortCol = ref(initialFilters?.sortCol || '');
    const sortDir = ref(initialFilters?.sortDir || '');

    const isFiltered = computed(() => search.value !== '' || sortCol.value !== '' || sortDir.value !== '');

    const updateResults = () => {
        router.get(route(routeName), {
            search: search.value,
            sortCol: sortCol.value,
            sortDir: sortDir.value
        }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const resetFilters = () => {
        search.value = '';
        sortCol.value = '';
        sortDir.value = '';
        updateResults();
    };

    let timeout: any = null;
    watch(search, () => {
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(updateResults, 300);
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

    return { search, sortCol, sortDir, isFiltered, resetFilters, toggleSort, updateResults };
}