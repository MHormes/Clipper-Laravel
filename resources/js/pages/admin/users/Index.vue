<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { reactive } from 'vue';
import { Users, Mail, Calendar, Trash2, Ban, CheckCircle, Search, UserRoundSearch } from '@lucide/vue';
import { AppPageProps } from '@/types';
import ConfirmationModal from '@/components/modal/ConfirmationModal.vue';
import SortButton from '@/components/SortButton.vue';
import Pagination from '@/components/Pagination.vue';
import { useFilters } from '@/util/useFilters';

const page = usePage<AppPageProps>();

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    users: {
        data: User[];
        links: PaginationLink[];
    };
    filters: {
        search: string;
        sortCol: string;
        sortDir: string;
    };
}>();

const { search, sortCol, sortDir, toggleSort } = useFilters('admin.users.index', props.filters);

const modalState = reactive({
    isOpen: false,
    title: '',
    description: '',
    onConfirm: () => { },
    loading: false
});

const openConfirmation = (title: string, description: string, onConfirm: () => void) => {
    modalState.title = title;
    modalState.description = description;
    modalState.onConfirm = onConfirm;
    modalState.isOpen = true;
};

const updateUserRole = (user: User) => {
    const newRole = user.role === 'admin' ? 'user' : 'admin';
    if (user.id === page.props.auth.user.id && newRole !== 'admin') {
        alert('You cannot remove your own admin role.');
        return;
    }

    openConfirmation(
        'Change User Role',
        `Are you sure you want to change ${user.name}'s role to ${newRole}?`,
        () => {
            modalState.loading = true;
            router.put(route('admin.users.update', user.id), {
                role: newRole,
                is_active: user.is_active
            }, {
                onFinish: () => {
                    modalState.loading = false;
                    modalState.isOpen = false;
                }
            });
        }
    );
};

const toggleUserStatus = (user: User) => {
    const newStatus = !user.is_active;
    const action = newStatus ? 'enable' : 'disable';

    openConfirmation(
        `${newStatus ? 'Enable' : 'Disable'} User`,
        `Are you sure you want to ${action} ${user.name}? ${!newStatus ? 'They will no longer be able to log in.' : ''}`,
        () => {
            modalState.loading = true;
            router.put(route('admin.users.update', user.id), {
                role: user.role,
                is_active: newStatus
            }, {
                onFinish: () => {
                    modalState.loading = false;
                    modalState.isOpen = false;
                }
            });
        }
    );
};

const deleteUser = (user: User) => {
    if (user.id === page.props.auth.user.id) {
        alert('You cannot delete yourself.');
        return;
    }

    openConfirmation(
        'Delete User',
        `Are you sure you want to delete ${user.name}? This will permanently remove their personal collection, but any series or clippers they created will remain.`,
        () => {
            modalState.loading = true;
            router.delete(route('admin.users.destroy', user.id), {
                onFinish: () => {
                    modalState.loading = false;
                    modalState.isOpen = false;
                }
            });
        }
    );
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>

    <Head title="User Management" />

    <AppLayout :breadcrumbs="[{ title: 'Administration', href: '#' }, { title: 'Users', href: '#' }]">
        <div class="w-full max-w-7xl mx-auto p-4 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-2xl bg-primary/10 text-primary">
                        <Users class="w-8 h-8" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black uppercase tracking-tight leading-tight">User Management</h1>
                        <p class="text-muted-content text-sm">Manage platform users, roles, and account access.</p>
                    </div>
                </div>

                <div class="relative w-full md:w-96">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-content" />
                    <input v-model="search" type="text" placeholder="Search users..."
                        class="w-full pl-10 pr-4 py-2.5 bg-component-background border border-border-color rounded-xl focus:ring-2 focus:ring-primary outline-none transition-all text-sm shadow-sm" />
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
                <SortButton label="Name" column="name" :activeColumn="sortCol" :direction="sortDir"
                    @toggle="toggleSort" />
                <SortButton label="Role" column="role" :activeColumn="sortCol" :direction="sortDir"
                    @toggle="toggleSort" />
                <SortButton label="Status" column="is_active" :activeColumn="sortCol" :direction="sortDir"
                    @toggle="toggleSort" />
                <SortButton label="Joined" column="created_at" :activeColumn="sortCol" :direction="sortDir"
                    @toggle="toggleSort" />
            </div>

            <div
                class="bg-component-background rounded-3xl border border-border-color shadow-sm overflow-hidden relative">
                <!-- Decorative background accent -->
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl pointer-events-none">
                </div>

                <div class="overflow-x-auto relative z-10">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-muted-background/30 border-b border-border-color">
                                <th
                                    class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-content">
                                    User</th>
                                <th
                                    class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-content text-center">
                                    Role</th>
                                <th
                                    class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-content text-center">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-content text-center">
                                    Joined</th>
                                <th
                                    class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-content text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-color">
                            <tr v-for="user in users.data" :key="user.id"
                                class="hover:bg-muted-background/20 transition-colors group"
                                :class="{ 'opacity-60': !user.is_active }">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-black text-primary-content uppercase tracking-tight">{{
                                                user.name }}</div>
                                            <div class="text-xs text-muted-content flex items-center gap-1">
                                                <Mail class="w-3 h-3 opacity-50" />
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="updateUserRole(user)"
                                        class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest transition-all"
                                        :class="user.role === 'admin'
                                            ? 'bg-primary text-button-content hover:shadow-lg shadow-primary/20'
                                            : 'bg-muted-background text-muted-content hover:bg-muted-background/50'">
                                        {{ user.role }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="toggleUserStatus(user)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest transition-all"
                                        :class="user.is_active
                                            ? 'text-success bg-success/10 border border-success/20 hover:bg-success hover:text-button-content!'
                                            : 'text-error bg-error/10 border border-error/20 hover:bg-error hover:text-button-content!'">
                                        <CheckCircle v-if="user.is_active" class="w-3 h-3" />
                                        <Ban v-else class="w-3 h-3" />
                                        {{ user.is_active ? 'Active' : 'Disabled' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-xs text-muted-content flex items-center justify-center gap-1 font-bold">
                                        <Calendar class="w-3 h-3 opacity-50" />
                                        {{ formatDate(user.created_at) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('users.show', user.id)"
                                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-black uppercase tracking-wider rounded-xl border border-border-color text-primary-content hover:border-primary/50 hover:text-primary transition-all">
                                            <UserRoundSearch class="w-4 h-4" />
                                            See Profile
                                        </Link>

                                        <button @click="deleteUser(user)"
                                            class="p-2 text-muted-content hover:text-error hover:bg-error/10 rounded-xl transition-all"
                                            title="Delete User" :disabled="user.id === page.props.auth.user.id"
                                            :class="{ 'opacity-20 cursor-not-allowed': user.id === page.props.auth.user.id }">
                                            <Trash2 class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-2 text-muted-content">
                                        <Users class="w-12 h-12 opacity-10" />
                                        <p class="font-black uppercase tracking-widest text-xs opacity-40">No users
                                            found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="users.links" />
        </div>

        <ConfirmationModal :open="modalState.isOpen" :title="modalState.title" :description="modalState.description"
            :loading="modalState.loading" @confirm="modalState.onConfirm" @cancel="modalState.isOpen = false"
            @update:open="modalState.isOpen = $event" />
    </AppLayout>
</template>
