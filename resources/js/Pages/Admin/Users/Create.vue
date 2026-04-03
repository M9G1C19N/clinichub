<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Eye, EyeOff, UserPlus, Info } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps({ roles: Array })

const form = useForm({
    name:           '',
    email:          '',
    employee_id:    '',
    role:           '',
    department:     '',
    specialization: '',
    prc_number:     '',
    ptr_number:     '',
    password:       '',
    extra_permissions: [],
})

const showPassword = ref(false)

// Auto-set department based on role
const roleDefaults = {
    admin:           { department: 'admin' },
    receptionist:    { department: 'reception' },
    nurse:           { department: 'nursing' },
    doctor:          { department: 'doctor' },
    lab_technician:  { department: 'laboratory' },
    xray_tech:       { department: 'xray_utz' },
    drug_test_staff: { department: 'drug_test' },
    billing:         { department: 'billing' },
}

function onRoleChange(val) {
    form.role = val
    if (roleDefaults[val]) {
        form.department = roleDefaults[val].department
    }
}

// Show doctor fields only for doctor/nurse roles
const showMedicalFields = computed(() =>
    ['doctor', 'nurse', 'lab_technician', 'xray_tech'].includes(form.role)
)

const roleLabel = {
    admin:           'System Administrator',
    receptionist:    'Receptionist',
    nurse:           'Nurse',
    doctor:          'Doctor / Physician',
    lab_technician:  'Laboratory Technician',
    xray_tech:       'X-Ray / UTZ Technician',
    drug_test_staff: 'Drug Test Staff',
    billing:         'Billing Staff',
}

const departments = [
    { value: 'laboratory', label: 'Laboratory' },
    { value: 'xray_utz',  label: 'X-Ray & UTZ' },
    { value: 'drug_test', label: 'Drug Test' },
    { value: 'reception', label: 'Reception' },
    { value: 'nursing',   label: 'Nursing' },
    { value: 'admin',     label: 'Admin' },
    { value: 'doctor',    label: 'Doctor' },
    { value: 'billing',   label: 'Billing' },
]

function togglePermission(perm) {
    const idx = form.extra_permissions.indexOf(perm)
    idx === -1
        ? form.extra_permissions.push(perm)
        : form.extra_permissions.splice(idx, 1)
}

function submit() {
    form.post(route('admin.users.store'))
}
</script>

