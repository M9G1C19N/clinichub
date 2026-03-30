<script setup>
import { computed, ref } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Pencil, KeyRound, Info, Save } from 'lucide-vue-next'

const props = defineProps({
    staff: Object,
    roles: Array,
})

const form = useForm({
    name:           props.staff.name,
    email:          props.staff.email,
    employee_id:    props.staff.employee_id ?? '',
    role:           props.staff.role ?? '',
    department:     props.staff.department ?? '',
    specialization: props.staff.specialization ?? '',
    prc_number:     props.staff.prc_number ?? '',
    ptr_number:     props.staff.ptr_number ?? '',
    is_active:      props.staff.is_active,
    extra_permissions: props.staff.extra_permissions ?? [],
    _method:        'PUT',
})

const roleDefaults = {
    admin:           { department: 'admin' },
    receptionist:    { department: 'reception' },
    nurse:           { department: 'nursing' },
    doctor:          { department: 'doctor' },
    lab_technician:  { department: 'laboratory' },
    xray_tech:       { department: 'xray_utz' },
    drug_test_staff: { department: 'drug_test' },
}

function onRoleChange(val) {
    form.role = val
    if (roleDefaults[val]) {
        form.department = roleDefaults[val].department
    }
}

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
}

const departments = [
    { value: 'laboratory', label: 'Laboratory' },
    { value: 'xray_utz',  label: 'X-Ray & UTZ' },
    { value: 'drug_test', label: 'Drug Test' },
    { value: 'reception', label: 'Reception' },
    { value: 'nursing',   label: 'Nursing' },
    { value: 'admin',     label: 'Admin' },
    { value: 'doctor',    label: 'Doctor' },
]

// Reset password inline
const showResetForm = ref(false)
const newPassword   = ref('')
const showPwd       = ref(false)

function submitResetPassword() {
    if (!newPassword.value || newPassword.value.length < 8) {
        alert('Password must be at least 8 characters.')
        return
    }
    router.post(route('admin.users.reset-password', props.staff.id), {
        password: newPassword.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showResetForm.value = false
            newPassword.value   = ''
        }
    })
}
function togglePermission(perm) {
    const idx = form.extra_permissions.indexOf(perm)
    idx === -1
        ? form.extra_permissions.push(perm)
        : form.extra_permissions.splice(idx, 1)
}

function submit() {
    form.post(route('admin.users.update', props.staff.id))
}
</script>

