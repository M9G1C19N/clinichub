<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Plus, Info } from 'lucide-vue-next'

const form = useForm({
    service_code:     '',
    service_name:     '',
    category:         '',
    room:             'none',
    base_price:       '',
    description:      '',
    requires_fasting: false,
    turnaround_hours: 4,
})

// Auto-set room based on category
const categoryRoomMap = {
    laboratory:   'laboratory',
    xray_utz:     'xray_utz',
    drug_test:    'drug_test',
    consultation: 'interview_room',
    procedure:    'none',
    other:        'none',
}

function onCategoryChange(val) {
    form.category = val
    form.room = categoryRoomMap[val] ?? 'none'
}

function submit() {
    form.post(route('admin.services.store'))
}
</script>

<template>
    <AppLayout title="Add Service">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.services.index')">
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Add New Service</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Add a service to the clinic catalog</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="max-w-2xl">
            <div class="space-y-4">

                <!-- Basic Info -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Service Information</h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">

                        <div class="space-y-1.5">
                            <Label>Service Code <span class="text-red-500">*</span></Label>
                            <Input v-model="form.service_code"
                                placeholder="e.g. CBC, CXRPA"
                                class="uppercase"
                                :class="form.errors.service_code ? 'border-red-400' : ''"/>
                            <p v-if="form.errors.service_code" class="text-xs text-red-500">{{ form.errors.service_code }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Service Name <span class="text-red-500">*</span></Label>
                            <Input v-model="form.service_name" placeholder="e.g. Complete Blood Count"
                                :class="form.errors.service_name ? 'border-red-400' : ''"/>
                            <p v-if="form.errors.service_name" class="text-xs text-red-500">{{ form.errors.service_name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Category <span class="text-red-500">*</span></Label>
                            <Select :modelValue="form.category" @update:modelValue="onCategoryChange">
                                <SelectTrigger :class="form.errors.category ? 'border-red-400' : ''">
                                    <SelectValue placeholder="Select category"/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="laboratory">Laboratory</SelectItem>
                                    <SelectItem value="xray_utz">X-Ray & Ultrasound</SelectItem>
                                    <SelectItem value="drug_test">Drug Test</SelectItem>
                                    <SelectItem value="consultation">Consultation</SelectItem>
                                    <SelectItem value="procedure">Procedure</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.category" class="text-xs text-red-500">{{ form.errors.category }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Room Assignment</Label>
                            <Select v-model="form.room">
                                <SelectTrigger>
                                    <SelectValue/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="laboratory">Laboratory</SelectItem>
                                    <SelectItem value="xray_utz">X-Ray & UTZ</SelectItem>
                                    <SelectItem value="drug_test">Drug Test</SelectItem>
                                    <SelectItem value="interview_room">Interview Room</SelectItem>
                                    <SelectItem value="none">No Room</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Base Price (₱) <span class="text-red-500">*</span></Label>
                            <Input v-model="form.base_price" type="number" min="0" step="0.01"
                                placeholder="0.00"
                                :class="form.errors.base_price ? 'border-red-400' : ''"/>
                            <p v-if="form.errors.base_price" class="text-xs text-red-500">{{ form.errors.base_price }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Turnaround Time (hours)</Label>
                            <Input v-model="form.turnaround_hours" type="number" min="0"
                                placeholder="4"/>
                            <p class="text-xs text-muted-foreground">0 = immediate result</p>
                        </div>

                        <div class="col-span-2 space-y-1.5">
                            <Label>Description</Label>
                            <Textarea v-model="form.description" :rows="2"
                                placeholder="Optional description or notes about this service"
                                class="resize-none"/>
                        </div>

                        <div class="col-span-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.requires_fasting"
                                    class="w-4 h-4 rounded border-slate-300 accent-blue-600"/>
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Requires Fasting</p>
                                    <p class="text-xs text-muted-foreground">Patient must fast before this test</p>
                                </div>
                            </label>
                        </div>

                    </div>
                </div>

                <!-- Important Note -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                    <Info class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5"/>
                    <div>
                        <p class="text-xs font-bold text-blue-700">Price Integrity</p>
                        <p class="text-xs text-blue-600 mt-0.5">
                            All price changes are automatically logged with timestamp and staff name.
                            Existing invoices always preserve their original price at time of billing.
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2">
                    <Link :href="route('admin.services.index')">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing"
                        style="background-color:#1B4F9B" class="gap-2">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <Plus v-else class="w-4 h-4"/>
                        {{ form.processing ? 'Adding...' : 'Add Service' }}
                    </Button>
                </div>

            </div>
        </form>
    </AppLayout>
</template>
