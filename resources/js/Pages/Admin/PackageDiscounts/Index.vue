<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { Package, FlaskConical, ScanLine, TestTube, Stethoscope, Tag, ToggleLeft, ToggleRight, Save, Info } from 'lucide-vue-next'

const props = defineProps({
    packages:             Array,
    drugtestCatalogPrice: Number,
})

// Per-package edit state
const editing = ref({})

function startEdit(pkg) {
    editing.value[pkg.id] = {
        package_price:        pkg.package_price,
        addon_drugtest_price: pkg.addon_drugtest_price ?? '',
        is_active:            pkg.is_active,
    }
}

function cancelEdit(id) {
    delete editing.value[id]
}

function savePackage(pkg) {
    const form = editing.value[pkg.id]
    router.patch(route('admin.package-discounts.update', pkg.id), {
        package_price:        parseFloat(form.package_price) || 0,
        addon_drugtest_price: form.addon_drugtest_price !== '' ? parseFloat(form.addon_drugtest_price) : null,
        is_active:            form.is_active,
    }, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(pkg.id),
    })
}

function toggleActive(pkg) {
    router.patch(route('admin.package-discounts.toggle', pkg.id), {}, { preserveScroll: true })
}

function computedDiscount(form, pkg) {
    const price = parseFloat(form.package_price) || 0
    return Math.max(0, pkg.catalog_total - price)
}

function fmt(val) {
    return Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 })
}

// Service icon by code
function serviceIcon(code) {
    if (['CBC', 'UA', 'FECALYSIS', 'BLOOD_CHEMISTRY'].includes(code)) return FlaskConical
    if (['CXRPA', 'XRAY', 'UTZ'].includes(code)) return ScanLine
    if (code.startsWith('DRUG')) return TestTube
    if (['PE_CONSULT', 'OPD', 'ANNUAL_PE', 'EXIT_PE'].includes(code)) return Stethoscope
    return Tag
}

const serviceLabel = {
    CBC:         'CBC',
    UA:          'Urinalysis',
    CXRPA:       'Chest X-Ray',
    FECALYSIS:   'Fecalysis',
    DRUGTEST:    'Drug Test',
    PE_CONSULT:  'Physical Exam',
    ANNUAL_PE:   'Annual PE Consult',
    EXIT_PE:     'Exit PE Consult',
}

const pkgColors = {
    pre_emp_pkg_1: { bg: '#EFF6FF', border: '#3B82F6', text: '#1D4ED8', badge: '#DBEAFE' },
    pre_emp_pkg_2: { bg: '#FDF4FF', border: '#A855F7', text: '#7E22CE', badge: '#F3E8FF' },
    pre_emp_pkg_3: { bg: '#F0FDF4', border: '#22C55E', text: '#15803D', badge: '#DCFCE7' },
}
</script>

