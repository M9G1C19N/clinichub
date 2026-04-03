<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { PenLine, Upload, Trash2, CheckCircle2, XCircle, ToggleLeft, ToggleRight, Users, ShieldCheck, AlertCircle } from 'lucide-vue-next'

const props = defineProps({ users: Array })

const departmentLabel = {
    admin:      'Admin',
    doctor:     'Doctor',
    nursing:    'Nurse',
    laboratory: 'Laboratory',
    xray_utz:   'X-Ray / UTZ',
    drug_test:  'Drug Test',
    reception:  'Reception',
    billing:    'Billing',
}

const deptColor = {
    doctor:     { bg: 'bg-blue-50',    text: 'text-blue-700',    border: 'border-blue-200',    badge: 'bg-blue-100 text-blue-700' },
    nursing:    { bg: 'bg-pink-50',    text: 'text-pink-700',    border: 'border-pink-200',    badge: 'bg-pink-100 text-pink-700' },
    laboratory: { bg: 'bg-teal-50',    text: 'text-teal-700',    border: 'border-teal-200',    badge: 'bg-teal-100 text-teal-700' },
    xray_utz:   { bg: 'bg-violet-50',  text: 'text-violet-700',  border: 'border-violet-200',  badge: 'bg-violet-100 text-violet-700' },
    drug_test:  { bg: 'bg-orange-50',  text: 'text-orange-700',  border: 'border-orange-200',  badge: 'bg-orange-100 text-orange-700' },
    reception:  { bg: 'bg-slate-50',   text: 'text-slate-700',   border: 'border-slate-200',   badge: 'bg-slate-100 text-slate-700' },
    billing:    { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', badge: 'bg-emerald-100 text-emerald-700' },
    admin:      { bg: 'bg-red-50',     text: 'text-red-700',     border: 'border-red-200',     badge: 'bg-red-100 text-red-700' },
}

const activeTab = ref('all')

// Grouped by dept
const grouped = computed(() => {
    const map = {}
    for (const u of props.users) {
        const dept = u.department ?? 'other'
        if (!map[dept]) map[dept] = []
        map[dept].push(u)
    }
    return map
})

const departments = computed(() => Object.keys(grouped.value))

const filteredUsers = computed(() => {
    if (activeTab.value === 'all') return props.users
    if (activeTab.value === 'missing') return props.users.filter(u => !u.has_signature)
    return grouped.value[activeTab.value] ?? []
})

// Stats
const totalUsers   = computed(() => props.users.length)
const withSig      = computed(() => props.users.filter(u => u.has_signature).length)
const activeSig    = computed(() => props.users.filter(u => u.esignature?.is_active).length)
const missingSig   = computed(() => props.users.filter(u => !u.has_signature).length)

// ── Modal state ───────────────────────────────────────────
const editingUser = ref(null)
const previewUrl  = ref(null)
const fileInput   = ref(null)

const form = useForm({
    user_id:        '',
    title:          '',
    license_number: '',
    ptr_number:     '',
    signature:      null,
    is_active:      true,
})

function openEdit(user) {
    editingUser.value   = user
    previewUrl.value    = user.esignature?.signature_url ?? null
    form.user_id        = user.id
    form.title          = user.esignature?.title          ?? ''
    form.license_number = user.esignature?.license_number ?? user.prc_number ?? ''
    form.ptr_number     = user.esignature?.ptr_number     ?? ''
    form.signature      = null
    form.is_active      = user.esignature?.is_active      ?? true
    form.clearErrors()
}

function closeEdit() {
    editingUser.value = null
    previewUrl.value  = null
}

function onFileChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.signature = file
    previewUrl.value = URL.createObjectURL(file)
}

function save() {
    form.post(route('admin.esignatures.store'), {
        forceFormData: true,
        onSuccess: () => closeEdit(),
    })
}

function remove(esigId) {
    if (!confirm('Remove this signature profile?')) return
    router.delete(route('admin.esignatures.destroy', esigId), { preserveScroll: true })
}

function toggle(esigId) {
    router.patch(route('admin.esignatures.toggle', esigId), {}, { preserveScroll: true })
}
</script>

