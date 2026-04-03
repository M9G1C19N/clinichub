<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Scan, CheckCircle2, Clock, AlertTriangle, Eye,
    RefreshCw, Wifi, ChevronRight, Activity, TrendingUp,
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
const priorityDot = {
    urgent:'bg-red-500', senior:'bg-amber-500',
    pwd:'bg-purple-500', pregnant:'bg-pink-500', regular:'bg-slate-300',
}

const maxExam = computed(() => Math.max(...Object.values(props.examBreakdown ?? { _: 1 })))
const activeQ = computed(() => (props.queue ?? []).filter(q => ['waiting','calling','serving'].includes(q.status)))
const doneQ   = computed(() => (props.queue ?? []).filter(q => q.status === 'completed').length)
const releasePct = computed(() => {
    const total = (props.stats?.pending ?? 0) + (props.stats?.released_today ?? 0)
    return total > 0 ? Math.round(((props.stats?.released_today ?? 0) / total) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let timer = null
onMounted(() => { timer = setInterval(refresh, 10000) })
onUnmounted(() => clearInterval(timer))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats', 'queue', 'recentReleases'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="X-Ray & Ultrasound Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">X-Ray & Ultrasound</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live — 10s
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <Link :href="route('xray.index')" class="flex items-center gap-2 text-sm font-semibold border border-[#1B4F9B] text-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                <Scan class="w-4 h-4"/> Full View
            </Link>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">In Queue</span>
                <Activity class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.in_queue }}</div>
            <div class="text-xs opacity-70 mt-1">{{ doneQ }} completed today</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.pending ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.pending ?? 0) > 0 ? 'background:linear-gradient(135deg,#D97706,#F59E0B)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.pending ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Reports Pending</span>
                <Clock class="w-5 h-5" :class="(stats.pending ?? 0) > 0 ? 'opacity-60' : 'text-amber-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.pending ?? 0) > 0 ? '' : 'text-amber-600'">{{ stats.pending }}</div>
            <div class="text-xs mt-1" :class="(stats.pending ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">awaiting report entry</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Released Today</span>
                <CheckCircle2 class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.released_today }}</div>
            <div class="text-xs opacity-70 mt-1">imaging reports released</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.provisional_pending ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.provisional_pending ?? 0) > 0 ? 'background:linear-gradient(135deg,#7C3AED,#A855F7)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.provisional_pending ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Provisional</span>
                <Eye class="w-5 h-5" :class="(stats.provisional_pending ?? 0) > 0 ? 'opacity-60' : 'text-purple-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.provisional_pending ?? 0) > 0 ? '' : 'text-purple-600'">{{ stats.provisional_pending }}</div>
            <div class="text-xs mt-1" :class="(stats.provisional_pending ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">awaiting official read</div>
        </div>
    </div>

    <!-- Release Progress -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-[#1B4F9B]"/>
                <span class="text-sm font-bold text-slate-700">Report Release Progress</span>
            </div>
            <span class="text-sm font-black text-[#1B4F9B]">{{ stats.released_today }} released / {{ (stats.pending ?? 0) + (stats.released_today ?? 0) }} total</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                style="background:#1B4F9B"
                :style="{ width: releasePct + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ releasePct }}% released</span>
            <span>{{ stats.pending }} pending</span>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">
        <!-- Queue -->
        <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Scan class="w-4 h-4 text-[#1B4F9B]"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Today's X-Ray / UTZ Queue</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold">{{ activeQ.length }} active</span>
                </div>
                <span class="text-xs text-slate-400">{{ queue?.length ?? 0 }} patient(s)</span>
            </div>
            <div class="overflow-y-auto" style="max-height:440px;">
                <div v-if="!queue?.length" class="py-16 text-center">
                    <CheckCircle2 class="w-10 h-10 text-slate-200 mx-auto mb-2"/>
                    <p class="text-slate-400 text-sm">No patients in X-Ray/UTZ queue today</p>
                </div>
                <div v-else class="divide-y divide-slate-50">
                    <div v-for="entry in queue" :key="entry.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/60 transition-colors">
                        <!-- Queue number -->
                        <div class="flex-shrink-0 text-center w-14">
                            <p class="text-xl font-black font-mono text-[#0F2044]">{{ entry.queue_number }}</p>
                            <p :class="['text-xs font-bold',
                                entry.status==='serving'  ? 'text-emerald-600' :
                                entry.status==='calling'  ? 'text-blue-600 animate-pulse' :
                                entry.status==='completed'? 'text-slate-400' : 'text-slate-400']">
                                {{ entry.status }}
                            </p>
                        </div>
                        <div class="w-px h-10 bg-slate-100 flex-shrink-0"/>
                        <!-- Patient info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot[entry.priority] ?? 'bg-slate-300'"/>
                                <p class="font-semibold text-[#0F2044] truncate">{{ entry.patient_name }}</p>
                                <span v-if="entry.is_provisional" class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-semibold flex-shrink-0">Provisional</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5 ml-4 font-mono">{{ entry.patient_code }} &bull; {{ entry.age_sex }}</p>
                        </div>
                        <!-- Exam type -->
                        <span class="text-xs font-semibold text-slate-600 bg-blue-50 px-2 py-1 rounded-lg flex-shrink-0">
                            {{ entry.imaging_type }}
                        </span>
                        <!-- Status + action -->
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="entry.imaging_status === 'released' ? 'bg-emerald-100 text-emerald-700' :
                                        entry.imaging_status === 'processing' ? 'bg-blue-100 text-blue-700' :
                                        'bg-amber-100 text-amber-700'">
                                {{ entry.imaging_status }}
                            </span>
                            <Link v-if="entry.visit_id" :href="route('xray.enter', entry.visit_id)"
                                class="text-xs font-semibold px-3 py-1.5 rounded-xl transition-colors"
                                :class="entry.imaging_status === 'released'
                                    ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                    : 'text-white hover:opacity-90'"
                                :style="entry.imaging_status !== 'released' ? 'background:#1B4F9B' : ''">
                                {{ entry.imaging_status === 'released' ? 'View' : entry.imaging_status === 'processing' ? 'Continue' : 'Enter' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel -->
        <div class="space-y-4">
            <!-- Exam type breakdown -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Activity class="w-4 h-4 text-slate-400"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Exam Types Today</h3>
                </div>
                <div class="space-y-2.5">
                    <div v-for="(count, type) in examBreakdown" :key="type">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-slate-600 font-semibold">{{ examLabels[type] ?? type }}</span>
                            <span class="text-xs font-black text-[#0F2044]">{{ count }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                :style="{ width: `${Math.max(4, (count / maxExam) * 100)}%`, background: examColors[type] ?? '#94A3B8' }"/>
                        </div>
                    </div>
                    <p v-if="!Object.keys(examBreakdown ?? {}).length" class="text-xs text-slate-400 text-center py-2">No exams today yet</p>
                </div>
            </div>

            <!-- Recent Releases -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recently Released</h3>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-if="!recentReleases?.length" class="py-6 text-center text-xs text-slate-400">No releases today yet</div>
                    <div v-for="r in recentReleases" :key="r.request_number" class="px-4 py-2.5 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                            :class="r.is_provisional ? 'bg-purple-50' : 'bg-emerald-50'">
                            <component :is="r.is_provisional ? Eye : CheckCircle2"
                                class="w-3.5 h-3.5"
                                :class="r.is_provisional ? 'text-purple-500' : 'text-emerald-500'"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-700 truncate">{{ r.patient_name }}</p>
                            <p class="text-xs text-slate-400">{{ r.imaging_type }}</p>
                            <p class="text-xs text-slate-400">{{ r.released_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</AppLayout>
</template>
