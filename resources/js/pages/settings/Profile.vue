<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui';
import { Input } from '@/components/ui';
import { Label } from '@/components/ui';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: edit().url,
    },
    {
        title: 'Profile',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile Settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-10 p-1">
                
                <div class="space-y-6">
                    <div class="space-y-1">
                        <HeadingSmall
                            title="Profile Information"
                            description="Update your account's public identity and contact email"
                            class="text-xl font-black tracking-tight"
                        />
                        <div class="h-1 w-12 bg-[var(--primary)] rounded-full mt-2"></div>
                    </div>

                    <div class="rounded-2xl border border-border-color  p-6 shadow-sm  bg-component-background">
                        <Form
                            v-bind="ProfileController.update.form()"
                            class="max-w-xl space-y-6"
                            v-slot="{ errors, processing, recentlySuccessful }"
                        >
                            <div class="grid gap-2">
                                <Label for="name" class="text-sm font-bold tracking-tight">Full Name</Label>
                                <div class="flex gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-[var(--primary)] dark:bg-primary/30">
                                        <span class="text-xs font-bold">{{ user.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                    <div class="grow">
                                        <Input
                                            id="name"
                                            class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] dark:bg-[#0c0c0c]"
                                            name="name"
                                            :default-value="user.name"
                                            required
                                            autocomplete="name"
                                            placeholder="Your Name"
                                        />
                                    </div>
                                </div>
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email" class="text-sm font-bold tracking-tight">Email Address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    class="rounded-xl border-border-color focus:ring-[var(--primary)] focus:border-[var(--primary)] dark:bg-[#0c0c0c]"
                                    name="email"
                                    :default-value="user.email"
                                    required
                                    autocomplete="username"
                                    placeholder="email@example.com"
                                />
                                <InputError :message="errors.email" />
                            </div>

                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-xl bg-warning p-4 border border-warning dark:bg-warning/10 dark:border-warning/20">
                                <p class="text-sm text-warning dark:text-warning">
                                    Your email is unverified.
                                    <Link
                                        :href="send()"
                                        as="button"
                                        class="font-bold underline decoration-amber-300 underline-offset-4 hover:text-warning dark:hover:text-warning"
                                    >
                                        Resend verification.
                                    </Link>
                                </p>

                                <div
                                    v-if="status === 'verification-link-sent'"
                                    class="mt-2 text-xs font-bold text-success dark:text-success"
                                >
                                    ✓ A new link has been sent.
                                </div>
                            </div>

                            <div class="flex items-center gap-4 pt-2">
                                <Button
                                    :disabled="processing"
                                    class="rounded-xl bg-[var(--primary)] px-8 font-bold text-secondary-content hover:bg-[var(--primary)] transition-all active:scale-95 shadow-lg shadow-primary/10"
                                    data-test="update-profile-button"
                                >
                                    <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                                    Save Profile
                                </Button>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0 translate-x-1"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0 -translate-x-1"
                                >
                                    <p v-show="recentlySuccessful" class="text-sm font-semibold text-success dark:text-success">
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </Form>
                    </div>
                </div>

                <div class="pt-4">
                    <div class="rounded-2xl border border-error bg-error/30 p-6 dark:border-error/20 dark:bg-error/5">
                        <DeleteUser />
                    </div>
                </div>

            </div>
        </SettingsLayout>
    </AppLayout>
</template>
