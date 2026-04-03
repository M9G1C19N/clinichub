<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Calendar, Search, CheckCircle2, XCircle, Clock,
    User, Phone, Mail, Stethoscope, FlaskConical, ScanLine,
    TestTube, ShieldCheck, Baby, HelpCircle, ChevronRight,
    AlertTriangle, RefreshCw, ExternalLink, ClipboardList,
    MoreHorizontal, FileText,
} from 'lucide-vue-next'

const props = defineProps({
    appointments: Object,
    summary:      Object,
    filters:      Object,
})

// ── Filters ───────────────────────────────────────────────────────────────
const search  = ref(props.filters.search  ?? '')
const status  = ref(props.filters.status  ?? '')
const date    = ref(props.filters.date    ?? '')
const service = ref(props.filters.service ?? '')

function doSearch() {
    router.get(route('appointments.index'), {
        search: search.value, status: status.value,
        date: date.value, service: service.value,
    }, { preserveState: true, replace: true })
}
function clearFilters() {
    search.value = ''; status.value = ''; date.value = ''; service.value = ''
    doSearch()
}

// ── Service config ────────────────────────────────────────────────────────
const serviceIcon = {
    general_consultation: Stethoscope,
    physical_exam:        ShieldCheck,
    laboratory:           FlaskConical,
    xray_utz:             ScanLine,
    drug_test:            TestTube,
    prenatal:             Baby,
    other:                HelpCircle,
}
const serviceColor = {
    general_consultation: 'text-purple-600',
    physical_exam:        'text-blue-600',
    laboratory:           'text-amber-600',
    xray_utz:             'text-orange-600',
    drug_test:            'text-rose-600',
    prenatal:             'text-pink-600',
    other:                'text-slate-500',
}
const serviceBg = {
    general_consultation: 'bg-purple-100',
    physical_exam:        'bg-blue-100',
    laboratory:           'bg-amber-100',
    xray_utz:             'bg-orange-100',
    drug_test:            'bg-rose-100',
    prenatal:             'bg-pink-100',
    other:                'bg-slate-100',
}

// ── Status config ─────────────────────────────────────────────────────────
const statusConfig = {
    pending:   { label: 'Pending',   class: 'bg-amber-100 text-amber-700'     },
    confirmed: { label: 'Confirmed', class: 'bg-blue-100 text-blue-700'       },
    completed: { label: 'Completed', class: 'bg-emerald-100 text-emerald-700' },
    no_show:   { label: 'No-Show',   class: 'bg-slate-100 text-slate-500'     },
    cancelled: { label: 'Cancelled', class: 'bg-red-100 text-red-500'         },
}

// ── Actions ───────────────────────────────────────────────────────────────
const activeAppt = ref(null) // expanded row
const cancelModal = ref(null) // appointment being cancelled
const notesModal  = ref(null)

const cancelForm = useForm({ cancellation_reason: '' })
const notesForm  = useForm({ admin_notes: '' })

function toggleExpand(appt) {
    activeAppt.value = activeAppt.value?.id === appt.id ? null : appt
}

function confirm(appt) {
    router.post(route('appointments.confirm', appt.id), {}, {
        preserveScroll: true,
    })
}

function openCancel(appt) {
    cancelForm.reset()
    cancelModal.value = appt
}
function submitCancel() {
    cancelForm.post(route('appointments.cancel', cancelModal.value.id), {
        preserveScroll: true,
        onSuccess: () => { cancelModal.value = null },
    })
}

function complete(appt) {
    router.post(route('appointments.complete', appt.id), {}, { preserveScroll: true })
}
function noShow(appt) {
    router.post(route('appointments.no-show', appt.id), {}, { preserveScroll: true })
}

function openNotes(appt) {
    notesForm.admin_notes = appt.admin_notes ?? ''
    notesModal.value = appt
}
function submitNotes() {
    notesForm.post(route('appointments.notes', notesModal.value.id), {
        preserveScroll: true,
        onSuccess: () => { notesModal.value = null },
    })
}

const today = new Date().toISOString().split('T')[0]
</script>

