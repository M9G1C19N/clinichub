<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    FlaskConical, CheckCircle2, Clock,
    Activity, AlertTriangle, Search,
    Calendar, FileText, ChevronRight,
    Loader2, SlidersHorizontal, X,
    PackageCheck, PackageOpen, Users,
    Tv2, SquareCheckBig, Minus,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE as visitTypeBadge } from '@/config/visitTypes.js'

const props = defineProps({
    todayQueue:     Array,
    pending:        Object,
    history:        Object,
    readyForPickup: Object,
    filters:        Object,
    summary:        Object,
})

const activeTab = ref('today')

// ── Server-side filter state ────────────────────────────
const search       = ref(props.filters?.search ?? '')
const date         = ref(props.filters?.date ?? '')
const statusFilter = ref(props.filters?.status ?? 'all')

// Pickup tab filter state
const pickupSearch      = ref(props.filters?.pickup_search ?? '')
const pickupClaimFilter = ref(props.filters?.pickup_claim ?? 'all')
const pickupDateFilter  = ref(props.filters?.pickup_date ?? '')

// Today's queue client-side filters
const queueSearch        = ref('')
const queueStatusFilter  = ref('all')
const resultStatusFilter = ref('all')

// Bulk selection state (pickup tab)
const selectedIds   = ref(new Set())
const selectAll     = ref(false)
const bulkLoading   = ref(false)

const filteredQueue = computed(() => {
    let list = props.todayQueue ?? []
    const s = queueSearch.value.toLowerCase().trim()
    if (s) {
        list = list.filter(item =>
            item.patient.full_name?.toLowerCase().includes(s) ||
            item.patient.patient_code?.toLowerCase().includes(s)
        )
    }
    if (queueStatusFilter.value !== 'all') {
        list = list.filter(item => item.status === queueStatusFilter.value)
    }
    if (resultStatusFilter.value !== 'all') {
        list = list.filter(item => (item.visit?.lab_status ?? 'none') === resultStatusFilter.value)
    }
    return list
})

// Bulk selection helpers
const pickupRows = computed(() => props.readyForPickup?.data ?? [])

function toggleAll() {
    if (selectedIds.value.size === pickupRows.value.length) {
        selectedIds.value = new Set()
        selectAll.value = false
    } else {
        selectedIds.value = new Set(pickupRows.value.map(r => r.id))
        selectAll.value = true
    }
}
function toggleRow(id) {
    const s = new Set(selectedIds.value)
    if (s.has(id)) { s.delete(id) } else { s.add(id) }
    selectedIds.value = s
    selectAll.value = s.size === pickupRows.value.length
}
function clearSelection() {
    selectedIds.value = new Set()
    selectAll.value = false
}

function bulkAction(action) {
    if (!selectedIds.value.size) return
    const label = action === 'claimed' ? 'claimed' : 'marked as unclaimed'
    if (!confirm(`Mark ${selectedIds.value.size} result(s) as ${label}?`)) return
    bulkLoading.value = true
    router.post(route('laboratory.bulk-claim'), {
        ids: [...selectedIds.value],
        action,
    }, {
        preserveScroll: true,
        onSuccess: () => { clearSelection() },
        onFinish: () => { bulkLoading.value = false },
    })
}

// Auto-refresh
let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({
            only: ['todayQueue','pending','history','readyForPickup','summary'],
            preserveScroll: true,
        })
    }, 15000)
})
onUnmounted(() => clearInterval(refreshTimer))