<template>
    <AppLayout title="Package Discounts">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Package Discounts</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Manage pre-employment package pricing and discounts shown in reception quick select
                    </p>
                </div>
            </div>
        </template>

        <!-- Info banner -->
        <div class="flex items-start gap-3 p-4 mb-5 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
            <Info class="w-4 h-4 flex-shrink-0 mt-0.5 text-blue-500"/>
            <div class="space-y-1">
                <p><strong>How it works:</strong> Set the <em>Package Price</em> to the discounted total for that package.
                   The system will automatically compute the discount (Catalog Total − Package Price) and apply it to the invoice when a receptionist selects the package.</p>
                <p>For packages <strong>without Drug Test included</strong> (Package 1 &amp; 3): set the <em>Drug Test Add-on Price</em> so drug test gets a discounted rate when added as an optional add-on. If no add-on price is set, drug test is billed at full catalog price.</p>
            </div>
        </div>

        <div class="space-y-4">
            <div v-for="pkg in packages" :key="pkg.id"
                class="bg-white rounded-xl border shadow-sm overflow-hidden">

                <!-- Package header -->
                <div class="flex items-center justify-between px-5 py-4 border-b"
                    :style="{ background: pkgColors[pkg.package_key]?.bg ?? '#F8FAFC' }">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                            :style="{ background: pkgColors[pkg.package_key]?.badge ?? '#E2E8F0' }">
                            <Package class="w-4 h-4" :style="{ color: pkgColors[pkg.package_key]?.text ?? '#64748B' }"/>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold" :style="{ color: pkgColors[pkg.package_key]?.text ?? '#1E293B' }">
                                {{ pkg.package_name }}
                            </h2>
                            <p class="text-xs text-slate-500">{{ pkg.service_codes.length }} services included</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Active badge -->
                        <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full',
                            pkg.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500']">
                            {{ pkg.is_active ? 'Active' : 'Inactive' }}
                        </span>

                        <!-- Toggle active -->
                        <button type="button" @click="toggleActive(pkg)"
                            :title="pkg.is_active ? 'Deactivate package' : 'Activate package'"
                            class="text-slate-400 hover:text-slate-700 transition-colors">
                            <ToggleRight v-if="pkg.is_active" class="w-5 h-5 text-emerald-500"/>
                            <ToggleLeft v-else class="w-5 h-5"/>
                        </button>

                        <!-- Edit toggle -->
                        <Button v-if="!editing[pkg.id]"
                            variant="outline" size="sm" class="text-xs"
                            @click="startEdit(pkg)">
                            Edit Pricing
                        </Button>
                        <template v-else>
                            <Button variant="outline" size="sm" class="text-xs" @click="cancelEdit(pkg.id)">
                                Cancel
                            </Button>
                            <Button size="sm" class="text-xs gap-1.5 text-white" style="background:#1B4F9B"
                                @click="savePackage(pkg)">
                                <Save class="w-3.5 h-3.5"/>
                                Save
                            </Button>
                        </template>
                    </div>
                </div>

                <div class="p-5 grid grid-cols-2 gap-6">

                    <!-- Left: Services included -->
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Services Included</p>
                        <div class="space-y-2">
                            <div v-for="svc in pkg.services" :key="svc.code"
                                class="flex items-center justify-between py-1.5 border-b border-slate-100 last:border-0">
                                <div class="flex items-center gap-2">
                                    <component :is="serviceIcon(svc.code)"
                                        class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"/>
                                    <span class="text-xs text-slate-700 font-medium">
                                        {{ serviceLabel[svc.code] ?? svc.name }}
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500 font-semibold">₱{{ fmt(svc.price) }}</span>
                            </div>

                            <!-- Drug test add-on note for packages without DRUGTEST included -->
                            <div v-if="!pkg.service_codes.includes('DRUGTEST')"
                                class="flex items-center gap-2 py-1.5 border-t border-dashed border-slate-200 mt-1">
                                <TestTube class="w-3.5 h-3.5 text-rose-400 flex-shrink-0"/>
                                <span class="text-xs text-slate-500 italic">Drug Test (add-on, optional)</span>
                                <span class="text-xs text-slate-400 font-semibold ml-auto">
                                    ₱{{ drugtestCatalogPrice ? fmt(drugtestCatalogPrice) : '—' }}
                                    <span class="font-normal">catalog</span>
                                </span>
                            </div>
                        </div>

                        <Separator class="my-3"/>

                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Catalog Total (sum of individual prices)</span>
                            <span class="font-semibold text-slate-700">₱{{ fmt(pkg.catalog_total) }}</span>
                        </div>
                    </div>

                    <!-- Right: Pricing config -->
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Pricing Configuration</p>

                        <!-- View mode -->
                        <div v-if="!editing[pkg.id]" class="space-y-4">
                            <div class="rounded-xl border p-4 space-y-3 bg-slate-50">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-500">Catalog Total</span>
                                    <span class="font-semibold text-slate-700">₱{{ fmt(pkg.catalog_total) }}</span>
                                </div>
                                <div class="flex justify-between text-xs text-emerald-600">
                                    <span>Package Discount</span>
                                    <span class="font-semibold">− ₱{{ fmt(pkg.discount_amount) }}</span>
                                </div>
                                <Separator/>
                                <div class="flex justify-between text-sm font-bold" style="color:#1B4F9B">
                                    <span>Package Price</span>
                                    <span>₱{{ fmt(pkg.package_price) }}</span>
                                </div>
                            </div>

                            <!-- Drug test addon for packages without DRUGTEST included -->
                            <div v-if="!pkg.service_codes.includes('DRUGTEST')"
                                class="rounded-xl border border-dashed p-4 bg-rose-50/50">
                                <p class="text-xs font-bold text-slate-500 mb-2">Drug Test Add-on (if selected with this package)</p>
                                <div v-if="pkg.addon_drugtest_price !== null" class="space-y-1.5">
                                    <div class="flex justify-between text-xs text-slate-500">
                                        <span>Catalog Price</span>
                                        <span class="font-semibold">₱{{ fmt(drugtestCatalogPrice ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-emerald-600">
                                        <span>Add-on Discount</span>
                                        <span class="font-semibold">− ₱{{ fmt((drugtestCatalogPrice ?? 0) - pkg.addon_drugtest_price) }}</span>
                                    </div>
                                    <Separator/>
                                    <div class="flex justify-between text-xs font-bold text-rose-700">
                                        <span>Add-on Price</span>
                                        <span>₱{{ fmt(pkg.addon_drugtest_price) }}</span>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-slate-400 italic">
                                    No add-on price set — drug test billed at full catalog price when added.
                                </p>
                            </div>

                            <p v-if="pkg.updated_by" class="text-xs text-slate-400">
                                Last updated by {{ pkg.updated_by }} · {{ pkg.updated_at }}
                            </p>
                        </div>

                        <!-- Edit mode -->
                        <div v-else class="space-y-4">
                            <div class="rounded-xl border p-4 space-y-4 bg-slate-50">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Package Price (discounted total)</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500 font-medium">₱</span>
                                        <Input v-model="editing[pkg.id].package_price"
                                            type="number" min="0" step="0.01"
                                            class="pl-7 h-8 text-xs"/>
                                    </div>
                                    <p class="text-xs text-emerald-600 font-semibold">
                                        Discount: ₱{{ fmt(computedDiscount(editing[pkg.id], pkg)) }}
                                        (catalog ₱{{ fmt(pkg.catalog_total) }} − package price)
                                    </p>
                                </div>

                                <!-- Drug test addon price for packages without DRUGTEST -->
                                <div v-if="!pkg.service_codes.includes('DRUGTEST')" class="space-y-1.5 border-t pt-4">
                                    <Label class="text-xs">
                                        Drug Test Add-on Price
                                        <span class="text-slate-400 font-normal">(leave blank = full catalog price)</span>
                                    </Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500 font-medium">₱</span>
                                        <Input v-model="editing[pkg.id].addon_drugtest_price"
                                            type="number" min="0" step="0.01"
                                            placeholder="e.g. 350"
                                            class="pl-7 h-8 text-xs"/>
                                    </div>
                                    <p v-if="editing[pkg.id].addon_drugtest_price !== '' && drugtestCatalogPrice"
                                        class="text-xs text-emerald-600 font-semibold">
                                        Add-on discount: ₱{{ fmt(drugtestCatalogPrice - (parseFloat(editing[pkg.id].addon_drugtest_price) || 0)) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
