<script setup>
import { ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'

const { can } = usePermissions()

const props = defineProps({
    patients: Object,
    filters: Object,
    total: Number,
})

// Search & filter state — pre-filled from server
const search     = ref(props.filters?.search ?? '')
const sex        = ref(props.filters?.sex ?? '')
const visitType  = ref(props.filters?.visit_type ?? '')
const status     = ref(props.filters?.status ?? '')

// Debounced search — wait 400ms before hitting server
let searchTimer = null
watch(search, (val) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})

watch([sex, visitType, status], () => applyFilters())

function applyFilters() {
    router.get(route('patients.index'), {
        search:     search.value || undefined,
        sex:        sex.value || undefined,
        visit_type: visitType.value || undefined,
        status:     status.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function clearFilters() {
    search.value    = ''
    sex.value       = ''
    visitType.value = ''
    status.value    = ''
    applyFilters()
}

const hasFilters = () => search.value || sex.value || visitType.value || status.value

// Status badge styles
const statusBadge = {
    true:  'bg-emerald-100 text-emerald-700',
    false: 'bg-red-100 text-red-600',
}

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
}
</script>

<template>
    <AppLayout title="Patients">

        <!-- Header -->
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Patients</h1>
                    <p class="text-slate-500 text-sm mt-0.5">
                        {{ total }} total registered patients
                    </p>
                </div>
                <Link
                    v-if="can('patients.create')"
                    :href="route('patients.create')"
                    class="inline-flex items-center gap-2 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                    style="background-color: #1B4F9B;"
                    onmouseover="this.style.backgroundColor='#0F2044'"
                    onmouseout="this.style.backgroundColor='#1B4F9B'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Register Patient
                </Link>
            </div>
        </template>

        <!-- Filters Bar -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5">
            <div class="flex flex-wrap gap-3">

                <!-- Search -->
                <div class="flex-1 min-w-60 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name, code, contact, PhilHealth..."
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <!-- Sex filter -->
                <select
                    v-model="sex"
                    class="px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-600"
                >
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>

                <!-- Visit type filter -->
                <select
                    v-model="visitType"
                    class="px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-600"
                >
                    <option value="">All Types</option>
                    <option value="opd">OPD</option>
                    <option value="pre_employment">Pre-Employment</option>
                </select>

                <!-- Status filter -->
                <select
                    v-model="status"
                    class="px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-600"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <!-- Clear filters -->
                <button
                    v-if="hasFilters()"
                    @click="clearFilters"
                    class="px-3 py-2.5 text-sm text-slate-500 hover:text-red-600 border border-slate-300 hover:border-red-300 rounded-xl transition-colors flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Clear
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            <!-- Empty state -->
            <div v-if="patients.data.length === 0" class="text-center py-16">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-slate-500 font-medium">No patients found</p>
                <p class="text-slate-400 text-sm mt-1">
                    {{ hasFilters() ? 'Try adjusting your filters.' : 'Register your first patient to get started.' }}
                </p>
                <Link
                    v-if="!hasFilters() && can('patients.create')"
                    :href="route('patients.create')"
                    class="mt-4 inline-flex items-center gap-2 text-white px-4 py-2 rounded-xl text-sm font-medium"
                    style="background-color:#1B4F9B"
                >
                    Register First Patient
                </Link>
            </div>

            <!-- Data table -->
            <table v-else class="w-full">
                <thead>
                    <tr style="background-color:#0F2044">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Patient
                        </th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Code
                        </th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Age / Sex
                        </th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Contact
                        </th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Visits
                        </th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="patient in patients.data" :key="patient.id"
                        class="hover:bg-slate-50 transition-colors group">

                        <!-- Patient name + photo -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <!-- Avatar -->
                                <div class="w-9 h-9 rounded-xl flex-shrink-0 overflow-hidden bg-slate-200 flex items-center justify-center">
                                    <img v-if="patient.photo_path"
                                        :src="`/storage/${patient.photo_path}`"
                                        :alt="patient.full_name"
                                        class="w-full h-full object-cover"
                                    />
                                    <span v-else class="text-sm font-semibold text-slate-500 uppercase">
                                        {{ patient.last_name.charAt(0) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ patient.full_name }}</p>
                                    <p class="text-xs text-slate-400">Registered {{ patient.created_at }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Code -->
                        <td class="px-4 py-4">
                            <span class="text-xs font-mono font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg">
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

                        <!-- Visit type -->
                        <td class="px-4 py-4">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', visitTypeBadge[patient.visit_type_default]]">
                                {{ patient.visit_type_default === 'opd' ? 'OPD' : 'Pre-Employment' }}
                            </span>
                        </td>

                        <!-- Visits count -->
                        <td class="px-4 py-4">
                            <span class="text-sm font-semibold text-slate-700">{{ patient.visits_count }}</span>
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-4">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', statusBadge[patient.is_active]]">
                                {{ patient.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- View -->
                                <Link :href="route('patients.show', patient.id)"
                                    class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="View Profile">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </Link>
                                <!-- Edit -->
                                <Link v-if="can('patients.edit')"
                                    :href="route('patients.edit', patient.id)"
                                    class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
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
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-state
                            :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors',
                                link.active
                                    ? 'text-white font-semibold'
                                    : 'text-slate-500 hover:bg-slate-100'
                            ]"
                            :style="link.active ? 'background-color:#1B4F9B' : ''"
                            v-html="link.label"
                        />
                        <span v-else
                            class="px-3 py-1.5 text-sm text-slate-300"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
