<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    FlaskConical, AlertTriangle, CheckCircle2, ChevronRight,
    Wifi, Clock, RefreshCw, Activity, TrendingUp, Beaker,
} from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    queue: Array,
    testVolume: Object,
    recentReleases: Array,
    backlog: Array,
})

const priorityStyle = {
    urgent:  { bg:'#FEF2F2', text:'#DC2626', label:'URGENT' },
    pregnant:{ bg:'#FDF2F8', text:'#DB2777', label:'PREGNANT' },
    pwd:     { bg:'#EFF6FF', text:'#1D4ED8', label:'PWD' },
    senior:  { bg:'#FFFBEB', text:'#D97706', label:'SENIOR' },
    regular: { bg:'#F8FAFC', text:'#475569', label:'REG' },
}
const visitLabel = {
    pre_employment:'Pre-Emp', annual_pe:'Annual PE',
    exit_pe:'Exit PE', opd:'OPD', follow_up:'Follow-up', lab_only:'Lab Only',
}
const labStatusStyle = {
    pending:    { bg:'#FFFBEB', text:'#D97706', label:'Pending' },
    processing: { bg:'#EFF6FF', text:'#1D4ED8', label:'Processing' },
    released:   { bg:'#F0FDF4', text:'#16A34A', label:'Released' },
}
const catColor = {
    hematology:'#3b82f6', urinalysis:'#8b5cf6', chemistry:'#f59e0b',
    stool:'#84cc16', serology:'#ec4899', other:'#94a3b8',
}

