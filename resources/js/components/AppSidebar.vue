<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Map, Users, ClipboardCheck, ClipboardList } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Map View',
            href: '/mapview',
            icon: Map,
        },
    ];

    if (page.props.auth.is_admin) {
        items.push({
            title: 'User Management',
            href: '/admin/users',
            icon: Users,
        });
        items.push({
            title: 'Series Requests',
            href: '/admin/requests/series',
            icon: ClipboardList,
        });
        items.push({
            title: 'Clipper Requests',
            href: '/admin/requests/clippers',
            icon: ClipboardCheck,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
