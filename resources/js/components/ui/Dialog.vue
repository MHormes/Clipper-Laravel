<script lang="ts">
import { h, type HTMLAttributes } from "vue"
import { 
  DialogClose as RekaDialogClose, DialogContent as RekaDialogContent,
  DialogDescription as RekaDialogDescription, DialogOverlay as RekaDialogOverlay,
  DialogPortal as RekaDialogPortal, DialogTitle as RekaDialogTitle,
  DialogTrigger as RekaDialogTrigger,
  useForwardProps, useForwardPropsEmits as useRekaForwardPropsEmits
} from "reka-ui"
import { cn } from "@/lib/utils"
import { reactiveOmit } from "@vueuse/core"
import { X } from "lucide-vue-next"

export const DialogTrigger = {
  name: 'DialogTrigger',
  setup(props: any, { slots }: any) {
    return () => h(RekaDialogTrigger, { 'data-slot': 'dialog-trigger', ...props }, slots)
  }
}

export const DialogClose = {
  name: 'DialogClose',
  setup(props: any, { slots }: any) {
    return () => h(RekaDialogClose, { 'data-slot': 'dialog-close', ...props }, slots)
  }
}

export const DialogOverlay = {
  name: 'DialogOverlay',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    return () => h(RekaDialogOverlay, {
      'data-slot': 'dialog-overlay',
      ...delegatedProps,
      class: cn('data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80', props.class)
    }, slots)
  }
}

export const DialogContent = {
  name: 'DialogContent',
  inheritAttrs: false,
  setup(props: any, { slots, emit, attrs }: any) {
    const showCloseButton = props.showCloseButton !== false
    const delegatedProps = reactiveOmit(props, "class", "showCloseButton")
    const forwarded = useRekaForwardPropsEmits(delegatedProps, emit)

    return () => h(RekaDialogPortal, null, {
      default: () => [
        h(DialogOverlay),
        h(RekaDialogContent, {
          'data-slot': 'dialog-content',
          ...attrs, ...forwarded,
          class: cn('bg-component-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed top-[50%] left-[50%] z-50 grid w-full max-w-[calc(100%-2rem)] translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border p-6 shadow-lg duration-200 sm:max-w-lg', props.class)
        }, {
          default: () => [
            slots.default?.(),
            showCloseButton && h(RekaDialogClose, {
              'data-slot': 'dialog-close',
              class: 'ring-offset-primary-background focus:ring-primary-content data-[state=open]:bg-muted-background data-[state=open]:text-muted-content absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4'
            }, { default: () => [h(X), h('span', { class: 'sr-only' }, 'Close')] })
          ]
        })
      ]
    })
  }
}

export const DialogHeader = {
  name: 'DialogHeader',
  setup(props: { class?: HTMLAttributes }, { slots }: any) {
    return () => h('div', { 'data-slot': 'dialog-header', class: cn('flex flex-col gap-2 text-center sm:text-left', props.class) }, slots)
  }
}

export const DialogFooter = {
  name: 'DialogFooter',
  setup(props: { class?: HTMLAttributes }, { slots }: any) {
    return () => h('div', { 'data-slot': 'dialog-footer', class: cn('flex flex-col-reverse gap-2 sm:flex-row sm:justify-end', props.class) }, slots)
  }
}

export const DialogTitle = {
  name: 'DialogTitle',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    const forwardedProps = useForwardProps(delegatedProps)
    return () => h(RekaDialogTitle, { 'data-slot': 'dialog-title', ...forwardedProps, class: cn('text-lg leading-none font-semibold', props.class) }, slots)
  }
}

export const DialogDescription = {
  name: 'DialogDescription',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    const forwardedProps = useForwardProps(delegatedProps)
    return () => h(RekaDialogDescription, { 'data-slot': 'dialog-description', ...forwardedProps, class: cn('text-muted-content text-sm', props.class) }, slots)
  }
}
</script>

<script setup lang="ts">
import type { DialogRootEmits, DialogRootProps } from "reka-ui"
import { DialogRoot, useForwardPropsEmits } from "reka-ui"

const props = defineProps<DialogRootProps>()
const emits = defineEmits<DialogRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DialogRoot
    v-slot="slotProps"
    data-slot="dialog"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </DialogRoot>
</template>
