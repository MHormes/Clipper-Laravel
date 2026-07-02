<script setup lang="ts">
import type { DialogRootEmits, DialogRootProps } from "reka-ui"
import { DialogRoot, useForwardPropsEmits } from "reka-ui"

const props = defineProps<DialogRootProps>()
const emits = defineEmits<DialogRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<script lang="ts">
import { h, type HTMLAttributes } from "vue"
import { 
  DialogClose as RekaDialogClose, DialogContent as RekaDialogContent,
  DialogDescription as RekaDialogDescription, DialogOverlay as RekaDialogOverlay,
  DialogPortal as RekaDialogPortal, DialogTitle as RekaDialogTitle,
  DialogTrigger as RekaDialogTrigger,
  useForwardPropsEmits as useRekaForwardPropsEmits
} from "reka-ui"
import { cn } from "@/lib/utils"
import { reactiveOmit } from "@vueuse/core"
import { X } from "@lucide/vue"

export const SheetTrigger = {
  name: 'SheetTrigger',
  setup(props: any, { slots }: any) {
    return () => h(RekaDialogTrigger, { 'data-slot': 'sheet-trigger', ...props }, slots)
  }
}

export const SheetClose = {
  name: 'SheetClose',
  setup(props: any, { slots }: any) {
    return () => h(RekaDialogClose, { 'data-slot': 'sheet-close', ...props }, slots)
  }
}

export const SheetOverlay = {
  name: 'SheetOverlay',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    return () => h(RekaDialogOverlay, {
      'data-slot': 'sheet-overlay',
      ...delegatedProps,
      class: cn('data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80', props.class)
    }, slots)
  }
}

export const SheetContent = {
  name: 'SheetContent',
  inheritAttrs: false,
  setup(props: any, { slots, emit, attrs }: any) {
    const side = props.side || 'left'
    const delegatedProps = reactiveOmit(props, "class", "side")
    const forwarded = useRekaForwardPropsEmits(delegatedProps, emit)

    return () => h(RekaDialogPortal, null, {
      default: () => [
        h(SheetOverlay),
        h(RekaDialogContent, {
          'data-slot': 'sheet-content',
          ...attrs, ...forwarded,
          class: cn(
            'bg-component-background data-[state=open]:animate-in data-[state=closed]:animate-out fixed z-50 flex flex-col gap-4 shadow-lg transition ease-in-out data-[state=closed]:duration-300 data-[state=open]:duration-500',
            side === 'right' && 'data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right inset-y-0 right-0 h-full w-3/4 border-l sm:max-w-sm',
            side === 'left' && 'data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left inset-y-0 left-0 h-full w-3/4 border-r sm:max-w-sm',
            side === 'top' && 'data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top inset-x-0 top-0 h-auto border-b',
            side === 'bottom' && 'data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom inset-x-0 bottom-0 h-auto border-t',
            props.class
          )
        }, {
          default: () => [
            slots.default?.(),
            h(RekaDialogClose, {
              class: 'ring-offset-primary-background focus:ring-primary-content data-[state=open]:bg-primary-background absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none'
            }, { default: () => [h(X, { class: 'size-4' }), h('span', { class: 'sr-only' }, 'Close')] })
          ]
        })
      ]
    })
  }
}

export const SheetHeader = {
  name: 'SheetHeader',
  setup(props: { class?: HTMLAttributes }, { slots }: any) {
    return () => h('div', { 'data-slot': 'sheet-header', class: cn('flex flex-col gap-1.5 p-4', props.class) }, slots)
  }
}

export const SheetFooter = {
  name: 'SheetFooter',
  setup(props: { class?: HTMLAttributes }, { slots }: any) {
    return () => h('div', { 'data-slot': 'sheet-footer', class: cn('mt-auto flex flex-col gap-2 p-4', props.class) }, slots)
  }
}

export const SheetTitle = {
  name: 'SheetTitle',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    return () => h(RekaDialogTitle, { 'data-slot': 'sheet-title', ...delegatedProps, class: cn('text-primary-content font-semibold', props.class) }, slots)
  }
}

export const SheetDescription = {
  name: 'SheetDescription',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    return () => h(RekaDialogDescription, { 'data-slot': 'sheet-description', ...delegatedProps, class: cn('text-muted-content text-sm', props.class) }, slots)
  }
}
</script>

<template>
  <DialogRoot
    v-slot="slotProps"
    data-slot="sheet"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </DialogRoot>
</template>
