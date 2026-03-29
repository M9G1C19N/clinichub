<script setup>
import { ref } from 'vue'
import AppSidebar from '@/Components/Layout/AppSidebar.vue'
import AppTopBar from '@/Components/Layout/AppTopBar.vue'
import FlashToast from '@/Components/Layout/FlashToast.vue'

defineProps({
    title: {
        type: String,
        default: 'ClinicHub',
    },
})
</script>

<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden">
        <!-- Sidebar -->
        <div class="relative">
            <AppSidebar />
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Top Bar -->
            <AppTopBar />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Page Header Slot -->
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>

                <!-- Main slot -->
                <slot />
            </main>
        </div>

        <!-- Flash notifications -->
        <FlashToast />
    </div>
</template>
