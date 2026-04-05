<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import {
    RefreshCw, Upload, DownloadCloud, CheckCircle2,
    AlertTriangle, X, UserPlus, UserCheck,
    MonitorCheck, ClipboardList, Info,
    DatabaseBackup, FileJson, ArrowRight,
    FlaskConical, Stethoscope, ScanLine, TestTube,
    HeartPulse, Wifi, WifiOff, BadgeCheck,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'

const props = defineProps({
    syncLogs:           { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    preview:            { type: Object, default: null },
    pendingExportCount: { type: Number, default: 0 },
})

// ── Upload ───────────────────────────────────────────────
const fileInput    = ref(null)
const dragging     = ref(false)
const uploading    = ref(false)
const importing    = ref(false)
const selectedFile = ref(null)

function onDragover(e) { e.preventDefault(); dragging.value = true }
function onDragleave()  { dragging.value = false }
function onDrop(e)      { e.preventDefault(); dragging.value = false; setFile(e.dataTransfer.files[0]) }
function onFileChange(e){ setFile(e.target.files[0]) }

function setFile(file) {
    if (!file || !file.name.endsWith('.json')) {
        alert('Please select a valid .json field export file.')
        return
    }
    selectedFile.value = file
}
function clearFile() {
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
}

function uploadPreview() {
    if (!selectedFile.value) return
    uploading.value = true
    const form = new FormData()
    form.append('sync_file', selectedFile.value)
    router.post(route('admin.field-sync.preview'), form, {
        forceFormData: true,
        onFinish: () => { uploading.value = false },
        onSuccess: () => { selectedFile.value = null },
    })
}

// ── Import ───────────────────────────────────────────────
const showImportConfirm = ref(false)

function confirmImport() {
    importing.value = true
    router.post(route('admin.field-sync.import'), {}, {
        onFinish: () => { importing.value = false; showImportConfirm.value = false },
    })
}

function cancelPreview() {
    router.post(route('admin.field-sync.cancel-preview'))
}

// ── Export ───────────────────────────────────────────────
function triggerExport() {
    window.location.href = route('admin.field-sync.export')
}

// ── Helpers ──────────────────────────────────────────────
function visitBadge(type) {
    return VISIT_TYPE_BADGE[type] ?? { bg: '#f1f5f9', color: '#475569', label: type }
}
function formatDate(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
    })
}
function formatDateOnly(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: '2-digit',
    })
}
function goPage(url) {
    if (url) router.get(url, {}, { preserveState: true })
}

const hasPreview  = computed(() => props.preview && props.preview.items?.length > 0)
const createCount = computed(() => props.preview?.items?.filter(i => i.patient_action === 'create').length ?? 0)
const matchCount  = computed(() => props.preview?.items?.filter(i => i.patient_action === 'match').length ?? 0)

