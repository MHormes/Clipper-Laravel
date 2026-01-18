<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
                    <div class="h-1 w-12 bg-[#f53003] rounded-full mt-2"></div>
                </div>

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]">
                    
                    <div
                        v-if="!twoFactorEnabled"
                        class="flex flex-col items-start space-y-6 max-w-xl"
                    >
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="rounded-lg px-2 py-0.5 border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                                Currently Disabled
                            </Badge>
                        </div>

                        <div class="flex gap-4">
                            <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-400 dark:bg-white/5">
                                <Lock class="h-6 w-6" />
                            </div>
                            <p class="text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                                When two-factor authentication is enabled, you'll be prompted for a secure 6-digit PIN from your authenticator app (like Google Authenticator or 1Password) whenever you sign in.
                            </p>
                        </div>

                        <div class="pt-2">
                            <Button
                                v-if="hasSetupData"
                                @click="showSetupModal = true"
                                class="rounded-xl bg-[#f53003] px-6 font-bold text-white hover:bg-[#ff4433] transition-all hover:scale-[1.02] active:scale-95"
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
                                    class="rounded-xl bg-[#f53003] px-6 font-bold text-white hover:bg-[#ff4433] transition-all hover:scale-[1.02] active:scale-95 shadow-lg shadow-orange-500/10"
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
                            <Badge class="rounded-lg px-2 py-0.5 bg-green-500/10 text-green-600 border-none font-bold uppercase tracking-wider text-[10px] dark:bg-green-500/20 dark:text-green-400">
                                Active & Secure
                            </Badge>
                        </div>

                        <p class="text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                            Your account is protected. You will be prompted for an authentication code when logging in from a new device.
                        </p>

                        <div class="w-full rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-white/5 dark:bg-white/5">
                             <TwoFactorRecoveryCodes />
                        </div>

                        <div class="pt-4 border-t border-gray-100 w-full dark:border-white/5">
                            <Form v-bind="disable.form()" #default="{ processing }">
                                <Button
                                    variant="ghost"
                                    type="submit"
                                    :disabled="processing"
                                    class="text-xs font-bold text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20"
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