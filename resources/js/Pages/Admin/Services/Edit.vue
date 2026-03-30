<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Separator } from '@/components/ui/separator'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Save, Info, History, TrendingUp, TrendingDown } from 'lucide-vue-next'

const props = defineProps({
    service:      Object,
    priceHistory: Array,
})

const form = useForm({
    service_name:     props.service.service_name,
    category:         props.service.category,
    room:             props.service.room,
    base_price:       props.service.base_price,
    description:      props.service.description ?? '',
    requires_fasting: props.service.requires_fasting,
    turnaround_hours: props.service.turnaround_hours,
    is_active:        props.service.is_active,
    price_reason:     '',
    _method:          'PUT',
})

const priceChanged = () => parseFloat(form.base_price) !== parseFloat(props.service.base_price)

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
    form.post(route('admin.services.update', props.service.id))
}
</script>

<template>
    <AppLayout :title="`Edit — ${service.service_name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.services.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Service</h1>
                        <p class="text-slate-400 text-xs mt-0.5">
                            {{ service.service_code }} · {{ service.service_name }}
                        </p>
                    </div>
                </div>

                <!-- Active toggle -->
                <div class="flex items-center gap-2 bg-card border rounded-xl px-4 py-2 shadow-sm">
                    <span class="text-xs font-semibold text-muted-foreground">Status</span>
                    <button type="button" @click="form.is_active = !form.is_active"
                        :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                            form.is_active ? 'bg-emerald-500' : 'bg-slate-300']">
                        <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform',
                            form.is_active ? 'translate-x-4' : 'translate-x-1']"/>
                    </button>
                    <span :class="['text-xs font-semibold',
                        form.is_active ? 'text-emerald-600' : 'text-red-500']">
                        {{ form.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-5 items-start">

                <!-- Main Form -->
                <div class="flex-1 min-w-0 space-y-4">

                    <!-- Service Info -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Service Information</h3>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">

                            <!-- Code — read only -->
                            <div class="space-y-1.5">
                                <Label>Service Code</Label>
                                <div class="px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono font-bold text-slate-600">
                                    {{ service.service_code }}
                                </div>
                                <p class="text-xs text-muted-foreground">Code cannot be changed</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Service Name <span class="text-red-500">*</span></Label>
                                <Input v-model="form.service_name"
                                    :class="form.errors.service_name ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.service_name" class="text-xs text-red-500">{{ form.errors.service_name }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Category <span class="text-red-500">*</span></Label>
                                <Select :modelValue="form.category" @update:modelValue="onCategoryChange">
                                    <SelectTrigger>
                                        <SelectValue/>
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
                            </div>

                            <div class="space-y-1.5">
                                <Label>Room Assignment</Label>
                                <Select v-model="form.room">
                                    <SelectTrigger><SelectValue/></SelectTrigger>
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
                                <Label>Turnaround (hours)</Label>
                                <Input v-model="form.turnaround_hours" type="number" min="0"/>
                            </div>

                            <div class="col-span-2 space-y-1.5">
                                <Label>Description</Label>
                                <Textarea v-model="form.description" :rows="2" class="resize-none"/>
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

                    <!-- Price Update -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Pricing</h3>
                        </div>
                        <div class="p-5 space-y-4">

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>Current Price (₱) <span class="text-red-500">*</span></Label>
                                    <Input v-model="form.base_price" type="number" min="0" step="0.01"
                                        :class="priceChanged() ? 'border-amber-400 bg-amber-50' : ''"/>
                                    <p v-if="priceChanged()" class="text-xs text-amber-600 font-medium">
                                        ⚠ Price will be changed from ₱{{ Number(service.base_price).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                                    </p>
                                </div>

                                <!-- Reason for price change -->
                                <div v-if="priceChanged()" class="space-y-1.5">
                                    <Label>Reason for Price Change</Label>
                                    <Input v-model="form.price_reason"
                                        placeholder="e.g. Annual price adjustment"/>
                                    <p class="text-xs text-muted-foreground">Optional but recommended for audit trail</p>
                                </div>
                            </div>

                            <!-- Critical note -->
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 flex items-start gap-2">
                                <Info class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5"/>
                                <p class="text-xs text-blue-600">
                                    Price changes are automatically logged. Existing invoices are <strong>not affected</strong> — they always preserve the price at time of billing.
                                </p>
                            </div>

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
                            <Save v-else class="w-4 h-4"/>
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>

                </div>

                <!-- Price History Sidebar -->
                <div class="w-72 flex-shrink-0">
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <History class="w-4 h-4 text-muted-foreground"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Price History</h3>
                        </div>

                        <div v-if="priceHistory.length === 0" class="py-8 text-center">
                            <p class="text-xs text-slate-400">No price changes recorded</p>
                        </div>

                        <div v-else class="divide-y divide-border">
                            <div v-for="h in priceHistory" :key="h.id" class="px-4 py-3">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <component
                                            :is="parseFloat(h.new_price.replace('₱ ','').replace(',','')) > parseFloat(h.old_price.replace('₱ ','').replace(',','')) ? TrendingUp : TrendingDown"
                                            class="w-3.5 h-3.5"
                                            :class="parseFloat(h.new_price.replace('₱ ','').replace(',','')) > parseFloat(h.old_price.replace('₱ ','').replace(',','')) ? 'text-red-500' : 'text-emerald-500'"
                                        />
                                        <span class="text-xs font-bold text-slate-700">{{ h.old_price }}</span>
                                        <span class="text-slate-300 text-xs">→</span>
                                        <span class="text-xs font-bold text-slate-800">{{ h.new_price }}</span>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground">{{ h.changed_at }}</p>
                                <p class="text-xs text-slate-500">by {{ h.changed_by }}</p>
                                <p v-if="h.reason" class="text-xs text-slate-400 italic mt-0.5">{{ h.reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </AppLayout>
</template>
