<script setup>
/**
 * StaffSignatorySelect — dropdown that picks a staff member and auto-fills
 * name, license, and captures their signature URL into the parent form.
 *
 * Props:
 *   label       — field label shown above the select
 *   staffList   — array of { id, name, title, license_number, ptr_number, signature_url }
 *   modelName   — v-model for the name field
 *   modelLicense— v-model for the license field
 *   modelSig    — v-model for the signature_url field (stored as text path)
 *   currentUser — { id, name, prc_number, esignature }  — default selection
 */
import { ref, watch, onMounted } from 'vue'
import { ChevronDown, User, BadgeCheck } from 'lucide-vue-next'

const props = defineProps({
    label:         { type: String, required: true },
    staffList:     { type: Array,  default: () => [] },
    modelName:     { type: String, default: '' },
    modelLicense:  { type: String, default: '' },
    modelSig:      { type: String, default: '' },
    currentUser:   { type: Object, default: null },
})

const emit = defineEmits(['update:modelName', 'update:modelLicense', 'update:modelSig'])

const selectedId = ref(null)
const showDropdown = ref(false)

// Build a unified list: current user first (if not already in list), then staffList
const fullList = computed(() => {
    if (!props.currentUser) return props.staffList
    const inList = props.staffList.find(s => s.id === props.currentUser.id)
    if (inList) return props.staffList
    const selfEntry = {
        id:             props.currentUser.id,
        name:           props.currentUser.name,
        title:          props.currentUser.esignature?.title ?? null,
        license_number: props.currentUser.esignature?.license_number ?? props.currentUser.prc_number ?? '',
        ptr_number:     props.currentUser.esignature?.ptr_number ?? '',
        signature_url:  props.currentUser.esignature?.signature_url ?? null,
        signature_path: props.currentUser.esignature?.signature_path ?? null,
    }
    return [selfEntry, ...props.staffList]
})

const selectedStaff = computed(() => fullList.value.find(s => s.id === selectedId.value) ?? null)

// Default: current user if name is already pre-filled, else first matching by name
onMounted(() => {
    if (props.modelName) {
        const match = fullList.value.find(s => s.name === props.modelName)
        if (match) {
            selectedId.value = match.id
            // If signature path not yet saved, fill it from the matched staff record
            if (!props.modelSig) applySelection(match.id)
        }
    } else if (props.currentUser) {
        selectedId.value = props.currentUser.id
        applySelection(props.currentUser.id)
    }
})

function applySelection(id) {
    const staff = fullList.value.find(s => s.id === id)
    if (!staff) return
    selectedId.value = id
    emit('update:modelName',    staff.name)
    emit('update:modelLicense', staff.license_number ?? '')
    // Store path (not full URL) so the print controller can build the correct URL
    emit('update:modelSig',     staff.signature_path ?? staff.signature_url ?? '')
    showDropdown.value = false
}

// Also allow direct name edits (manual override)
function onNameInput(e) {
    emit('update:modelName', e.target.value)
    selectedId.value = null  // de-select dropdown
}
function onLicenseInput(e) {
    emit('update:modelLicense', e.target.value)
}

import { computed } from 'vue'
</script>

<template>
<div class="space-y-1.5">
    <label class="block text-xs font-semibold text-slate-600">{{ label }}</label>

    <!-- Dropdown selector -->
    <div class="relative">
        <button type="button" @click="showDropdown = !showDropdown"
            class="w-full h-9 px-3 text-sm border border-slate-200 rounded-xl text-left flex items-center justify-between hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white transition-colors">
            <span class="truncate" :class="selectedStaff ? 'text-slate-800 font-semibold' : 'text-slate-400'">
                {{ selectedStaff ? selectedStaff.name : '— Select staff —' }}
            </span>
            <ChevronDown class="w-4 h-4 text-slate-400 flex-shrink-0 ml-2" :class="showDropdown && 'rotate-180'" style="transition:transform .15s"/>
        </button>

        <!-- Dropdown list -->
        <div v-if="showDropdown"
            class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-56 overflow-y-auto">
            <button v-for="staff in fullList" :key="staff.id" type="button"
                @click="applySelection(staff.id)"
                :class="['w-full text-left px-4 py-2.5 hover:bg-blue-50 transition-colors flex items-center gap-3',
                    selectedId === staff.id ? 'bg-blue-50' : '']">
                <!-- Signature preview thumbnail -->
                <div class="w-10 h-8 rounded-lg border border-slate-100 bg-slate-50 flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <img v-if="staff.signature_url" :src="staff.signature_url" class="max-h-7 max-w-full object-contain"/>
                    <User v-else class="w-4 h-4 text-slate-300"/>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate flex items-center gap-1">
                        {{ staff.name }}
                        <BadgeCheck v-if="staff.signature_url" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0"/>
                    </p>
                    <p class="text-xs text-slate-400 truncate">
                        {{ staff.title ?? '' }}
                        <span v-if="staff.license_number" class="ml-1 font-mono">· {{ staff.license_number }}</span>
                    </p>
                </div>
            </button>
            <div v-if="!fullList.length" class="px-4 py-3 text-sm text-slate-400 text-center">
                No staff with signatures set up
            </div>
        </div>
    </div>

    <!-- Signature preview -->
    <div v-if="selectedStaff?.signature_url"
        class="flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-200">
        <img :src="selectedStaff.signature_url" alt="Signature"
             :style="{ height: Math.round(40 * (selectedStaff.signature_scale ?? 1)) + 'px', objectFit: 'contain' }"/>
        <div class="text-xs text-slate-500">
            <p class="font-semibold text-slate-700">{{ selectedStaff.name }}</p>
            <p>{{ selectedStaff.title }}</p>
            <p v-if="selectedStaff.license_number" class="font-mono">PRC: {{ selectedStaff.license_number }}</p>
        </div>
    </div>

    <!-- Manual override inputs (shown below dropdown) -->
    <div class="grid grid-cols-2 gap-2 mt-1">
        <div>
            <label class="text-xs text-slate-400">Name (override)</label>
            <input :value="modelName" @input="onNameInput"
                class="w-full h-8 px-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400"
                placeholder="Full name"/>
        </div>
        <div>
            <label class="text-xs text-slate-400">License No. (override)</label>
            <input :value="modelLicense" @input="onLicenseInput"
                class="w-full h-8 px-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400"
                placeholder="PRC No."/>
        </div>
    </div>
</div>
</template>
