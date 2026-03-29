<script setup>
import { router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'

const loading = ref(false)
const progress = ref(0)
const visible = ref(false)

let timer = null
let incrementTimer = null

function startProgress() {
    loading.value  = true
    visible.value  = true
    progress.value = 0

    // Fake increment to show activity
    incrementTimer = setInterval(() => {
        if (progress.value < 85) {
            // Slow down as it gets higher
            const increment = progress.value < 30 ? 8
                : progress.value < 60 ? 4
                : progress.value < 80 ? 1.5
                : 0.5
            progress.value = Math.min(progress.value + increment, 85)
        }
    }, 100)
}

function finishProgress() {
    clearInterval(incrementTimer)
    progress.value = 100

    timer = setTimeout(() => {
        loading.value  = false
        visible.value  = false
        progress.value = 0
    }, 400)
}

function failProgress() {
    clearInterval(incrementTimer)
    progress.value = 100
    timer = setTimeout(() => {
        loading.value  = false
        visible.value  = false
        progress.value = 0
    }, 500)
}

// Inertia event listeners
const removeStart    = router.on('start',    () => startProgress())
const removeFinish   = router.on('finish',   () => finishProgress())
const removeError    = router.on('error',    () => failProgress())
const removeNavigate = router.on('navigate', () => finishProgress())

onUnmounted(() => {
    removeStart()
    removeFinish()
    removeError()
    removeNavigate()
    clearInterval(incrementTimer)
    clearTimeout(timer)
})
</script>

<template>
    <!-- Top progress bar -->
    <Transition name="bar">
        <div v-if="visible" class="fixed top-0 left-0 right-0 z-[9999] h-0.5 pointer-events-none">
            <div
                class="h-full transition-all duration-200 ease-out"
                :style="{
                    width: progress + '%',
                    background: 'linear-gradient(90deg, #1B4F9B, #0EA5E9, #1B4F9B)',
                    backgroundSize: '200% 100%',
                    animation: loading ? 'shimmer 1.5s infinite' : 'none',
                    boxShadow: '0 0 8px rgba(14, 165, 233, 0.6)',
                }"
            />
        </div>
    </Transition>

    <!-- Center spinner overlay for form submissions -->
    <Transition name="overlay">
        <div v-if="loading"
            class="fixed inset-0 z-[9998] flex items-center justify-center pointer-events-none">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-slate-200 px-6 py-4 flex items-center gap-3 pointer-events-none">
                <!-- Animated spinner -->
                <div class="relative w-8 h-8 flex-shrink-0">
                    <svg class="animate-spin w-8 h-8" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="16" r="13" stroke="#E2E8F0" stroke-width="3"/>
                        <path d="M16 3 A13 13 0 0 1 29 16" stroke="#1B4F9B" stroke-width="3"
                            stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-700">Please wait...</p>
                    <p class="text-xs text-slate-400">Processing your request</p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.bar-enter-active,
.bar-leave-active {
    transition: opacity 0.2s ease;
}
.bar-enter-from,
.bar-leave-to {
    opacity: 0;
}

.overlay-enter-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.overlay-leave-active {
    transition: opacity 0.3s ease, transform 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
</style>
