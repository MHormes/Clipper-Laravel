<script setup lang="ts">
import { ref } from "vue"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui"
import { Button } from "@/components/ui"
import { Loader2 } from "@lucide/vue"

interface Props {
    title: string
    description: string
    open: boolean
    confirmText?: string
    cancelText?: string
    loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    confirmText: "Decline",
    cancelText: "Cancel",
    loading: false,
})

const emit = defineEmits<{
    (e: "confirm", reason: string): void
    (e: "cancel"): void
    (e: "update:open", value: boolean): void
}>()

const reason = ref("")

const handleConfirm = () => {
    if (props.loading) return
    emit("confirm", reason.value.trim())
}

const handleCancel = () => {
    if (props.loading) return
    reason.value = ""
    emit("cancel")
    emit("update:open", false)
}

const handleOpenChange = (value: boolean) => {
    if (!value) {
        handleCancel()
    } else {
        emit("update:open", true)
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-[420px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-2 space-y-2">
                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-content">
                    Reason for declining (optional)
                </label>
                <textarea
                    v-model="reason"
                    rows="3"
                    placeholder="Enter a reason..."
                    :disabled="loading"
                    class="w-full rounded-xl border border-border-color bg-input-background px-3 py-2 text-sm text-primary-content placeholder:text-muted-content focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none disabled:opacity-50"
                />
            </div>

            <DialogFooter class="flex flex-col-reverse sm:flex-row gap-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="loading"
                    @click="handleCancel"
                >
                    {{ cancelText }}
                </Button>
                <Button
                    type="button"
                    :disabled="loading"
                    @click="handleConfirm"
                >
                    <Loader2
                        v-if="loading"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    {{ confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
