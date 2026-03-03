<script setup lang="ts">
import type { TooltipRootEmits, TooltipRootProps } from "reka-ui"
import { TooltipRoot, useForwardPropsEmits } from "reka-ui"

const props = defineProps<TooltipRootProps>()
const emits = defineEmits<TooltipRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<script lang="ts">
import { h } from "vue"
import { 
  TooltipArrow, TooltipContent as RekaTooltipContent, TooltipPortal, 
  TooltipProvider as RekaTooltipProvider, TooltipTrigger as RekaTooltipTrigger,
  useForwardPropsEmits as useRekaForwardPropsEmits
} from "reka-ui"
import { cn } from "@/lib/utils"
import { reactiveOmit } from "@vueuse/core"

export const TooltipProvider = {
  name: 'TooltipProvider',
  setup(props: any, { slots }: any) {
    const delayDuration = props.delayDuration ?? 0
    return () => h(RekaTooltipProvider, { delayDuration, ...props }, slots)
  }
}

export const TooltipTrigger = {
  name: 'TooltipTrigger',
  setup(props: any, { slots }: any) {
    return () => h(RekaTooltipTrigger, { 'data-slot': 'tooltip-trigger', ...props }, slots)
  }
}

export const TooltipContent = {
  name: 'TooltipContent',
  inheritAttrs: false,
  setup(props: any, { slots, emit, attrs }: any) {
    const sideOffset = props.sideOffset ?? 4
    const delegatedProps = reactiveOmit(props, "class")
    const forwarded = useRekaForwardPropsEmits(delegatedProps, emit)

    return () => h(TooltipPortal, null, {
      default: () => h(RekaTooltipContent, {
        'data-slot': 'tooltip-content',
        sideOffset,
        ...attrs, ...forwarded,
        class: cn('bg-foreground text-primary-background animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 w-fit rounded-md px-3 py-1.5 text-xs text-balance', props.class)
      }, {
        default: () => [
          slots.default?.(),
          h(TooltipArrow, { class: 'bg-foreground fill-foreground z-50 size-2.5 translate-y-[calc(-50%_-_2px)] rotate-45 rounded-[2px]' })
        ]
      })
    })
  }
}
</script>

<template>
  <TooltipRoot
    v-slot="slotProps"
    data-slot="tooltip"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </TooltipRoot>
</template>
