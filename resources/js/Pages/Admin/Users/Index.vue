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
import {
    UserPlus, Search, Shield, ShieldOff,
    Pencil, KeyRound, Users, ToggleLeft, ToggleRight,
} from 'lucide-vue-next'

const props = defineProps({
    users:   { type: Object, default: () => ({ data: [], links: [], from: 0, to: 0, total: 0 }) },
    roles:   { type: Array,  default: () => [] },
    filters: { type: Object, default: () => ({}) },
    total:   { type: Number, default: 0 },
})

const search     = ref(props.filters?.search     ?? '')
const role       = ref(props.filters?.role       ?? 'all')
const status     = ref(props.filters?.status     ?? 'all')
const department = ref(props.filters?.department ?? 'all')

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})
watch([role, status, department], () => applyFilters())

function applyFilters() {
    router.get(route('admin.users.index'), {
        search:     search.value || undefined,
        role:       (role.value && role.value !== 'all')       ? role.value       : undefined,
        status:     (status.value && status.value !== 'all')   ? status.value     : undefined,
        department: (department.value && department.value !== 'all') ? department.value : undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value     = ''
    role.value       = 'all'
    status.value     = 'all'
    department.value = 'all'
    applyFilters()
}

function toggleActive(userId) {
    router.patch(route('admin.users.toggle-active', userId), {}, { preserveScroll: true })
}

function resetPassword(userId) {
    const pwd = prompt('Enter new password for this staff member (min 8 chars, must have uppercase + number):')
    if (!pwd) return
    if (pwd.length < 8) {
        alert('Password must be at least 8 characters.')
        return
    }
    router.post(route('admin.users.reset-password', userId), { password: pwd }, { preserveScroll: true })
}

const roleBadge = {
    admin:           'bg-sky-100 text-sky-700 border-sky-200',
    receptionist:    'bg-blue-100 text-blue-700 border-blue-200',
    nurse:           'bg-emerald-100 text-emerald-700 border-emerald-200',
    doctor:          'bg-purple-100 text-purple-700 border-purple-200',
    lab_technician:  'bg-amber-100 text-amber-700 border-amber-200',
    xray_tech:       'bg-orange-100 text-orange-700 border-orange-200',
    drug_test_staff: 'bg-rose-100 text-rose-700 border-rose-200',
}

const roleLabel = {
    admin:           'Admin',
    receptionist:    'Receptionist',
    nurse:           'Nurse',
    doctor:          'Doctor',
    lab_technician:  'Lab Tech',
    xray_tech:       'X-Ray Tech',
    drug_test_staff: 'Drug Test',
}

const departments = [
    { value: 'laboratory',  label: 'Laboratory' },
    { value: 'xray_utz',   label: 'X-Ray & UTZ' },
    { value: 'drug_test',  label: 'Drug Test' },
    { value: 'reception',  label: 'Reception' },
    { value: 'nursing',    label: 'Nursing' },
    { value: 'admin',      label: 'Admin' },
    { value: 'doctor',     label: 'Doctor' },
]
</script>

<template>
    <AppLayout title="Users & Staff">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Users & Staff</h1>
                    <p class="text-slate-400 text-xs mt-0.5">{{ total }} total staff accounts</p>
                </div>
                <Link :href="route('admin.users.create')">
                    <Button style="background-color:#1B4F9B" class="gap-2">
                        <UserPlus class="w-4 h-4" />
                        Add Staff
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-card rounded-xl border shadow-sm p-4 mb-5">
            <div class="flex flex-wrap gap-3">

                <!-- Search -->
                <div class="flex-1 min-w-52 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                    <Input v-model="search" placeholder="Search name, email, employee ID..." class="pl-9"/>
                </div>

                <!-- Role filter -->
                <Select v-model="role" @update:modelValue="applyFilters">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All Roles"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Roles</SelectItem>
                        <SelectItem v-for="r in roles" :key="r" :value="r">
                            {{ roleLabel[r] ?? r }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Department filter -->
                <Select v-model="department" @update:modelValue="applyFilters">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All Departments"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Departments</SelectItem>
                        <SelectItem v-for="d in departments" :key="d.value" :value="d.value">
                            {{ d.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Status filter -->
                <Select v-model="status" @update:modelValue="applyFilters">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All Status"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                </Select>

                <Button v-if="search || (role && role !== 'all') || (status && status !== 'all') || (department && department !== 'all')"
                    variant="outline" @click="clearFilters"
                    class="gap-1.5 text-red-500 hover:text-red-700 hover:border-red-300">
                    Clear
                </Button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">

            <!-- Empty -->
            <div v-if="!users?.data?.length" class="py-16 text-center">
                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <Users class="w-7 h-7 text-slate-300"/>
                </div>
                <p class="text-sm font-medium text-slate-400">No staff accounts found</p>
            </div>

           <table v-else-if="users?.data?.length" class="w-full">
                <thead>
                    <tr style="background-color:#0F2044">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Staff</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Role</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Department</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Employee ID</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                   <tr v-for="user in users.data" :key="user.id"
                        class="hover:bg-slate-50 transition-colors group"
                        :class="!user.is_active ? 'opacity-60' : ''">

                        <!-- Staff name + email -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex-shrink-0 overflow-hidden flex items-center justify-center text-white font-bold text-sm uppercase"
                                    :style="{ background: user.is_active ? '#1B4F9B' : '#94A3B8' }">
                                    <img v-if="user.photo_path"
                                        :src="`/storage/${user.photo_path}`"
                                        class="w-full h-full object-cover"/>
                                    <span v-else>{{ user.name.charAt(0) }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-sm font-semibold text-slate-800">{{ user.name }}</p>
                                        <span v-if="user.must_change_password"
                                            class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">
                                            Must change pwd
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- Role badge -->
                        <td class="px-4 py-4">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full border', roleBadge[user.role] ?? 'bg-slate-100 text-slate-600 border-slate-200']">
                                {{ roleLabel[user.role] ?? user.role ?? '—' }}
                            </span>
                        </td>

                        <!-- Department -->
                        <td class="px-4 py-4">
                            <span class="text-sm text-slate-600 capitalize">
                                {{ user.department?.replace('_', ' ') ?? '—' }}
                            </span>
                        </td>

                        <!-- Employee ID -->
                        <td class="px-4 py-4">
                            <span class="text-xs font-mono font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg">
                                {{ user.employee_id ?? '—' }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-4">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                                user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600']">
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-1.5">

                                <!-- Edit -->
                                <Link :href="route('admin.users.edit', user.id)">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-slate-400 hover:text-blue-600 hover:bg-blue-50">
                                        <Pencil class="w-3.5 h-3.5"/>
                                    </Button>
                                </Link>

                                <!-- Reset Password -->
                                <Button variant="ghost" size="sm"
                                    class="h-8 w-8 p-0 text-slate-400 hover:text-amber-600 hover:bg-amber-50"
                                    @click="resetPassword(user.id)"
                                    title="Reset Password">
                                    <KeyRound class="w-3.5 h-3.5"/>
                                </Button>

                                <!-- Toggle Active -->
                                <Button variant="ghost" size="sm"
                                    class="h-8 w-8 p-0"
                                    :class="user.is_active
                                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50'
                                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50'"
                                    @click="toggleActive(user.id)"
                                    :title="user.is_active ? 'Deactivate' : 'Activate'">
                                    <ToggleRight v-if="user.is_active" class="w-4 h-4"/>
                                    <ToggleLeft v-else class="w-4 h-4"/>
                                </Button>

                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="users?.data?.length > 0"
                class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing {{ users.from }}–{{ users.to }} of {{ users.total }} staff
                </p>
                <div class="flex items-center gap-1">
                    <template v-for="link in users.links" :key="link.label">
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
