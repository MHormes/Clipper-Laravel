<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui';
import { Sidebar, useSidebar } from '@/components/ui';
import { dashboard } from '@/routes';
import { type NavItem, type AppPageProps } from '@/types';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Map, Users, ClipboardCheck, ClipboardList, ListCheck, CheckCircle, Library, ChevronsUpDown, Settings, LogOut } from '@lucide/vue';
import AppLogo from './AppLogo.vue';
import AppLogoIcon from './AppLogoIcon.vue';
import InstallAppButton from './InstallAppButton.vue';
import UserInfo from '@/components/UserInfo.vue';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import { router } from '@inertiajs/vue3';

const page = usePage<AppPageProps>();
const { urlIsActive } = useActiveUrl();
const { isMobile, state } = useSidebar();

const user = computed(() => page.props.auth.user);

const handleLogout = () => {
    router.flushAll();
};

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'All Series', href: '/series', icon: Library },
        {
            title: 'Users',
            icon: Users,
            children: [
                ...(user.value ? [{ title: 'View Public Profile', href: `/users/${user.value.id}`, icon: Users }] : []),
                { title: 'Find Users', href: '/users', icon: Users },
                { title: 'Following', href: '/users/following', icon: Users },
            ],
        },
        {
            title: 'Collection',
            icon: ListCheck,
            children: [
                { title: 'Your Clippers', href: '/collection/clippers', icon: CheckCircle },
                { title: 'Your Series', href: '/collection', icon: ListCheck },
                { title: 'Map View', href: '/mapview', icon: Map },
            ],
        },
    ];

    if (page.props.auth.is_admin) {
        items.push({
            title: 'Admin',
            icon: Users,
            children: [{ title: 'User Management', href: '/admin/users', icon: Users }],
        });
        items.push({
            title: 'Admin Requests',
            icon: ClipboardList,
            children: [
                { title: 'Series Requests', href: '/admin/requests/series', icon: ClipboardList },
                { title: 'Clipper Requests', href: '/admin/requests/clippers', icon: ClipboardCheck },
            ],
        });
    } else {
        items.push({
            title: 'Pending Requests',
            icon: ClipboardList,
            children: [
                { title: 'My Series Requests', href: '/requests/pending/series', icon: ClipboardList },
                { title: 'My Clipper Requests', href: '/requests/pending/clippers', icon: ClipboardCheck },
            ],
        });
    }
    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <!-- Header / Logo -->
        <div class="flex h-16 items-center border-b border-border-color transition-all duration-200"
             :class="state === 'collapsed' ? 'justify-center px-0 h-12' : 'px-4'">
            <Link :href="dashboard()" class="flex items-center gap-2 overflow-hidden">
                <AppLogo v-if="state === 'expanded' || isMobile" />
                <div v-else class="flex items-center justify-center w-8 h-8">
                    <AppLogoIcon class="size-6" />
                </div>
            </Link>
        </div>

        <!-- Main Navigation -->
        <div class="flex-1 overflow-y-auto py-2 space-y-2 transition-all duration-200"
             :class="state === 'collapsed' ? 'px-1' : 'px-2'">
            <div v-for="group in mainNavItems" :key="group.title" class="space-y-1">
                <!-- Group Label (if children exist) -->
                <div v-if="group.children && state !== 'collapsed'" class="px-3 py-2 text-[10px] font-black uppercase tracking-widest opacity-70 text-muted-content">
                    {{ group.title }}
                </div>

                <!-- Single Item -->
                <Link v-if="!group.children" :href="group.href!" 
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted-background text-primary-content"
                    :class="[
                        { 'bg-muted-background font-medium': urlIsActive(group.href!) },
                        state === 'collapsed' ? 'justify-center px-0' : ''
                    ]"
                >
                    <component :is="group.icon" class="size-4 shrink-0" />
                    <span v-if="state !== 'collapsed'" class="truncate">{{ group.title }}</span>
                </Link>

                <!-- Children Items -->
                <div v-else class="space-y-1" :class="state === 'collapsed' ? 'ml-0 border-l-0 pl-0' : 'ml-4 border-l-2 border-border-color pl-2 my-1'">
                    <Link v-for="child in group.children" :key="child.title" :href="child.href!"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted-background text-primary-content"
                        :class="[
                            { 'bg-muted-background font-black text-primary': urlIsActive(child.href!) },
                            state === 'collapsed' ? 'justify-center px-0' : ''
                        ]"
                    >
                        <component :is="child.icon" class="size-4 shrink-0" />
                        <span v-if="state !== 'collapsed'" class="truncate">{{ child.title }}</span>
                    </Link>
                </div>
            </div>
        </div>

        <div
            class="px-2 pb-2"
            :class="state === 'collapsed' ? 'px-1' : 'px-2'"
        >
            <InstallAppButton
                :full-width="state !== 'collapsed' || isMobile"
            />
        </div>

        <!-- Footer / User Menu -->
        <div class="p-2 border-t border-border-color">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button class="flex w-full items-center gap-2 rounded-md p-2 text-left text-sm transition-colors hover:bg-muted-background focus:outline-none"
                            :class="state === 'collapsed' ? 'justify-center' : ''">
                        <UserInfo :user="user" :show-name="state !== 'collapsed'" />
                        <ChevronsUpDown v-if="state !== 'collapsed'" class="ml-auto size-4" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-56"
                    :side="isMobile ? 'bottom' : state === 'collapsed' ? 'right' : 'bottom'"
                    align="end"
                >
                    <DropdownMenuLabel class="p-0 font-normal">
                        <div class="flex items-center gap-2 px-3 py-2 text-left text-sm">
                            <UserInfo :user="user" :show-email="true" />
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <Link :href="edit()" class="flex w-full items-center gap-2 cursor-pointer">
                            <Settings class="size-4" />
                            Settings
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem as-child>
                        <Link :href="logout()" method="post" as="button" @click="handleLogout" class="flex w-full items-center gap-2 cursor-pointer">
                            <LogOut class="size-4" />
                            Log out
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </Sidebar>
</template>