<template>
    <AppLayout title="Appointments">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Appointments</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Manage online booking requests from patients</p>
                </div>
                <a href="/book-appointment" target="_blank"
                    class="flex items-center gap-1.5 text-xs font-semibold text-white px-3 py-2 rounded-xl transition-opacity hover:opacity-90"
                    style="background:#1B4F9B">
                    <ExternalLink class="w-3.5 h-3.5"/> Booking Page
                </a>
            </div>
        </template>

        <!-- ── Summary Cards ────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="status = 'pending'; doSearch()">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                    <Clock class="w-5 h-5 text-amber-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Pending</p>
                    <p class="text-2xl font-black text-amber-600">{{ summary.pending }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="status = 'confirmed'; doSearch()">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                    <CheckCircle2 class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Confirmed</p>
                    <p class="text-2xl font-black text-blue-600">{{ summary.confirmed }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="date = today; doSearch()">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                    <Calendar class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Today</p>
                    <p class="text-2xl font-black text-emerald-700">{{ summary.today }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                    <ClipboardList class="w-5 h-5 text-slate-500"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">This Week</p>
                    <p class="text-2xl font-black text-slate-700">{{ summary.this_week }}</p>
                </div>
            </div>
        </div>

        <!-- ── Filters ────────────────────────────────── -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                <input v-model="search" @keyup.enter="doSearch"
                    placeholder="Name, phone, email, or ref number..."
                    class="w-full h-9 pl-9 pr-4 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <select v-model="status" @change="doSearch"
                class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="no_show">No-Show</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select v-model="service" @change="doSearch"
                class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Services</option>
                <option value="general_consultation">General Consultation</option>
                <option value="physical_exam">Physical Exam</option>
                <option value="laboratory">Laboratory</option>
                <option value="xray_utz">X-Ray / UTZ</option>
                <option value="drug_test">Drug Test</option>
                <option value="prenatal">Prenatal</option>
                <option value="other">Other</option>
            </select>
            <input v-model="date" type="date" @change="doSearch"
                class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            <button v-if="search || status || date || service"
                @click="clearFilters"
                class="h-9 px-3 text-xs font-semibold border border-slate-200 rounded-xl text-slate-600 hover:border-slate-300 transition-colors">
                Clear
            </button>
        </div>

        <!-- ── Table ──────────────────────────────────── -->
        <div v-if="!appointments.data?.length"
            class="bg-card rounded-xl border shadow-sm py-20 text-center">
            <Calendar class="w-12 h-12 text-slate-200 mx-auto mb-3"/>
            <p class="text-slate-500 font-semibold">No appointments found</p>
            <p class="text-slate-400 text-sm mt-1">Try adjusting your filters or check the booking page</p>
        </div>

        <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr style="background:#0F2044">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Ref / Date</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Service</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Schedule</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template v-for="appt in appointments.data" :key="appt.id">
                        <!-- Main row -->
                        <tr class="hover:bg-slate-50 transition-colors group"
                            :class="{ 'bg-amber-50/50': appt.status === 'pending' }">

                            <!-- Ref + submitted -->
                            <td class="px-5 py-3.5">
                                <p class="font-mono font-bold text-blue-700 text-xs">{{ appt.appointment_number }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Submitted {{ appt.created_at }}</p>
                            </td>

                            <!-- Patient -->
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-slate-800 text-sm">{{ appt.patient_name }}</p>
                                <p class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                                    <Phone class="w-3 h-3"/> {{ appt.patient_phone }}
                                </p>
                                <p v-if="appt.patient_email" class="text-xs text-slate-400 flex items-center gap-1">
                                    <Mail class="w-3 h-3"/> {{ appt.patient_email }}
                                </p>
                            </td>

                            <!-- Service -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        :class="serviceBg[appt.service_type]">
                                        <component :is="serviceIcon[appt.service_type] ?? HelpCircle"
                                            class="w-3.5 h-3.5" :class="serviceColor[appt.service_type]"/>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700">{{ appt.service_label }}</span>
                                </div>
                            </td>

                            <!-- Schedule -->
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-slate-800 text-sm">{{ appt.preferred_date }}</p>
                                <p class="text-xs text-slate-400 capitalize">{{ appt.preferred_time ?? 'Any time' }}</p>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3.5">
                                <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', statusConfig[appt.status]?.class]">
                                    {{ statusConfig[appt.status]?.label }}
                                </span>
                                <p v-if="appt.status === 'confirmed' && appt.confirmed_by"
                                    class="text-xs text-slate-400 mt-0.5">by {{ appt.confirmed_by }}</p>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Expand / details -->
                                    <button @click="toggleExpand(appt)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Details">
                                        <MoreHorizontal class="w-4 h-4"/>
                                    </button>

                                    <!-- Confirm -->
                                    <button v-if="appt.status === 'pending'"
                                        @click="confirm(appt)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        title="Confirm appointment">
                                        <CheckCircle2 class="w-4 h-4"/>
                                    </button>

                                    <!-- Complete -->
                                    <button v-if="['pending','confirmed'].includes(appt.status)"
                                        @click="complete(appt)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Mark as completed">
                                        <Calendar class="w-4 h-4"/>
                                    </button>

                                    <!-- No-show -->
                                    <button v-if="['pending','confirmed'].includes(appt.status)"
                                        @click="noShow(appt)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                                        title="Mark as no-show">
                                        <AlertTriangle class="w-4 h-4"/>
                                    </button>

                                    <!-- Cancel -->
                                    <button v-if="!['completed','cancelled'].includes(appt.status)"
                                        @click="openCancel(appt)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                        title="Cancel appointment">
                                        <XCircle class="w-4 h-4"/>
                                    </button>

                                    <!-- Notes -->
                                    <button @click="openNotes(appt)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-purple-600 hover:bg-purple-50 transition-colors"
                                        title="Admin notes">
                                        <FileText class="w-4 h-4"/>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Expanded detail row -->
                        <tr v-if="activeAppt?.id === appt.id">
                            <td colspan="6" class="px-5 py-4 bg-slate-50 border-t border-b border-dashed border-slate-200">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Chief Complaint</p>
                                        <p class="text-sm text-slate-700">{{ appt.chief_complaint || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Confirmed At</p>
                                        <p class="text-sm text-slate-700">{{ appt.confirmed_at || '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase mb-1">Admin Notes</p>
                                        <p class="text-sm text-slate-700">{{ appt.admin_notes || '—' }}</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-5 py-3 border-t flex items-center justify-between text-xs text-slate-500">
                <span>{{ appointments.from }}–{{ appointments.to }} of {{ appointments.total }}</span>
                <div class="flex items-center gap-1">
                    <template v-for="link in appointments.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            class="px-2.5 py-1 rounded border text-xs transition-colors"
                            :class="link.active ? 'text-white border-transparent' : 'border-slate-200 hover:border-slate-300'"
                            :style="link.active ? 'background:#1B4F9B' : ''"
                            v-html="link.label"/>
                        <span v-else class="px-2 py-1 text-slate-300" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Cancel Modal ───────────────────────────── -->
        <div v-if="cancelModal"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="cancelModal = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-black text-slate-800 mb-1">Cancel Appointment</h3>
                    <p class="text-sm text-slate-500 mb-4">
                        Cancel booking for <strong>{{ cancelModal.patient_name }}</strong>?
                    </p>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        Reason <span class="font-normal text-slate-400 text-xs">(optional)</span>
                    </label>
                    <textarea v-model="cancelForm.cancellation_reason"
                        rows="3" placeholder="e.g. Doctor unavailable, patient requested..."
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"/>
                </div>
                <div class="px-6 pb-5 flex gap-3">
                    <button @click="cancelModal = null"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-slate-300 transition-colors">
                        Back
                    </button>
                    <button @click="submitCancel"
                        :disabled="cancelForm.processing"
                        class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700 transition-colors disabled:opacity-60">
                        Cancel Appointment
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Notes Modal ────────────────────────────── -->
        <div v-if="notesModal"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="notesModal = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-black text-slate-800 mb-1">Admin Notes</h3>
                    <p class="text-sm text-slate-500 mb-4">{{ notesModal.patient_name }} &bull; {{ notesModal.appointment_number }}</p>
                    <textarea v-model="notesForm.admin_notes"
                        rows="4" placeholder="Internal notes about this appointment..."
                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"/>
                </div>
                <div class="px-6 pb-5 flex gap-3">
                    <button @click="notesModal = null"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-slate-300 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitNotes"
                        :disabled="notesForm.processing"
                        class="flex-1 py-2.5 rounded-xl text-white text-sm font-bold hover:opacity-90 transition-opacity disabled:opacity-60"
                        style="background:#1B4F9B">
                        Save Notes
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
