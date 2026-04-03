<script setup>
import { ref, onUnmounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription,
} from '@/components/ui/dialog'

const visitTypeOptions = [
    { value: 'opd',            label: 'OPD',            desc: 'Out-patient',       color: '#1d4ed8' },
    { value: 'pre_employment', label: 'Pre-Employment', desc: 'Company referral',  color: '#7c3aed' },
    { value: 'annual_pe',      label: 'Annual PE',      desc: 'Yearly check-up',   color: '#15803d' },
    { value: 'exit_pe',        label: 'Exit PE',        desc: 'End of employment', color: '#c2410c' },
    { value: 'follow_up',      label: 'Follow-up',      desc: 'Return visit',      color: '#b45309' },
    { value: 'lab_only',       label: 'Lab Only',       desc: 'Lab requests only', color: '#0f766e' },
]

const form = useForm({
    first_name:               '',
    last_name:                '',
    middle_name:              '',
    date_of_birth:            '',
    sex:                      '',
    civil_status:             '',
    contact_number:           '',
    email:                    '',
    address:                  '',
    philhealth_number:        '',
    blood_type:               '',
    occupation:               '',
    emergency_contact_name:   '',
    emergency_contact_number: '',
    visit_type_default:       'opd',
    photo:                    null,
})

// ── Photo state ────────────────────────────────────────
const photoPreview    = ref(null)
const showPhotoModal  = ref(false)

// ── Camera state ───────────────────────────────────────
const cameraMode      = ref(false)   // 'camera' view inside modal
const videoRef        = ref(null)
const canvasRef       = ref(null)
const stream          = ref(null)
const cameraError     = ref(null)
const cameraReady     = ref(false)

// Open modal
function openPhotoModal() {
    cameraMode.value  = false
    cameraError.value = null
    showPhotoModal.value = true
}

// Close modal + stop camera
function closePhotoModal() {
    stopCamera()
    showPhotoModal.value = false
    cameraMode.value     = false
}

// Start webcam
async function startCamera() {
    cameraMode.value  = true
    cameraError.value = null
    cameraReady.value = false

    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' },
            audio: false,
        })
        // Wait for video element to mount
        await new Promise(r => setTimeout(r, 100))
        if (videoRef.value) {
            videoRef.value.srcObject = stream.value
            videoRef.value.onloadedmetadata = () => {
                videoRef.value.play()
                cameraReady.value = true
            }
        }
    } catch (err) {
        cameraError.value = 'Camera access denied or not available. Please allow camera access and try again.'
        cameraMode.value  = false
    }
}

// Stop webcam
function stopCamera() {
    if (stream.value) {
        stream.value.getTracks().forEach(t => t.stop())
        stream.value = null
    }
    cameraReady.value = false
}

// Capture photo from webcam
function capturePhoto() {
    if (!videoRef.value || !canvasRef.value) return

    const video  = videoRef.value
    const canvas = canvasRef.value
    canvas.width  = video.videoWidth
    canvas.height = video.videoHeight

    const ctx = canvas.getContext('2d')
    // Mirror the image (selfie mode)
    ctx.translate(canvas.width, 0)
    ctx.scale(-1, 1)
    ctx.drawImage(video, 0, 0)

    canvas.toBlob((blob) => {
        const file = new File([blob], 'webcam-photo.jpg', { type: 'image/jpeg' })
        form.photo     = file
        photoPreview.value = canvas.toDataURL('image/jpeg')
        closePhotoModal()
    }, 'image/jpeg', 0.92)
}

// Browse file
function handleBrowse(e) {
    const file = e.target.files[0]
    if (!file) return
    form.photo = file
    const reader = new FileReader()
    reader.onload = (ev) => {
        photoPreview.value = ev.target.result
        closePhotoModal()
    }
    reader.readAsDataURL(file)
}

function removePhoto() {
    form.photo         = null
    photoPreview.value = null
}

