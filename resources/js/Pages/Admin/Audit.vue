<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow, TableEmpty,
} from '@/components/ui/table'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import {
    Activity, Users, ShieldCheck, Pencil,
    Trash2, PlusCircle, Search, X, ChevronLeft,
    ChevronRight, Eye, ClipboardList, CalendarDays,
    Filter,
} from 'lucide-vue-next'

const props = defineProps({
    logs:         { type: Object, default: () => ({ data: [], links: [], last_page: 1, total: 0, from: 0, to: 0 }) },
    filters:      { type: Object, default: () => ({}) },
    users:        { type: Array,  default: () => [] },
    subjectTypes: { type: Array,  default: () => [] },
    totals:       { type: Object, default: () => ({ today: 0, created: 0, updated: 0, deleted: 0 }) },
})

// ── Filters ────────────────────────────────────────────
const form = ref({
    search:       props.filters?.search       ?? '',
    event:        props.filters?.event        ?? 'all',
    subject_type: props.filters?.subject_type ?? 'all',
    user_id:      props.filters?.user_id      ?? 'all',
    date_from:    props.filters?.date_from    ?? '',
    date_to:      props.filters?.date_to      ?? '',
})

let debounceTimer = null
function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get(route('admin.audit'), {
            search:       form.value.search       || undefined,
            event:        form.value.event        !== 'all' ? form.value.event        : undefined,
            subject_type: form.value.subject_type !== 'all' ? form.value.subject_type : undefined,
            user_id:      form.value.user_id      !== 'all' ? form.value.user_id      : undefined,
            date_from:    form.value.date_from    || undefined,
            date_to:      form.value.date_to      || undefined,
        }, { preserveState: true, replace: true })
    }, 350)
}

function clearFilters() {
    form.value = { search: '', event: 'all', subject_type: 'all', user_id: 'all', date_from: '', date_to: '' }
    router.get(route('admin.audit'), {}, { preserveState: true, replace: true })
}

const hasFilters = computed(() =>
    form.value.search !== '' ||
    form.value.event !== 'all' ||
    form.value.subject_type !== 'all' ||
    form.value.user_id !== 'all' ||
    form.value.date_from !== '' ||
    form.value.date_to !== ''
)

// ── Diff modal ─────────────────────────────────────────
const selected = ref(null)
function openDiff(log) { selected.value = log }
function closeDiff()   { selected.value = null }

