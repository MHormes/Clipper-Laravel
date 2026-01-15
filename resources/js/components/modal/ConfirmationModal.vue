<script setup lang="ts">
/**
 * Generic confirmation modal component.
 * Used to ask users for confirmation before proceeding with an action.
 */

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Loader2 } from "lucide-vue-next"

interface Props {
    /**
     * The title of the modal.
     */
    title: string
    /**
     * The description text shown in the modal body.
     */
    description: string
    /**
     * Controls the visibility of the modal.
     */
    open: boolean
    /**
     * Text for the confirmation button.
     * @default "Yes"
     */
    confirmText?: string
    /**
     * Text for the cancel button.
     * @default "No"
     */
    cancelText?: string
    /**
     * Shows a loading state on the confirm button and disables interaction.
     * @default false
     */
    loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    confirmText: "Yes",
    cancelText: "No",
    loading: false,
})

const emit = defineEmits<{
    /**
     * Emitted when the confirm button is clicked.
     */
    (e: "confirm"): void
    /**
     * Emitted when the cancel button is clicked or the modal is closed.
     */
    (e: "cancel"): void
    /**
     * Emitted for two-way binding of the visibility state.
     */
    (e: "update:open", value: boolean): void
}>()

/**
 * Handles the confirmation action.
 */
const handleConfirm = () => {
    if (props.loading) return
    emit("confirm")
}

/**
 * Handles the cancellation action.
 */
const handleCancel = () => {
    if (props.loading) return
    emit("cancel")
    emit("update:open", false)
}

/**
 * Handles the open change event from the Dialog component.
 * @param value The new open state.
 */
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
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

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
