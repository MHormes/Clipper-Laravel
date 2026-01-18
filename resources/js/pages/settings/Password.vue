<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/user-password';
import { Form, Head } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Security',
        href: edit().url,
    },
    {
        title: 'Password',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Password Settings" />

        <h1 class="sr-only">Password Settings</h1>

        <SettingsLayout>
            <div class="space-y-8 p-1">
                <div class="space-y-1">
                    <HeadingSmall
                        title="Update Password"
                        description="Ensure your account is using a long, random password to stay secure"
                        class="text-xl font-black tracking-tight"
                    />
                    <div class="h-1 w-12 bg-[#f53003] rounded-full mt-2"></div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]">
                    <Form
                        v-bind="PasswordController.update.form()"
                        :options="{
                            preserveScroll: true,
                        }"
                        reset-on-success
                        :reset-on-error="[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]"
                        class="max-w-xl space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password" class="text-sm font-bold tracking-tight">Current Password</Label>
                            <Input
                                id="current_password"
                                name="current_password"
                                type="password"
                                class="rounded-xl border-gray-200 focus:ring-[#f53003] focus:border-[#f53003] dark:bg-[#0c0c0c]"
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="password" class="text-sm font-bold tracking-tight">New Password</Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    class="rounded-xl border-gray-200 focus:ring-[#f53003] focus:border-[#f53003] dark:bg-[#0c0c0c]"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation" class="text-sm font-bold tracking-tight">Confirm New</Label>
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    class="rounded-xl border-gray-200 focus:ring-[#f53003] focus:border-[#f53003] dark:bg-[#0c0c0c]"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>
                        <InputError :message="errors.password" />
                        <InputError :message="errors.password_confirmation" />

                        <div class="flex items-center gap-4 pt-2">
                            <Button
                                :disabled="processing"
                                class="rounded-xl bg-[#f53003] px-8 font-bold text-white hover:bg-[#ff4433] transition-all active:scale-95"
                                data-test="update-password-button"
                            >
                                <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                                Save Changes
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0 translate-x-1"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0 -translate-x-1"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm font-semibold text-green-600 dark:text-green-400 flex items-center gap-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Updated
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>