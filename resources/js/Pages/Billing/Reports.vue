<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BarChart3, TrendingUp, Receipt, AlertTriangle, Clock,
    CheckCircle2, XCircle, Banknote, Smartphone, CreditCard,
    DollarSign, Users, Printer, ChevronRight, ArrowUpRight,
    ArrowDownRight, Stethoscope, FileText,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'

const props = defineProps({
    filters:        Object,
    dateLabel:      String,
    summary:        Object,
    byMethod:       Array,
    dailyBreakdown: Array,
    topServices:    Array,
    byVisitType:    Array,
    byCollector:    Array,
    statusSummary:  Array,
})

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

// ── Filters ───────────────────────────────────────────
const preset   = ref(props.filters.preset ?? 'this_month')
const fromDate = ref(props.filters.from ?? '')
const toDate   = ref(props.filters.to ?? '')

const presets = [
    { value: 'today',       label: 'Today' },
    { value: 'yesterday',   label: 'Yesterday' },
    { value: 'this_week',   label: 'This Week' },
    { value: 'last_week',   label: 'Last Week' },
    { value: 'this_month',  label: 'This Month' },
    { value: 'last_month',  label: 'Last Month' },
    { value: 'this_year',   label: 'This Year' },
    { value: 'custom',      label: 'Custom Range' },
]

function applyFilter() {
    router.get(route('billing.reports'), {
        preset:  preset.value,
        from:    preset.value === 'custom' ? fromDate.value : undefined,
        to:      preset.value === 'custom' ? toDate.value   : undefined,
    }, { preserveScroll: true, replace: true })
}

// ── Method helpers ────────────────────────────────────
const methodIcon = {
    cash:       Banknote,
    gcash:      Smartphone,
    maya:       Smartphone,
    card:       CreditCard,
    philhealth: Receipt,
    other:      DollarSign,
}
const methodColor = {
    cash:       { text: 'text-emerald-600', bg: 'bg-emerald-50',  bar: '#10B981' },
    gcash:      { text: 'text-blue-600',    bg: 'bg-blue-50',     bar: '#3B82F6' },
    maya:       { text: 'text-purple-600',  bg: 'bg-purple-50',   bar: '#9333EA' },
    card:       { text: 'text-slate-600',   bg: 'bg-slate-100',   bar: '#64748B' },
    philhealth: { text: 'text-teal-600',    bg: 'bg-teal-50',     bar: '#0D9488' },
    other:      { text: 'text-slate-500',   bg: 'bg-slate-50',    bar: '#94A3B8' },
}

const statusConfig = {
    paid:      { label: 'Paid',    class: 'bg-emerald-100 text-emerald-700', icon: CheckCircle2, bar: '#10B981' },
    partial:   { label: 'Partial', class: 'bg-amber-100 text-amber-700',    icon: Clock,        bar: '#F59E0B' },
    unpaid:    { label: 'Unpaid',  class: 'bg-red-100 text-red-700',        icon: AlertTriangle,bar: '#EF4444' },
    cancelled: { label: 'Void',    class: 'bg-slate-100 text-slate-500',    icon: XCircle,      bar: '#94A3B8' },
}

// ── Chart helpers ─────────────────────────────────────
const maxMethodTotal = computed(() =>
    Math.max(...(props.byMethod ?? []).map(m => m.total), 1)
)
const maxDailyCollected = computed(() =>
    Math.max(...(props.dailyBreakdown ?? []).map(d => d.collected), 1)
)
const maxServiceRevenue = computed(() =>
    Math.max(...(props.topServices ?? []).map(s => s.revenue), 1)
)

