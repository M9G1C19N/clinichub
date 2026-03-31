<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    ScanLine, CheckCircle2, Clock,
    Activity, AlertTriangle, Search,
    Calendar, FileText, ChevronRight,
    Loader2,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE as visitTypeBadge } from '@/config/visitTypes.js'
const props = defineProps({
    queue:   Array,
    pending: Object,
    history: Object,
    filters: Object,
    summary: Object,
})

const activeTab = ref('today')
const search    = ref(props.filters?.search ?? '')
const date      = ref(props.filters?.date ?? '')

let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({
            only: ['queue','pending','history','summary'],
            preserveScroll: true,
        })
    }, 15000)
})
onUnmounted(() => clearInterval(refreshTimer))

function applyFilters() {
    router.get(route('xray.index'), {
        search: search.value,
        date:   date.value,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    date.value   = ''
    router.get(route('xray.index'), {}, { preserveState: true, replace: true })
}

const imagingStatusConfig = {
    none:       { dot: '#94a3b8', label: 'No Report',   color: '#64748b' },
    pending:    { dot: '#f59e0b', label: 'Pending',     color: '#b45309' },
    processing: { dot: '#8b5cf6', label: 'In Progress', color: '#6d28d9' },
    released:   { dot: '#10b981', label: 'Released',    color: '#047857' },
}

const queueStatusConfig = {
    waiting:   { label: 'Waiting',   dot: '#f59e0b', color: '#92400e' },
    calling:   { label: 'Calling',   dot: '#3b82f6', color: '#1e40af' },
    serving:   { label: 'Serving',   dot: '#10b981', color: '#065f46' },
    completed: { label: 'Completed', dot: '#94a3b8', color: '#475569' },
}

function getImagingStatus(visit) {
    if (!visit) return 'none'
    return visit.imaging_status ?? (visit.has_report ? 'processing' : 'none')
}
</script>

<template>
    <AppLayout title="X-Ray & Ultrasound">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <ScanLine class="w-5 h-5 text-purple-600"/>
                        X-Ray & Ultrasound
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">Imaging reports and radiological findings</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-purple-600 bg-purple-50 border border-purple-200 px-2.5 py-1 rounded-lg">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-purple-500"/>
                        </span>
                        Live · 15s
                    </div>
                    <a :href="route('queue.room', 'xray_utz')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7">
                            <Activity class="w-3.5 h-3.5"/>
                            Room Screen
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <!-- ── TAB NAV ────────────────────────────────── -->
        <div class="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit">
            <button v-for="tab in [
                { key: 'today',   label: 'Today\'s Queue',  count: summary.today          },
                { key: 'pending', label: 'Pending Release',  count: summary.pending        },
                { key: 'history', label: 'History',          count: summary.released_today },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                    activeTab === tab.key
                        ? 'bg-white shadow-sm text-slate-800'
                        : 'text-slate-500 hover:text-slate-700'
                ]">
                <ScanLine v-if="tab.key === 'today'" class="w-3.5 h-3.5"/>
                <Clock    v-if="tab.key === 'pending'" class="w-3.5 h-3.5"/>
                <FileText v-if="tab.key === 'history'" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span :class="[
                    'px-1.5 py-0.5 rounded-md text-xs font-black min-w-[20px] text-center',
                    activeTab === tab.key ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-600'
                ]">{{ tab.count }}</span>
            </button>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 1: TODAY'S QUEUE                           -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'today'">

            <!-- Legend -->
            <div class="flex items-center gap-4 mb-3 px-1">
                <span class="text-xs text-slate-400 font-medium">Report Status:</span>
                <div v-for="(cfg, key) in imagingStatusConfig" :key="key"
                    class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :style="{ background: cfg.dot }"/>
                    <span class="text-xs text-slate-500">{{ cfg.label }}</span>
                </div>
            </div>

            <div v-if="!queue.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <ScanLine class="w-6 h-6 text-purple-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No patients routed to X-Ray today</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">Queue</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Services</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Queue Status</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Report Status</th>
                            <th class="px-4 py-2.5 w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in queue" :key="item.id"
                            class="hover:bg-slate-50/60 transition-colors group">

                            <td class="px-4 py-3">
                                <span class="text-base font-black font-mono text-slate-700">
                                    {{ item.queue_number }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                        style="background-color:#7c3aed">
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
                                        :style="{
                                            background: visitTypeBadge[item.visit.visit_type]?.bg,
                                            color: visitTypeBadge[item.visit.visit_type]?.color
                                        }">
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
                                        style="background:#faf5ff; color:#7c3aed; border-color:#e9d5ff;">
                                        {{ svc.code }}
                                    </span>
                                    <span v-if="!item.visit?.services?.length" class="text-xs text-slate-300">—</span>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5"
                                    :style="{ color: queueStatusConfig[item.status]?.color }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :style="{ background: queueStatusConfig[item.status]?.dot }"/>
                                    <span class="text-xs font-semibold">
                                        {{ queueStatusConfig[item.status]?.label ?? item.status }}
                                    </span>
                                </div>
                                <div v-if="item.status === 'completed' && !item.visit?.is_released"
                                    class="flex items-center gap-1 mt-1">
                                    <AlertTriangle class="w-3 h-3 text-amber-500"/>
                                    <span class="text-xs text-amber-600">Awaiting report</span>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :style="{ background: imagingStatusConfig[getImagingStatus(item.visit)]?.dot }"/>
                                    <span class="text-xs font-semibold"
                                        :style="{ color: imagingStatusConfig[getImagingStatus(item.visit)]?.color }">
                                        {{ imagingStatusConfig[getImagingStatus(item.visit)]?.label }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <Link v-if="item.visit?.id" :href="route('xray.enter', item.visit.id)">
                                    <Button size="sm"
                                        class="text-xs h-7 gap-1.5 text-white opacity-80 group-hover:opacity-100 transition-opacity"
                                        :style="{
                                            backgroundColor:
                                                item.visit?.is_released ? '#10b981' :
                                                item.visit?.has_report  ? '#f59e0b' : '#8b5cf6'
                                        }">
                                        {{ item.visit?.is_released ? 'View'   :
                                           item.visit?.has_report  ? 'Update' : 'Enter' }}
                                        <ChevronRight class="w-3 h-3"/>
                                    </Button>
                                </Link>
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
            <div class="flex items-center gap-2 mb-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                    <Input v-model="search" placeholder="Search patient..." class="pl-9 h-8 text-xs"
                        @keyup.enter="applyFilters"/>
                </div>
                <Button size="sm" class="h-8 text-xs gap-1.5"
                    style="background-color:#7c3aed; color:white;" @click="applyFilters">
                    <Search class="w-3.5 h-3.5"/> Search
                </Button>
                <Button v-if="search" size="sm" variant="outline" class="h-8 text-xs" @click="clearFilters">
                    Clear
                </Button>
            </div>

            <div v-if="!pending.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No pending imaging reports</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Imaging Type</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Status</th>
                            <th class="px-4 py-2.5 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in pending.data" :key="r.id"
                            class="hover:bg-purple-50/30 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{
                                            background: visitTypeBadge[r.visit_type]?.bg,
                                            color: visitTypeBadge[r.visit_type]?.color
                                        }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                    style="background:#faf5ff; color:#7c3aed;">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold text-slate-600">{{ r.imaging_type }}</span>
                                <span v-if="r.is_provisional"
                                    class="ml-2 text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 font-semibold">
                                    Provisional
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ r.visit_date }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <Loader2 v-if="r.status === 'processing'" class="w-3 h-3 text-purple-500 animate-spin"/>
                                    <Clock v-else class="w-3 h-3 text-amber-500"/>
                                    <span class="text-xs font-semibold"
                                        :class="r.status === 'processing' ? 'text-purple-600' : 'text-amber-600'">
                                        {{ r.status === 'processing' ? 'In Progress' : 'Pending' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('xray.enter', r.visit_id)">
                                    <Button size="sm"
                                        class="text-xs h-7 gap-1 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                        style="background-color:#8b5cf6;">
                                        {{ r.status === 'processing' ? 'Continue' : 'Enter' }}
                                        <ChevronRight class="w-3 h-3"/>
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ pending.from }}–{{ pending.to }} of {{ pending.total }}</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in pending.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#7c3aed' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- TAB 3: HISTORY                                 -->
        <!-- ══════════════════════════════════════════════ -->
        <div v-if="activeTab === 'history'">
            <div class="flex items-center gap-2 mb-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                    <Input v-model="search" placeholder="Search patient..." class="pl-9 h-8 text-xs"
                        @keyup.enter="applyFilters"/>
                </div>
                <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                    <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                    <Input v-model="date" type="date"
                        class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                        @change="applyFilters"/>
                </div>
                <Button size="sm" class="h-8 text-xs gap-1.5"
                    style="background-color:#7c3aed; color:white;" @click="applyFilters">
                    <Search class="w-3.5 h-3.5"/> Search
                </Button>
                <Button v-if="search || date" size="sm" variant="outline" class="h-8 text-xs"
                    @click="clearFilters">Clear</Button>
            </div>

            <div v-if="!history.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <FileText class="w-10 h-10 text-slate-300 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No results found</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Imaging Type</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-44">Released</th>
                            <th class="px-4 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in history.data" :key="r.id"
                            class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{
                                            background: visitTypeBadge[r.visit_type]?.bg,
                                            color: visitTypeBadge[r.visit_type]?.color
                                        }">
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
                                <span class="text-xs font-semibold text-slate-600">{{ r.imaging_type }}</span>
                                <p v-if="r.impression" class="text-xs text-slate-400 mt-0.5 italic">
                                    {{ r.impression.substring(0, 50) }}{{ r.impression.length > 50 ? '...' : '' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-medium">{{ r.released_at }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('xray.enter', r.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                            <ScanLine class="w-3 h-3"/> View
                                        </Button>
                                    </Link>
                                    <a :href="route('xray.print', r.visit_id)" target="_blank">
                                        <Button variant="outline" size="sm" class="text-xs h-7">Print</Button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ history.from }}–{{ history.to }} of {{ history.total }}</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#7c3aed' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