const MODULE_ICON = {
    'Vitals':       HeartPulse,
    'Consultation': Stethoscope,
    'Lab':          FlaskConical,
    'Drug Test':    TestTube,
}
function moduleIcon(label) {
    for (const [key, icon] of Object.entries(MODULE_ICON)) {
        if (label.startsWith(key)) return icon
    }
    return ScanLine
}
function moduleColor(label) {
    if (label.startsWith('Vitals'))       return 'bg-teal-100 text-teal-700'
    if (label.startsWith('Consultation')) return 'bg-purple-100 text-purple-700'
    if (label.startsWith('Lab'))          return 'bg-amber-100 text-amber-700'
    if (label.startsWith('Imaging'))      return 'bg-sky-100 text-sky-700'
    if (label.startsWith('Drug'))         return 'bg-rose-100 text-rose-700'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <AppLayout title="Field Sync">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-sky-50">
                    <RefreshCw class="w-5 h-5 text-sky-600" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Field Visit Sync</h1>
                    <p class="text-xs text-slate-500">Export from field server · Import to main database</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Safety banner -->
            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <AlertTriangle class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-500" />
                <span>
                    <strong>Before importing:</strong> Always
                    <a :href="route('admin.backup.index')" class="underline font-semibold">create a database backup</a>
                    on the main server first. This ensures you can recover if anything goes wrong during import.
                </span>
            </div>

            <!-- ── Two-step flow cards ───────────────────���─── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- STEP 1: EXPORT (field server) -->
                <div class="bg-white rounded-xl border-2 border-slate-200 overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3"
                         style="background: linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%)">
                        <div class="w-8 h-8 rounded-full bg-sky-600 text-white flex items-center justify-center text-sm font-black flex-shrink-0">
                            1
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-sky-800">Export from Field Server</h2>
                            <p class="text-xs text-sky-600">Run this on the field laptop / device</p>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium bg-orange-100 text-orange-700">
                                <WifiOff class="w-3 h-3" /> Offline Device
                            </span>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col gap-4">
                        <p class="text-sm text-slate-600">
                            On the <strong>field server</strong>, go to this same page and click the button below.
                            It will download a <code class="bg-slate-100 px-1 rounded text-xs">.json</code>
                            file containing <strong>all unsynced field visits</strong> — including vitals,
                            consultation, lab results, imaging, and drug test records.
                        </p>

                        <!-- What gets exported -->
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div v-for="item in [
                                { icon: HeartPulse,   label: 'Vitals',        color: 'text-teal-600',   bg: 'bg-teal-50' },
                                { icon: Stethoscope,  label: 'Consultation',  color: 'text-purple-600', bg: 'bg-purple-50' },
                                { icon: FlaskConical, label: 'Lab Results',   color: 'text-amber-600',  bg: 'bg-amber-50' },
                                { icon: ScanLine,     label: 'Imaging',       color: 'text-sky-600',    bg: 'bg-sky-50' },
                                { icon: TestTube,     label: 'Drug Test',     color: 'text-rose-600',   bg: 'bg-rose-50' },
                                { icon: ClipboardList,label: 'Patient Info',  color: 'text-slate-600',  bg: 'bg-slate-50' },
                            ]" :key="item.label"
                                class="flex flex-col items-center gap-1 p-2 rounded-lg border border-slate-100" :class="item.bg">
                                <component :is="item.icon" class="w-4 h-4" :class="item.color" />
                                <span class="text-slate-600 text-center leading-tight">{{ item.label }}</span>
                            </div>
                        </div>

                        <!-- Pending count -->
                        <div class="flex items-center gap-2 p-3 bg-sky-50 border border-sky-200 rounded-lg text-sm">
                            <Info class="w-4 h-4 flex-shrink-0 text-sky-500" />
                            <span class="text-sky-700">
                                <strong>{{ pendingExportCount }}</strong>
                                unsynced field visit{{ pendingExportCount !== 1 ? 's' : '' }} ready to export on this server.
                            </span>
                        </div>

                        <div class="mt-auto pt-2">
                            <Button @click="triggerExport" :disabled="pendingExportCount === 0"
                                class="w-full gap-2 bg-sky-600 hover:bg-sky-700 text-white disabled:opacity-40">
                                <DownloadCloud class="w-4 h-4" />
                                Download Field Export (.json)
                            </Button>
                            <p v-if="pendingExportCount === 0" class="text-xs text-slate-400 text-center mt-2">
                                No unsynced field visits on this server.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: IMPORT (main server) -->
                <div class="bg-white rounded-xl border-2 flex flex-col overflow-hidden"
                     :class="hasPreview ? 'border-emerald-400' : 'border-slate-200'">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3"
                         :style="hasPreview
                            ? 'background: linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%)'
                            : 'background: linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%)'">
                        <div class="w-8 h-8 rounded-full text-white flex items-center justify-center text-sm font-black flex-shrink-0"
                             :class="hasPreview ? 'bg-emerald-600' : 'bg-slate-500'">
                            2
                        </div>
                        <div>
                            <h2 class="text-sm font-bold" :class="hasPreview ? 'text-emerald-800' : 'text-slate-700'">
                                Import to Main Server
                            </h2>
                            <p class="text-xs" :class="hasPreview ? 'text-emerald-600' : 'text-slate-500'">
                                Upload the exported .json file here
                            </p>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium bg-emerald-100 text-emerald-700">
                                <Wifi class="w-3 h-3" /> Main Server
                            </span>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col gap-4">

                        <!-- No preview yet: show upload zone -->
                        <template v-if="!hasPreview">
                            <p class="text-sm text-slate-600">
                                Take the <code class="bg-slate-100 px-1 rounded text-xs">.json</code> file
                                exported from the field server (Step 1) and upload it here.
                                The system will analyze it, show you a full preview, and let you confirm before importing.
                            </p>

                            <!-- Drop zone -->
                            <div @dragover="onDragover" @dragleave="onDragleave" @drop="onDrop"
                                @click="fileInput?.click()"
                                :class="[
                                    'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all',
                                    dragging ? 'border-emerald-400 bg-emerald-50 scale-[1.01]'
                                             : 'border-slate-200 hover:border-emerald-300 hover:bg-slate-50',
                                ]">
                                <input ref="fileInput" type="file" accept=".json" class="hidden" @change="onFileChange" />
                                <FileJson class="w-10 h-10 mx-auto mb-3" :class="dragging ? 'text-emerald-400' : 'text-slate-300'" />
                                <p class="text-sm font-medium text-slate-600">
                                    {{ selectedFile ? selectedFile.name : 'Drop .json export file here or click to browse' }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">Only .json files exported from ClinicHub</p>
                            </div>

                            <!-- Selected file pill -->
                            <div v-if="selectedFile" class="flex items-center justify-between p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                                <div class="flex items-center gap-2 text-sm text-emerald-700">
                                    <FileJson class="w-4 h-4" />
                                    <span class="font-medium">{{ selectedFile.name }}</span>
                                    <span class="text-xs text-emerald-500">({{ (selectedFile.size / 1024).toFixed(1) }} KB)</span>
                                </div>
                                <button @click.stop="clearFile" class="text-emerald-400 hover:text-emerald-600 transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="mt-auto">
                                <Button @click="uploadPreview" :disabled="!selectedFile || uploading"
                                    class="w-full gap-2 bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-40">
                                    <span v-if="uploading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                    <Upload v-else class="w-4 h-4" />
                                    {{ uploading ? 'Analyzing file…' : 'Analyze & Preview' }}
                                </Button>
                            </div>
                        </template>

                        <!-- Preview ready -->
                        <template v-else>
                            <div class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-800">
                                <BadgeCheck class="w-4 h-4 flex-shrink-0 text-emerald-500" />
                                <span>
                                    File analyzed — <strong>{{ preview.items.length }} visit(s)</strong> ready to import.
                                    Review the table below before confirming.
                                </span>
                            </div>
                            <!-- Meta strip -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2.5 bg-slate-50 rounded-lg">
                                    <p class="text-slate-400 mb-0.5">File</p>
                                    <p class="font-semibold text-slate-700 truncate">{{ preview.filename }}</p>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-lg">
                                    <p class="text-slate-400 mb-0.5">Device</p>
                                    <p class="font-semibold text-slate-700 truncate">{{ preview.device_id ?? '—' }}</p>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-lg">
                                    <p class="text-slate-400 mb-0.5">Exported At</p>
                                    <p class="font-semibold text-slate-700">{{ formatDate(preview.exported_at) }}</p>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-lg">
                                    <p class="text-slate-400 mb-0.5">Exported By</p>
                                    <p class="font-semibold text-slate-700 truncate">{{ preview.exported_by ?? '—' }}</p>
                                </div>
                            </div>
                            <!-- Summary -->
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-sky-100 text-sky-700 font-medium">
                                    <ClipboardList class="w-3.5 h-3.5" /> {{ preview.items.length }} visit(s)
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">
                                    <UserCheck class="w-3.5 h-3.5" /> {{ matchCount }} matched
                                </span>
                                <span v-if="createCount > 0"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-violet-100 text-violet-700 font-medium">
                                    <UserPlus class="w-3.5 h-3.5" /> {{ createCount }} new patient(s)
                                </span>
                            </div>
                            <div class="mt-auto flex flex-col gap-2">
                                <Button @click="showImportConfirm = true"
                                    class="w-full gap-2 bg-emerald-600 hover:bg-emerald-700 text-white">
                                    <CheckCircle2 class="w-4 h-4" />
                                    Confirm Import
                                </Button>
                                <button @click="cancelPreview"
                                    class="text-xs text-slate-400 hover:text-slate-600 text-center transition-colors py-1">
                                    Cancel — upload a different file
                                </button>
                            </div>
                        </template>

                    </div>
                </div>
            </div>

            <!-- ── Preview detail table ───────────────────── -->
            <div v-if="hasPreview" class="bg-white rounded-xl border border-emerald-300 overflow-hidden">
                <div class="px-5 py-4 border-b border-emerald-100 flex items-center gap-2 bg-emerald-50">
                    <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                    <h2 class="text-sm font-semibold text-emerald-800">Preview — What will be imported</h2>
                </div>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50 text-xs">
                                <TableHead>#</TableHead>
                                <TableHead>Patient</TableHead>
                                <TableHead>Visit Date</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Data Included</TableHead>
                                <TableHead>Patient Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, i) in preview.items" :key="item.field_temp_id ?? i"
                                class="hover:bg-slate-50 text-sm">
                                <TableCell class="text-slate-400 text-xs">{{ i + 1 }}</TableCell>
                                <TableCell>
                                    <p class="font-semibold text-slate-800">{{ item.patient_name }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ item.patient_code }}
                                        <span v-if="item.date_of_birth"> · DOB {{ formatDateOnly(item.date_of_birth) }}</span>
                                    </p>
                                </TableCell>
                                <TableCell class="text-sm text-slate-600 whitespace-nowrap">
                                    {{ formatDate(item.visit_date) }}
                                </TableCell>
                                <TableCell>
                                    <span class="text-xs px-2 py-1 rounded-full font-medium"
                                        :style="{ background: visitBadge(item.visit_type).bg, color: visitBadge(item.visit_type).color }">
                                        {{ visitBadge(item.visit_type).label }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <span v-if="item.modules.length === 0" class="text-xs text-slate-400">Visit only</span>
                                        <span v-for="mod in item.modules" :key="mod"
                                            class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium"
                                            :class="moduleColor(mod)">
                                            <component :is="moduleIcon(mod)" class="w-3 h-3" />
                                            {{ mod }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span v-if="item.patient_action === 'match'"
                                        class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 font-medium">
                                        <UserCheck class="w-3 h-3" /> Match
                                        <span class="font-mono ml-0.5 text-emerald-600">{{ item.matched_code }}</span>
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-violet-100 text-violet-700 font-medium">
                                        <UserPlus class="w-3 h-3" /> New Patient
                                    </span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>

            <!-- ── Import History ──────────────────────────── -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                    <MonitorCheck class="w-4 h-4 text-slate-400" />
                    <h2 class="text-sm font-semibold text-slate-700">Import History</h2>
                    <span class="ml-auto text-xs text-slate-400">{{ syncLogs.total }} total</span>
                </div>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50 text-xs">
                                <TableHead>Date</TableHead>
                                <TableHead>File</TableHead>
                                <TableHead>Device</TableHead>
                                <TableHead class="text-center">Visits</TableHead>
                                <TableHead class="text-center">New Patients</TableHead>
                                <TableHead class="text-center">Matched</TableHead>
                                <TableHead class="text-center">Skipped</TableHead>
                                <TableHead>Imported By</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="syncLogs.data.length === 0">
                                <TableCell colspan="8" class="text-center text-slate-400 py-10 text-sm">
                                    No imports yet.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="log in syncLogs.data" :key="log.id" class="hover:bg-slate-50 text-sm">
                                <TableCell class="whitespace-nowrap text-slate-600 text-xs">{{ formatDate(log.created_at) }}</TableCell>
                                <TableCell class="font-mono text-xs text-slate-500 max-w-[180px] truncate">
                                    {{ log.original_filename }}
                                </TableCell>
                                <TableCell class="text-xs text-slate-500 max-w-[140px] truncate">{{ log.device_id ?? '—' }}</TableCell>
                                <TableCell class="text-center font-bold text-sky-700">{{ log.visits_imported }}</TableCell>
                                <TableCell class="text-center">
                                    <span v-if="log.patients_created > 0" class="text-xs font-semibold text-violet-700">{{ log.patients_created }}</span>
                                    <span v-else class="text-slate-300 text-xs">0</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="log.patients_matched > 0" class="text-xs font-semibold text-emerald-700">{{ log.patients_matched }}</span>
                                    <span v-else class="text-slate-300 text-xs">0</span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="log.visits_skipped > 0" class="text-xs font-semibold text-red-600">{{ log.visits_skipped }}</span>
                                    <span v-else class="text-slate-300 text-xs">0</span>
                                </TableCell>
                                <TableCell class="text-sm text-slate-600">{{ log.imported_by ?? '—' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <!-- Pagination -->
                <div v-if="syncLogs.last_page > 1"
                    class="flex items-center justify-between px-5 py-3 border-t border-slate-100 text-xs text-slate-500">
                    <span>{{ syncLogs.from }}–{{ syncLogs.to }} of {{ syncLogs.total }}</span>
                    <div class="flex gap-1">
                        <button v-for="link in syncLogs.links" :key="link.label"
                            @click="goPage(link.url)" :disabled="!link.url"
                            v-html="link.label"
                            :class="[
                                'px-2.5 py-1 rounded-lg transition-colors',
                                link.active  ? 'bg-sky-600 text-white' : '',
                                !link.active && link.url ? 'hover:bg-slate-100 text-slate-600' : '',
                                !link.url    ? 'opacity-40 cursor-not-allowed text-slate-400' : '',
                            ]" />
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Import Confirm Dialog ─────────────────────── -->
        <Dialog :open="showImportConfirm" @update:open="showImportConfirm = false">
            <DialogContent class="max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-emerald-700">
                        <CheckCircle2 class="w-5 h-5" /> Confirm Import
                    </DialogTitle>
                    <DialogDescription>
                        You are about to import <strong>{{ preview?.items?.length }}</strong> field visit(s)
                        with all associated data into the main database.
                        Case numbers will be assigned automatically.
                    </DialogDescription>
                </DialogHeader>
                <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 my-3">
                    <AlertTriangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <span>Ensure you have a <strong>database backup</strong> before proceeding. This cannot be undone easily.</span>
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="showImportConfirm = false" :disabled="importing">Cancel</Button>
                    <Button @click="confirmImport" :disabled="importing"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white gap-2">
                        <span v-if="importing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        <CheckCircle2 v-else class="w-4 h-4" />
                        {{ importing ? 'Importing…' : 'Import Now' }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