function applyFilters() {
    router.get(route('laboratory.index'), {
        search:        search.value,
        date:          date.value,
        status:        statusFilter.value,
        pickup_search: pickupSearch.value,
        pickup_claim:  pickupClaimFilter.value,
        pickup_date:   pickupDateFilter.value,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    date.value   = ''
    statusFilter.value = 'all'
    router.get(route('laboratory.index'), {
        pickup_search: pickupSearch.value,
        pickup_claim:  pickupClaimFilter.value,
        pickup_date:   pickupDateFilter.value,
    }, { preserveState: true, replace: true })
}

function clearPickupFilters() {
    pickupSearch.value      = ''
    pickupClaimFilter.value = 'all'
    pickupDateFilter.value  = ''
    router.get(route('laboratory.index'), {
        search: search.value,
        date:   date.value,
        status: statusFilter.value,
    }, { preserveState: true, replace: true })
}

function applyPickupFilters() {
    router.get(route('laboratory.index'), {
        search:        search.value,
        date:          date.value,
        status:        statusFilter.value,
        pickup_search: pickupSearch.value,
        pickup_claim:  pickupClaimFilter.value,
        pickup_date:   pickupDateFilter.value,
    }, { preserveState: true, replace: true })
}

// Single claim / unclaim
const claimingId   = ref(null)
const unclaimingId = ref(null)

function markClaimed(id) {
    claimingId.value = id
    router.post(route('laboratory.claim', id), {}, {
        preserveScroll: true,
        onFinish: () => { claimingId.value = null },
    })
}
function markUnclaimed(id) {
    if (!confirm('Mark this patient as unclaimed?')) return
    unclaimingId.value = id
    router.post(route('laboratory.unclaim', id), {}, {
        preserveScroll: true,
        onFinish: () => { unclaimingId.value = null },
    })
}

// Lab result status
const labStatusConfig = {
    none:       { dot: '#94a3b8', label: 'No Results',  bg: '#f1f5f9', color: '#64748b' },
    pending:    { dot: '#f59e0b', label: 'Pending',     bg: '#fffbeb', color: '#b45309' },
    collecting: { dot: '#0d9488', label: 'Collected',   bg: '#f0fdfa', color: '#0f766e' },
    processing: { dot: '#3b82f6', label: 'In Progress', bg: '#eff6ff', color: '#1d4ed8' },
    released:   { dot: '#10b981', label: 'Released',    bg: '#f0fdf4', color: '#047857' },
}

// Queue status
const queueStatusConfig = {
    waiting:   { label: 'Waiting',   bg: '#fffbeb', color: '#92400e', dot: '#f59e0b' },
    calling:   { label: 'Calling',   bg: '#eff6ff', color: '#1e40af', dot: '#3b82f6' },
    serving:   { label: 'Serving',   bg: '#ecfdf5', color: '#065f46', dot: '#10b981' },
    completed: { label: 'Completed', bg: '#f8fafc', color: '#475569', dot: '#94a3b8' },
}

function getLabStatus(visit) {
    if (!visit) return 'none'
    return visit.lab_status ?? (visit.has_results ? 'processing' : 'none')
}

const collectingId = ref(null)
function collectSample(visitId) {
    collectingId.value = visitId
    router.post(route('laboratory.collect', visitId), {}, {
        preserveScroll: true,
        onFinish: () => { collectingId.value = null },
    })
}

// Pickup tab summary counts
const pickupWaitingCount   = computed(() =>
    (props.readyForPickup?.data ?? []).filter(r => r.claim_status === 'waiting').length
)
const pickupUnclaimedCount = computed(() =>
    (props.readyForPickup?.data ?? []).filter(r => r.claim_status === 'unclaimed').length
)
</script>

<template>
    <AppLayout title="Laboratory">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <FlaskConical class="w-5 h-5 text-blue-600"/>
                        Laboratory
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">Results entry and management</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Live indicator -->
                    <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"/>
                        </span>
                        Live · 15s
                    </div>
                    <!-- TV Display link -->
                    <a :href="route('laboratory.results-display')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7">
                            <Tv2 class="w-3.5 h-3.5 text-blue-500"/>
                            Results Board
                        </Button>
                    </a>
                    <a :href="route('queue.room', 'laboratory')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7">
                            <Activity class="w-3.5 h-3.5"/>
                            Room Screen
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <!-- ── TAB NAV ────────────────────────────────────── -->
        <div class="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit flex-wrap">
            <button v-for="tab in [
                { key: 'today',   label: 'Today\'s Queue',   count: summary.today,         icon: 'queue'   },
                { key: 'pending', label: 'Pending Release',  count: summary.pending,        icon: 'pending' },
                { key: 'pickup',  label: 'Ready for Pickup', count: (summary.waiting_pickup + summary.unclaimed), icon: 'pickup'  },
                { key: 'history', label: 'History',          count: history.total,          icon: 'history' },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                    activeTab === tab.key
                        ? 'bg-white shadow-sm text-slate-800'
                        : 'text-slate-500 hover:text-slate-700'
                ]">
                <FlaskConical v-if="tab.key === 'today'"   class="w-3.5 h-3.5"/>
                <Clock        v-if="tab.key === 'pending'" class="w-3.5 h-3.5"/>
                <PackageCheck v-if="tab.key === 'pickup'"  class="w-3.5 h-3.5"/>
                <FileText     v-if="tab.key === 'history'" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span :class="[
                    'px-1.5 py-0.5 rounded-md text-xs font-black min-w-[20px] text-center',
                    activeTab === tab.key
                        ? (tab.key === 'pickup' ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white')
                        : 'bg-slate-200 text-slate-600'
                ]">{{ tab.count }}</span>
                <!-- Urgency dot for unclaimed -->
                <span v-if="tab.key === 'pickup' && summary.unclaimed > 0 && activeTab !== 'pickup'"
                    class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"/>
            </button>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 1: TODAY'S QUEUE                           -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'today'">
            <!-- Filter bar -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <span class="text-xs text-slate-400">
                        <strong class="text-slate-700 font-bold">{{ filteredQueue.length }}</strong> / {{ todayQueue.length }} shown
                    </span>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="queueSearch" placeholder="Search patient name or code..."
                            class="pl-9 h-8 text-xs"/>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Queue</span>
                        <button v-for="s in ['all','waiting','calling','serving','completed']" :key="s"
                            @click="queueStatusFilter = s"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="queueStatusFilter === s
                                ? 'background-color:#3b82f6;color:white;border-color:#3b82f6;box-shadow:0 1px 4px rgba(59,130,246,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Result</span>
                        <button v-for="s in ['all','none','collecting','processing','released']" :key="s"
                            @click="resultStatusFilter = s"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="resultStatusFilter === s
                                ? 'background-color:#3b82f6;color:white;border-color:#3b82f6;box-shadow:0 1px 4px rgba(59,130,246,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s === 'all' ? 'All' : s === 'none' ? 'No Results' : s === 'collecting' ? 'Collected' : s === 'processing' ? 'In Progress' : 'Released' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!todayQueue.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <FlaskConical class="w-6 h-6 text-blue-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No patients routed to laboratory today</p>
            </div>

            <div v-else-if="!filteredQueue.length"
                class="bg-white rounded-xl border border-slate-200 py-12 text-center">
                <p class="text-sm font-semibold text-slate-400">No patients match current filters</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">Queue</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Services Ordered</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Queue Status</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Result Status</th>
                            <th class="px-4 py-2.5 w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in filteredQueue" :key="item.id"
                            class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black font-mono text-sm border-2"
                                    :style="{
                                        background: item.status === 'serving' ? '#ecfdf5' : item.status === 'calling' ? '#eff6ff' : '#f8fafc',
                                        color: item.status === 'serving' ? '#065f46' : item.status === 'calling' ? '#1e40af' : '#475569',
                                        borderColor: item.status === 'serving' ? '#6ee7b7' : item.status === 'calling' ? '#93c5fd' : '#e2e8f0',
                                    }">
                                    {{ item.queue_number }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                        style="background-color:#1B4F9B">
                                        {{ item.patient.full_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 leading-tight">{{ item.patient.full_name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ item.patient.patient_code }} · {{ item.patient.age_sex }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1.5 ml-9">
                                    <span v-if="item.visit"
                                        class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background: visitTypeBadge[item.visit.visit_type]?.bg, color: visitTypeBadge[item.visit.visit_type]?.color }">
                                        {{ visitTypeBadge[item.visit.visit_type]?.label }}
                                    </span>
                                    <span v-if="item.visit?.employer_company" class="text-xs text-slate-400">
                                        · {{ item.visit.employer_company }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="svc in (item.visit?.services ?? [])" :key="svc.code"
                                        class="text-xs font-mono font-bold px-1.5 py-0.5 rounded border"
                                        style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                                        {{ svc.code }}
                                    </span>
                                    <span v-if="!item.visit?.services?.length" class="text-xs text-slate-300">—</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5" :style="{ color: queueStatusConfig[item.status]?.color }">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ background: queueStatusConfig[item.status]?.dot }"/>
                                    <span class="text-xs font-semibold">{{ queueStatusConfig[item.status]?.label ?? item.status }}</span>
                                </div>
                                <div v-if="item.status === 'completed' && !item.visit?.is_released" class="flex items-center gap-1 mt-1">
                                    <AlertTriangle class="w-3 h-3 text-amber-500"/>
                                    <span class="text-xs text-amber-600">Awaiting results</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ background: labStatusConfig[getLabStatus(item.visit)]?.dot }"/>
                                    <span class="text-xs font-semibold" :style="{ color: labStatusConfig[getLabStatus(item.visit)]?.color }">
                                        {{ labStatusConfig[getLabStatus(item.visit)]?.label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <button
                                        v-if="item.visit?.id && ['calling','serving'].includes(item.status) && !item.visit?.is_collected"
                                        :disabled="collectingId === item.visit.id"
                                        class="inline-flex items-center gap-1.5 text-xs h-7 px-2.5 rounded-md font-semibold text-white transition-all disabled:opacity-60"
                                        style="background-color:#0d9488;"
                                        @click="collectSample(item.visit.id)">
                                        <FlaskConical class="w-3 h-3"/>
                                        {{ collectingId === item.visit.id ? 'Saving...' : 'Collect Sample' }}
                                    </button>
                                    <Link v-if="item.visit?.id" :href="route('laboratory.enter', item.visit.id)">
                                        <Button size="sm"
                                            class="text-xs h-7 gap-1.5 text-white opacity-80 group-hover:opacity-100 transition-opacity"
                                            :style="{
                                                backgroundColor:
                                                    item.visit?.is_released  ? '#10b981' :
                                                    item.visit?.is_collected ? '#3b82f6' :
                                                    item.visit?.has_results  ? '#f59e0b' : '#94a3b8'
                                            }">
                                            {{ item.visit?.is_released  ? 'View'         :
                                               item.visit?.is_collected ? 'Enter Results' :
                                               item.visit?.has_results  ? 'Update'        : 'Enter' }}
                                            <ChevronRight class="w-3 h-3"/>
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 2: PENDING RELEASE                         -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'pending'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="search || date || statusFilter !== 'all'"
                        @click="clearFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="search" placeholder="Search patient name or code..."
                                class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="date" type="date"
                                class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                                @change="applyFilters"/>
                        </div>
                        <Button size="sm" class="h-8 text-xs gap-1.5" style="background-color:#3b82f6; color:white;" @click="applyFilters">
                            <Search class="w-3.5 h-3.5"/> Search
                        </Button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Status</span>
                        <button v-for="s in [
                            { value: 'all',        label: 'All'         },
                            { value: 'pending',    label: 'Pending'     },
                            { value: 'collecting', label: 'Collected'   },
                            { value: 'processing', label: 'In Progress' },
                        ]" :key="s.value"
                            @click="statusFilter = s.value; applyFilters()"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="statusFilter === s.value
                                ? 'background-color:#3b82f6;color:white;border-color:#3b82f6;box-shadow:0 1px 4px rgba(59,130,246,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!pending.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No pending lab results</p>
                <p class="text-xs text-slate-400 mt-1">All results have been released</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Status</th>
                            <th class="px-4 py-2.5 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in pending.data" :key="r.id" class="hover:bg-amber-50/40 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background: visitTypeBadge[r.visit_type]?.bg, color: visitTypeBadge[r.visit_type]?.color }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded" style="background:#eff6ff; color:#1d4ed8;">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ r.visit_date }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <Loader2 v-if="r.status === 'processing'" class="w-3 h-3 text-blue-500 animate-spin"/>
                                        <CheckCircle2 v-else-if="r.status === 'collecting'" class="w-3 h-3 text-teal-500"/>
                                        <Clock v-else class="w-3 h-3 text-amber-500"/>
                                        <span class="text-xs font-semibold"
                                            :class="{
                                                'text-blue-600':  r.status === 'processing',
                                                'text-teal-600':  r.status === 'collecting',
                                                'text-amber-600': r.status === 'pending',
                                            }">
                                            {{ r.status === 'processing' ? 'In Progress' : r.status === 'collecting' ? 'Collected' : 'Pending' }}
                                        </span>
                                    </div>
                                    <span v-if="r.collected_at && r.status === 'collecting'"
                                        class="text-xs text-slate-400 ml-4.5">at {{ r.collected_at }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('laboratory.enter', r.visit_id)">
                                    <Button size="sm" class="text-xs h-7 gap-1 text-white opacity-60 group-hover:opacity-100 transition-opacity" style="background-color:#3b82f6;">
                                        {{ r.status === 'processing' ? 'Continue' : r.status === 'collecting' ? 'Enter Results' : 'Enter' }}
                                        <ChevronRight class="w-3 h-3"/>
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ pending.from }}–{{ pending.to }} of {{ pending.total }} records</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in pending.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#1B4F9B' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 3: READY FOR PICKUP                        -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'pickup'">

            <!-- Summary mini cards -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <PackageCheck class="w-5 h-5 text-emerald-600"/>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-700">{{ summary.waiting_pickup }}</p>
                        <p class="text-xs font-semibold text-emerald-600">Waiting for Pickup</p>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <AlertTriangle class="w-5 h-5 text-amber-600"/>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-700">{{ summary.unclaimed }}</p>
                        <p class="text-xs font-semibold text-amber-600">Unclaimed Results</p>
                    </div>
                </div>
            </div>

            <!-- Filter bar -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="pickupSearch || pickupDateFilter || pickupClaimFilter !== 'all'"
                        @click="clearPickupFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="relative flex-1 min-w-[200px] max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="pickupSearch" placeholder="Search patient name or code..."
                                class="pl-9 h-8 text-xs" @keyup.enter="applyPickupFilters"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="pickupDateFilter" type="date"
                                class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                                @change="applyPickupFilters"/>
                        </div>
                        <Button size="sm" class="h-8 text-xs gap-1.5" style="background-color:#10b981; color:white;" @click="applyPickupFilters">
                            <Search class="w-3.5 h-3.5"/> Search
                        </Button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Status</span>
                        <button v-for="s in [
                            { value: 'all',       label: 'All',             activeStyle: 'background-color:#10b981;color:white;border-color:#10b981;' },
                            { value: 'waiting',   label: 'Waiting',         activeStyle: 'background-color:#059669;color:white;border-color:#059669;' },
                            { value: 'unclaimed', label: 'Unclaimed',       activeStyle: 'background-color:#d97706;color:white;border-color:#d97706;' },
                        ]" :key="s.value"
                            @click="pickupClaimFilter = s.value; applyPickupFilters()"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="pickupClaimFilter === s.value
                                ? s.activeStyle + 'box-shadow:0 1px 4px rgba(0,0,0,0.15);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!readyForPickup?.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <PackageOpen class="w-12 h-12 text-emerald-300 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No results awaiting pickup</p>
                <p class="text-xs text-slate-400 mt-1">All released results have been claimed</p>
            </div>

            <!-- Table + bulk bar -->
            <div v-else class="relative">
                <!-- Bulk action bar (slides up when selection active) -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-to-class="opacity-0 translate-y-2">
                    <div v-if="selectedIds.size > 0"
                        class="sticky top-2 z-10 flex items-center gap-3 mb-3 px-4 py-3 rounded-xl border shadow-lg"
                        style="background:#0F2044; border-color:#1e3a6e;">
                        <div class="flex items-center gap-2 flex-1">
                            <SquareCheckBig class="w-4 h-4 text-blue-400"/>
                            <span class="text-sm font-bold text-white">{{ selectedIds.size }} selected</span>
                        </div>
                        <button @click="clearSelection"
                            class="flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-white transition-colors px-2 py-1 rounded">
                            <X class="w-3 h-3"/> Deselect all
                        </button>
                        <button
                            :disabled="bulkLoading"
                            @click="bulkAction('claimed')"
                            class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg transition-all disabled:opacity-50"
                            style="background:#10b981; color:white;">
                            <PackageCheck class="w-3.5 h-3.5"/>
                            {{ bulkLoading ? 'Processing...' : `Mark ${selectedIds.size} as Claimed` }}
                        </button>
                        <button
                            :disabled="bulkLoading"
                            @click="bulkAction('unclaimed')"
                            class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg transition-all disabled:opacity-50"
                            style="background:#d97706; color:white;">
                            <AlertTriangle class="w-3.5 h-3.5"/>
                            Mark as Unclaimed
                        </button>
                    </div>
                </Transition>

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/80">
                                <!-- Select all -->
                                <th class="px-4 py-2.5 w-10">
                                    <button @click="toggleAll"
                                        class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all"
                                        :style="selectedIds.size > 0 && selectedIds.size === pickupRows.length
                                            ? 'background:#3b82f6; border-color:#3b82f6;'
                                            : selectedIds.size > 0
                                                ? 'background:#dbeafe; border-color:#3b82f6;'
                                                : 'background:white; border-color:#cbd5e1;'">
                                        <Minus v-if="selectedIds.size > 0 && selectedIds.size < pickupRows.length"
                                            class="w-2.5 h-2.5 text-blue-600"/>
                                        <svg v-else-if="selectedIds.size === pickupRows.length && pickupRows.length > 0"
                                            class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 12 12">
                                            <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-44">Released At</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Status</th>
                                <th class="px-4 py-2.5 w-52 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="r in readyForPickup.data" :key="r.id"
                                class="transition-colors group"
                                :class="selectedIds.has(r.id) ? 'bg-blue-50' : (r.claim_status === 'unclaimed' ? 'hover:bg-amber-50/40' : 'hover:bg-emerald-50/30')">

                                <!-- Checkbox -->
                                <td class="px-4 py-3">
                                    <button @click="toggleRow(r.id)"
                                        class="w-4 h-4 rounded border-2 flex items-center justify-center transition-all"
                                        :style="selectedIds.has(r.id)
                                            ? 'background:#3b82f6; border-color:#3b82f6;'
                                            : 'background:white; border-color:#cbd5e1;'">
                                        <svg v-if="selectedIds.has(r.id)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 12 12">
                                            <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </td>

                                <!-- Patient -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs flex-shrink-0 text-white"
                                            :style="{ backgroundColor: r.claim_status === 'unclaimed' ? '#d97706' : '#059669' }">
                                            {{ r.patient_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800 leading-tight">{{ r.patient_name }}</p>
                                            <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1 ml-10">
                                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                            :style="{ background: visitTypeBadge[r.visit_type]?.bg, color: visitTypeBadge[r.visit_type]?.color }">
                                            {{ visitTypeBadge[r.visit_type]?.label }}
                                        </span>
                                        <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                        <!-- Abnormal flag -->
                                        <span v-if="r.has_abnormal"
                                            class="flex items-center gap-1 text-xs font-bold px-1.5 py-0.5 rounded"
                                            style="background:#fef2f2; color:#dc2626;">
                                            <AlertTriangle class="w-3 h-3"/>
                                            {{ r.abnormal_count }} abnormal
                                        </span>
                                    </div>
                                </td>

                                <!-- Request No. -->
                                <td class="px-4 py-3">
                                    <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                        :style="r.claim_status === 'unclaimed'
                                            ? 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;'
                                            : 'background:#f0fdf4; color:#047857; border:1px solid #bbf7d0;'">
                                        {{ r.request_number }}
                                    </span>
                                </td>

                                <!-- Released At -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-emerald-600">
                                        <CheckCircle2 class="w-3.5 h-3.5"/>
                                        <span class="text-xs font-medium">{{ r.released_at }}</span>
                                    </div>
                                </td>

                                <!-- Claim Status -->
                                <td class="px-4 py-3">
                                    <span v-if="r.claim_status === 'waiting'"
                                        class="inline-flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full"
                                        style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"/>
                                        Waiting
                                    </span>
                                    <span v-else-if="r.claim_status === 'unclaimed'"
                                        class="inline-flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full"
                                        style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                        <AlertTriangle class="w-3 h-3"/>
                                        Unclaimed
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5 opacity-70 group-hover:opacity-100 transition-opacity">
                                        <!-- View results -->
                                        <Link :href="route('laboratory.enter', r.visit_id)">
                                            <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                                <FlaskConical class="w-3 h-3"/> View
                                            </Button>
                                        </Link>
                                        <!-- Print -->
                                        <a :href="route('laboratory.print', r.visit_id)" target="_blank">
                                            <Button variant="outline" size="sm" class="text-xs h-7">Print</Button>
                                        </a>
                                        <!-- Mark Claimed -->
                                        <button v-if="r.claim_status !== 'claimed'"
                                            :disabled="claimingId === r.id"
                                            @click="markClaimed(r.id)"
                                            class="flex items-center gap-1 text-xs h-7 px-2.5 rounded-md font-semibold text-white transition-all disabled:opacity-50"
                                            style="background-color:#10b981;">
                                            <PackageCheck class="w-3 h-3"/>
                                            {{ claimingId === r.id ? '...' : 'Claimed' }}
                                        </button>
                                        <!-- Mark Unclaimed -->
                                        <button v-if="r.claim_status === 'waiting'"
                                            :disabled="unclaimingId === r.id"
                                            @click="markUnclaimed(r.id)"
                                            class="flex items-center gap-1 text-xs h-7 px-2.5 rounded-md font-semibold transition-all disabled:opacity-50"
                                            style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                            <AlertTriangle class="w-3 h-3"/>
                                            {{ unclaimingId === r.id ? '...' : 'Unclaimed' }}
                                        </button>
                                        <!-- Re-mark Waiting (from unclaimed) -->
                                        <button v-if="r.claim_status === 'unclaimed'"
                                            :disabled="claimingId === r.id"
                                            @click="markClaimed(r.id)"
                                            class="flex items-center gap-1 text-xs h-7 px-2.5 rounded-md font-semibold text-white transition-all disabled:opacity-50"
                                            style="background-color:#10b981;">
                                            <PackageCheck class="w-3 h-3"/>
                                            {{ claimingId === r.id ? '...' : 'Claimed' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <p class="text-xs text-slate-400">
                            {{ readyForPickup.from }}–{{ readyForPickup.to }} of {{ readyForPickup.total }} records
                        </p>
                        <div class="flex items-center gap-0.5">
                            <template v-for="link in readyForPickup.links" :key="link.label">
                                <Link v-if="link.url" :href="link.url" preserve-state
                                    class="px-2.5 py-1 text-xs rounded transition-colors"
                                    :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                    :style="link.active ? 'background-color:#1B4F9B' : ''"
                                    v-html="link.label"/>
                                <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 4: HISTORY                                 -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'history'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="search || date" @click="clearFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="search" placeholder="Search patient name or code..."
                                class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="date" type="date"
                                class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                                @change="applyFilters"/>
                        </div>
                        <Button size="sm" class="h-8 text-xs gap-1.5" style="background-color:#1B4F9B; color:white;" @click="applyFilters">
                            <Search class="w-3.5 h-3.5"/> Search
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="!history.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <FileText class="w-10 h-10 text-slate-300 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No results found</p>
                <p class="text-xs text-slate-400 mt-1">Try a different name or date</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-44">Released</th>
                            <th class="px-4 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in history.data" :key="r.id" class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background: visitTypeBadge[r.visit_type]?.bg, color: visitTypeBadge[r.visit_type]?.color }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                    style="background:#f0fdf4; color:#047857; border:1px solid #bbf7d0;">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-medium">{{ r.released_at }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('laboratory.enter', r.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                            <FlaskConical class="w-3 h-3"/> View
                                        </Button>
                                    </Link>
                                    <a :href="route('laboratory.print', r.visit_id)" target="_blank">
                                        <Button variant="outline" size="sm" class="text-xs h-7">Print</Button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ history.from }}–{{ history.to }} of {{ history.total }} records</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#1B4F9B' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
