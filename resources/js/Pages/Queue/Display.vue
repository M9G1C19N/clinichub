<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    FlaskConical, ScanLine, TestTube,
    Stethoscope, HeartPulse,
} from 'lucide-vue-next'
import { CLINIC_LOGO } from '@/config/clinic.js'

const props = defineProps({
    board:          Object,
    todayTotal:     Number,
    todayCompleted: Number,
})

let refreshTimer = null
const now = ref(new Date())
let clockTimer = null

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['board', 'todayTotal', 'todayCompleted'] })
    }, 10000)
    clockTimer = setInterval(() => { now.value = new Date() }, 1000)
})

onUnmounted(() => {
    clearInterval(refreshTimer)
    clearInterval(clockTimer)
})

const timeString = () => now.value.toLocaleTimeString('en-PH', {
    hour: '2-digit', minute: '2-digit', second: '2-digit'
})
const dateString = () => now.value.toLocaleDateString('en-PH', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
})

const roomConfig = {
    laboratory:     { label: 'Laboratory',         color: '#3B82F6', icon: FlaskConical },
    xray_utz:       { label: 'X-Ray & Ultrasound', color: '#8B5CF6', icon: ScanLine    },
    drug_test:      { label: 'Drug Test',           color: '#F43F5E', icon: TestTube    },
    nurse_station:  { label: 'Nurse Station',       color: '#10B981', icon: HeartPulse  },
    interview_room: { label: 'Interview Room',      color: '#0EA5E9', icon: Stethoscope },
}

const roomOrder = ['laboratory', 'xray_utz', 'drug_test', 'nurse_station', 'interview_room']
</script>

<template>
    <div class="h-screen overflow-hidden flex flex-col" style="background:#0F2044; font-family: system-ui, sans-serif;">

        <!-- ── Header ──────────────────────────────── -->
        <div class="flex items-center justify-between px-6 py-3 flex-shrink-0"
            style="border-bottom: 1px solid rgba(255,255,255,0.1)">
            <div class="flex items-center gap-3">
                <img :src="CLINIC_LOGO" class="w-10 h-10 object-contain bg-white rounded-xl p-1"/>
                <div>
                    <h1 class="text-lg font-black text-white leading-tight">St. Peter Diagnostics & Laboratory</h1>
                    <p class="text-white/40 text-xs">Queue Display Board</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-3xl font-black text-white font-mono leading-none">{{ timeString() }}</p>
                <p class="text-white/40 text-xs mt-1">{{ dateString() }}</p>
            </div>
        </div>

        <!-- ── 5-Column Room Grid ────────────────── -->
        <div class="flex-1 grid grid-cols-5 gap-3 p-3 min-h-0">
            <div v-for="room in roomOrder" :key="room"
                class="rounded-2xl flex flex-col min-h-0 overflow-hidden"
                :style="{
                    background: 'rgba(255,255,255,0.04)',
                    border: `1px solid ${roomConfig[room]?.color}40`,
                }">

                <!-- Room label bar -->
                <div class="flex items-center gap-2 px-4 py-3 flex-shrink-0"
                    :style="{
                        background: roomConfig[room]?.color + '18',
                        borderBottom: `1px solid ${roomConfig[room]?.color}30`
                    }">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                        :style="{ background: roomConfig[room]?.color + '25' }">
                        <component :is="roomConfig[room]?.icon" class="w-4 h-4"
                            :style="{ color: roomConfig[room]?.color }"/>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black text-white truncate leading-tight">
                            {{ roomConfig[room]?.label }}
                        </p>
                        <p class="text-xs font-medium" :style="{ color: roomConfig[room]?.color + 'bb' }">
                            {{ board[room]?.wait_count ?? 0 }} waiting
                        </p>
                    </div>
                </div>

                <!-- NOW CALLING — most prominent, top section -->
                <div class="flex-shrink-0 flex flex-col items-center justify-center py-5 px-3 text-center"
                    :style="{
                        background: board[room]?.calling
                            ? roomConfig[room]?.color + '18'
                            : 'transparent',
                        borderBottom: `1px solid rgba(255,255,255,0.07)`,
                        minHeight: '40%',
                    }">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] mb-2"
                        :style="{ color: board[room]?.calling ? roomConfig[room]?.color : 'rgba(255,255,255,0.2)' }">
                        Now Calling
                    </p>

                    <template v-if="board[room]?.calling">
                        <!-- Pulsing badge when actively calling -->
                        <div class="animate-pulse mb-1">
                            <p class="font-black leading-none"
                                :style="{ fontSize: '3.8rem', color: roomConfig[room]?.color, lineHeight: 1 }">
                                {{ board[room].calling.queue_number }}
                            </p>
                        </div>
                        <p class="text-white font-bold mt-2 leading-snug"
                            style="font-size: 1.05rem; max-width: 100%; word-break: break-word;">
                            {{ board[room].calling.patient_name }}
                        </p>
                        <!-- Flashing "PLEASE PROCEED" indicator -->
                        <div class="mt-3 px-3 py-1 rounded-full text-xs font-black animate-pulse"
                            :style="{
                                background: roomConfig[room]?.color + '30',
                                color: roomConfig[room]?.color,
                                border: `1px solid ${roomConfig[room]?.color}60`
                            }">
                            PLEASE PROCEED
                        </div>
                    </template>
                    <template v-else>
                        <p class="font-black" style="font-size:3rem; color:rgba(255,255,255,0.06)">—</p>
                        <p style="color:rgba(255,255,255,0.15); font-size:0.7rem;" class="mt-1">No active call</p>
                    </template>
                </div>

                <!-- NOW SERVING — lower section -->
                <div class="flex-1 flex flex-col items-center justify-center px-3 py-4 text-center min-h-0">
                    <p class="text-xs font-bold uppercase tracking-widest mb-2"
                        style="color: rgba(255,255,255,0.25)">
                        Now Serving
                    </p>
                    <template v-if="board[room]?.serving">
                        <p class="font-black leading-none"
                            :style="{ fontSize: '2.6rem', color: 'rgba(255,255,255,0.7)' }">
                            {{ board[room].serving.queue_number }}
                        </p>
                        <p class="text-white/50 font-semibold mt-1 text-sm leading-snug">
                            {{ board[room].serving.patient_name }}
                        </p>
                    </template>
                    <template v-else>
                        <p class="font-black" style="font-size:2rem; color:rgba(255,255,255,0.06)">—</p>
                        <p style="color:rgba(255,255,255,0.1); font-size:0.7rem;" class="mt-1">None</p>
                    </template>
                </div>

            </div>
        </div>

        <!-- ── Footer ──────────────────────────────── -->
        <div class="flex items-center justify-between px-6 py-2 flex-shrink-0"
            style="border-top: 1px solid rgba(255,255,255,0.08)">
            <p class="text-white/30 text-xs">
                Total Today: <span class="text-white font-bold">{{ todayTotal }}</span>
            </p>
            <p class="text-white/30 text-xs">
                Completed: <span class="text-emerald-400 font-bold">{{ todayCompleted }}</span>
            </p>
            <p class="text-white/20 text-[11px]">Auto-refreshes every 10s</p>
        </div>

    </div>
</template>
