<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    FlaskConical, AlertTriangle, CheckCircle2, Clock,
    RefreshCw, Wifi, ChevronRight, Activity, BarChart3
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    queue: Array,
    testVolume: Object,
    recentReleases: Array,
    backlog: Array,
})

const categoryLabels = {
    hematology: 'Hematology',
    chemistry:  'Chemistry',
    urinalysis: 'Urinalysis',
    serology:   'Serology',
    stool:      'Stool',
    thyroid:    'Thyroid',
    other:      'Other',
}

const categoryColors = {
    hematology: '#DC2626',
    chemistry:  '#0369A1',
    urinalysis: '#D97706',
    serology:   '#059669',
    stool:      '#7C3AED',
    thyroid:    '#0EA5E9',
    other:      '#94A3B8',
}

const maxVolume = Math.max(...Object.values(props.testVolume ?? { _: 1 }))

const priorityDot = {
    urgent:   'bg-red-500',
    senior:   'bg-amber-500',
    pwd:      'bg-purple-500',
    pregnant: 'bg-pink-500',
    regular:  'bg-slate-300',
}

let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'queue', 'recentReleases'] })
    }, 10000)
})
onUnmounted(() => clearInterval(timer))

const refresh = () => router.reload({ only: ['stats', 'queue', 'recentReleases', 'testVolume'] })
</script>

<template>
    <AppLayout title="Laboratory Dashboard">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">Laboratory</h1>
                <p class="text-sm text-slate-400 mt-0.5">Result entry and release management</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                    <Wifi class="w-3 h-3" /> <span class="font-semibold">Live — 10s</span>
                </div>
                <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                    <RefreshCw class="w-4 h-4" />
                </button>
                <Link :href="route('laboratory.index')" class="flex items-center gap-2 text-sm text-[#1B4F9B] font-semibold border border-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                    Full View <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard label="In Queue" :value="stats.in_queue" :icon="Activity" color="#1B4F9B" />
            <StatCard label="Pending Entry" :value="stats.pending_entry" :icon="Clock" color="#D97706" :warning="stats.pending_entry > 0" />
            <StatCard label="Released Today" :value="stats.released_today" :icon="CheckCircle2" color="#059669" :success="stats.released_today > 0" />
            <StatCard label="Abnormal Flags" :value="stats.abnormal_today" :icon="AlertTriangle" color="#DC2626" :danger="stats.abnormal_today > 0" />
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Queue -->
            <div class="col-span-3 lg:col-span-2 space-y-5">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50 flex items-center gap-2">
                        <FlaskConical class="w-4 h-4 text-[#1B4F9B]" />
                        <h3 class="font-bold text-[#0F2044] flex-1">Today's Lab Queue</h3>
                        <span class="text-xs text-slate-400">{{ queue?.length ?? 0 }} patient(s)</span>
                    </div>

                    <div class="divide-y divide-slate-50">
                        <div
                            v-for="entry in queue" :key="entry.id"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors"
                        >
                            <!-- Priority + queue number -->
                            <div class="flex-shrink-0 text-center w-16">
                                <p class="text-lg font-black text-[#0F2044]">{{ entry.queue_number }}</p>
                                <QueueBadge :status="entry.status" size="xs" />
                            </div>

                            <!-- Patient -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot[entry.priority] ?? 'bg-slate-300'" />
                                    <p class="font-semibold text-[#0F2044]">{{ entry.patient_name }}</p>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 ml-4">
                                    <p class="text-xs text-slate-400 font-mono">{{ entry.patient_code }}</p>
                                    <span class="text-slate-300">·</span>
                                    <p class="text-xs text-slate-400">{{ entry.age_sex }}</p>
                                    <span v-if="entry.employer" class="text-slate-300">·</span>
                                    <p v-if="entry.employer" class="text-xs text-slate-400 truncate max-w-32">{{ entry.employer }}</p>
                                </div>
                            </div>

                            <!-- Services -->
                            <div class="flex-shrink-0 flex gap-1 flex-wrap max-w-32">
                                <span v-for="svc in entry.services?.slice(0, 4)" :key="svc" class="text-xs font-mono bg-[#D6E8F7] text-[#1B4F9B] px-1.5 py-0.5 rounded">{{ svc }}</span>
                                <span v-if="entry.services?.length > 4" class="text-xs text-slate-400">+{{ entry.services.length - 4 }}</span>
                            </div>

                            <!-- Visit type -->
                            <div class="flex-shrink-0">
                                <VisitTypeBadge :type="entry.visit_type" size="xs" />
                            </div>

                            <!-- Lab status + action -->
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <QueueBadge :status="entry.lab_status" size="xs" />
                                <Link
                                    v-if="entry.visit_id"
                                    :href="route('laboratory.enter', entry.visit_id)"
                                    class="text-xs font-semibold px-3 py-2 rounded-xl transition-colors"
                                    :class="entry.lab_status === 'released'
                                        ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        : 'bg-[#1B4F9B] text-white hover:bg-[#0F2044]'"
                                >
                                    {{ entry.lab_status === 'released' ? 'View' : entry.lab_status === 'processing' ? 'Continue' : 'Enter' }}
                                </Link>
                            </div>
                        </div>

                        <div v-if="!queue?.length" class="py-16 text-center">
                            <CheckCircle2 class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                            <p class="text-slate-400">No patients in lab queue today</p>
                        </div>
                    </div>
                </div>

                <!-- Backlog -->
                <div v-if="backlog?.length" class="bg-amber-50 border border-amber-100 rounded-2xl p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <AlertTriangle class="w-4 h-4 text-amber-600" />
                        <h3 class="font-bold text-amber-800">Pending from Previous Days</h3>
                    </div>
                    <div class="space-y-2">
                        <div v-for="r in backlog" :key="r.id" class="bg-white rounded-xl p-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ r.date }} · {{ r.request_number }}</p>
                            </div>
                            <VisitTypeBadge :type="r.visit_type" size="xs" />
                            <Link :href="route('laboratory.enter', r.visit_id)" class="text-[#1B4F9B] hover:text-[#0F2044]">
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- Test volume -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-[#0F2044]">Test Volume Today</h3>
                        <BarChart3 class="w-4 h-4 text-slate-300" />
                    </div>
                    <div class="space-y-3">
                        <div v-for="(count, cat) in testVolume" :key="cat">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-600">{{ categoryLabels[cat] ?? cat }}</span>
                                <span class="text-sm font-black text-[#0F2044]">{{ count }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{
                                        width: `${Math.max(4, (count / maxVolume) * 100)}%`,
                                        background: categoryColors[cat] ?? '#94A3B8'
                                    }"
                                />
                            </div>
                        </div>
                        <div v-if="!Object.keys(testVolume ?? {}).length" class="text-sm text-slate-400 text-center py-4">
                            No tests entered yet
                        </div>
                    </div>
                </div>

                <!-- Recent releases -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Recently Released</h3>
                    <div class="space-y-3">
                        <div v-for="r in recentReleases" :key="r.request_number" class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                                :class="r.has_abnormal ? 'bg-red-50' : 'bg-emerald-50'">
                                <component :is="r.has_abnormal ? AlertTriangle : CheckCircle2"
                                    class="w-3.5 h-3.5"
                                    :class="r.has_abnormal ? 'text-red-500' : 'text-emerald-500'" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ r.request_number }} · {{ r.released_at }}</p>
                            </div>
                            <span v-if="r.has_abnormal" class="text-xs text-red-500 font-bold flex-shrink-0">!</span>
                        </div>
                        <div v-if="!recentReleases?.length" class="text-sm text-slate-400 text-center py-4">
                            No releases today yet
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