// ── Print ─────────────────────────────────────────────
function printReport() {
    const now    = new Date().toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' })
    const p      = props
    const fmt    = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

    const methodLabel = { cash:'Cash', gcash:'GCash', maya:'Maya', card:'Card', philhealth:'PhilHealth', other:'Other' }

    const visitTypeLabel = {
        opd:'OPD', pre_employment:'Pre-Employment', annual_pe:'Annual PE',
        exit_pe:'Exit PE', follow_up:'Follow-up', lab_only:'Lab Only',
    }

    const byMethodRows = (p.byMethod ?? []).map(m => `
        <tr>
            <td>${methodLabel[m.method] ?? m.method}</td>
            <td class="num">${m.transactions}</td>
            <td class="num">${fmt(m.total)}</td>
            <td class="num">${p.summary.total_collected > 0 ? ((m.total / p.summary.total_collected) * 100).toFixed(1) + '%' : '—'}</td>
        </tr>`).join('')

    const dailyRows = (p.dailyBreakdown ?? []).filter(d => d.invoices > 0).map(d => `
        <tr>
            <td>${d.label}</td>
            <td class="num">${d.invoices}</td>
            <td class="num">${fmt(d.billed)}</td>
            <td class="num">${d.discounts > 0 ? fmt(d.discounts) : '—'}</td>
            <td class="num">${fmt(d.collected)}</td>
            <td class="num">${d.balance > 0 ? fmt(d.balance) : '—'}</td>
        </tr>`).join('')

    const serviceRows = (p.topServices ?? []).map((s, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${s.service_name}</td>
            <td class="mono">${s.service_code}</td>
            <td class="num">${s.transactions}</td>
            <td class="num">${fmt(s.revenue)}</td>
        </tr>`).join('')

    const visitTypeRows = (p.byVisitType ?? []).map(vt => `
        <tr>
            <td>${visitTypeLabel[vt.visit_type] ?? vt.visit_type}</td>
            <td class="num">${vt.invoice_count}</td>
            <td class="num">${fmt(vt.billed)}</td>
            <td class="num">${fmt(vt.collected)}</td>
            <td class="num">${vt.billed > 0 ? ((vt.collected / vt.billed) * 100).toFixed(1) + '%' : '—'}</td>
        </tr>`).join('')

    const collectorRows = (p.byCollector ?? []).map(c => `
        <tr>
            <td>${c.collector}</td>
            <td class="num">${c.transactions}</td>
            <td class="num">${fmt(c.total)}</td>
            <td class="num">${p.summary.total_collected > 0 ? ((c.total / p.summary.total_collected) * 100).toFixed(1) + '%' : '—'}</td>
        </tr>`).join('')

    const html = `<!DOCTYPE html><html><head><meta charset="utf-8">
    <title>Billing Collections Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: A4 portrait; margin: 18mm 15mm 18mm 15mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #111; background: white; }

        /* ── Letterhead ── */
        .letterhead { display: flex; align-items: flex-start; gap: 14px; padding-bottom: 10px; border-bottom: 2.5px solid #0F2044; margin-bottom: 14px; }
        .letterhead img { width: 56px; height: 56px; object-fit: contain; }
        .letterhead-text h1 { font-size: 14pt; font-weight: 900; color: #0F2044; line-height: 1.2; }
        .letterhead-text h2 { font-size: 9pt; font-weight: normal; color: #444; margin-top: 2px; }
        .letterhead-text p  { font-size: 7.5pt; color: #666; margin-top: 2px; }

        /* ── Report Title ── */
        .report-title { text-align: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid #ddd; }
        .report-title h2 { font-size: 13pt; font-weight: 900; color: #0F2044; text-transform: uppercase; letter-spacing: 0.5px; }
        .report-title p  { font-size: 8.5pt; color: #555; margin-top: 3px; }

        /* ── Summary Box ── */
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border: 1px solid #ccc; margin-bottom: 16px; }
        .summary-cell { padding: 8px 10px; border-right: 1px solid #ccc; border-bottom: 1px solid #ccc; }
        .summary-cell:nth-child(3n) { border-right: none; }
        .summary-cell:nth-last-child(-n+3) { border-bottom: none; }
        .summary-cell .label { font-size: 7pt; color: #666; text-transform: uppercase; letter-spacing: 0.4px; }
        .summary-cell .value { font-size: 13pt; font-weight: 900; color: #0F2044; margin-top: 2px; }
        .summary-cell .sub   { font-size: 7pt; color: #888; margin-top: 1px; }

        /* ── Section ── */
        .section { margin-bottom: 18px; }
        .section-title { font-size: 9pt; font-weight: 900; color: #0F2044; text-transform: uppercase;
            letter-spacing: 0.5px; padding: 5px 0 5px 8px; border-left: 3px solid #1B4F9B;
            background: #f0f4ff; margin-bottom: 6px; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        thead tr { background: #0F2044; color: white; }
        thead th { padding: 6px 8px; text-align: left; font-weight: 700; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.3px; }
        tbody tr { border-bottom: 1px solid #e5e5e5; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 5px 8px; }
        tfoot tr { background: #e8edf5; border-top: 2px solid #0F2044; }
        tfoot td { padding: 6px 8px; font-weight: 900; font-size: 8.5pt; }
        .num  { text-align: right; }
        .mono { font-family: monospace; font-size: 8pt; }

        /* ── Footer ── */
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc; display: flex; justify-content: space-between; font-size: 7pt; color: #999; }

        /* ── Page break ── */
        .page-break { page-break-before: always; }
    </style>
    </head><body>

    <!-- LETTERHEAD -->
    <div class="letterhead">
        <img src="/images/spdl-logo.png" alt="Logo"/>
        <div class="letterhead-text">
            <h1>Saint Peter Diagnostics and Laboratory</h1>
            <h2>Medical and Dental Clinic</h2>
            <p>Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</p>
            <p>Tel: 09204043408 / 09516832212 &nbsp;|&nbsp; spdl.claver2007@gmail.com</p>
        </div>
    </div>

    <!-- REPORT TITLE -->
    <div class="report-title">
        <h2>Billing Collections Report</h2>
        <p>Period: ${p.dateLabel} &nbsp;·&nbsp; Generated: ${now}</p>
    </div>

    <!-- SUMMARY -->
    <div class="summary-grid">
        <div class="summary-cell">
            <div class="label">Total Billed</div>
            <div class="value">${fmt(p.summary.total_billed)}</div>
            <div class="sub">${p.summary.invoice_count} invoice(s)</div>
        </div>
        <div class="summary-cell">
            <div class="label">Total Collected</div>
            <div class="value" style="color:#15803d">${fmt(p.summary.total_collected)}</div>
            <div class="sub">Collection Rate: ${p.summary.collection_rate}%</div>
        </div>
        <div class="summary-cell">
            <div class="label">Outstanding Balance</div>
            <div class="value" style="color:#b91c1c">${fmt(p.summary.outstanding)}</div>
            <div class="sub">${p.summary.unpaid_count} unpaid · ${p.summary.partial_count} partial</div>
        </div>
        <div class="summary-cell">
            <div class="label">Discounts Given</div>
            <div class="value" style="font-size:11pt">${fmt(p.summary.total_discounts)}</div>
            <div class="sub">&nbsp;</div>
        </div>
        <div class="summary-cell">
            <div class="label">Paid Invoices</div>
            <div class="value" style="font-size:11pt">${p.summary.paid_count}</div>
            <div class="sub">Fully settled</div>
        </div>
        <div class="summary-cell">
            <div class="label">Void / Cancelled</div>
            <div class="value" style="font-size:11pt">${p.summary.void_count}</div>
            <div class="sub">&nbsp;</div>
        </div>
    </div>

    <!-- DAILY BREAKDOWN -->
    ${dailyRows ? `
    <div class="section">
        <div class="section-title">Daily Collections Breakdown</div>
        <table>
            <thead><tr>
                <th>Date</th>
                <th class="num">Invoices</th>
                <th class="num">Billed</th>
                <th class="num">Discounts</th>
                <th class="num">Collected</th>
                <th class="num">Balance</th>
            </tr></thead>
            <tbody>${dailyRows}</tbody>
            <tfoot><tr>
                <td><strong>TOTAL</strong></td>
                <td class="num">${p.summary.invoice_count}</td>
                <td class="num">${fmt(p.summary.total_billed)}</td>
                <td class="num">${p.summary.total_discounts > 0 ? fmt(p.summary.total_discounts) : '—'}</td>
                <td class="num">${fmt(p.summary.total_collected)}</td>
                <td class="num">${p.summary.outstanding > 0 ? fmt(p.summary.outstanding) : '—'}</td>
            </tr></tfoot>
        </table>
    </div>` : ''}

    <!-- BY PAYMENT METHOD -->
    ${byMethodRows ? `
    <div class="section">
        <div class="section-title">Collections by Payment Method</div>
        <table>
            <thead><tr>
                <th>Payment Method</th>
                <th class="num">Transactions</th>
                <th class="num">Amount Collected</th>
                <th class="num">% Share</th>
            </tr></thead>
            <tbody>${byMethodRows}</tbody>
            <tfoot><tr>
                <td><strong>TOTAL</strong></td>
                <td class="num">${(p.byMethod ?? []).reduce((s,m) => s + m.transactions, 0)}</td>
                <td class="num">${fmt(p.summary.total_collected)}</td>
                <td class="num">100.0%</td>
            </tr></tfoot>
        </table>
    </div>` : ''}

    <!-- BY VISIT TYPE -->
    ${visitTypeRows ? `
    <div class="section">
        <div class="section-title">Revenue by Visit Type</div>
        <table>
            <thead><tr>
                <th>Visit Type</th>
                <th class="num">Invoices</th>
                <th class="num">Billed</th>
                <th class="num">Collected</th>
                <th class="num">Collection Rate</th>
            </tr></thead>
            <tbody>${visitTypeRows}</tbody>
        </table>
    </div>` : ''}

    <!-- TOP SERVICES -->
    ${serviceRows ? `
    <div class="section">
        <div class="section-title">Top Services by Revenue</div>
        <table>
            <thead><tr>
                <th style="width:30px">#</th>
                <th>Service Name</th>
                <th>Code</th>
                <th class="num">Transactions</th>
                <th class="num">Revenue</th>
            </tr></thead>
            <tbody>${serviceRows}</tbody>
        </table>
    </div>` : ''}

    <!-- COLLECTIONS BY STAFF -->
    ${collectorRows ? `
    <div class="section">
        <div class="section-title">Collections by Staff</div>
        <table>
            <thead><tr>
                <th>Collector</th>
                <th class="num">Transactions</th>
                <th class="num">Amount Collected</th>
                <th class="num">% Share</th>
            </tr></thead>
            <tbody>${collectorRows}</tbody>
            <tfoot><tr>
                <td><strong>TOTAL</strong></td>
                <td class="num">${(p.byCollector ?? []).reduce((s,c) => s + c.transactions, 0)}</td>
                <td class="num">${fmt(p.summary.total_collected)}</td>
                <td class="num">100.0%</td>
            </tr></tfoot>
        </table>
    </div>` : ''}

    <!-- FOOTER -->
    <div class="footer">
        <span>Saint Peter Diagnostics and Laboratory &nbsp;·&nbsp; Claver, Surigao del Norte</span>
        <span>Generated: ${now} &nbsp;·&nbsp; CONFIDENTIAL</span>
    </div>

    </body></html>`

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open(); doc.write(html); doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe) }, 3000)
    }
}
</script>

<template>
    <AppLayout title="Billing Reports">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <BarChart3 class="w-5 h-5 text-blue-600"/> Billing Reports
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">{{ dateLabel }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('billing.index')">
                        <button class="flex items-center gap-1.5 text-xs border border-slate-200 rounded-xl px-3 py-1.5 text-slate-500 hover:border-blue-300 hover:text-blue-600 transition-colors">
                            <Receipt class="w-3.5 h-3.5"/> Invoices
                        </button>
                    </Link>
                    <button @click="printReport"
                        class="flex items-center gap-1.5 text-xs border border-slate-200 rounded-xl px-3 py-1.5 text-slate-500 hover:border-blue-300 hover:text-blue-600 transition-colors no-print">
                        <Printer class="w-3.5 h-3.5"/> Print Report
                    </button>
                </div>
            </div>
        </template>

        <!-- ── Date Range Filter ──────────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm p-4 mb-5 no-print">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button v-for="p in presets" :key="p.value"
                        @click="preset = p.value; if (p.value !== 'custom') applyFilter()"
                        :class="[
                            'text-xs px-3 py-1.5 rounded-lg font-semibold transition-all border',
                            preset === p.value
                                ? 'text-white border-transparent'
                                : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700'
                        ]"
                        :style="preset === p.value ? 'background:#1B4F9B' : ''">
                        {{ p.label }}
                    </button>
                </div>

                <!-- Custom range inputs -->
                <template v-if="preset === 'custom'">
                    <div class="flex items-center gap-2 ml-2">
                        <input v-model="fromDate" type="date"
                            class="h-8 px-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <span class="text-xs text-slate-400">to</span>
                        <input v-model="toDate" type="date"
                            class="h-8 px-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <button @click="applyFilter"
                            class="h-8 px-3 text-xs text-white rounded-lg font-semibold"
                            style="background:#1B4F9B">
                            Apply
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <div id="billing-print-area">

        <!-- Print-only header -->
        <div style="display:none; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid #0F2044;" id="billing-print-header">
            <h1 style="font-size:18px; font-weight:900; color:#0F2044; margin:0 0 4px 0;">St. Peter Diagnostics & Laboratory</h1>
            <h2 style="font-size:14px; font-weight:700; color:#1B4F9B; margin:0 0 2px 0;">Billing Collections Report</h2>
            <p style="font-size:11px; color:#64748b; margin:0;">{{ dateLabel }} &nbsp;·&nbsp; Printed {{ new Date().toLocaleString('en-PH') }}</p>
        </div>

        <!-- ── KPI Summary Cards ──────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-5">

            <div class="bg-card rounded-xl border shadow-sm p-4">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Total Billed</p>
                <p class="text-xl font-black text-slate-800">{{ peso(summary.total_billed) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ summary.invoice_count }} invoices</p>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Collected</p>
                <p class="text-xl font-black text-emerald-700">{{ peso(summary.total_collected) }}</p>
                <div class="flex items-center gap-1 mt-0.5">
                    <div class="flex-1 bg-slate-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full bg-emerald-500 transition-all"
                            :style="{ width: Math.min(summary.collection_rate, 100) + '%' }"/>
                    </div>
                    <span class="text-xs font-bold text-emerald-600">{{ summary.collection_rate }}%</span>
                </div>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Discounts</p>
                <p class="text-xl font-black text-amber-600">{{ peso(summary.total_discounts) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Total given</p>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Outstanding</p>
                <p class="text-xl font-black text-red-600">{{ peso(summary.outstanding) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ summary.unpaid_count + summary.partial_count }} invoices
                </p>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Paid Invoices</p>
                <p class="text-xl font-black text-slate-800">{{ summary.paid_count }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ summary.partial_count }} partial · {{ summary.unpaid_count }} unpaid
                </p>
            </div>
        </div>

        <!-- ── Row 2: Method Breakdown + Status Summary ── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

            <!-- Payment Method Breakdown -->
            <div class="lg:col-span-2 bg-card rounded-xl border shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <Banknote class="w-4 h-4 text-slate-400"/> Collections by Payment Method
                </p>
                <div v-if="byMethod?.length" class="space-y-3">
                    <div v-for="m in byMethod" :key="m.method" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="methodColor[m.method]?.bg ?? 'bg-slate-50'">
                            <component :is="methodIcon[m.method] ?? DollarSign"
                                class="w-4 h-4"
                                :class="methodColor[m.method]?.text ?? 'text-slate-500'"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-semibold capitalize text-slate-700">{{ m.method }}</span>
                                <span class="text-xs font-black" :class="methodColor[m.method]?.text ?? 'text-slate-700'">
                                    {{ peso(m.total) }}
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                    :style="{
                                        width: ((m.total / maxMethodTotal) * 100) + '%',
                                        background: methodColor[m.method]?.bar ?? '#94A3B8'
                                    }"/>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">{{ m.transactions }} transactions</p>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-slate-300 text-sm">No payments in this period</div>
            </div>

            <!-- Invoice Status Summary -->
            <div class="bg-card rounded-xl border shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <FileText class="w-4 h-4 text-slate-400"/> Invoice Status
                </p>
                <div class="space-y-3">
                    <div v-for="s in statusSummary" :key="s.status"
                        class="flex items-center gap-3 p-3 rounded-xl"
                        :class="statusConfig[s.status]?.class">
                        <component :is="statusConfig[s.status]?.icon" class="w-4 h-4 flex-shrink-0"/>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold">{{ s.label }}</p>
                            <p class="text-xs font-semibold mt-0.5">
                                {{ s.count }} invoice{{ s.count !== 1 ? 's' : '' }}
                                <span v-if="s.amount > 0"> · {{ peso(s.amount) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Daily Collections Table ────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden mb-5">
            <div class="px-5 py-3.5 border-b flex items-center gap-2">
                <BarChart3 class="w-4 h-4 text-slate-400"/>
                <p class="text-sm font-bold text-slate-700">Daily Collections Breakdown</p>
            </div>

            <!-- Mini bar chart -->
            <div v-if="dailyBreakdown?.length > 1" class="px-5 pt-4 pb-2">
                <div class="flex items-end gap-1" style="height: 64px;">
                    <div v-for="d in dailyBreakdown" :key="d.date"
                        class="flex-1 flex flex-col items-center gap-0.5 group relative">
                        <div class="w-full rounded-t transition-all"
                            style="background:#1B4F9B; opacity:0.8; min-height:2px;"
                            :style="{ height: Math.max((d.collected / maxDailyCollected) * 56, d.collected > 0 ? 4 : 2) + 'px' }"/>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                            {{ d.label }}: {{ peso(d.collected) }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="dailyBreakdown?.length" class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-y">
                            <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoices</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Billed</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Discounts</th>
                            <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Collected</th>
                            <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="d in dailyBreakdown" :key="d.date"
                            :class="['hover:bg-slate-50 transition-colors', d.invoices === 0 ? 'opacity-40' : '']">
                            <td class="px-5 py-3 text-sm font-semibold text-slate-700">{{ d.label }}</td>
                            <td class="px-4 py-3 text-center text-sm text-slate-600">{{ d.invoices }}</td>
                            <td class="px-4 py-3 text-right text-sm text-slate-700">{{ peso(d.billed) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-amber-600">
                                {{ d.discounts > 0 ? '-' + peso(d.discounts) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-sm text-emerald-700">{{ peso(d.collected) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-black"
                                :class="d.balance > 0 ? 'text-red-500' : 'text-slate-300'">
                                {{ d.balance > 0 ? peso(d.balance) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background:#0F2044">
                            <td class="px-5 py-3 text-sm font-bold text-white">TOTAL</td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-white">{{ summary.invoice_count }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-white">{{ peso(summary.total_billed) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-amber-300">
                                {{ summary.total_discounts > 0 ? '-' + peso(summary.total_discounts) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-black text-emerald-300">{{ peso(summary.total_collected) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-black text-red-300">
                                {{ summary.outstanding > 0 ? peso(summary.outstanding) : '—' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div v-else class="py-10 text-center text-slate-300 text-sm">No data for this period</div>
        </div>

        <!-- ── Row 3: Top Services + Visit Type ──────── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

            <!-- Top Services -->
            <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b flex items-center gap-2">
                    <Stethoscope class="w-4 h-4 text-slate-400"/>
                    <p class="text-sm font-bold text-slate-700">Top Services by Revenue</p>
                </div>
                <div v-if="topServices?.length" class="p-5 space-y-3">
                    <div v-for="(svc, i) in topServices" :key="svc.service_code"
                        class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 text-xs font-black"
                            :style="i < 3 ? 'background:#1B4F9B; color:white' : 'background:#f1f5f9; color:#64748b'">
                            {{ i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-0.5">
                                <span class="text-xs font-semibold text-slate-800 truncate">{{ svc.service_name }}</span>
                                <span class="text-xs font-black text-slate-800 ml-2 flex-shrink-0">{{ peso(svc.revenue) }}</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all"
                                    style="background:#1B4F9B; opacity:0.7"
                                    :style="{ width: ((svc.revenue / maxServiceRevenue) * 100) + '%' }"/>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ svc.service_code }} · {{ svc.transactions }} txns</p>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center text-slate-300 text-sm">No service data</div>
            </div>

            <!-- Revenue by Visit Type -->
            <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b flex items-center gap-2">
                    <Users class="w-4 h-4 text-slate-400"/>
                    <p class="text-sm font-bold text-slate-700">Revenue by Visit Type</p>
                </div>
                <div v-if="byVisitType?.length" class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b">
                                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase">Type</th>
                                <th class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase">Invoices</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase">Billed</th>
                                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase">Collected</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="vt in byVisitType" :key="vt.visit_type"
                                class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                        :style="{
                                            background: VISIT_TYPE_BADGE[vt.visit_type]?.bg ?? '#f1f5f9',
                                            color: VISIT_TYPE_BADGE[vt.visit_type]?.color ?? '#64748b',
                                        }">
                                        {{ VISIT_TYPE_BADGE[vt.visit_type]?.label ?? vt.visit_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-slate-600">{{ vt.invoice_count }}</td>
                                <td class="px-4 py-3 text-right text-sm text-slate-700">{{ peso(vt.billed) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-sm text-emerald-700">{{ peso(vt.collected) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="py-10 text-center text-slate-300 text-sm">No visit data</div>
            </div>
        </div>

        <!-- ── Collector Summary ──────────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden mb-5">
            <div class="px-5 py-3.5 border-b flex items-center gap-2">
                <Users class="w-4 h-4 text-slate-400"/>
                <p class="text-sm font-bold text-slate-700">Collections by Staff</p>
            </div>
            <div v-if="byCollector?.length" class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b">
                            <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Collector</th>
                            <th class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Transactions</th>
                            <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount Collected</th>
                            <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in byCollector" :key="c.collector"
                            class="hover:bg-slate-50">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                        style="background:#1B4F9B">
                                        {{ c.collector?.charAt(0) }}
                                    </div>
                                    <span class="font-semibold text-slate-800 text-sm">{{ c.collector }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center text-sm text-slate-600">{{ c.transactions }}</td>
                            <td class="px-5 py-3.5 text-right font-black text-sm text-emerald-700">{{ peso(c.total) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-20 bg-slate-100 rounded-full h-2">
                                        <div class="h-2 rounded-full bg-emerald-500 transition-all"
                                            :style="{ width: summary.total_collected > 0 ? ((c.total / summary.total_collected) * 100) + '%' : '0%' }"/>
                                    </div>
                                    <span class="text-xs font-bold text-slate-600 w-10 text-right">
                                        {{ summary.total_collected > 0 ? ((c.total / summary.total_collected) * 100).toFixed(1) : 0 }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="py-10 text-center text-slate-300 text-sm">No collection data</div>
        </div>

        </div><!-- /#billing-print-area -->

    </AppLayout>
</template>