const totalVol = computed(() => Math.max(Object.values(props.testVolume ?? {}).reduce((s,v) => s + Number(v), 0), 1))
const activeQ  = computed(() => (props.queue ?? []).filter(q => ['waiting','calling','serving'].includes(q.status)))
const doneQ    = computed(() => (props.queue ?? []).filter(q => q.status === 'completed').length)
const releasePct = computed(() => {
    const total = (props.stats?.pending_entry ?? 0) + (props.stats?.released_today ?? 0)
    return total > 0 ? Math.round(((props.stats?.released_today ?? 0) / total) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let t = null
onMounted(() => { t = setInterval(refresh, 20000) })
onUnmounted(() => clearInterval(t))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats','queue','testVolume','recentReleases','backlog'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Laboratory Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Laboratory</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-slate-50 rounded-lg transition-colors">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <a :href="route('laboratory.index')" class="flex items-center gap-2 text-sm font-semibold border border-blue-600 text-blue-600 px-3 py-2 rounded-xl hover:bg-blue-600 hover:text-white transition-colors">
                <FlaskConical class="w-4 h-4"/> Lab Requests
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#1D4ED8,#3b82f6)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">In Queue</span>
                <Activity class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.in_queue }}</div>
            <div class="text-xs opacity-70 mt-1">{{ doneQ }} completed today</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.pending_entry ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.pending_entry ?? 0) > 0 ? 'background:linear-gradient(135deg,#D97706,#F59E0B)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.pending_entry ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Pending Entry</span>
                <Clock class="w-5 h-5" :class="(stats.pending_entry ?? 0) > 0 ? 'opacity-60' : 'text-amber-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.pending_entry ?? 0) > 0 ? '' : 'text-amber-600'">{{ stats.pending_entry }}</div>
            <div class="text-xs mt-1" :class="(stats.pending_entry ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">awaiting result entry</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Released Today</span>
                <CheckCircle2 class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.released_today }}</div>
            <div class="text-xs opacity-70 mt-1">results released</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.abnormal_today ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.abnormal_today ?? 0) > 0 ? 'background:linear-gradient(135deg,#DC2626,#ef4444)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.abnormal_today ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Abnormal</span>
                <AlertTriangle class="w-5 h-5" :class="(stats.abnormal_today ?? 0) > 0 ? 'opacity-60' : 'text-red-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.abnormal_today ?? 0) > 0 ? '' : 'text-red-600'">{{ stats.abnormal_today }}</div>
            <div class="text-xs mt-1" :class="(stats.abnormal_today ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">abnormal results today</div>
        </div>
    </div>

    <!-- Release progress -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-blue-500"/>
                <span class="text-sm font-bold text-slate-700">Results Release Progress</span>
            </div>
            <span class="text-sm font-black text-blue-600">{{ stats.released_today }} released / {{ (stats.pending_entry ?? 0) + (stats.released_today ?? 0) }} total</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full bg-blue-500 transition-all duration-500"
                :style="{ width: releasePct + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ releasePct }}% released</span>
            <span>{{ stats.pending_entry }} still pending</span>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">
        <!-- Queue -->
        <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-blue-500"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Lab Queue</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold">{{ activeQ.length }} active</span>
                </div>
                <span class="text-xs text-slate-400">{{ doneQ }} done today</span>
            </div>
            <div class="overflow-y-auto" style="max-height:440px;">
                <div v-if="!activeQ.length" class="py-16 text-center">
                    <CheckCircle2 class="w-10 h-10 text-slate-200 mx-auto mb-2"/>
                    <p class="text-slate-400 text-sm">No active patients in lab queue</p>
                </div>
                <div v-else class="divide-y divide-slate-50">
                    <div v-for="q in activeQ" :key="q.id"
                        class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/60 transition-colors">
                        <div class="w-14 text-center flex-shrink-0">
                            <p class="text-xl font-black font-mono text-[#0F2044]">{{ q.queue_number }}</p>
                            <p :class="['text-xs font-bold',
                                q.status==='serving'  ? 'text-emerald-600' :
                                q.status==='calling'  ? 'text-blue-600 animate-pulse' : 'text-slate-400']">
                                {{ q.status }}
                            </p>
                        </div>
                        <div class="w-px h-10 bg-slate-100 flex-shrink-0"/>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ q.patient_name }}</p>
                                <span v-if="q.priority !== 'regular'" class="text-xs font-bold px-1.5 py-0.5 rounded-full flex-shrink-0"
                                    :style="{background:priorityStyle[q.priority]?.bg, color:priorityStyle[q.priority]?.text}">
                                    {{ priorityStyle[q.priority]?.label }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">{{ q.patient_code }} &bull; {{ q.age_sex }}</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <span v-for="s in q.services?.slice(0,4)" :key="s"
                                    class="text-xs px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">{{ s }}</span>
                                <span v-if="q.services?.length > 4" class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-semibold">+{{ q.services.length - 4 }}</span>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                            :style="{background:labStatusStyle[q.lab_status]?.bg??'#f1f5f9', color:labStatusStyle[q.lab_status]?.text??'#64748b'}">
                            {{ labStatusStyle[q.lab_status]?.label ?? q.lab_status }}
                        </span>
                        <a v-if="q.visit_id" :href="route('laboratory.enter', q.visit_id)"
                            class="flex-shrink-0 text-xs font-bold px-3 py-1.5 rounded-xl text-white flex items-center gap-1 transition-colors hover:opacity-90"
                            :class="q.lab_status === 'released' ? 'bg-slate-400' : ''"
                            :style="q.lab_status !== 'released' ? 'background:#3b82f6' : ''">
                            {{ q.lab_status === 'released' ? 'View' : q.lab_status === 'processing' ? 'Continue' : 'Enter' }}
                            <ChevronRight class="w-3 h-3"/>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel -->
        <div class="space-y-4">

            <!-- Test Volume by Category -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Beaker class="w-4 h-4 text-slate-400"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Test Volume by Category</h3>
                </div>
                <div class="space-y-2.5">
                    <div v-for="(count, cat) in testVolume" :key="cat">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-semibold capitalize text-slate-600">{{ cat }}</span>
                            <span class="font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                :style="{width:(count/totalVol*100)+'%', background:catColor[cat]??'#94a3b8'}"/>
                        </div>
                    </div>
                    <p v-if="!Object.keys(testVolume??{}).length" class="text-xs text-slate-400 text-center py-2">No results yet</p>
                </div>
            </div>

            <!-- Recent Releases -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recent Releases</h3>
                </div>
                <div class="divide-y divide-slate-50">
                    <p v-if="!recentReleases?.length" class="py-6 text-center text-xs text-slate-400">None released today yet</p>
                    <div v-for="r in recentReleases" :key="r.request_number" class="px-4 py-2.5">
                        <div class="flex justify-between items-start gap-2">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ r.patient_name }}</p>
                            <span v-if="r.has_abnormal" class="flex items-center gap-0.5 text-xs text-red-600 font-bold flex-shrink-0">
                                <AlertTriangle class="w-3 h-3"/> Abnormal
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-mono">{{ r.request_number }} &bull; {{ r.released_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Backlog -->
            <div v-if="backlog?.length" class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-amber-100 flex items-center gap-2" style="background:#FFFBEB">
                    <Clock class="w-4 h-4 text-amber-600"/>
                    <h3 class="text-xs font-bold text-amber-700 uppercase tracking-widest">Backlog</h3>
                    <span class="ml-auto text-xs bg-amber-200 text-amber-800 font-bold px-2 py-0.5 rounded-full">{{ backlog.length }}</span>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-for="b in backlog" :key="b.id" class="px-4 py-2.5 flex items-center justify-between hover:bg-amber-50/50 transition-colors">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ b.patient_name }}</p>
                            <p class="text-xs text-amber-600 font-semibold">{{ b.date }}</p>
                        </div>
                        <a :href="route('laboratory.enter', b.visit_id)" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex-shrink-0 ml-2">
                            Enter →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</AppLayout>
</template>
