<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { ChevronDown, User, LogOut, Settings } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

defineProps<{
    userName?: string
    userAvatar?: string
}>()

const router = useRouter()
const authStore = useAuthStore()
const isOpen = ref(false)
const el = ref<HTMLElement | null>(null)

function initials(name?: string) {
    if (!name) return '?'
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

async function handleLogout() {
    isOpen.value = false
    await authStore.logout()
    router.push('/login')
}

function handleClickOutside(e: MouseEvent) {
    if (el.value && !el.value.contains(e.target as Node)) isOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
    <div ref="el" class="relative">
        <button type="button" @click="isOpen = !isOpen"
            class="flex items-center gap-3 hover:bg-bg rounded-lg px-2 py-1.5 transition-colors">
            <div class="text-sm text-right leading-tight hidden sm:block">
                <div class="font-semibold text-text">{{ userName || 'User' }}</div>
            </div>

            <img v-if="userAvatar" :src="userAvatar" class="w-9 h-9 rounded-full object-cover border border-border" />
            <div v-else
                class="w-9 h-9 rounded-full bg-accent/10 flex items-center justify-center text-accent text-xs font-bold">
                {{ initials(userName) }}
            </div>

            <ChevronDown class="w-4 h-4 text-text/40 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
        </button>

        <div v-if="isOpen"
            class="absolute right-0 mt-2 w-52 bg-surface border border-border rounded-lg shadow-lg py-1 z-20">
            <div class="px-3 py-2.5 border-b border-border">
                <div class="text-sm font-semibold text-text">{{ userName || 'User' }}</div>
            </div>
            <router-link to="/profile"
                class="flex items-center gap-2 px-3 py-2.5 text-sm text-text hover:bg-bg transition-colors">
                <User class="w-4 h-4 text-text/50" /> Profile
            </router-link>
            <router-link to="/settings"
                class="flex items-center gap-2 px-3 py-2.5 text-sm text-text hover:bg-bg transition-colors">
                <Settings class="w-4 h-4 text-text/50" /> Settings
            </router-link>
            <button @click="handleLogout"
                class="w-full flex items-center gap-2 px-3 py-2.5 text-sm text-error hover:bg-error/10 transition-colors">
                <LogOut class="w-4 h-4" /> Log out
            </button>
        </div>
    </div>
</template>