<template>
    <AppLayout :title="`Edit — ${staff.name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.users.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Staff Account</h1>
                        <p class="text-slate-400 text-xs mt-0.5">{{ staff.name }} · {{ staff.email }}</p>
                    </div>
                </div>

                <!-- Active toggle -->
                <div class="flex items-center gap-2 bg-card border rounded-xl px-4 py-2 shadow-sm">
                    <span class="text-xs font-semibold text-muted-foreground">Account Status</span>
                    <button type="button" @click="form.is_active = !form.is_active"
                        :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                            form.is_active ? 'bg-emerald-500' : 'bg-slate-300']">
                        <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform',
                            form.is_active ? 'translate-x-4' : 'translate-x-1']"/>
                    </button>
                    <Badge :class="form.is_active
                        ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100'
                        : 'bg-red-100 text-red-600 hover:bg-red-100'">
                        {{ form.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-5 items-start">

                <!-- LEFT — Role -->
                <div class="w-56 flex-shrink-0 space-y-4">

                    <!-- Role -->
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
                                        : 'border-border hover:border-slate-300'
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

                    <!-- Reset Password Card -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Password Reset</p>

                        <div v-if="!showResetForm">
                            <Button type="button" variant="outline" size="sm" class="w-full gap-2"
                                @click="showResetForm = true">
                                <KeyRound class="w-3.5 h-3.5"/>
                                Reset Password
                            </Button>
                        </div>

                        <div v-else class="space-y-2">
                            <div class="relative">
                                <Input v-model="newPassword"
                                    :type="showPwd ? 'text' : 'password'"
                                    placeholder="New password"
                                    class="pr-9 text-sm"/>
                                <button type="button" @click="showPwd = !showPwd"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            :d="showPwd
                                                ? 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21'
                                                : 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex gap-1.5">
                                <Button type="button" size="sm" class="flex-1 text-xs" style="background-color:#1B4F9B"
                                    @click="submitResetPassword">
                                    Set Password
                                </Button>
                                <Button type="button" size="sm" variant="ghost" class="text-xs"
                                    @click="showResetForm = false; newPassword = ''">
                                    Cancel
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">Staff will be required to change on next login.</p>
                        </div>
                    </div>

                    <!-- Account info -->
                    <div class="bg-slate-50 rounded-xl border p-4 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Created</span>
                            <span class="font-semibold text-slate-700">{{ staff.created_at }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-muted-foreground">Must change pwd</span>
                            <span :class="['font-semibold', staff.must_change_password ? 'text-amber-600' : 'text-emerald-600']">
                                {{ staff.must_change_password ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>

                </div>

                <!-- RIGHT — Form -->
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
                                <Input v-model="form.name" placeholder="Full name"
                                    :class="form.errors.name ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Email Address <span class="text-red-500">*</span></Label>
                                <Input v-model="form.email" type="email"
                                    :class="form.errors.email ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Employee ID</Label>
                                <Input v-model="form.employee_id" placeholder="EMP-001"
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

                        </div>
                    </div>

                    <!-- Professional Details -->
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

                    <!-- Extended Access — nurse/doctor only -->
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
                                class="flex items-start gap-3 cursor-pointer p-4 rounded-xl border-2 transition-all"
                                :class="form.extra_permissions.includes('doctor_features')
                                    ? 'border-purple-400 bg-purple-50'
                                    : 'border-border hover:border-slate-300 hover:bg-muted/30'">
                                <input type="checkbox"
                                    :checked="form.extra_permissions.includes('doctor_features')"
                                    @change="togglePermission('doctor_features')"
                                    class="mt-0.5 w-4 h-4 rounded accent-purple-600 flex-shrink-0"/>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Full Doctor Access</p>
                                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                        Allows this nurse to write SOAP notes, diagnoses, ICD-10 codes,
                                        pre-employment classification (Class A–E), and prescriptions.
                                    </p>
                                    <div v-if="form.extra_permissions.includes('doctor_features')"
                                        class="mt-2 flex flex-wrap gap-1.5">
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">SOAP Notes</span>
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">ICD-10</span>
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">Classification</span>
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-medium">Prescriptions</span>
                                    </div>
                                </div>
                            </label>

                            <!-- Grant nurse features to doctor -->
                            <label v-if="form.role === 'doctor'"
                                class="flex items-start gap-3 cursor-pointer p-4 rounded-xl border-2 transition-all"
                                :class="form.extra_permissions.includes('nurse_features')
                                    ? 'border-emerald-400 bg-emerald-50'
                                    : 'border-border hover:border-slate-300 hover:bg-muted/30'">
                                <input type="checkbox"
                                    :checked="form.extra_permissions.includes('nurse_features')"
                                    @change="togglePermission('nurse_features')"
                                    class="mt-0.5 w-4 h-4 rounded accent-emerald-600 flex-shrink-0"/>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Nurse Intake Access</p>
                                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                        Allows this doctor to use the nurse intake workflow for recording
                                        patient vitals, visual acuity, and Ishihara test.
                                        <span class="text-emerald-600 font-medium">(Doctors already have this by default.)</span>
                                    </p>
                                </div>
                            </label>

                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="bg-card rounded-xl border shadow-sm px-5 py-3.5 flex items-center justify-between">
                        <p class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <Info class="w-3.5 h-3.5 opacity-40"/>
                            Changes take effect immediately on next login
                        </p>
                        <div class="flex items-center gap-2">
                            <Link :href="route('admin.users.index')">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing"
                                style="background-color:#1B4F9B" class="gap-2">
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <Save v-else class="w-4 h-4"/>
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </div>

                </div>
            </div>
        </form>

    </AppLayout>
</template>
