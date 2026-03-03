<script lang="ts">
import { h, type HTMLAttributes } from "vue"
import { 
  DropdownMenuPortal, DropdownMenuContent as RekaDropdownMenuContent,
  DropdownMenuItem as RekaDropdownMenuItem, DropdownMenuTrigger as RekaDropdownMenuTrigger,
  DropdownMenuGroup as RekaDropdownMenuGroup, DropdownMenuLabel as RekaDropdownMenuLabel,
  DropdownMenuSeparator as RekaDropdownMenuSeparator,
  useForwardProps, useForwardPropsEmits as useRekaForwardPropsEmits
} from "reka-ui"
import { cn } from "@/lib/utils"
import { reactiveOmit } from "@vueuse/core"

export const DropdownMenuTrigger = {
  name: 'DropdownMenuTrigger',
  setup(props: any, { slots }: any) {
    const forwardedProps = useForwardProps(props)
    return () => h(RekaDropdownMenuTrigger, { 'data-slot': 'dropdown-menu-trigger', ...forwardedProps }, slots)
  }
}

export const DropdownMenuContent = {
  name: 'DropdownMenuContent',
  inheritAttrs: false,
  setup(props: any, { slots, emit, attrs }: any) {
    const sideOffset = props.sideOffset ?? 4
    const delegatedProps = reactiveOmit(props, "class")
    const forwarded = useRekaForwardPropsEmits(delegatedProps, emit)
    return () => h(DropdownMenuPortal, null, {
      default: () => h(RekaDropdownMenuContent, {
        'data-slot': 'dropdown-menu-content',
        sideOffset,
        ...attrs, ...forwarded,
        class: cn('bg-muted-background text-primary-content data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 max-h-(--reka-dropdown-menu-content-available-height) min-w-[8rem] origin-(--reka-dropdown-menu-content-transform-origin) overflow-x-hidden overflow-y-auto rounded-md border p-1 shadow-md mx-2', props.class)
      }, slots)
    })
  }
}

export const DropdownMenuItem = {
  name: 'DropdownMenuItem',
  setup(props: any, { slots }: any) {
    const variant = props.variant || 'default'
    const delegatedProps = reactiveOmit(props, "inset", "variant", "class")
    const forwardedProps = useForwardProps(delegatedProps)
    return () => h(RekaDropdownMenuItem, {
      'data-slot': 'dropdown-menu-item',
      'data-inset': props.inset ? '' : undefined,
      'data-variant': variant,
      ...forwardedProps,
      class: cn('focus:bg-muted-background focus:text-primary-content data-[variant=destructive]:text-error data-[variant=destructive]:focus:bg-error/10 dark:data-[variant=destructive]:focus:bg-error/20 data-[variant=destructive]:focus:text-error data-[variant=destructive]:*:[svg]:!text-error [&_svg:not([class*=\'text-\'])]:text-muted-content relative flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 data-[inset]:pl-8 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4', props.class)
    }, slots)
  }
}

export const DropdownMenuGroup = {
  name: 'DropdownMenuGroup',
  setup(props: any, { slots }: any) {
    return () => h(RekaDropdownMenuGroup, { 'data-slot': 'dropdown-menu-group', ...props }, slots)
  }
}

export const DropdownMenuLabel = {
  name: 'DropdownMenuLabel',
  setup(props: any, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class", "inset")
    const forwardedProps = useForwardProps(delegatedProps)
    return () => h(RekaDropdownMenuLabel, {
      'data-slot': 'dropdown-menu-label',
      'data-inset': props.inset ? '' : undefined,
      ...forwardedProps,
      class: cn('px-2 py-1.5 text-sm font-medium data-[inset]:pl-8', props.class)
    }, slots)
  }
}

export const DropdownMenuSeparator = {
  name: 'DropdownMenuSeparator',
  setup(props: any) {
    const delegatedProps = reactiveOmit(props, "class")
    return () => h(RekaDropdownMenuSeparator, {
      'data-slot': 'dropdown-menu-separator',
      ...delegatedProps,
      class: cn('bg-border-color -mx-1 my-1 h-px', props.class)
    })
  }
}
</script>

<script setup lang="ts">
import type { DropdownMenuRootEmits, DropdownMenuRootProps } from "reka-ui"
import { DropdownMenuRoot, useForwardPropsEmits } from "reka-ui"

const props = defineProps<DropdownMenuRootProps>()
const emits = defineEmits<DropdownMenuRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <DropdownMenuRoot
    v-slot="slotProps"
    data-slot="dropdown-menu"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </DropdownMenuRoot>
</template>
