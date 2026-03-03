<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    showName?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    showName: true,
});

const { getInitials } = useInitials();
const userInitials = computed(() => getInitials(props.user.name));
</script>

<template>
    <Avatar class="h-8 w-8 rounded-lg">
        <AvatarImage :src="user.avatar" :alt="user.name" />
        <AvatarFallback class="rounded-lg">
            {{ userInitials }}
        </AvatarFallback>
    </Avatar>
    <div v-if="showName" class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-content">{{
            user.email
        }}</span>
    </div>
</template>
