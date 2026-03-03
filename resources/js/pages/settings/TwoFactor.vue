<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui';
import { Button } from '@/components/ui';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { disable, enable, show } from '@/routes/two-factor';
import { BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';
import { ShieldBan, ShieldCheck, Lock } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

interface Props {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '#',
    },
    {
        title: 'Security',
        href: show.url(),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Two-Factor Authentication" />

        <h1 class="sr-only">Two-Factor Authentication Settings</h1>

        <SettingsLayout>
            <div class="space-y-8 p-1">
                <div class="space-y-1">
                    <HeadingSmall
                        title="Two-Factor Authentication"
                        description="Add an extra layer of security to your account"
                        class="text-xl font-black tracking-tight"
                    />
                    <div class="h-1 w-12 bg-[var(--primary)] rounded-full mt-2"></div>
                </div>

                <div class="rounded-2xl border border-border-color  p-6 shadow-sm  bg-component-background">
                    
                    <div
                        v-if="!twoFactorEnabled"
                        class="flex flex-col items-start space-y-6 max-w-xl"
                    >
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="rounded-lg px-2 py-0.5 border-border-color text-muted-content font-bold uppercase tracking-wider text-[10px]">
                                Currently Disabled
                            </Badge>
                        </div>

                        <div class="flex gap-4">
                            <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-muted-background text-muted-content dark:bg-secondary-content/5">
                                <Lock class="h-6 w-6" />
                            </div>
                            <p class="text-sm leading-relaxed text-[var(--muted-content)] dark:text-[var(--muted-content)]">
                                When two-factor authentication is enabled, you'll be prompted for a secure 6-digit PIN from your authenticator app (like Google Authenticator or 1Password) whenever you sign in.
                            </p>
                        </div>

                        <div class="pt-2">
                            <Button
                                v-if="hasSetupData"
                                @click="showSetupModal = true"
                                class="rounded-xl bg-[var(--primary)] px-6 font-bold text-secondary-content hover:bg-[var(--primary)] transition-all hover:scale-[1.02] active:scale-95"
                            >
                                <ShieldCheck class="mr-2 h-4 w-4" />
                                Continue Setup
                            </Button>

                            <Form
                                v-else
                                v-bind="enable.form()"
                                @success="showSetupModal = true"
                                #default="{ processing }"
                            >
                                <Button 
                                    type="submit" 
                                    :disabled="processing"
                                    class="rounded-xl bg-[var(--primary)] px-6 font-bold text-secondary-content hover:bg-[var(--primary)] transition-all hover:scale-[1.02] active:scale-95 shadow-lg shadow-primary/10"
                                >
                                    <ShieldCheck class="mr-2 h-4 w-4" />
                                    Enable Security Layer
                                </Button>
                            </Form>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-start space-y-6 max-w-2xl"
                    >
                        <div class="flex items-center gap-2">
                            <Badge class="rounded-lg px-2 py-0.5 bg-success/10 text-success border-none font-bold uppercase tracking-wider text-[10px] dark:bg-success/20 dark:text-success">
                                Active & Secure
                            </Badge>
                        </div>

                        <p class="text-sm leading-relaxed text-[var(--muted-content)] dark:text-[var(--muted-content)]">
                            Your account is protected. You will be prompted for an authentication code when logging in from a new device.
                        </p>

                        <div class="w-full rounded-xl border border-border-color bg-muted-background/50 p-4 dark:border-secondary-content/5 dark:bg-secondary-content/5">
                             <TwoFactorRecoveryCodes />
                        </div>

                        <div class="pt-4 border-t border-border-color w-full dark:border-secondary-content/5">
                            <Form v-bind="disable.form()" #default="{ processing }">
                                <Button
                                    variant="ghost"
                                    type="submit"
                                    :disabled="processing"
                                    class="text-xs font-bold text-error hover:text-error hover:bg-error hover:text-button-content!  dark:hover:bg-error hover:text-button-content! /20"
                                >
                                    <ShieldBan class="mr-2 h-3 w-3" />
                                    Disable Protection
                                </Button>
                            </Form>
                        </div>
                    </div>
                </div>

                <TwoFactorSetupModal
                    v-model:isOpen="showSetupModal"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
