<script lang="ts">
import { h, type HTMLAttributes as VueHTMLAttributes, computed, ref, type Ref } from 'vue'
import { cn as utilCn } from "@/lib/utils"
import { SIDEBAR_WIDTH_MOBILE, useSidebar, provideSidebarContext, SIDEBAR_COOKIE_MAX_AGE, SIDEBAR_COOKIE_NAME, SIDEBAR_KEYBOARD_SHORTCUT, SIDEBAR_WIDTH, SIDEBAR_WIDTH_ICON } from "./sidebar-utils"
import { defaultDocument, useEventListener, useMediaQuery, useVModel } from "@vueuse/core"
import { TooltipProvider } from "reka-ui"
import { Button } from './index'
import { PanelLeftClose, PanelLeftOpen } from '@lucide/vue'

export const SidebarInset = (props: { class?: VueHTMLAttributes }, { slots }: any) => h('main', {
  class: utilCn('bg-primary-background relative flex w-full flex-1 flex-col md:peer-data-[variant=inset]:m-0 md:peer-data-[variant=inset]:ml-0 md:peer-data-[variant=inset]:rounded-none md:peer-data-[variant=inset]:peer-data-[state=collapsed]:ml-0', props.class)
}, slots)

export const SidebarTrigger = (props: { class?: VueHTMLAttributes }) => {
  const { isMobile, state, toggleSidebar } = useSidebar()
  return h(Button, {
    variant: 'ghost', size: 'icon', class: utilCn('h-7 w-7', props.class),
    onClick: toggleSidebar
  }, () => [
    h(isMobile.value || state.value === 'collapsed' ? PanelLeftOpen : PanelLeftClose),
    h('span', { class: 'sr-only' }, 'Toggle Sidebar')
  ])
}

export const SidebarProvider = {
  name: 'SidebarProvider',
  inheritAttrs: false,
  props: {
    defaultOpen: { type: Boolean, default: undefined },
    open: { type: Boolean, default: undefined },
    class: { type: String, default: '' }
  },
  emits: ['update:open'],
  setup(props: any, { slots, emit }: any) {
    const isMobile = useMediaQuery("(max-width: 768px)")
    const openMobile = ref(false)
    const open = useVModel(props, "open", emit, {
      defaultValue: props.defaultOpen ?? !defaultDocument?.cookie.includes(`${SIDEBAR_COOKIE_NAME}=false`),
      passive: (props.open === undefined) as false,
    }) as Ref<boolean>

    function setOpen(value: boolean) {
      open.value = value
      document.cookie = `${SIDEBAR_COOKIE_NAME}=${open.value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
    }

    function setOpenMobile(value: boolean) { openMobile.value = value }
    function toggleSidebar() { return isMobile.value ? setOpenMobile(!openMobile.value) : setOpen(!open.value) }

    useEventListener("keydown", (event: KeyboardEvent) => {
      if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
        event.preventDefault()
        toggleSidebar()
      }
    })

    const state = computed(() => open.value ? "expanded" : "collapsed")

    provideSidebarContext({ state, open, setOpen, isMobile, openMobile, setOpenMobile, toggleSidebar })

    return () => h(TooltipProvider, { delayDuration: 0 }, {
      default: () => h('div', {
        'data-slot': 'sidebar-wrapper',
        style: { '--sidebar-width': SIDEBAR_WIDTH, '--sidebar-width-icon': SIDEBAR_WIDTH_ICON },
        class: utilCn('group/sidebar-wrapper has-data-[variant=inset]:bg-primary-background flex min-h-svh w-full', props.class)
      }, slots)
    })
  }
}
</script>

<script setup lang="ts">
import { cn } from "@/lib/utils"
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from './index'
import { SIDEBAR_WIDTH_MOBILE, useSidebar } from "./sidebar-utils"

interface Props {
  side?: "left" | "right"
  variant?: "sidebar" | "floating" | "inset"
  collapsible?: "offcanvas" | "icon" | "none"
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  side: "left",
  variant: "sidebar",
  collapsible: "offcanvas",
})

const { isMobile: sidebarIsMobile, state: sidebarState, openMobile: sidebarOpenMobile, setOpenMobile: sidebarSetOpenMobile } = useSidebar()
</script>

<template>
  <div
    v-if="collapsible === 'none'"
    data-slot="sidebar"
    :class="cn('bg-component-background text-muted-content flex h-full w-(--sidebar-width) flex-col', props.class)"
    v-bind="$attrs"
  >
    <slot />
  </div>

  <Sheet v-else-if="sidebarIsMobile" :open="sidebarOpenMobile" v-bind="$attrs" @update:open="sidebarSetOpenMobile">
    <SheetContent
      data-sidebar="sidebar"
      data-slot="sidebar"
      data-mobile="true"
      :side="side"
      class="bg-component-background text-muted-content w-(--sidebar-width) p-0 [&>button]:hidden"
      :style="{ '--sidebar-width': SIDEBAR_WIDTH_MOBILE }"
    >
      <SheetHeader class="sr-only">
        <SheetTitle>Sidebar</SheetTitle>
        <SheetDescription>Mobile navigation</SheetDescription>
      </SheetHeader>
      <div class="flex h-full w-full flex-col">
        <slot />
      </div>
    </SheetContent>
  </Sheet>

  <div
    v-else
    class="group peer text-muted-content hidden md:block"
    data-slot="sidebar"
    :data-state="sidebarState"
    :data-collapsible="sidebarState === 'collapsed' ? collapsible : ''"
    :data-variant="variant"
    :data-side="side"
  >
    <div :class="cn(
      'relative w-(--sidebar-width) bg-transparent transition-[width] duration-200 ease-linear',
      'group-data-[collapsible=offcanvas]:w-0',
      variant === 'floating' || variant === 'inset'
        ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon))]'
        : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon)'
    )" />
    <div :class="cn(
      'fixed inset-y-0 z-10 hidden h-svh w-(--sidebar-width) transition-[left,right,width] duration-200 ease-linear md:flex',
      side === 'left' ? 'left-0 group-data-[collapsible=offcanvas]:left-[calc(var(--sidebar-width)*-1)]' : 'right-0 group-data-[collapsible=offcanvas]:right-[calc(var(--sidebar-width)*-1)]',
      variant === 'floating' || variant === 'inset' ? 'p-0 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon))]' : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon) group-data-[side=left]:border-r group-data-[side=right]:border-l',
      props.class
    )" v-bind="$attrs">
      <div class="bg-component-background flex h-full w-full flex-col group-data-[variant=floating]:rounded-none group-data-[variant=floating]:border-r group-data-[variant=floating]:shadow-none border-r border-border-color">
        <slot />
      </div>
    </div>
  </div>
</template>
