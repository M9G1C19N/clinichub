<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table'
import {
    DatabaseBackup, Download, RotateCcw, Trash2,
    ShieldCheck, HardDrive, Clock, FilePlus2,
    AlertTriangle, CheckCircle2, Info,
} from 'lucide-vue-next'

const props = defineProps({
    backups:     { type: Array,  default: () => [] },
    db_size:     { type: String, default: 'N/A' },
    count:       { type: Number, default: 0 },
    last_backup: { type: String, default: null },
})

// ── Confirm modals ───────────────────────────────────────
const restoreTarget   = ref(null)
const deleteTarget    = ref(null)
const restoreConfirm  = ref('')
const creating        = ref(false)

function openRestore(backup) {
    restoreTarget.value  = backup
    restoreConfirm.value = ''
}
function openDelete(backup) {
    deleteTarget.value = backup
}

const canRestore = computed(() => restoreConfirm.value.trim().toUpperCase() === 'RESTORE')

// ── Actions ──────────────────────────────────────────────
function createBackup() {
    creating.value = true
    router.post(route('admin.backup.create'), {}, {
        preserveScroll: true,
        onFinish: () => { creating.value = false },
    })
}

function confirmRestore() {
    if (!canRestore.value) return
    router.post(route('admin.backup.restore', restoreTarget.value.filename), {}, {
        preserveScroll: true,
        onSuccess: () => { restoreTarget.value = null },
    })
}

function confirmDelete() {
    router.delete(route('admin.backup.destroy', deleteTarget.value.filename), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null },
    })
}

function download(filename) {
    window.location.href = route('admin.backup.download', filename)
}

function formatDate(dt) {
    if (!dt) return '—'
    return new Date(dt).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    })
}
</script>

<template>
    <AppLayout title="Database Backup">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-50">
                    <DatabaseBackup class="w-5 h-5 text-emerald-600" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Database Backup & Recovery</h1>
                    <p class="text-xs text-slate-500">Create, download, and restore database snapshots</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Warning banner -->
            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                <AlertTriangle class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-500" />
                <span>
                    <strong>Important:</strong> Always create a backup <em>before</em> importing field visits
                    or making bulk changes. Restoring a backup will overwrite all current data.
                </span>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-blue-50">
                        <HardDrive class="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Database Size</p>
                        <p class="text-2xl font-bold text-slate-800">{{ db_size }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-emerald-50">
                        <FilePlus2 class="w-5 h-5 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Total Backups</p>
                        <p class="text-2xl font-bold text-slate-800">{{ count }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-purple-50">
                        <Clock class="w-5 h-5 text-purple-600" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Last Backup</p>
                        <p class="text-sm font-semibold text-slate-700">
                            {{ last_backup ? formatDate(last_backup) : 'Never' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Create backup -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <ShieldCheck class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Create New Backup</p>
                        <p class="text-xs text-slate-500">Dumps the entire database to a SQL file stored on the server.</p>
                    </div>
                </div>
                <Button @click="createBackup" :disabled="creating"
                    class="flex-shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white gap-2">
                    <DatabaseBackup v-if="!creating" class="w-4 h-4" />
                    <span v-if="creating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    {{ creating ? 'Creating…' : 'Create Backup Now' }}
                </Button>
            </div>

            <!-- Backups table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Saved Backups</h2>
                    <span class="text-xs text-slate-400">{{ count }} file(s)</span>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow class="bg-slate-50">
                            <TableHead>Filename</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="backups.length === 0">
                            <TableCell colspan="4" class="text-center text-slate-400 py-10 text-sm">
                                No backups yet. Create your first backup above.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="b in backups" :key="b.filename" class="hover:bg-slate-50">
                            <TableCell class="font-mono text-xs text-slate-700">{{ b.filename }}</TableCell>
                            <TableCell class="text-sm text-slate-600">{{ formatDate(b.created_at) }}</TableCell>
                            <TableCell class="text-sm text-slate-600">{{ b.size_human }}</TableCell>
                            <TableCell>
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="download(b.filename)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                               bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                                        <Download class="w-3.5 h-3.5" /> Download
                                    </button>
                                    <button @click="openRestore(b)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                               bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                                        <RotateCcw class="w-3.5 h-3.5" /> Restore
                                    </button>
                                    <button @click="openDelete(b)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                               bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" /> Delete
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

        </div>

        <!-- ── Restore Confirm Dialog ────────────────────── -->
        <Dialog :open="!!restoreTarget" @update:open="restoreTarget = null">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-amber-700">
                        <RotateCcw class="w-5 h-5" /> Restore Database
                    </DialogTitle>
                    <DialogDescription>
                        This will <strong>overwrite all current data</strong> with the selected backup.
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 pt-2">
                    <div class="p-3 bg-slate-50 rounded-lg text-xs font-mono text-slate-600 break-all">
                        {{ restoreTarget?.filename }}
                    </div>
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700 flex items-start gap-2">
                        <AlertTriangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        <span>All data created <em>after</em> this backup was made will be permanently lost.</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Type <span class="font-mono bg-slate-100 px-1 rounded">RESTORE</span> to confirm
                        </label>
                        <input v-model="restoreConfirm" type="text" placeholder="RESTORE"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                            autocomplete="off" />
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <Button variant="outline" @click="restoreTarget = null">Cancel</Button>
                        <Button @click="confirmRestore" :disabled="!canRestore"
                            class="bg-amber-600 hover:bg-amber-700 text-white disabled:opacity-40">
                            <RotateCcw class="w-4 h-4 mr-1.5" /> Restore Now
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ── Delete Confirm Dialog ─────────────────────── -->
        <Dialog :open="!!deleteTarget" @update:open="deleteTarget = null">
            <DialogContent class="max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <Trash2 class="w-5 h-5" /> Delete Backup
                    </DialogTitle>
                    <DialogDescription>
                        This will permanently delete the backup file. You cannot undo this.
                    </DialogDescription>
                </DialogHeader>
                <div class="p-3 bg-slate-50 rounded-lg text-xs font-mono text-slate-600 break-all my-3">
                    {{ deleteTarget?.filename }}
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
                    <Button @click="confirmDelete" class="bg-red-600 hover:bg-red-700 text-white">
                        <Trash2 class="w-4 h-4 mr-1.5" /> Delete
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
