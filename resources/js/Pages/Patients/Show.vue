<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()

const props = defineProps({
    patient: Object,
})

function archivePatient() {
    if (!confirm(`Archive patient ${props.patient.full_name}? They will no longer appear in active lists.`)) return
    router.delete(route('patients.destroy', props.patient.id))
}

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
    follow_up:      'bg-amber-100 text-amber-700',
    lab_only:       'bg-teal-100 text-teal-700',
}

const visitStatusBadge = {
    pending:     'bg-slate-100 text-slate-600',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-emerald-100 text-emerald-700',
    cancelled:   'bg-red-100 text-red-600',
    no_show:     'bg-orange-100 text-orange-600',
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}
</script>

<template>
    <AppLayout :title="patient.full_name">

        <!-- Header -->
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">{{ patient.full_name }}</h1>
                            <span :class="[
                                'text-xs font-semibold px-2.5 py-0.5 rounded-full',
                                patient.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'
                            ]">
                                {{ patient.is_active ? 'Active' : 'Archived' }}
                            </span>
                        </div>
                        <p class="text-slate-400 text-xs mt-0.5">
                            {{ patient.patient_code }} · Registered {{ patient.created_at }} by {{ patient.registered_by }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <Link
                        v-if="can('patients.edit')"
                        :href="route('patients.edit', patient.id)">
                        <Button variant="outline" size="sm" class="gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </Button>
                    </Link>
                    <Button
                        v-if="can('patients.delete') && patient.is_active"
                        variant="destructive" size="sm"
                        class="gap-2" @click="archivePatient">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8m-9 4v6m4-6v6"/>
                        </svg>
                        Archive
                    </Button>
                </div>
            </div>
        </template>

        <div class="flex gap-5 items-start">

            <!-- ── LEFT PANEL ─────────────────────────── -->
            <div class="w-64 flex-shrink-0 space-y-4">

                <!-- Photo + Basic Info -->
                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <!-- Photo -->
                    <div class="aspect-square bg-muted flex items-center justify-center relative overflow-hidden">
                        <img v-if="patient.photo_path"
                            :src="`/storage/${patient.photo_path}`"
                            :alt="patient.full_name"
                            class="w-full h-full object-cover"/>
                        <div v-else class="flex flex-col items-center gap-2 text-muted-foreground">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold text-white"
                                style="background:#1B4F9B">
                                {{ patient.last_name.charAt(0) }}{{ patient.first_name.charAt(0) }}
                            </div>
                            <p class="text-xs opacity-50">No photo on file</p>
                        </div>
                        <!-- Visit type ribbon -->
                        <div class="absolute top-3 left-3">
                            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full shadow-sm',
                                visitTypeBadge[patient.visit_type_default]]">
                                {{ patient.visit_type_default === 'pre_employment' ? 'Pre-Employment' : 'OPD' }}
                            </span>
                        </div>
                    </div>

                    <!-- Key info -->
                    <div class="p-4 space-y-3">
                        <div class="text-center">
                            <p class="font-bold text-slate-800">{{ patient.full_name }}</p>
                            <p class="text-xs text-muted-foreground mt-0.5">{{ patient.age_sex }}</p>
                        </div>

                        <Separator/>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-muted-foreground">Patient Code</span>
                                <span class="text-xs font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ patient.patient_code }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-muted-foreground">Blood Type</span>
                                <span class="text-xs font-bold text-slate-700">{{ patient.blood_type || '—' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-muted-foreground">Civil Status</span>
                                <span class="text-xs font-semibold text-slate-700 capitalize">{{ patient.civil_status }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-muted-foreground">Total Visits</span>
                                <span class="text-xs font-bold text-slate-700">{{ patient.visits.length }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Quick Actions</p>
                    <div class="space-y-2">
                        <Button class="w-full justify-start gap-2 text-xs" size="sm"
                            style="background-color:#1B4F9B">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"/>
                            </svg>
                            New Visit
                        </Button>
                        <Button variant="outline" class="w-full justify-start gap-2 text-xs" size="sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            View Lab Results
                        </Button>
                        <Button variant="outline" class="w-full justify-start gap-2 text-xs" size="sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print Summary
                        </Button>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT PANEL ────────────────────────── -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Personal Details -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Personal Information</h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 lg:grid-cols-4 gap-4">

                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Full Name</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.full_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Date of Birth</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.date_of_birth }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Age / Sex</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.age_sex }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Civil Status</p>
                            <p class="text-sm font-semibold text-slate-800 capitalize">{{ patient.civil_status }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Blood Type</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.blood_type || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Occupation</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.occupation || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">PhilHealth No.</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.philhealth_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Default Visit</p>
                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', visitTypeBadge[patient.visit_type_default]]">
                                {{ patient.visit_type_default === 'pre_employment' ? 'Pre-Employment' : 'OPD' }}
                            </span>
                        </div>

                    </div>
                </div>

                <!-- Contact Details -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-sky-500"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Contact Information</h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 lg:grid-cols-3 gap-4">

                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Mobile Number</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.contact_number || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Email Address</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">PhilHealth No.</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.philhealth_number || '—' }}</p>
                        </div>
                        <div class="col-span-2 lg:col-span-3">
                            <p class="text-xs text-muted-foreground mb-0.5">Home Address</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.address || '—' }}</p>
                        </div>

                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-red-400"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Emergency Contact</h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Contact Person</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.emergency_contact_name || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-0.5">Contact Number</p>
                            <p class="text-sm font-semibold text-slate-800">{{ patient.emergency_contact_number || '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Visit History -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Visit History</h3>
                            <span class="text-xs text-muted-foreground">({{ patient.visits.length }} total)</span>
                        </div>
                        <Button size="sm" style="background-color:#1B4F9B" class="text-xs gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            New Visit
                        </Button>
                    </div>

                    <!-- Empty state -->
                    <div v-if="patient.visits.length === 0" class="py-12 text-center">
                        <svg class="w-10 h-10 text-slate-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-400">No visits yet</p>
                        <p class="text-xs text-slate-300 mt-1">Create a new visit to get started</p>
                    </div>

                    <!-- Visit table -->
                    <div v-else class="divide-y divide-border">
                        <div v-for="visit in patient.visits" :key="visit.id"
                            class="px-5 py-3.5 flex items-center justify-between hover:bg-muted/40 transition-colors">

                            <div class="flex items-center gap-3">
                                <!-- Visit type icon -->
                                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                                    visit.visit_type === 'pre_employment' ? 'bg-purple-100' : 'bg-blue-100']">
                                    <svg class="w-4 h-4"
                                        :class="visit.visit_type === 'pre_employment' ? 'text-purple-600' : 'text-blue-600'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ visitTypeLabel[visit.visit_type] ?? visit.visit_type }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ visit.visit_date }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', visitStatusBadge[visit.status]]">
                                    {{ visit.status.replace('_', ' ') }}
                                </span>
                                <Button variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:text-slate-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
