<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <AuthLayout
        title="Set new password"
        description="Almost there! Enter a secure new password for your account."
    >
        <Head title="Reset password" />

        <Form
            v-bind="update.form()"
            :transform="(data) => ({ ...data, token, email })"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-bold tracking-tight opacity-50">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        readonly
                        class="rounded-xl border-gray-200 bg-gray-50/50 opacity-60 cursor-not-allowed dark:bg-[#161615] dark:border-white/10"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="password" class="text-sm font-bold tracking-tight">New Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            autofocus
                            placeholder="••••••••"
                            class="rounded-xl border-gray-200 focus:ring-[#f53003] focus:border-[#f53003] dark:bg-[#161615] dark:border-white/10"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-sm font-bold tracking-tight">Confirm</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="••••••••"
                            class="rounded-xl border-gray-200 focus:ring-[#f53003] focus:border-[#f53003] dark:bg-[#161615] dark:border-white/10"
                        />
                    </div>
                </div>

                <div class="col-span-full">
                    <InputError :message="errors.password" />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full py-6 rounded-xl bg-[#f53003] text-white font-bold text-lg shadow-lg shadow-orange-500/20 hover:bg-[#ff4433] hover:scale-[1.02] transition-all active:scale-[0.98]"
                    :disabled="processing"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                    Reset Password
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>