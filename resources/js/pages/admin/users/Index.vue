<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed, reactive } from 'vue';
import { Users, ShieldCheck, Mail, Calendar, Trash2, Ban, CheckCircle, Search } from 'lucide-vue-next';
import { AppPageProps } from '@/types';
import ConfirmationModal from '@/components/modal/ConfirmationModal.vue';

const page = usePage<AppPageProps>();

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{
    users: User[];
}>();

const searchTerm = ref('');
const filteredUsers = computed(() => {
    return props.users.filter(user =>
        user.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        user.email.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const modalState = reactive({
    isOpen: false,
    title: '',
    description: '',
    onConfirm: () => {},
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
        <div class="w-full max-w-7xl mx-auto p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                <div>
                    <h1 class="text-3xl font-black uppercase tracking-tight leading-tight flex items-center gap-3">
                        <Users class="w-8 h-8 text-orange-600" />
                        User Management
                    </h1>
                    <p class="text-muted-content text-sm mt-1">Manage platform users, roles, and account access.</p>
                </div>

                <div class="relative w-full md:w-96">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-content" />
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Search users..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-black border border-border-color rounded-xl focus:ring-2 focus:ring-orange-500 outline-none transition-all text-sm shadow-sm"
                    />
                </div>
            </div>

            <div class="bg-white dark:bg-[#161615] rounded-3xl border border-border-color shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-white/[0.02] border-b border-border-color">
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-muted-content">User</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-muted-content text-center">Role</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-muted-content text-center">Status</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-muted-content text-center">Joined</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-muted-content text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-color">
                            <tr v-for="user in filteredUsers" :key="user.id"
                                class="hover:bg-gray-50 dark:hover:bg-white/[0.01] transition-colors group"
                                :class="{'opacity-60': !user.is_active}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-950/30 flex items-center justify-center text-orange-600 font-bold">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-base">{{ user.name }}</div>
                                            <div class="text-xs text-muted-content flex items-center gap-1">
                                                <Mail class="w-3 h-3" />
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        @click="updateUserRole(user)"
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest transition-all"
                                        :class="user.role === 'admin'
                                            ? 'bg-orange-600 text-white hover:bg-orange-700'
                                            : 'bg-gray-100 dark:bg-white/5 text-muted-content hover:bg-gray-200 dark:hover:bg-white/10'">
                                        {{ user.role }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest transition-all"
                                        :class="user.is_active
                                            ? 'text-green-600 bg-green-100 dark:bg-green-950/20 hover:bg-green-200 dark:hover:bg-green-950/40'
                                            : 'text-red-600 bg-red-100 dark:bg-red-950/20 hover:bg-red-200 dark:hover:bg-red-950/40'">
                                        <CheckCircle v-if="user.is_active" class="w-3 h-3" />
                                        <Ban v-else class="w-3 h-3" />
                                        {{ user.is_active ? 'Active' : 'Disabled' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs text-muted-content flex items-center justify-center gap-1 font-medium">
                                        <Calendar class="w-3 h-3" />
                                        {{ formatDate(user.created_at) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="deleteUser(user)"
                                        class="p-2 text-muted-content hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-all"
                                        title="Delete User"
                                        :disabled="user.id === page.props.auth.user.id"
                                        :class="{'opacity-20 cursor-not-allowed': user.id === page.props.auth.user.id}">
                                        <Trash2 class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-2 text-muted-content">
                                        <Users class="w-12 h-12 opacity-20" />
                                        <p class="font-bold">No users found matching your search.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :open="modalState.isOpen"
            :title="modalState.title"
            :description="modalState.description"
            :loading="modalState.loading"
            @confirm="modalState.onConfirm"
            @cancel="modalState.isOpen = false"
            @update:open="modalState.isOpen = $event"
        />
    </AppLayout>
</template>
