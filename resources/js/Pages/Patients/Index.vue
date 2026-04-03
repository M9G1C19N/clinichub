<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { usePermissions } from '@/composables/usePermissions'
import {
    UserPlus, Search, Eye, Pencil,
    Users, UserCheck, UserX,
} from 'lucide-vue-next'

const { can } = usePermissions()

const props = defineProps({
    patients: Object,
    filters:  Object,
    total:    Number,
})

const search    = ref(props.filters?.search     ?? '')
const sex       = ref(props.filters?.sex        ?? 'all')
const visitType = ref(props.filters?.visit_type ?? 'all')
const status    = ref(props.filters?.status     ?? 'all')

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})
watch([sex, visitType, status], () => applyFilters())

function applyFilters() {
    router.get(route('patients.index'), {
        search:     search.value     || undefined,
        sex:        sex.value       !== 'all' ? sex.value       : undefined,
        visit_type: visitType.value !== 'all' ? visitType.value : undefined,
        status:     status.value    !== 'all' ? status.value    : undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value    = ''
    sex.value       = 'all'
    visitType.value = 'all'
    status.value    = 'all'
    applyFilters()
}

const hasFilters = () =>
    search.value ||
    sex.value !== 'all' ||
    visitType.value !== 'all' ||
    status.value !== 'all'

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700 hover:bg-blue-100',
    pre_employment: 'bg-purple-100 text-purple-700 hover:bg-purple-100',
    annual_pe:      'bg-green-100 text-green-700 hover:bg-green-100',
    exit_pe:        'bg-orange-100 text-orange-700 hover:bg-orange-100',
    follow_up:      'bg-sky-100 text-sky-700 hover:bg-sky-100',
    lab_only:       'bg-violet-100 text-violet-700 hover:bg-violet-100',
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    annual_pe:      'Annual PE',
    exit_pe:        'Exit PE',
    follow_up:      'Follow-Up',
    lab_only:       'Lab Only',
}

</script>

<template>
    <AppLayout title="Patients">

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Patients</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        {{ total }} total registered patients
                    </p>
                </div>
                <Link v-if="can('patients.create')" :href="route('patients.create')">
                    <Button style="background-color:#1B4F9B" class="gap-2">
                        <UserPlus class="w-4 h-4"/>
                        Register Patient
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-card rounded-xl border shadow-sm p-4 mb-5">
            <div class="flex flex-wrap gap-3">

                <!-- Search -->
                <div class="flex-1 min-w-60 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                    <Input v-model="search"
                        placeholder="Search by name, code, contact, PhilHealth..."
                        class="pl-9"/>
                </div>

                <!-- Sex -->
                <Select v-model="sex">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All Genders"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Genders</SelectItem>
                        <SelectItem value="male">Male</SelectItem>
                        <SelectItem value="female">Female</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Visit Type -->
                <Select v-model="visitType">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All Types"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Types</SelectItem>
                        <SelectItem value="opd">OPD</SelectItem>
                        <SelectItem value="pre_employment">Pre-Employment</SelectItem>
                        <SelectItem value="annual_pe">Annual PE</SelectItem>
                        <SelectItem value="exit_pe">Exit PE</SelectItem>
                        <SelectItem value="follow_up">Follow-Up</SelectItem>
                        <SelectItem value="lab_only">Lab Only</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Status -->
                <Select v-model="status">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All Status"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Clear -->
                <Button v-if="hasFilters()" variant="outline" @click="clearFilters"
                    class="gap-1.5 text-red-500 hover:text-red-700 hover:border-red-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Clear
                </Button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">

            <!-- Empty state -->
            <div v-if="!patients.data.length" class="text-center py-16">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Users class="w-8 h-8 text-slate-300"/>
                </div>
                <p class="text-sm font-semibold text-slate-400">No patients found</p>
                <p class="text-xs text-slate-300 mt-1">
                    {{ hasFilters() ? 'Try adjusting your filters.' : 'Register your first patient to get started.' }}
                </p>
                <Link v-if="!hasFilters() && can('patients.create')"
                    :href="route('patients.create')">
                    <Button class="mt-4 gap-2" style="background-color:#1B4F9B">
                        <UserPlus class="w-4 h-4"/>
                        Register First Patient
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <table v-else class="w-full">
                <thead>
                    <tr style="background-color:#0F2044">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Code</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Age / Sex</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Contact</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Visits</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="patient in patients.data" :key="patient.id"
                        class="hover:bg-muted/30 transition-colors group">

                        <!-- Patient -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex-shrink-0 overflow-hidden flex items-center justify-center text-white font-bold text-sm uppercase"
                                    style="background-color:#1B4F9B">
                                    <img v-if="patient.photo_path"
                                        :src="`/storage/${patient.photo_path}`"
                                        :alt="patient.full_name"
                                        class="w-full h-full object-cover"/>
                                    <span v-else>{{ patient.last_name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ patient.full_name }}</p>
                                    <p class="text-xs text-muted-foreground">Registered {{ patient.created_at }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Code -->
                        <td class="px-4 py-4">
                            <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg">
                                {{ patient.patient_code }}
                            </span>
                        </td>

                        <!-- Age/Sex -->
                        <td class="px-4 py-4">
                            <span class="text-sm text-slate-700">{{ patient.age_sex }}</span>
                        </td>

                        <!-- Contact -->
                        <td class="px-4 py-4">
                            <span class="text-sm text-slate-600">{{ patient.contact_number ?? '—' }}</span>
                        </td>

                        <!-- Visit Type -->
                        <td class="px-4 py-4">
                            <Badge :class="visitTypeBadge[patient.visit_type_default]">
                                {{ visitTypeLabel[patient.visit_type_default] }}
                            </Badge>
                        </td>

                        <!-- Visits -->
                        <td class="px-4 py-4">
                            <span class="text-sm font-semibold text-slate-700">
                                {{ patient.visits_count }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-1.5">
                                <component
                                    :is="patient.is_active ? UserCheck : UserX"
                                    :class="['w-3.5 h-3.5', patient.is_active ? 'text-emerald-500' : 'text-red-400']"/>
                                <span :class="['text-xs font-semibold',
                                    patient.is_active ? 'text-emerald-700' : 'text-red-600']">
                                    {{ patient.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Link :href="route('patients.show', patient.id)">
                                    <Button variant="ghost" size="sm"
                                        class="h-8 w-8 p-0 text-slate-400 hover:text-blue-600 hover:bg-blue-50"
                                        title="View Profile">
                                        <Eye class="w-4 h-4"/>
                                    </Button>
                                </Link>
                                <Link v-if="can('patients.edit')"
                                    :href="route('patients.edit', patient.id)">
                                    <Button variant="ghost" size="sm"
                                        class="h-8 w-8 p-0 text-slate-400 hover:text-amber-600 hover:bg-amber-50"
                                        title="Edit">
                                        <Pencil class="w-4 h-4"/>
                                    </Button>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="patients.data.length > 0"
                class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing {{ patients.from }}–{{ patients.to }} of {{ patients.total }} patients
                </p>
                <div class="flex items-center gap-1">
                    <template v-for="link in patients.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            :class="['px-3 py-1.5 text-sm rounded-lg transition-colors',
                                link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100']"
                            :style="link.active ? 'background-color:#1B4F9B' : ''"
                            v-html="link.label"/>
                        <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
