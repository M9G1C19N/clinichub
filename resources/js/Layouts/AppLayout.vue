<script setup>
import AppSidebar from '@/Components/Layout/AppSidebar.vue'
import AppTopBar from '@/Components/Layout/AppTopBar.vue'
import FlashToast from '@/Components/Layout/FlashToast.vue'
import ProgressBar from '@/Components/Layout/ProgressBar.vue'

defineProps({
    title: {
        type: String,
        default: 'ClinicHub',
    },
})
</script>

<template>
    <div class="flex h-screen bg-slate-100 overflow-hidden">

        <!-- Global Progress Bar — always present -->
        <ProgressBar />

        <!-- Sidebar -->
        <div class="relative">
            <AppSidebar />
        </div>

        <!-- Main -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <AppTopBar />
            <main class="flex-1 overflow-y-auto p-6">
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>

        <FlashToast />
    </div>
</template>
