<script setup lang="ts">
import NotificationsController from '@/actions/App/Http/Controllers/Settings/NotificationsController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/notifications';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, usePage } from '@inertiajs/vue3';

interface NotificationCategory {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
    recipient: 'admin' | 'user';
}

interface Props {
    categories: NotificationCategory[];
}

const props = defineProps<Props>();

const page = usePage();
const isAdmin = page.props.auth.is_admin;

const adminCategories = props.categories.filter(c => c.recipient === 'admin');
const userCategories = props.categories.filter(c => c.recipient === 'user');

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Settings', href: edit().url },
    { title: 'Notifications', href: edit().url },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Notification Settings" />

        <h1 class="sr-only">Notification Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-10 p-1">
                <div class="space-y-1">
                    <HeadingSmall title="Email Notifications" description="Choose which emails you want to receive"
                        class="text-xl font-black tracking-tight" />
                    <div class="h-1 w-12 bg-[var(--primary)] rounded-full mt-2"></div>
                </div>

                <Form v-bind="NotificationsController.update.form()" class="space-y-6"
                    v-slot="{ processing, recentlySuccessful }">
                    <div v-if="isAdmin && adminCategories.length"
                        class="rounded-2xl border border-border-color bg-component-background p-6 shadow-sm space-y-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted-content">Admin
                            Notifications</p>
                        <div v-for="cat in adminCategories" :key="cat.key"
                            class="flex items-start justify-between gap-6 py-2 border-b border-border-color last:border-0 last:pb-0">
                            <div class="space-y-0.5">
                                <p class="text-sm font-semibold text-primary-content">{{ cat.label }}</p>
                                <p class="text-xs text-muted-content">{{ cat.description }}</p>
                            </div>
                            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="checkbox" :name="cat.key" value="1" :checked="cat.enabled"
                                    class="peer sr-only" />
                                <div
                                    class="h-5 w-9 rounded-full bg-muted-background peer-checked:bg-[var(--primary)] transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4 after:shadow-sm">
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-border-color bg-component-background p-6 shadow-sm space-y-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted-content">My Requests</p>
                        <div v-for="cat in userCategories" :key="cat.key"
                            class="flex items-start justify-between gap-6 py-2 border-b border-border-color last:border-0 last:pb-0">
                            <div class="space-y-0.5">
                                <p class="text-sm font-semibold text-primary-content">{{ cat.label }}</p>
                                <p class="text-xs text-muted-content">{{ cat.description }}</p>
                            </div>
                            <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                                <input type="checkbox" :name="cat.key" value="1" :checked="cat.enabled"
                                    class="peer sr-only" />
                                <div
                                    class="h-5 w-9 rounded-full bg-muted-background peer-checked:bg-[var(--primary)] transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4 after:shadow-sm">
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <Button :disabled="processing"
                            class="rounded-xl bg-[var(--primary)] px-8 font-bold text-secondary-content hover:bg-[var(--primary)] transition-all active:scale-95 shadow-lg shadow-primary/10">
                            Save Preferences
                        </Button>

                        <Transition enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0 translate-x-1" leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0 -translate-x-1">
                            <p v-show="recentlySuccessful" class="text-sm font-semibold text-success">
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