// Cleanup on unmount
onUnmounted(() => stopCamera())

function submit() {
    form.post(route('patients.store'), { forceFormData: true })
}
</script>

<template>
    <AppLayout title="Register Patient">

        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('patients.index')">
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Register New Patient</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Fields marked <span class="text-red-500">*</span> are required</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-5 items-start">

                <!-- ── LEFT PANEL ─────────────────────────── -->
                <div class="w-56 flex-shrink-0 space-y-4">

                    <!-- Photo Card -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Photo</p>

                        <!-- Preview -->
                        <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-muted border-2 border-dashed border-border flex items-center justify-center mb-3 group">
                            <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover"/>
                            <div v-else class="flex flex-col items-center gap-1 text-muted-foreground">
                                <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <p class="text-xs opacity-40">No photo</p>
                            </div>

                            <!-- Overlay on hover when photo exists -->
                            <div v-if="photoPreview"
                                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button type="button" @click="openPhotoModal"
                                    class="text-white text-xs font-semibold bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg backdrop-blur-sm transition-all">
                                    Change Photo
                                </button>
                            </div>
                        </div>

                        <!-- Camera Button -->
                        <Button type="button" variant="outline" size="sm" class="w-full gap-2" @click="openPhotoModal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ photoPreview ? 'Change Photo' : 'Add Photo' }}
                        </Button>

                        <button v-if="photoPreview" type="button" @click="removePhoto"
                            class="w-full mt-1.5 text-xs text-destructive hover:opacity-70 transition-opacity py-1">
                            × Remove photo
                        </button>
                        <p class="text-xs text-muted-foreground text-center mt-2">JPG/PNG · max 2MB</p>
                    </div>

                    <!-- Visit Type -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Default Visit Type</p>
                        <div class="space-y-1.5">
                            <button v-for="vt in visitTypeOptions" :key="vt.value"
                                type="button"
                                @click="form.visit_type_default = vt.value"
                                :class="['w-full flex items-center gap-2.5 p-2.5 rounded-lg border-2 transition-all text-left',
                                    form.visit_type_default === vt.value
                                        ? 'border-current'
                                        : 'border-border hover:border-slate-300 hover:bg-muted/50']"
                                :style="form.visit_type_default === vt.value
                                    ? { borderColor: vt.color, background: vt.color + '12' }
                                    : {}">
                                <div class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                    :style="form.visit_type_default === vt.value
                                        ? { borderColor: vt.color }
                                        : { borderColor: '#cbd5e1' }">
                                    <div v-if="form.visit_type_default === vt.value"
                                        class="w-2 h-2 rounded-full"
                                        :style="{ background: vt.color }"/>
                                </div>
                                <div>
                                    <p class="text-xs font-bold"
                                        :style="form.visit_type_default === vt.value ? { color: vt.color } : {}">
                                        {{ vt.label }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ vt.desc }}</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Preview</p>
                        <div class="space-y-2.5">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-xs text-muted-foreground flex-shrink-0">Name</span>
                                <span class="text-xs font-semibold text-right truncate max-w-[120px]">
                                    {{ (form.last_name || form.first_name) ? `${form.last_name}, ${form.first_name}` : '—' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-xs text-muted-foreground">Sex</span>
                                <span class="text-xs font-semibold capitalize">{{ form.sex || '—' }}</span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-xs text-muted-foreground">DOB</span>
                                <span class="text-xs font-semibold">{{ form.date_of_birth || '—' }}</span>
                            </div>
                            <Separator/>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-xs text-muted-foreground">Type</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                    :style="{
                                        background: visitTypeOptions.find(v => v.value === form.visit_type_default)?.color + '20',
                                        color: visitTypeOptions.find(v => v.value === form.visit_type_default)?.color,
                                    }">
                                    {{ visitTypeOptions.find(v => v.value === form.visit_type_default)?.label ?? form.visit_type_default }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ── RIGHT PANEL ────────────────────────── -->
                <div class="flex-1 min-w-0 space-y-4">

                    <!-- Personal Information -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Personal Information</h3>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">

                            <div class="space-y-1.5">
                                <Label>Last Name <span class="text-red-500">*</span></Label>
                                <Input v-model="form.last_name" placeholder="Dela Cruz"
                                    :class="form.errors.last_name ? 'border-red-400 focus-visible:ring-red-400' : ''"/>
                                <p v-if="form.errors.last_name" class="text-xs text-red-500">{{ form.errors.last_name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>First Name <span class="text-red-500">*</span></Label>
                                <Input v-model="form.first_name" placeholder="Juan"
                                    :class="form.errors.first_name ? 'border-red-400 focus-visible:ring-red-400' : ''"/>
                                <p v-if="form.errors.first_name" class="text-xs text-red-500">{{ form.errors.first_name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Middle Name</Label>
                                <Input v-model="form.middle_name" placeholder="Santos"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Date of Birth <span class="text-red-500">*</span></Label>
                                <Input v-model="form.date_of_birth" type="date"
                                    :max="new Date().toISOString().split('T')[0]"
                                    :class="form.errors.date_of_birth ? 'border-red-400 focus-visible:ring-red-400' : ''"/>
                                <p v-if="form.errors.date_of_birth" class="text-xs text-red-500">{{ form.errors.date_of_birth }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Sex <span class="text-red-500">*</span></Label>
                                <Select v-model="form.sex">
                                    <SelectTrigger :class="form.errors.sex ? 'border-red-400' : ''">
                                        <SelectValue placeholder="Select sex"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">Male</SelectItem>
                                        <SelectItem value="female">Female</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.sex" class="text-xs text-red-500">{{ form.errors.sex }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Civil Status <span class="text-red-500">*</span></Label>
                                <Select v-model="form.civil_status">
                                    <SelectTrigger :class="form.errors.civil_status ? 'border-red-400' : ''">
                                        <SelectValue placeholder="Select status"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="single">Single</SelectItem>
                                        <SelectItem value="married">Married</SelectItem>
                                        <SelectItem value="widowed">Widowed</SelectItem>
                                        <SelectItem value="separated">Separated</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.civil_status" class="text-xs text-red-500">{{ form.errors.civil_status }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Blood Type</Label>
                                <Select v-model="form.blood_type">
                                    <SelectTrigger><SelectValue placeholder="Unknown"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unknown">Unknown</SelectItem>
                                        <SelectItem v-for="bt in ['A+','A-','B+','B-','O+','O-','AB+','AB-']"
                                            :key="bt" :value="bt">{{ bt }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Occupation</Label>
                                <Input v-model="form.occupation" placeholder="e.g. Engineer"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label>PhilHealth No.</Label>
                                <Input v-model="form.philhealth_number" placeholder="XX-XXXXXXXXX-X"/>
                            </div>

                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-sky-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Contact Information</h3>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">

                            <div class="space-y-1.5">
                                <Label>Mobile Number</Label>
                                <Input v-model="form.contact_number" placeholder="09XX XXX XXXX"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Email Address</Label>
                                <Input v-model="form.email" type="email" placeholder="juan@email.com"
                                    :class="form.errors.email ? 'border-red-400 focus-visible:ring-red-400' : ''"/>
                                <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>PhilHealth No.</Label>
                                <Input v-model="form.philhealth_number" placeholder="XX-XXXXXXXXX-X"/>
                            </div>

                            <div class="col-span-3 space-y-1.5">
                                <Label>Home Address</Label>
                                <Textarea v-model="form.address" :rows="2"
                                    placeholder="House No., Street, Barangay, Municipality/City, Province"
                                    class="resize-none"/>
                            </div>

                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-red-400"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Emergency Contact</h3>
                            <span class="text-xs text-muted-foreground">(Optional)</span>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Contact Person</Label>
                                <Input v-model="form.emergency_contact_name" placeholder="Full name"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Contact Number</Label>
                                <Input v-model="form.emergency_contact_number" placeholder="09XX XXX XXXX"/>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="bg-card rounded-xl border shadow-sm px-5 py-3.5 flex items-center justify-between">
                        <p class="text-xs text-muted-foreground flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Patient Code (PT-YYYY-NNNNN) is auto-generated on save
                        </p>
                        <div class="flex items-center gap-2">
                            <Link :href="route('patients.index')">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing" style="background-color:#1B4F9B">
                                <svg v-if="form.processing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                {{ form.processing ? 'Registering...' : 'Register Patient' }}
                            </Button>
                        </div>
                    </div>

                </div>
            </div>
        </form>

        <!-- ── PHOTO MODAL ──────────────────────────────── -->
        <Dialog :open="showPhotoModal" @update:open="closePhotoModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Patient Photo</DialogTitle>
                    <DialogDescription>
                        Take a photo using your camera or browse from your files.
                    </DialogDescription>
                </DialogHeader>

                <!-- Choice screen -->
                <div v-if="!cameraMode" class="space-y-3 py-2">

                    <!-- Error -->
                    <div v-if="cameraError"
                        class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-lg flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ cameraError }}
                    </div>

                    <!-- Take Photo button -->
                    <button type="button" @click="startCamera"
                        class="w-full flex items-center gap-4 p-4 border-2 border-dashed border-slate-200 rounded-xl hover:border-blue-400 hover:bg-blue-50 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-slate-700 group-hover:text-blue-700">Take Photo</p>
                            <p class="text-xs text-slate-400">Use your webcam to capture a photo</p>
                        </div>
                    </button>

                    <!-- Browse File button -->
                    <Label class="w-full cursor-pointer">
                        <div class="flex items-center gap-4 p-4 border-2 border-dashed border-slate-200 rounded-xl hover:border-emerald-400 hover:bg-emerald-50 transition-all group">
                            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200 transition-colors flex-shrink-0">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-slate-700 group-hover:text-emerald-700">Browse File</p>
                                <p class="text-xs text-slate-400">Choose JPG or PNG from your device</p>
                            </div>
                        </div>
                        <input type="file" accept="image/*" class="hidden" @change="handleBrowse"/>
                    </Label>

                </div>

                <!-- Camera screen -->
                <div v-if="cameraMode" class="space-y-3">

                    <!-- Video feed -->
                    <div class="relative w-full aspect-video bg-black rounded-xl overflow-hidden">
                        <video
                            ref="videoRef"
                            autoplay
                            playsinline
                            muted
                            class="w-full h-full object-cover"
                            style="transform: scaleX(-1);"
                        />
                        <!-- Loading overlay -->
                        <div v-if="!cameraReady"
                            class="absolute inset-0 flex items-center justify-center bg-black/70">
                            <div class="text-center text-white">
                                <svg class="animate-spin w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <p class="text-sm">Starting camera...</p>
                            </div>
                        </div>

                        <!-- Crosshair guide -->
                        <div v-if="cameraReady" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-32 h-40 border-2 border-white/50 rounded-xl"></div>
                        </div>
                    </div>

                    <!-- Hidden canvas for capture -->
                    <canvas ref="canvasRef" class="hidden"/>

                    <!-- Camera controls -->
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" class="flex-1" @click="() => { stopCamera(); cameraMode = false }">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Back
                        </Button>

                        <button type="button" @click="capturePhoto" :disabled="!cameraReady"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold text-white transition-all disabled:opacity-40"
                            style="background-color:#1B4F9B">
                            <div class="w-5 h-5 rounded-full border-2 border-white flex items-center justify-center">
                                <div class="w-3 h-3 rounded-full bg-white"/>
                            </div>
                            Capture Photo
                        </button>
                    </div>

                    <p class="text-xs text-muted-foreground text-center">
                        Position the patient's face within the guide box
                    </p>
                </div>

            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