<template>
<AppLayout title="Staff E-Signatures">
    <template #header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                    <PenLine class="w-5 h-5 text-blue-600"/> Staff E-Signatures
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Register staff signatures used on lab, imaging, drug test, and medical reports
                </p>
            </div>
        </div>
    </template>

    <div class="space-y-5">

        <!-- Stats Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center">
                        <Users class="w-4 h-4 text-slate-600"/>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-800">{{ totalUsers }}</p>
                        <p class="text-xs text-slate-400">Total Staff</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                        <CheckCircle2 class="w-4 h-4 text-emerald-600"/>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-700">{{ withSig }}</p>
                        <p class="text-xs text-slate-400">Have Signature</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center">
                        <ShieldCheck class="w-4 h-4 text-blue-600"/>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-blue-700">{{ activeSig }}</p>
                        <p class="text-xs text-slate-400">Active</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                        <AlertCircle class="w-4 h-4 text-amber-500"/>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600">{{ missingSig }}</p>
                        <p class="text-xs text-slate-400">Missing</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Tabs -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="flex items-center gap-1 px-4 pt-3 overflow-x-auto">
                <button
                    @click="activeTab = 'all'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors',
                        activeTab === 'all'
                            ? 'bg-slate-800 text-white'
                            : 'text-slate-500 hover:bg-slate-100']">
                    All ({{ totalUsers }})
                </button>
                <button
                    @click="activeTab = 'missing'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors',
                        activeTab === 'missing'
                            ? 'bg-amber-500 text-white'
                            : 'text-amber-600 hover:bg-amber-50']">
                    Missing ({{ missingSig }})
                </button>
                <div class="w-px h-5 bg-slate-200 mx-1 flex-shrink-0"></div>
                <button
                    v-for="dept in departments" :key="dept"
                    @click="activeTab = dept"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors',
                        activeTab === dept
                            ? (deptColor[dept]?.badge ?? 'bg-slate-100 text-slate-700') + ' ring-1 ring-current'
                            : 'text-slate-500 hover:bg-slate-100']">
                    {{ departmentLabel[dept] ?? dept }}
                    <span class="ml-1 opacity-60">({{ grouped[dept].length }})</span>
                </button>
            </div>

            <!-- Staff Grid -->
            <div class="p-4">
                <div v-if="filteredUsers.length === 0"
                    class="py-16 text-center text-slate-400 text-sm">
                    No staff in this category.
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div v-for="user in filteredUsers" :key="user.id"
                        class="rounded-2xl border p-4 flex flex-col gap-3 transition-all hover:shadow-md"
                        :class="[
                            user.has_signature && user.esignature?.is_active
                                ? 'bg-white border-slate-200'
                                : user.has_signature
                                    ? 'bg-slate-50 border-slate-200 opacity-70'
                                    : 'bg-amber-50/50 border-amber-200'
                        ]">

                        <!-- Header -->
                        <div class="flex items-start gap-2.5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                :style="{ background: user.has_signature ? '#1B4F9B' : '#94a3b8' }">
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-800 text-sm truncate">{{ user.name }}</p>
                                <p v-if="user.esignature?.title" class="text-xs text-slate-500 truncate">
                                    {{ user.esignature.title }}
                                </p>
                                <p v-else class="text-xs text-slate-400 italic">No title</p>
                            </div>
                            <!-- Status pill -->
                            <span v-if="user.has_signature && user.esignature?.is_active"
                                class="text-xs px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold flex-shrink-0">
                                Active
                            </span>
                            <span v-else-if="user.has_signature"
                                class="text-xs px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-semibold flex-shrink-0">
                                Off
                            </span>
                            <span v-else
                                class="text-xs px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold flex-shrink-0">
                                Setup
                            </span>
                        </div>

                        <!-- Dept badge -->
                        <span class="self-start text-xs px-2 py-0.5 rounded-full font-semibold"
                            :class="deptColor[user.department]?.badge ?? 'bg-slate-100 text-slate-600'">
                            {{ departmentLabel[user.department] ?? user.department }}
                        </span>

                        <!-- Signature preview -->
                        <div class="rounded-xl border border-dashed border-slate-200 bg-white h-16 flex items-center justify-center overflow-hidden">
                            <img v-if="user.esignature?.signature_url"
                                :src="user.esignature.signature_url"
                                alt="Signature" class="max-h-12 max-w-full object-contain p-1"/>
                            <span v-else class="text-xs text-slate-400 flex items-center gap-1">
                                <AlertCircle class="w-3.5 h-3.5 text-amber-400"/> No signature
                            </span>
                        </div>

                        <!-- License info -->
                        <div v-if="user.esignature?.license_number || user.esignature?.ptr_number"
                            class="text-xs text-slate-500 bg-slate-50 rounded-lg px-2.5 py-1.5 space-y-0.5">
                            <p v-if="user.esignature?.license_number">
                                PRC: <strong class="text-slate-700">{{ user.esignature.license_number }}</strong>
                            </p>
                            <p v-if="user.esignature?.ptr_number">
                                PTR: <strong class="text-slate-700">{{ user.esignature.ptr_number }}</strong>
                            </p>
                        </div>
                        <div v-else class="text-xs text-slate-400 italic">No credentials on file</div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 mt-auto">
                            <button @click="openEdit(user)"
                                class="flex-1 flex items-center justify-center gap-1.5 text-xs font-semibold py-1.5 rounded-lg border transition-colors"
                                :class="user.has_signature
                                    ? 'border-blue-200 text-blue-600 hover:bg-blue-50'
                                    : 'border-amber-300 text-amber-700 bg-amber-50 hover:bg-amber-100'">
                                <PenLine class="w-3.5 h-3.5"/>
                                {{ user.has_signature ? 'Edit' : 'Setup' }}
                            </button>
                            <button v-if="user.has_signature"
                                @click="toggle(user.esignature.id)"
                                class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 transition-colors"
                                :title="user.esignature.is_active ? 'Deactivate' : 'Activate'">
                                <component :is="user.esignature.is_active ? ToggleRight : ToggleLeft"
                                    class="w-4 h-4"
                                    :class="user.esignature.is_active ? 'text-emerald-600' : 'text-slate-400'"/>
                            </button>
                            <button v-if="user.has_signature"
                                @click="remove(user.esignature.id)"
                                class="p-1.5 rounded-lg border border-red-200 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                <Trash2 class="w-3.5 h-3.5"/>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Edit Modal ──────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="editingUser" class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background:rgba(0,0,0,.55)">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b flex items-center justify-between" style="background:#0F2044">
                    <div>
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <PenLine class="w-4 h-4"/> Signature Profile
                        </h2>
                        <p class="text-xs text-white/60 mt-0.5">{{ editingUser.name }}</p>
                    </div>
                    <button @click="closeEdit" class="text-white/60 hover:text-white text-xl leading-none w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/10">&times;</button>
                </div>

                <form @submit.prevent="save" class="p-6 space-y-4">

                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Position / Title</label>
                        <input v-model="form.title" type="text"
                            placeholder="e.g. Medical Technologist, Radiologic Technologist"
                            class="w-full h-9 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                    </div>

                    <!-- License + PTR -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">PRC License No.</label>
                            <input v-model="form.license_number" type="text" placeholder="e.g. 0123456"
                                class="w-full h-9 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">PTR No.</label>
                            <input v-model="form.ptr_number" type="text" placeholder="e.g. 7654321"
                                class="w-full h-9 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        </div>
                    </div>

                    <!-- Signature upload -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                            E-Signature Image
                            <span class="font-normal text-slate-400 ml-1">— PNG with transparent bg preferred</span>
                        </label>
                        <div class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition-colors"
                            @click="fileInput.click()">
                            <img v-if="previewUrl" :src="previewUrl" alt="Preview"
                                class="max-h-24 mx-auto object-contain mb-2 drop-shadow"/>
                            <div v-else class="py-3">
                                <Upload class="w-8 h-8 text-slate-300 mx-auto mb-2"/>
                                <p class="text-xs text-slate-400 font-semibold">Click to upload</p>
                                <p class="text-xs text-slate-300">PNG / JPG / WEBP</p>
                            </div>
                            <p v-if="previewUrl" class="text-xs text-blue-500 hover:underline mt-1">Click to replace</p>
                        </div>
                        <input ref="fileInput" type="file" accept="image/png,image/jpeg,image/webp"
                            class="hidden" @change="onFileChange"/>
                        <p v-if="form.errors.signature" class="text-xs text-red-500 mt-1">{{ form.errors.signature }}</p>
                    </div>

                    <!-- Active toggle -->
                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl hover:bg-slate-50 border border-slate-100">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-700">Active</p>
                            <p class="text-xs text-slate-400">Appears in staff dropdowns on reports</p>
                        </div>
                        <button type="button" @click="form.is_active = !form.is_active"
                            :class="['relative inline-flex h-6 w-10 items-center rounded-full transition-colors flex-shrink-0',
                                form.is_active ? 'bg-emerald-500' : 'bg-slate-300']">
                            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                                form.is_active ? 'translate-x-5' : 'translate-x-1']"/>
                        </button>
                    </label>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="closeEdit"
                            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-50 font-semibold transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 py-2.5 text-white rounded-xl text-sm font-bold transition-colors hover:opacity-90 disabled:opacity-50"
                            style="background:#1B4F9B">
                            {{ form.processing ? 'Saving…' : 'Save Profile' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</AppLayout>
</template>
