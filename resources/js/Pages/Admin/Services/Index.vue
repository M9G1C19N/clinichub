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
    Plus, Search, Pencil, ToggleLeft,
    ToggleRight, FlaskConical, ScanLine,
    TestTube, Stethoscope, Settings2, Tag,
} from 'lucide-vue-next'

const props = defineProps({
    services: Object,
    filters:  Object,
    summary:  Object,
})

const search   = ref(props.filters?.search   ?? '')
const category = ref(props.filters?.category ?? 'all')
const status   = ref(props.filters?.status   ?? 'all')

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})
watch([category, status], () => applyFilters())

function applyFilters() {
    router.get(route('admin.services.index'), {
        search:   search.value || undefined,
        category: category.value !== 'all' ? category.value : undefined,
        status:   status.value   !== 'all' ? status.value   : undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value   = ''
    category.value = 'all'
    status.value   = 'all'
    applyFilters()
}

function toggleActive(id) {
    router.patch(route('admin.services.toggle-active', id), {}, { preserveScroll: true })
}

// Category config
const categoryConfig = {
    laboratory:   { label: 'Laboratory',      color: '#3B82F6', icon: FlaskConical },
    xray_utz:     { label: 'X-Ray & UTZ',     color: '#8B5CF6', icon: ScanLine },
    drug_test:    { label: 'Drug Test',        color: '#F43F5E', icon: TestTube },
    consultation: { label: 'Consultation',    color: '#10B981', icon: Stethoscope },
    procedure:    { label: 'Procedure',       color: '#F59E0B', icon: Settings2 },
    other:        { label: 'Other',           color: '#6B7280', icon: Tag },
}

// Group services by category for display
const hasFilters = () => search.value || category.value !== 'all' || status.value !== 'all'
</script>

<template>
    <AppLayout title="Service Catalog">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Service Catalog</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        {{ summary.total }} total · {{ summary.active }} active · {{ summary.inactive }} inactive
                    </p>
                </div>
                <Link :href="route('admin.services.create')">
                    <Button style="background-color:#1B4F9B" class="gap-2">
                        <Plus class="w-4 h-4"/>
                        Add Service
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Summary Cards -->
        <div class="grid grid-cols-6 gap-4 mb-5">
            <div v-for="(cfg, key) in categoryConfig" :key="key"
                class="bg-card rounded-xl border shadow-sm p-4 cursor-pointer transition-all hover:shadow-md"
                :class="category === key ? 'ring-2' : ''"
                :style="category === key ? `ring-color: ${cfg.color}` : ''"
                @click="category = category === key ? 'all' : key">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                        :style="{ background: cfg.color + '20' }">
                        <component :is="cfg.icon" class="w-3.5 h-3.5" :style="{ color: cfg.color }"/>
                    </div>
                    <span class="text-xs font-semibold text-slate-600">{{ cfg.label }}</span>
                </div>
                <p class="text-xl font-black text-slate-800">
                    {{ services.data.filter(s => s.category === key).length }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-xl border shadow-sm p-4 mb-5">
            <div class="flex flex-wrap gap-3">
                <div class="flex-1 min-w-52 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                    <Input v-model="search" placeholder="Search service name or code..." class="pl-9"/>
                </div>

                <Select v-model="category">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="All Categories"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Categories</SelectItem>
                        <SelectItem v-for="(cfg, key) in categoryConfig" :key="key" :value="key">
                            {{ cfg.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

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

                <Button v-if="hasFilters()" variant="outline" @click="clearFilters"
                    class="text-red-500 hover:text-red-700 hover:border-red-300">
                    Clear
                </Button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">

            <div v-if="!services.data.length" class="py-16 text-center">
                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <Tag class="w-7 h-7 text-slate-300"/>
                </div>
                <p class="text-sm font-medium text-slate-400">No services found</p>
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr style="background-color:#0F2044">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Service</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Category</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Code</th>
                        <th class="text-right px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Price</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Turnaround</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Last Price Change</th>
                        <th class="px-4 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="svc in services.data" :key="svc.id"
                        class="hover:bg-slate-50 transition-colors group"
                        :class="!svc.is_active ? 'opacity-60' : ''">

                        <!-- Service Name -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                    :style="{ background: categoryConfig[svc.category]?.color + '20' }">
                                    <component :is="categoryConfig[svc.category]?.icon" class="w-3.5 h-3.5"
                                        :style="{ color: categoryConfig[svc.category]?.color }"/>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ svc.service_name }}</p>
                                    <div v-if="svc.requires_fasting"
                                        class="text-xs text-amber-600 font-medium flex items-center gap-1 mt-0.5">
                                        ⚠ Requires fasting
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="px-4 py-3.5">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                                :style="{
                                    background: categoryConfig[svc.category]?.color + '15',
                                    color: categoryConfig[svc.category]?.color
                                }">
                                {{ categoryConfig[svc.category]?.label }}
                            </span>
                        </td>

                        <!-- Code -->
                        <td class="px-4 py-3.5">
                            <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg">
                                {{ svc.service_code }}
                            </span>
                        </td>

                        <!-- Price -->
                        <td class="px-4 py-3.5 text-right">
                            <span class="text-sm font-bold text-slate-800">{{ svc.formatted_price }}</span>
                        </td>

                        <!-- Turnaround -->
                        <td class="px-4 py-3.5">
                            <span class="text-xs text-slate-500">
                                {{ svc.turnaround_hours === 0 ? 'Immediate' : svc.turnaround_hours + 'h' }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-3.5">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                                svc.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600']">
                                {{ svc.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>

                        <!-- Last price change -->
                        <td class="px-4 py-3.5">
                            <div v-if="svc.price_changed_at" class="text-xs text-slate-400">
                                <p>{{ svc.price_changed_at }}</p>
                                <p class="text-slate-500">by {{ svc.price_changed_by }}</p>
                            </div>
                            <span v-else class="text-xs text-slate-300">—</span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Link :href="route('admin.services.edit', svc.id)">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0 text-slate-400 hover:text-blue-600 hover:bg-blue-50">
                                        <Pencil class="w-3.5 h-3.5"/>
                                    </Button>
                                </Link>
                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0"
                                    :class="svc.is_active
                                        ? 'text-slate-400 hover:text-red-600 hover:bg-red-50'
                                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50'"
                                    @click="toggleActive(svc.id)"
                                    :title="svc.is_active ? 'Deactivate' : 'Activate'">
                                    <ToggleRight v-if="svc.is_active" class="w-4 h-4"/>
                                    <ToggleLeft v-else class="w-4 h-4"/>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="services.data.length > 0"
                class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing {{ services.from }}–{{ services.to }} of {{ services.total }} services
                </p>
                <div class="flex items-center gap-1">
                    <template v-for="link in services.links" :key="link.label">
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
