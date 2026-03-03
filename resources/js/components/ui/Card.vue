<script setup lang="ts">
import { h, type HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<script lang="ts">
import { h as vueH, type HTMLAttributes as VueHTMLAttributes } from "vue"
import { cn as utilCn } from "@/lib/utils"

export const CardHeader = {
  name: 'CardHeader',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => vueH('div', { 'data-slot': 'card-header', class: utilCn('@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6', props.class) }, slots)
  }
}

export const CardTitle = {
  name: 'CardTitle',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => vueH('h3', { 'data-slot': 'card-title', class: utilCn('leading-none font-semibold', props.class) }, slots)
  }
}

export const CardDescription = {
  name: 'CardDescription',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => vueH('p', { 'data-slot': 'card-description', class: utilCn('text-muted-content text-sm', props.class) }, slots)
  }
}

export const CardContent = {
  name: 'CardContent',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => vueH('div', { 'data-slot': 'card-content', class: utilCn('px-6', props.class) }, slots)
  }
}

export const CardFooter = {
  name: 'CardFooter',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => vueH('div', { 'data-slot': 'card-footer', class: utilCn('flex items-center px-6 [.border-t]:pt-6', props.class) }, slots)
  }
}

export const CardAction = {
  name: 'CardAction',
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => vueH('div', { 'data-slot': 'card-action', class: utilCn('col-start-2 row-span-2 row-start-1 self-start justify-self-end', props.class) }, slots)
  }
}
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-primary-background text-primary-content-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