<template>
    <AppLayout title="Add Staff">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.users.index')">
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Add Staff Account</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Staff will be required to change password on first login</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-5 items-start">

                <!-- LEFT — Role & Info -->
                <div class="w-56 flex-shrink-0 space-y-4">

                    <!-- Role Selector -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Role</p>
                        <div class="space-y-1.5">
                            <button v-for="r in roles" :key="r"
                                type="button"
                                @click="onRoleChange(r)"
                                :class="[
                                    'w-full flex items-center gap-2.5 p-2.5 rounded-lg border-2 transition-all text-left',
                                    form.role === r
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-border hover:border-slate-300 hover:bg-muted/50'
                                ]">
                                <div :class="['w-3.5 h-3.5 rounded-full border-2 flex-shrink-0 flex items-center justify-center',
                                    form.role === r ? 'border-blue-500' : 'border-muted-foreground/30']">
                                    <div v-if="form.role === r" class="w-2 h-2 rounded-full bg-blue-500"/>
                                </div>
                                <span :class="['text-xs font-semibold',
                                    form.role === r ? 'text-blue-700' : 'text-foreground']">
                                    {{ roleLabel[r] ?? r }}
                                </span>
                            </button>
                        </div>
                        <p v-if="form.errors.role" class="mt-2 text-xs text-red-500">{{ form.errors.role }}</p>
                    </div>

                    <!-- Info card -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <div class="flex items-start gap-2">
                            <Info class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"/>
                            <div>
                                <p class="text-xs font-bold text-amber-700 mb-1">First Login</p>
                                <p class="text-xs text-amber-600">Staff will be prompted to change their password on first login.</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT — Form Fields -->
                <div class="flex-1 min-w-0 space-y-4">

                    <!-- Account Info -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Account Information</h3>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">

                            <div class="col-span-2 space-y-1.5">
                                <Label>Full Name <span class="text-red-500">*</span></Label>
                                <Input v-model="form.name" placeholder="e.g. Juan Dela Cruz"
                                    :class="form.errors.name ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Email Address <span class="text-red-500">*</span></Label>
                                <Input v-model="form.email" type="email" placeholder="staff@clinichub.local"
                                    :class="form.errors.email ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Employee ID</Label>
                                <Input v-model="form.employee_id" placeholder="e.g. EMP-001"
                                    :class="form.errors.employee_id ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.employee_id" class="text-xs text-red-500">{{ form.errors.employee_id }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Department</Label>
                                <Select v-model="form.department">
                                    <SelectTrigger><SelectValue placeholder="Select department"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="d in departments" :key="d.value" :value="d.value">
                                            {{ d.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Password -->
                            <div class="space-y-1.5">
                                <Label>Initial Password <span class="text-red-500">*</span></Label>
                                <div class="relative">
                                    <Input v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Min 8 chars, uppercase + number"
                                        class="pr-10"
                                        :class="form.errors.password ? 'border-red-400' : ''"/>
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <Eye v-if="!showPassword" class="w-4 h-4"/>
                                        <EyeOff v-else class="w-4 h-4"/>
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                            </div>

                        </div>
                    </div>

                    <!-- Medical/Professional Fields — shown for clinical roles -->
                    <div v-if="showMedicalFields" class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-purple-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Professional Details</h3>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <Label>Specialization</Label>
                                <Input v-model="form.specialization" placeholder="e.g. General Practitioner"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label>PRC License No.</Label>
                                <Input v-model="form.prc_number" placeholder="0000000"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label>PTR No.</Label>
                                <Input v-model="form.ptr_number" placeholder="0000000"/>
                            </div>
                        </div>
                    </div>
                    <!-- Extra Permissions — shown for nurse/doctor roles -->
                    <div v-if="form.role === 'nurse' || form.role === 'doctor'"
                        class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-amber-400"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Extended Access</h3>
                            <span class="text-xs text-muted-foreground">(Optional)</span>
                        </div>
                        <div class="p-5 space-y-3">
                            <p class="text-xs text-muted-foreground">
                                Grant this account additional features beyond their base role.
                            </p>

                            <!-- Grant doctor features to nurse -->
                            <label v-if="form.role === 'nurse'"
                                class="flex items-start gap-3 cursor-pointer p-3 rounded-xl border-2 transition-all"
                                :class="form.extra_permissions.includes('doctor_features')
                                    ? 'border-purple-400 bg-purple-50'
                                    : 'border-border hover:border-slate-300'">
                                <input type="checkbox"
                                    :checked="form.extra_permissions.includes('doctor_features')"
                                    @change="togglePermission('doctor_features')"
                                    class="mt-0.5 w-4 h-4 rounded accent-purple-600"/>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Full Doctor Access</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Allows this nurse to write SOAP notes, diagnoses, ICD-10 codes,
                                        pre-employment classification, and prescriptions.
                                    </p>
                                </div>
                            </label>

                            <!-- Grant nurse features to doctor -->
                            <label v-if="form.role === 'doctor'"
                                class="flex items-start gap-3 cursor-pointer p-3 rounded-xl border-2 transition-all"
                                :class="form.extra_permissions.includes('nurse_features')
                                    ? 'border-emerald-400 bg-emerald-50'
                                    : 'border-border hover:border-slate-300'">
                                <input type="checkbox"
                                    :checked="form.extra_permissions.includes('nurse_features')"
                                    @change="togglePermission('nurse_features')"
                                    class="mt-0.5 w-4 h-4 rounded accent-emerald-600"/>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Nurse Intake Access</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Allows this doctor to record patient vitals in the nurse intake workflow.
                                        (Doctors already have this by default in ClinicHub.)
                                    </p>
                                </div>
                            </label>

                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="bg-card rounded-xl border shadow-sm px-5 py-3.5 flex items-center justify-between">
                        <p class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <Info class="w-3.5 h-3.5 opacity-40"/>
                            Staff must change their password on first login
                        </p>
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.users.index')">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing" style="background-color:#1B4F9B" class="gap-2">
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <UserPlus v-else class="w-4 h-4"/>
                                {{ form.processing ? 'Creating...' : 'Create Account' }}
                            </Button>
                        </div>
                    </div>

                </div>
            </div>
        </form>

    </AppLayout>
</template>
