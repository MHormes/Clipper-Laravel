<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <template v-if="item.children && item.children.length > 0">
                    <SidebarMenuItem>
                        <div class="px-3 py-2 text-[10px] font-bold text-muted-content uppercase tracking-widest opacity-70">
                            {{ item.title }}
                        </div>

                        <div class="flex flex-col gap-1 ml-2 border-l pl-2">
                            <SidebarMenuButton
                                v-for="child in item.children"
                                :key="child.title"
                                as-child
                                :is-active="urlIsActive(child.href ?? '')"
                                :tooltip="child.title"
                            >
                                <Link :href="child.href">
                                    <component :is="child.icon" v-if="child.icon" />
                                    <span>{{ child.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </div>
                    </SidebarMenuItem>
                </template>

                <template v-else>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="urlIsActive(item.href ?? '')"
                            :tooltip="item.title"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </template>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