// ── Helpers ────────────────────────────────────────────
const EVENT_CONFIG = {
    created: { label: 'Created', variant: 'default',     icon: PlusCircle,  class: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    updated: { label: 'Updated', variant: 'secondary',   icon: Pencil,      class: 'bg-blue-100 text-blue-700 border-blue-200' },
    deleted: { label: 'Deleted', variant: 'destructive', icon: Trash2,      class: 'bg-red-100 text-red-700 border-red-200' },
}

const MODULE_CLASS = {
    Patient:           'bg-violet-100 text-violet-700 border-violet-200',
    PatientVisit:      'bg-indigo-100 text-indigo-700 border-indigo-200',
    Consultation:      'bg-sky-100 text-sky-700 border-sky-200',
    Prescription:      'bg-teal-100 text-teal-700 border-teal-200',
    Invoice:           'bg-yellow-100 text-yellow-700 border-yellow-200',
    Payment:           'bg-green-100 text-green-700 border-green-200',
    LaboratoryRequest: 'bg-orange-100 text-orange-700 border-orange-200',
    DrugTestRequest:   'bg-pink-100 text-pink-700 border-pink-200',
    ImagingRequest:    'bg-cyan-100 text-cyan-700 border-cyan-200',
    User:              'bg-slate-100 text-slate-700 border-slate-200',
    ServiceCatalog:    'bg-amber-100 text-amber-700 border-amber-200',
}

function moduleClass(type) {
    return MODULE_CLASS[type] ?? 'bg-gray-100 text-gray-600 border-gray-200'
}

function eventConfig(event) {
    return EVENT_CONFIG[event] ?? { label: event, class: 'bg-gray-100 text-gray-600 border-gray-200', icon: Activity }
}

function diffKeys(log) {
    return [...new Set([
        ...Object.keys(log.old ?? {}),
        ...Object.keys(log.new ?? {}),
    ])]
}

function formatValue(val) {
    if (val === null || val === undefined) return null
    if (typeof val === 'boolean') return val ? 'Yes' : 'No'
    if (typeof val === 'object') return JSON.stringify(val, null, 2)
    return String(val)
}

function hasDiff(log) {
    return (log.old && Object.keys(log.old).length > 0) ||
           (log.new && Object.keys(log.new).length > 0)
}

function initials(name) {
    return name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '?'
}

// ── Pagination ─────────────────────────────────────────
function goPage(url) {
    if (url) router.get(url, form.value, { preserveState: true })
}

const statCards = computed(() => [
    { label: "Today's Actions", value: props.totals?.today ?? 0,   color: 'text-slate-800',   bg: 'bg-slate-50',   icon: CalendarDays  },
    { label: 'Records Created', value: props.totals?.created ?? 0, color: 'text-emerald-600', bg: 'bg-emerald-50', icon: PlusCircle    },
    { label: 'Records Updated', value: props.totals?.updated ?? 0, color: 'text-blue-600',    bg: 'bg-blue-50',    icon: Pencil        },
    { label: 'Records Deleted', value: props.totals?.deleted ?? 0, color: 'text-red-500',     bg: 'bg-red-50',     icon: Trash2        },
])
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-slate-100">
                    <ClipboardList class="w-5 h-5 text-slate-600" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Audit Log</h1>
                    <p class="text-xs text-slate-500">Full activity trail across all modules</p>
                </div>
            </div>
        </template>

        <div class="space-y-5">

            <!-- ── Stat Cards ──────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="card in statCards" :key="card.label"
                    class="bg-white rounded-xl border shadow-sm p-4 flex items-center gap-4">
                    <div :class="['p-2.5 rounded-lg', card.bg]">
                        <component :is="card.icon" :class="['w-5 h-5', card.color]" />
                    </div>
                    <div>
                        <div :class="['text-2xl font-black', card.color]">{{ card.value.toLocaleString() }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ card.label }}</div>
                    </div>
                </div>
            </div>

            <!-- ── Filters ─────────────────────────────── -->
            <div class="bg-white rounded-xl border shadow-sm p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Filter class="w-4 h-4 text-slate-400" />
                    <span class="text-sm font-semibold text-slate-600">Filters</span>
                    <Button v-if="hasFilters" variant="ghost" size="sm"
                        @click="clearFilters"
                        class="ml-auto h-7 text-xs text-slate-400 hover:text-red-500 gap-1">
                        <X class="w-3 h-3" /> Clear
                    </Button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <!-- Search -->
                    <div class="relative lg:col-span-2">
                        <label class="text-xs text-slate-500 mb-1 block">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input v-model="form.search" @input="applyFilters"
                                placeholder="Search description or user..."
                                class="pl-9" />
                        </div>
                    </div>

                    <!-- Event -->
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Event</label>
                        <Select v-model="form.event" @update:modelValue="applyFilters">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All Events" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Events</SelectItem>
                                <SelectItem value="created">Created</SelectItem>
                                <SelectItem value="updated">Updated</SelectItem>
                                <SelectItem value="deleted">Deleted</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Module -->
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Module</label>
                        <Select v-model="form.subject_type" @update:modelValue="applyFilters">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All Modules" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Modules</SelectItem>
                                <SelectItem v-for="t in subjectTypes" :key="t" :value="t">{{ t }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- User -->
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">User</label>
                        <Select v-model="form.user_id" @update:modelValue="applyFilters">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="All Users" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Users</SelectItem>
                                <SelectItem v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">From</label>
                        <Input type="date" v-model="form.date_from" @change="applyFilters" class="text-sm" />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">To</label>
                        <Input type="date" v-model="form.date_to" @change="applyFilters" class="text-sm" />
                    </div>
                </div>
            </div>

            <!-- ── Table ───────────────────────────────── -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">

                <!-- Table header summary -->
                <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
                    <span class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Activity class="w-4 h-4 text-slate-400" />
                        Activity Records
                    </span>
                    <span class="text-xs text-slate-400">
                        {{ logs.total?.toLocaleString() ?? 0 }} total entries
                    </span>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow class="bg-slate-50 hover:bg-slate-50">
                            <TableHead class="w-44">When</TableHead>
                            <TableHead class="w-44">Who</TableHead>
                            <TableHead class="w-28">Event</TableHead>
                            <TableHead class="w-36">Module</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead class="w-28 text-center">Changes</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableEmpty v-if="logs.data.length === 0" :colspan="6">
                            <div class="flex flex-col items-center gap-2 py-10 text-slate-400">
                                <ClipboardList class="w-10 h-10 opacity-30" />
                                <p class="text-sm font-medium">No activity logs found</p>
                                <p v-if="hasFilters" class="text-xs">Try clearing your filters</p>
                            </div>
                        </TableEmpty>

                        <TableRow v-for="log in logs.data" :key="log.id"
                            class="hover:bg-slate-50/80 transition-colors">

                            <!-- When -->
                            <TableCell class="py-3">
                                <div class="text-xs font-medium text-slate-800">{{ log.created_at }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ log.created_at_diff }}</div>
                            </TableCell>

                            <!-- Who -->
                            <TableCell class="py-3">
                                <div v-if="log.causer" class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ initials(log.causer.name) }}
                                    </div>
                                    <span class="text-xs font-medium text-slate-700 leading-tight">{{ log.causer.name }}</span>
                                </div>
                                <span v-else class="text-xs text-slate-400 italic flex items-center gap-1">
                                    <ShieldCheck class="w-3.5 h-3.5" /> System
                                </span>
                            </TableCell>

                            <!-- Event -->
                            <TableCell class="py-3">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold border"
                                    :class="eventConfig(log.event).class">
                                    <component :is="eventConfig(log.event).icon" class="w-3 h-3" />
                                    {{ eventConfig(log.event).label }}
                                </span>
                            </TableCell>

                            <!-- Module -->
                            <TableCell class="py-3">
                                <span v-if="log.subject_type"
                                    class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold border"
                                    :class="moduleClass(log.subject_type)">
                                    {{ log.subject_type }}
                                </span>
                                <span v-else class="text-slate-300 text-xs">—</span>
                            </TableCell>

                            <!-- Description -->
                            <TableCell class="py-3 max-w-xs">
                                <div class="text-sm text-slate-700 truncate font-medium">{{ log.description }}</div>
                                <div v-if="log.subject_id" class="text-xs text-slate-400 mt-0.5">ID #{{ log.subject_id }}</div>
                            </TableCell>

                            <!-- Changes -->
                            <TableCell class="py-3 text-center">
                                <Button v-if="hasDiff(log)"
                                    variant="outline" size="sm"
                                    @click="openDiff(log)"
                                    class="h-7 text-xs gap-1.5 text-blue-600 border-blue-200 hover:bg-blue-50">
                                    <Eye class="w-3.5 h-3.5" />
                                    {{ diffKeys(log).length }} field{{ diffKeys(log).length !== 1 ? 's' : '' }}
                                </Button>
                                <span v-else class="text-slate-300 text-xs">—</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1"
                    class="flex items-center justify-between px-4 py-3 border-t bg-slate-50">
                    <span class="text-xs text-slate-500">
                        Showing <strong>{{ logs.from }}</strong>–<strong>{{ logs.to }}</strong>
                        of <strong>{{ logs.total?.toLocaleString() }}</strong> entries
                    </span>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="sm"
                            :disabled="!logs.links[0]?.url"
                            @click="goPage(logs.links[0]?.url)"
                            class="h-7 w-7 p-0">
                            <ChevronLeft class="w-3.5 h-3.5" />
                        </Button>
                        <template v-for="link in logs.links.slice(1, -1)" :key="link.label">
                            <Button
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                @click="goPage(link.url)"
                                v-html="link.label"
                                class="h-7 min-w-7 px-2 text-xs" />
                        </template>
                        <Button variant="outline" size="sm"
                            :disabled="!logs.links[logs.links.length - 1]?.url"
                            @click="goPage(logs.links[logs.links.length - 1]?.url)"
                            class="h-7 w-7 p-0">
                            <ChevronRight class="w-3.5 h-3.5" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Diff Dialog ─────────────────────────────── -->
        <Dialog :open="!!selected" @update:open="val => !val && closeDiff()">
            <DialogContent class="max-w-2xl max-h-[85vh] flex flex-col p-0 gap-0">
                <DialogHeader class="px-5 py-4 border-b bg-slate-50 rounded-t-lg shrink-0">
                    <DialogTitle class="text-base">Change Details</DialogTitle>
                    <DialogDescription v-if="selected" class="flex flex-wrap items-center gap-2 mt-1">
                        <!-- Who -->
                        <span class="flex items-center gap-1.5 text-slate-600 text-xs">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">
                                {{ initials(selected.causer?.name ?? 'S') }}
                            </div>
                            {{ selected.causer?.name ?? 'System' }}
                        </span>
                        <span class="text-slate-300">·</span>
                        <!-- Event badge -->
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold border"
                            :class="eventConfig(selected.event).class">
                            <component :is="eventConfig(selected.event).icon" class="w-3 h-3" />
                            {{ eventConfig(selected.event).label }}
                        </span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-500 font-medium">{{ selected.description }}</span>
                        <span class="text-slate-300">·</span>
                        <span class="text-xs text-slate-400">{{ selected.created_at }}</span>
                    </DialogDescription>
                </DialogHeader>

                <!-- Diff table -->
                <div class="overflow-y-auto flex-1">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100 sticky top-0 border-b">
                            <tr>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wide w-1/4">Field</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-red-500 uppercase tracking-wide w-[37.5%]">Before</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-emerald-600 uppercase tracking-wide w-[37.5%]">After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="key in diffKeys(selected ?? {})" :key="key"
                                class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-600 font-semibold align-top">
                                    {{ key }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <template v-if="selected?.old?.[key] !== undefined">
                                        <pre v-if="typeof selected.old[key] === 'object' && selected.old[key] !== null"
                                            class="text-xs bg-red-50 text-red-700 rounded-md p-2 whitespace-pre-wrap break-all max-w-[220px] border border-red-100">{{ formatValue(selected.old[key]) }}</pre>
                                        <span v-else-if="selected.old[key] !== null"
                                            class="text-xs bg-red-50 text-red-700 rounded-md px-2 py-1 inline-block break-all border border-red-100">
                                            {{ formatValue(selected.old[key]) }}
                                        </span>
                                        <span v-else class="text-slate-300 text-xs italic">null</span>
                                    </template>
                                    <span v-else class="text-slate-300 text-xs italic">—</span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <template v-if="selected?.new?.[key] !== undefined">
                                        <pre v-if="typeof selected.new[key] === 'object' && selected.new[key] !== null"
                                            class="text-xs bg-emerald-50 text-emerald-700 rounded-md p-2 whitespace-pre-wrap break-all max-w-[220px] border border-emerald-100">{{ formatValue(selected.new[key]) }}</pre>
                                        <span v-else-if="selected.new[key] !== null"
                                            class="text-xs bg-emerald-50 text-emerald-700 rounded-md px-2 py-1 inline-block break-all border border-emerald-100">
                                            {{ formatValue(selected.new[key]) }}
                                        </span>
                                        <span v-else class="text-slate-300 text-xs italic">null</span>
                                    </template>
                                    <span v-else class="text-slate-300 text-xs italic">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3 border-t bg-slate-50 rounded-b-lg shrink-0 flex justify-end">
                    <Button variant="outline" size="sm" @click="closeDiff">Close</Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
