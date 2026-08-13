<script setup lang="ts">
import { ShieldAlert, Mail } from 'lucide-vue-next'

withDefaults(
    defineProps<{
        adminEmail?: string
        title?: string
        message?: string
    }>(),
    {
        adminEmail: 'admin@university.edu',
        title: 'No workspace access',
        message: "Your account doesn't have any active permissions yet. Contact your administrator to get access.",
    }
)
</script>

<template>
    <section id="no-access" class="min-h-screen flex items-center justify-center p-6 bg-bg">
        <div class="max-w-md w-full text-center bg-surface border border-border rounded-xl p-8 shadow-sm space-y-6">
            <div
                class="w-14 h-14 mx-auto rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-600">
                <ShieldAlert :size="28" />
            </div>

            <div class="space-y-2">
                <h2 class="font-bold text-lg text-text">{{ title }}</h2>
                <p class="text-sm text-text/60 leading-relaxed">{{ message }}</p>
            </div>

            <div class="pt-2 border-t border-border">
                <p class="text-xs font-semibold text-text/50 uppercase tracking-wider mb-2">Administrator Contact</p>
                <div
                    class="flex items-center justify-center gap-2 text-sm font-medium text-text bg-bg p-3 rounded-lg border border-border select-all">
                    <Mail :size="16" class="text-accent shrink-0" />
                    <span>{{ adminEmail }}</span>
                </div>
            </div>
        </div>
    </section>
</template>
