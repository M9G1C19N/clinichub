<script setup>
import { onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    Scan, CheckCircle2, Clock, AlertTriangle,
    RefreshCw, Wifi, ChevronRight, Activity, Eye
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    queue: Array,
    examBreakdown: Object,
    recentReleases: Array,
})

const examLabels = {
    chest_xray_pa:      'Chest X-Ray PA',
    kub:                'KUB',
    ultrasound_abdomen: 'UTZ Abdomen',
    ultrasound_ob:      'UTZ OB',
    ultrasound_pelvis:  'UTZ Pelvis',
    ecg:                'ECG',
    other:              'Other',
}

const examColors = {
    chest_xray_pa:      '#1B4F9B',
    kub:                '#059669',
    ultrasound_abdomen: '#0369A1',
    ultrasound_ob:      '#7C3AED',
    ultrasound_pelvis:  '#D97706',
    ecg:                '#DC2626',
    other:              '#94A3B8',
}

const maxExam = Math.max(...Object.values(props.examBreakdown ?? { _: 1 }))

const priorityDot = {
    urgent: 'bg-red-500', senior: 'bg-amber-500',
    pwd: 'bg-purple-500', pregnant: 'bg-pink-500', regular: 'bg-slate-300',
}

let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'queue', 'recentReleases'] })
    }, 10000)
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
    <AppLayout title="X-Ray & Ultrasound Dashboard">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">X-Ray & Ultrasound</h1>
                <p class="text-sm text-slate-400 mt-0.5">Imaging reports and findings</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                    <Wifi class="w-3 h-3" /> <span class="font-semibold">Live — 10s</span>
                </div>
                <button @click="() => router.reload()" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg">
                    <RefreshCw class="w-4 h-4" />
                </button>
                <Link :href="route('xray.index')" class="flex items-center gap-2 text-sm text-[#1B4F9B] font-semibold border border-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                    Full View <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard label="In Queue" :value="stats.in_queue" :icon="Activity" color="#1B4F9B" />
            <StatCard label="Reports Pending" :value="stats.pending" :icon="Clock" color="#D97706" :warning="stats.pending > 0" />
            <StatCard label="Released Today" :value="stats.released_today" :icon="CheckCircle2" color="#059669" :success="stats.released_today > 0" />
            <StatCard label="Provisional Pending" :value="stats.provisional_pending" :icon="Eye" color="#7C3AED" :warning="stats.provisional_pending > 0" sub="Awaiting official read" />
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Queue panel -->
            <div class="col-span-3 lg:col-span-2 space-y-5">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50 flex items-center gap-2">
                        <Scan class="w-4 h-4 text-[#1B4F9B]" />
                        <h3 class="font-bold text-[#0F2044] flex-1">Today's X-Ray/UTZ Queue</h3>
                        <span class="text-xs text-slate-400">{{ queue?.length ?? 0 }} patient(s)</span>
                    </div>

                    <div class="divide-y divide-slate-50">
                        <div
                            v-for="entry in queue" :key="entry.id"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors"
                        >
                            <!-- Queue number -->
                            <div class="flex-shrink-0 text-center w-16">
                                <p class="text-lg font-black text-[#0F2044]">{{ entry.queue_number }}</p>
                                <QueueBadge :status="entry.status" size="xs" />
                            </div>

                            <!-- Patient -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot[entry.priority] ?? 'bg-slate-300'" />
                                    <p class="font-semibold text-[#0F2044]">{{ entry.patient_name }}</p>
                                    <span v-if="entry.is_provisional" class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-semibold">Provisional</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 ml-4">
                                    <p class="text-xs text-slate-400 font-mono">{{ entry.patient_code }}</p>
                                    <span class="text-slate-300">·</span>
                                    <p class="text-xs text-slate-400">{{ entry.age_sex }}</p>
                                </div>
                            </div>

                            <!-- Exam type -->
                            <div class="flex-shrink-0">
                                <p class="text-xs font-semibold text-slate-600 bg-[#D6E8F7] px-2 py-1 rounded-lg">
                                    {{ entry.imaging_type }}
                                </p>
                            </div>

                            <!-- Visit type -->
                            <div class="flex-shrink-0">
                                <VisitTypeBadge :type="entry.visit_type" size="xs" />
                            </div>

                            <!-- Status + action -->
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <QueueBadge :status="entry.imaging_status" size="xs" />
                                <Link
                                    v-if="entry.visit_id"
                                    :href="route('xray.enter', entry.visit_id)"
                                    class="text-xs font-semibold px-3 py-2 rounded-xl transition-colors"
                                    :class="entry.imaging_status === 'released'
                                        ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        : 'bg-[#1B4F9B] text-white hover:bg-[#0F2044]'"
                                >
                                    {{ entry.imaging_status === 'released' ? 'View' : entry.imaging_status === 'processing' ? 'Continue' : 'Enter' }}
                                </Link>
                            </div>
                        </div>

                        <div v-if="!queue?.length" class="py-16 text-center">
                            <CheckCircle2 class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                            <p class="text-slate-400">No patients in X-Ray/UTZ queue today</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- Exam breakdown -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Exam Types Today</h3>
                    <div class="space-y-3">
                        <div v-for="(count, type) in examBreakdown" :key="type">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-600">{{ examLabels[type] ?? type }}</span>
                                <span class="text-sm font-black text-[#0F2044]">{{ count }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :style="{ width: `${Math.max(4, (count / maxExam) * 100)}%`, background: examColors[type] ?? '#94A3B8' }"
                                />
                            </div>
                        </div>
                        <div v-if="!Object.keys(examBreakdown ?? {}).length" class="text-sm text-slate-400 text-center py-4">
                            No exams today yet
                        </div>
                    </div>
                </div>

                <!-- Recent releases -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Recently Released</h3>
                    <div class="space-y-3">
                        <div v-for="r in recentReleases" :key="r.request_number" class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                                :class="r.is_provisional ? 'bg-purple-50' : 'bg-emerald-50'">
                                <component :is="r.is_provisional ? Eye : CheckCircle2"
                                    class="w-3.5 h-3.5"
                                    :class="r.is_provisional ? 'text-purple-500' : 'text-emerald-500'" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ r.imaging_type }}</p>
                                <p class="text-xs text-slate-400">{{ r.released_at }}</p>
                            </div>
                        </div>
                        <div v-if="!recentReleases?.length" class="text-sm text-slate-400 text-center py-4">No releases today yet</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
