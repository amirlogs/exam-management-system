<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import {
    RefreshCw,
    Search,
    SlidersHorizontal,
    Columns3,
    Maximize2,
    Minimize2,
    X,
    Archive,
    Building,
} from 'lucide-vue-next'

withDefaults(
    defineProps<{
        title: string
        description?: string
        searchPlaceholder?: string

        showSearch?: boolean
        showFilter?: boolean
        showRefresh?: boolean
        showColumns?: boolean
        showFullscreen?: boolean

        refreshing?: boolean

        // Resource tabs
        showTabs?: boolean
        activeTab?: 'active' | 'archived'
        activeCount?: number
        archivedCount?: number
    }>(),
    {
        showSearch: false,
        showFilter: false,
        showRefresh: false,
        showColumns: false,
        showFullscreen: false,
        refreshing: false,

        showTabs: false,
        activeTab: 'active',
        activeCount: 0,
        archivedCount: 0,
    },
)

const emit = defineEmits<{
    'update:search': [string]
    refresh: []
    columns: []
    fullscreen: []
    'change-tab': ['active' | 'archived']
}>()

const filterOpen = ref(false)
const isFullscreen = ref(false)

/**
 * Toggle browser fullscreen for the entire application.
 */
const toggleFullscreen = async () => {
    try {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen()
        } else {
            await document.exitFullscreen()
        }
    } catch (error) {
        console.error('Fullscreen error:', error)
    }
}

/**
 * Keep the button icon/text in sync if the user exits fullscreen
 * using ESC or the browser's fullscreen controls.
 */
const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement
}

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange)
})

onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
})
</script>

<template>
    <div class="space-y-5">

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold font-display text-text">
                    {{ title }}
                </h1>

                <p
                    v-if="description"
                    class="mt-1.5 text-sm text-text/60"
                >
                    {{ description }}
                </p>
            </div>

            <!-- Page actions -->
            <div class="flex items-center gap-2 shrink-0">
                <slot name="actions" />
            </div>
        </div>


        <!-- Resource navigation + controls -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

            <!-- LEFT: Active / Archived + Search -->
            <div class="flex min-w-0 items-center gap-5">

                <!-- Active / Archived -->
                <div
                    v-if="showTabs"
                    class="shrink-0 border-b border-border sm:border-0"
                >
                    <div class="flex gap-6">

                        <!-- Active -->
                        <button
                            type="button"
                            class="relative flex h-10 items-center gap-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === 'active'
                                    ? 'text-accent'
                                    : 'text-text/60 hover:text-text'
                            "
                            @click="emit('change-tab', 'active')"
                        >
                            <Building class="h-4 w-4" />

                            Active

                            <span
                                class="rounded-full bg-accent/10 px-2 py-0.5 text-xs tabular-nums"
                            >
                                {{ activeCount }}
                            </span>

                            <span
                                v-if="activeTab === 'active'"
                                class="absolute inset-x-0 bottom-0 h-0.5 bg-accent"
                            />
                        </button>


                        <!-- Archived -->
                        <button
                            type="button"
                            class="relative flex h-10 items-center gap-2 text-sm font-medium transition-colors"
                            :class="
                                activeTab === 'archived'
                                    ? 'text-accent'
                                    : 'text-text/60 hover:text-text'
                            "
                            @click="emit('change-tab', 'archived')"
                        >
                            <Archive class="h-4 w-4" />

                            Archived

                            <span
                                class="rounded-full bg-text/5 px-2 py-0.5 text-xs tabular-nums"
                            >
                                {{ archivedCount }}
                            </span>

                            <span
                                v-if="activeTab === 'archived'"
                                class="absolute inset-x-0 bottom-0 h-0.5 bg-accent"
                            />
                        </button>

                    </div>
                </div>


                <!-- Search -->
                <div
                    v-if="showSearch"
                    class="relative w-full sm:w-72"
                >
                    <Search
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/35"
                    />

                    <input
                        type="text"
                        :placeholder="searchPlaceholder || 'Search…'"
                        class="w-full rounded-lg border border-border bg-surface py-2.5 pl-9 pr-4 text-sm text-text outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/10"
                        @input="
                            emit(
                                'update:search',
                                ($event.target as HTMLInputElement).value
                            )
                        "
                    />
                </div>

            </div>


            <!-- RIGHT: Filter + Refresh + Columns + Fullscreen -->
            <div class="ml-auto flex shrink-0 items-center gap-2 pr-2">

                <!-- Filter -->
                <button
                    v-if="showFilter"
                    type="button"
                    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border px-3.5 text-sm font-medium transition-colors"
                    :class="
                        filterOpen
                            ? 'border-accent bg-accent/5 text-accent'
                            : 'border-border text-text/70 hover:border-accent/40 hover:text-text'
                    "
                    @click="filterOpen = !filterOpen"
                >
                    <X
                        v-if="filterOpen"
                        class="h-4 w-4"
                    />

                    <SlidersHorizontal
                        v-else
                        class="h-4 w-4"
                    />

                    {{ filterOpen ? 'Hide Filter' : 'Filter' }}
                </button>


                <!-- Refresh -->
                <button
                    v-if="showRefresh"
                    type="button"
                    :disabled="refreshing"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-50"
                    title="Refresh"
                    @click="emit('refresh')"
                >
                    <RefreshCw
                        class="h-4 w-4"
                        :class="refreshing ? 'animate-spin' : ''"
                    />
                </button>


                <!-- Columns -->
                <button
                    v-if="showColumns"
                    type="button"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent"
                    title="Show/hide columns"
                    @click="emit('columns')"
                >
                    <Columns3 class="h-4 w-4" />
                </button>


                <!-- Fullscreen -->
                <button
                    v-if="showFullscreen"
                    type="button"
                    class="mr-2 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent"
                    :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
                    @click="toggleFullscreen"
                >
                    <Minimize2
                        v-if="isFullscreen"
                        class="h-4 w-4"
                    />

                    <Maximize2
                        v-else
                        class="h-4 w-4"
                    />
                </button>

            </div>
        </div>


        <!-- Filters -->
        <div
            v-if="showFilter && filterOpen"
            class="rounded-lg border border-border bg-bg/50 p-4"
        >
            <slot name="filters">
                <p class="text-sm text-text/40">
                    No filters configured for this page yet.
                </p>
            </slot>
        </div>

    </div>
</template>