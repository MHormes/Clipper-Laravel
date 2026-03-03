<script lang="ts">
import { h, type HTMLAttributes as VueHTMLAttributes } from "vue"
import { cn as utilCn } from "@/lib/utils"
import { cva, type VariantProps } from "class-variance-authority"

export const alertVariants = cva(
  "bg-primary-background text-primary-content relative grid w-full grid-cols-[(--spacing(4))_1fr] items-start gap-y-0.5 gap-x-3 rounded-lg border p-4 shadow-sm has-[>svg]:grid-cols-[calc(var(--spacing(4))+(--spacing(1)))_1fr] [&>svg]:size-4 [&>svg]:translate-y-0.5",
  {
    variants: {
      variant: {
        default: "bg-primary-background text-primary-content",
        destructive:
          "border-error/50 text-error dark:border-error [&>svg]:text-error",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type AlertVariants = VariantProps<typeof alertVariants>

export const AlertTitle = {
  name: 'AlertTitle',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h('div', { 'data-slot': 'alert-title', class: utilCn('col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight', props.class) }, slots)
  }
}

export const AlertDescription = {
  name: 'AlertDescription',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h('div', { 'data-slot': 'alert-description', class: utilCn('text-muted-content col-start-2 grid justify-items-start gap-1 text-sm [&_p]:leading-relaxed', props.class) }, slots)
  }
}
</script>

<script setup lang="ts">
import { type HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  variant?: AlertVariants["variant"]
}>()
</script>

<template>
  <div
    data-slot="alert"
    :class="cn(alertVariants({ variant }), props.class)"
    role="alert"
  >
    <slot />
  </div>
</template>
