<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    BarChart3, Users, CalendarDays, Receipt, FlaskConical,
    ScanLine, TestTube, Stethoscope, TrendingUp, AlertTriangle,
    CheckCircle2, Clock, Printer, Building2, HeartPulse,
    Banknote, Smartphone, CreditCard, DollarSign, Activity,
    ChevronRight, ArrowUp, UserCheck, UserX,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'

const props = defineProps({
    filters:   Object,
    dateLabel: String,
    overview:  Object,
    patients:  Object,
    clinical:  Object,
    financial: Object,
    queue:     Object,
    trend:     Array,
})

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const pct  = (n, d) => d > 0 ? ((n / d) * 100).toFixed(1) + '%' : '0%'

// ── Date range ────────────────────────────────────────
const preset   = ref(props.filters.preset ?? 'this_month')
const fromDate = ref(props.filters.from ?? '')
const toDate   = ref(props.filters.to ?? '')

const presets = [
    { value: 'today',      label: 'Today' },
    { value: 'yesterday',  label: 'Yesterday' },
    { value: 'this_week',  label: 'This Week' },
    { value: 'last_week',  label: 'Last Week' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'this_year',  label: 'This Year' },
    { value: 'custom',     label: 'Custom' },
]

function applyFilter() {
    router.get(route('reports'), {
        preset: preset.value,
        from:   preset.value === 'custom' ? fromDate.value : undefined,
        to:     preset.value === 'custom' ? toDate.value   : undefined,
    }, { preserveScroll: true, replace: true })
}

// ── Active tab ────────────────────────────────────────
const activeTab = ref('overview')
const tabs = [
    { key: 'overview',  label: 'Overview',   icon: BarChart3 },
    { key: 'patients',  label: 'Patients',   icon: Users },
    { key: 'clinical',  label: 'Clinical',   icon: Stethoscope },
    { key: 'financial', label: 'Financial',  icon: Receipt },
    { key: 'queue',     label: 'Queue',      icon: HeartPulse },
]

// ── Chart helpers ─────────────────────────────────────
const maxTrendVisits  = computed(() => Math.max(...(props.trend ?? []).map(d => d.visits), 1))
const maxTrendRevenue = computed(() => Math.max(...(props.trend ?? []).map(d => d.revenue), 1))

const maxAgeCount = computed(() =>
    Math.max(...(props.patients?.ageGroups ?? []).map(g => g.count), 1)
)
const maxCompanyVisits = computed(() =>
    Math.max(...(props.patients?.topCompanies ?? []).map(c => c.visits), 1)
)
const maxMethodTotal = computed(() =>
    Math.max(...(props.financial?.byMethod ?? []).map(m => m.total), 1)
)

// ── Method helpers ────────────────────────────────────
const methodIcon = { cash: Banknote, gcash: Smartphone, maya: Smartphone, card: CreditCard, philhealth: Receipt, other: DollarSign }
const methodBar  = { cash: '#10B981', gcash: '#3B82F6', maya: '#9333EA', card: '#64748B', philhealth: '#0D9488', other: '#94A3B8' }
const methodText = { cash: 'text-emerald-600', gcash: 'text-blue-600', maya: 'text-purple-600', card: 'text-slate-600', philhealth: 'text-teal-600', other: 'text-slate-500' }

// ── Status label maps ─────────────────────────────────
const labStatusColor = { pending: 'bg-amber-100 text-amber-700', released: 'bg-emerald-100 text-emerald-700', in_progress: 'bg-blue-100 text-blue-700' }
const drugStatusColor = { pending: 'bg-amber-100 text-amber-700', completed: 'bg-emerald-100 text-emerald-700', positive: 'bg-red-100 text-red-700', negative: 'bg-teal-100 text-teal-700' }
const priorityColor  = { regular: 'bg-slate-100 text-slate-600', senior: 'bg-blue-100 text-blue-700', pwd: 'bg-purple-100 text-purple-700', pregnant: 'bg-pink-100 text-pink-700', urgent: 'bg-red-100 text-red-700' }

const imagingTypeLabel = {
    chest_xray_pa:      'Chest X-Ray (PA)',
    kub:                'KUB X-Ray',
    ultrasound_abdomen: 'UTZ Abdomen',
    ultrasound_ob:      'UTZ OB',
    ultrasound_pelvis:  'UTZ Pelvis',
    ecg:                'ECG',
    other:              'Other',
}

const peClassLabel = {
    fit:              'Fit for Work',
    fit_with_remarks: 'Fit with Remarks',
    unfit:            'Unfit for Work',
    pending:          'Pending Results',
}

function printReport() {
    const p   = props
    const ov  = p.overview  ?? {}
    const pat = p.patients  ?? {}
    const clin= p.clinical  ?? {}
    const fin = p.financial ?? {}
    const q   = p.queue     ?? {}
    const tr  = p.trend     ?? []

    const now = new Date().toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' })

    const $ = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
    const n = (v) => Number(v ?? 0).toLocaleString('en-PH')
    const pctFn = (a, b) => b > 0 ? ((a / b) * 100).toFixed(1) + '%' : '0%'

    const methodLabels = { cash: 'Cash', gcash: 'GCash', maya: 'Maya', card: 'Card', philhealth: 'PhilHealth', other: 'Other' }
    const visitLabels  = { opd: 'OPD', pre_employment: 'Pre-Employment', annual_pe: 'Annual PE', exit_pe: 'Exit PE', follow_up: 'Follow-Up', lab_only: 'Lab Only' }
    const labStatusLbl = { pending: 'Pending', released: 'Released', in_progress: 'In Progress' }
    const drugStatusLbl= { pending: 'Pending', completed: 'Completed', positive: 'Positive', negative: 'Negative' }
    const peClassLbl   = { fit: 'Fit for Work', fit_with_remarks: 'Fit with Remarks', unfit: 'Unfit for Work', pending: 'Pending' }
    const imgTypeLbl   = { chest_xray_pa: 'Chest X-Ray (PA)', kub: 'KUB X-Ray', ultrasound_abdomen: 'UTZ Abdomen', ultrasound_ob: 'UTZ OB', ultrasound_pelvis: 'UTZ Pelvis', ecg: 'ECG', other: 'Other' }
    const priorityLbl  = { regular: 'Regular', senior: 'Senior Citizen', pwd: 'PWD', pregnant: 'Pregnant', urgent: 'Urgent' }

    // ── Helper: build a standard two-column label/value summary grid ──
    const summaryCell = (label, value, sub = '') => `
        <td style="width:25%; padding:10px 12px; border:1px solid #CBD5E1; vertical-align:top;">
            <div style="font-size:9px; color:#64748B; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;">${label}</div>
            <div style="font-size:15px; font-weight:700; color:#0F2044;">${value}</div>
            ${sub ? `<div style="font-size:9px; color:#94A3B8; margin-top:2px;">${sub}</div>` : ''}
        </td>`

    // ── Helper: standard table ──
    const tableWrap = (title, headerRow, bodyRows, foot = '') => `
        <div style="margin-bottom:20px; page-break-inside:avoid;">
            <div style="background:#0F2044; color:#fff; padding:7px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">${title}</div>
            <table style="width:100%; border-collapse:collapse; font-size:10px;">
                <thead>
                    <tr style="background:#1B4F9B; color:#fff;">
                        ${headerRow}
                    </tr>
                </thead>
                <tbody>${bodyRows}</tbody>
                ${foot ? `<tfoot style="background:#F1F5F9; font-weight:700;">${foot}</tfoot>` : ''}
            </table>
        </div>`

    const th = (t, align = 'left') => `<th style="padding:6px 10px; text-align:${align}; font-size:9px; letter-spacing:.04em;">${t}</th>`
    const td = (t, align = 'left', bold = false) => `<td style="padding:6px 10px; text-align:${align}; border-bottom:1px solid #E2E8F0; ${bold ? 'font-weight:700;' : ''}">${t ?? ''}</td>`
    const altRow = (i) => i % 2 === 1 ? 'background:#F8FAFC;' : ''

    // ══════════════════════════════════════════════
    // SECTION 1: OVERVIEW
    // ══════════════════════════════════════════════
    const overviewSection = `
        <div style="margin-bottom:24px;">
            <div style="background:#0F2044; color:#fff; padding:7px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">Overview Summary</div>
            <table style="width:100%; border-collapse:collapse; border:1px solid #CBD5E1;">
                <tr>
                    ${summaryCell('Total Patients (Registered)', n(ov.totalPatients))}
                    ${summaryCell('Total Visits', n(ov.totalVisits))}
                    ${summaryCell('Consultations', n(ov.totalConsultations))}
                    ${summaryCell('Queue Tickets Issued', n(ov.totalTickets))}
                </tr>
                <tr>
                    ${summaryCell('Lab Requests', n(ov.totalLabRequests))}
                    ${summaryCell('Imaging Requests', n(ov.totalImagingRequests))}
                    ${summaryCell('Drug Tests', n(ov.totalDrugTests))}
                    ${summaryCell('Total Revenue', $(ov.totalRevenue), 'Billed amount')}
                </tr>
            </table>
        </div>

        ${tr.length ? tableWrap(
            'Daily Trend',
            [th('Date'), th('Visits', 'right'), th('Revenue', 'right')].join(''),
            tr.map((d, i) => `<tr style="${altRow(i)}">
                ${td(d.date)}
                ${td(n(d.visits), 'right')}
                ${td($(d.revenue), 'right')}
            </tr>`).join('')
        ) : ''}`

    // ══════════════════════════════════════════════
    // SECTION 2: PATIENTS
    // bySex is an object {male:N, female:N}, not array
    // ══════════════════════════════════════════════
    const bySexEntries  = Object.entries(pat.bySex ?? {})
    const sexTotal      = bySexEntries.reduce((s, [, v]) => s + Number(v), 0)
    const newRegTotal   = (pat.newRegistrations ?? []).reduce((s, r) => s + Number(r.count), 0)
    const patientSection = `
        <div style="margin-bottom:24px;">
            <div style="background:#0F2044; color:#fff; padding:7px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">Patient Statistics</div>
            <table style="width:100%; border-collapse:collapse; border:1px solid #CBD5E1;">
                <tr>
                    ${summaryCell('New Registrations', n(newRegTotal), 'within period')}
                    ${summaryCell('Returning Patients', n(pat.repeatCount), 'repeat visits')}
                    ${summaryCell('New Visitors (first visit)', n(pat.newVisitorCount), 'in period')}
                    ${summaryCell('Total Visits', n(ov.totalVisits))}
                </tr>
            </table>
        </div>

        ${tableWrap(
            'Sex Breakdown',
            [th('Sex'), th('Count', 'right'), th('% Share', 'right')].join(''),
            bySexEntries.map(([sex, cnt], i) => `<tr style="${altRow(i)}">
                ${td(sex === 'male' ? 'Male' : sex === 'female' ? 'Female' : 'Unknown')}
                ${td(n(cnt), 'right')}
                ${td(pctFn(cnt, sexTotal), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Age Group Distribution',
            [th('Age Group'), th('Count', 'right')].join(''),
            (pat.ageGroups ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(r.group ?? r.age_group)}
                ${td(n(r.count), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Visit Types',
            [th('Visit Type'), th('Count', 'right'), th('% Share', 'right')].join(''),
            (pat.byVisitType ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(visitLabels[r.visit_type] ?? r.visit_type)}
                ${td(n(r.count), 'right')}
                ${td(pctFn(r.count, ov.totalVisits), 'right')}
            </tr>`).join('')
        )}

        ${(pat.topCompanies ?? []).length ? tableWrap(
            'Top Companies / Employers',
            [th('Company'), th('Visits', 'right')].join(''),
            (pat.topCompanies ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(r.company || '(Individual)')}
                ${td(n(r.visits), 'right')}
            </tr>`).join('')
        ) : ''}`

    // ══════════════════════════════════════════════
    // SECTION 3: CLINICAL
    // labByStatus, imagingByModality, drugTestByStatus, consultByType are objects
    // peClassifications is array of {classification, count}
    // ══════════════════════════════════════════════
    const clinicalSection = `
        ${tableWrap(
            'Laboratory Results by Status',
            [th('Status'), th('Count', 'right')].join(''),
            Object.entries(clin.labByStatus ?? {}).map(([status, cnt], i) => `<tr style="${altRow(i)}">
                ${td(labStatusLbl[status] ?? status)}
                ${td(n(cnt), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Imaging Requests by Type',
            [th('Imaging Type'), th('Count', 'right')].join(''),
            Object.entries(clin.imagingByModality ?? {}).map(([type, cnt], i) => `<tr style="${altRow(i)}">
                ${td(imgTypeLbl[type] ?? type)}
                ${td(n(cnt), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Drug Test Results by Status',
            [th('Status'), th('Count', 'right')].join(''),
            Object.entries(clin.drugTestByStatus ?? {}).map(([status, cnt], i) => `<tr style="${altRow(i)}">
                ${td(drugStatusLbl[status] ?? status)}
                ${td(n(cnt), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Consultations by Visit Type',
            [th('Visit Type'), th('Count', 'right')].join(''),
            Object.entries(clin.consultByType ?? {}).map(([type, cnt], i) => `<tr style="${altRow(i)}">
                ${td(visitLabels[type] ?? type)}
                ${td(n(cnt), 'right')}
            </tr>`).join('')
        )}

        ${(clin.peClassifications ?? []).length ? tableWrap(
            'PE Classifications',
            [th('Classification'), th('Count', 'right')].join(''),
            (clin.peClassifications ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(peClassLbl[r.classification] ?? r.classification)}
                ${td(n(r.count), 'right')}
            </tr>`).join('')
        ) : ''}`

    // ══════════════════════════════════════════════
    // SECTION 4: FINANCIAL
    // byMethod is array {method, transactions, total}
    // byVisitType is array {visit_type, count, billed, collected}
    // ══════════════════════════════════════════════
    const finTotal = (fin.byMethod ?? []).reduce((s, r) => s + Number(r.total), 0)
    const financialSection = `
        <div style="margin-bottom:24px;">
            <div style="background:#0F2044; color:#fff; padding:7px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">Financial Summary</div>
            <table style="width:100%; border-collapse:collapse; border:1px solid #CBD5E1;">
                <tr>
                    ${summaryCell('Total Billed', $(fin.totalBilled))}
                    ${summaryCell('Total Collected', $(fin.totalCollected), pctFn(fin.totalCollected, fin.totalBilled) + ' collection rate')}
                    ${summaryCell('Total Discounts', $(fin.totalDiscounts))}
                    ${summaryCell('Outstanding Balance', $(fin.totalOutstanding))}
                </tr>
            </table>
        </div>

        ${tableWrap(
            'Collections by Payment Method',
            [th('Method'), th('Transactions', 'right'), th('Amount', 'right'), th('% Share', 'right')].join(''),
            (fin.byMethod ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(methodLabels[r.method] ?? r.method)}
                ${td(n(r.transactions), 'right')}
                ${td($(r.total), 'right')}
                ${td(pctFn(r.total, finTotal), 'right')}
            </tr>`).join(''),
            `<tr>
                ${td('<strong>TOTAL</strong>', 'left', true)}
                ${td('', 'right')}
                ${td('<strong>' + $(finTotal) + '</strong>', 'right', true)}
                ${td('100%', 'right')}
            </tr>`
        )}

        ${tableWrap(
            'Revenue by Visit Type',
            [th('Visit Type'), th('Invoices', 'right'), th('Billed', 'right'), th('Collected', 'right'), th('Rate', 'right')].join(''),
            (fin.byVisitType ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(visitLabels[r.visit_type] ?? r.visit_type)}
                ${td(n(r.count), 'right')}
                ${td($(r.billed), 'right')}
                ${td($(r.collected), 'right')}
                ${td(pctFn(r.collected, r.billed), 'right')}
            </tr>`).join('')
        )}`

    // ══════════════════════════════════════════════
    // SECTION 5: QUEUE
    // byPriority and byVisitType are objects {key: count}
    // byCounter is array {counter_name, counter_code, total, completed}
    // top-level keys: total, completed, no_show, avg_wait_min
    // ══════════════════════════════════════════════
    const queueSection = `
        <div style="margin-bottom:24px;">
            <div style="background:#0F2044; color:#fff; padding:7px 12px; font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">Queue Summary</div>
            <table style="width:100%; border-collapse:collapse; border:1px solid #CBD5E1;">
                <tr>
                    ${summaryCell('Total Tickets', n(q.total))}
                    ${summaryCell('Completed', n(q.completed))}
                    ${summaryCell('No-Show', n(q.no_show))}
                    ${summaryCell('Avg. Wait Time', (q.avg_wait_min ?? 0) + ' min')}
                </tr>
            </table>
        </div>

        ${tableWrap(
            'Queue by Counter',
            [th('Counter'), th('Total', 'right'), th('Completed', 'right')].join(''),
            (q.byCounter ?? []).map((r, i) => `<tr style="${altRow(i)}">
                ${td(r.counter_name ?? r.counter_code)}
                ${td(n(r.total), 'right')}
                ${td(n(r.completed), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Queue by Priority',
            [th('Priority'), th('Count', 'right'), th('% Share', 'right')].join(''),
            Object.entries(q.byPriority ?? {}).map(([priority, cnt], i) => `<tr style="${altRow(i)}">
                ${td(priorityLbl[priority] ?? priority)}
                ${td(n(cnt), 'right')}
                ${td(pctFn(cnt, q.total), 'right')}
            </tr>`).join('')
        )}

        ${tableWrap(
            'Queue by Visit Type',
            [th('Visit Type'), th('Count', 'right')].join(''),
            Object.entries(q.byVisitType ?? {}).map(([type, cnt], i) => `<tr style="${altRow(i)}">
                ${td(visitLabels[type] ?? type)}
                ${td(n(cnt), 'right')}
            </tr>`).join('')
        )}`

    // ══════════════════════════════════════════════
    // ASSEMBLE FULL DOCUMENT
    // ══════════════════════════════════════════════
    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Clinic Report — ${p.dateLabel}</title>
<style>
    @page { size: A4 portrait; margin: 18mm 15mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #1E293B; background: #fff; }

    /* ── Letterhead ─────────────────────────────── */
    .letterhead { display: flex; align-items: center; gap: 14px; padding-bottom: 12px; border-bottom: 3px solid #0F2044; margin-bottom: 14px; }
    .letterhead img { width: 60px; height: 60px; object-fit: contain; }
    .letterhead-text { flex: 1; }
    .letterhead-name { font-size: 16px; font-weight: 900; color: #0F2044; line-height: 1.2; }
    .letterhead-sub  { font-size: 10px; color: #1B4F9B; font-weight: 700; margin-top: 1px; }
    .letterhead-addr { font-size: 8.5px; color: #64748B; margin-top: 3px; line-height: 1.5; }

    /* ── Report Title ───────────────────────────── */
    .report-title { background: #1B4F9B; color: #fff; padding: 8px 14px; margin-bottom: 16px; }
    .report-title h2 { font-size: 13px; font-weight: 800; letter-spacing: .04em; }
    .report-title p  { font-size: 8.5px; opacity: .85; margin-top: 2px; }

    /* ── Section break ──────────────────────────── */
    .section-break { page-break-before: always; margin-top: 0; }

    /* ── Footer ─────────────────────────────────── */
    .doc-footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #CBD5E1; display: flex; justify-content: space-between; font-size: 8px; color: #94A3B8; }
</style>
</head>
<body>

<!-- LETTERHEAD -->
<div class="letterhead">
    <img src="/images/spdl-logo.png" alt="Logo">
    <div class="letterhead-text">
        <div class="letterhead-name">Saint Peter Diagnostics and Laboratory</div>
        <div class="letterhead-sub">Medical and Dental Clinic</div>
        <div class="letterhead-addr">
            Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410<br>
            Tel: 09204043408 / 09516832212 &nbsp;·&nbsp; spdl.claver2007@gmail.com
        </div>
    </div>
</div>

<!-- REPORT TITLE -->
<div class="report-title">
    <h2>CLINIC REPORT — ALL SECTIONS</h2>
    <p>Period: ${p.dateLabel} &nbsp;·&nbsp; Generated: ${now}</p>
</div>

<!-- OVERVIEW -->
${overviewSection}

<!-- PAGE BREAK: PATIENTS -->
<div class="section-break"></div>
<div style="font-size:12px; font-weight:800; color:#0F2044; border-bottom:2px solid #0F2044; padding-bottom:4px; margin-bottom:12px;">PATIENT STATISTICS</div>
${patientSection}

<!-- PAGE BREAK: CLINICAL -->
<div class="section-break"></div>
<div style="font-size:12px; font-weight:800; color:#0F2044; border-bottom:2px solid #0F2044; padding-bottom:4px; margin-bottom:12px;">CLINICAL DATA</div>
${clinicalSection}

<!-- PAGE BREAK: FINANCIAL -->
<div class="section-break"></div>
<div style="font-size:12px; font-weight:800; color:#0F2044; border-bottom:2px solid #0F2044; padding-bottom:4px; margin-bottom:12px;">FINANCIAL DATA</div>
${financialSection}

<!-- PAGE BREAK: QUEUE -->
<div class="section-break"></div>
<div style="font-size:12px; font-weight:800; color:#0F2044; border-bottom:2px solid #0F2044; padding-bottom:4px; margin-bottom:12px;">QUEUE STATISTICS</div>
${queueSection}

<!-- FOOTER -->
<div class="doc-footer">
    <span>Saint Peter Diagnostics and Laboratory — CONFIDENTIAL</span>
    <span>Printed: ${now}</span>
</div>

</body>
</html>`

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(html)
    doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe) }, 3000)
    }
}
</script>

<template>
    <AppLayout title="Reports">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <BarChart3 class="w-5 h-5 text-blue-600"/> Clinic Reports
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">{{ dateLabel }}</p>
                </div>
                <button @click="printReport"
                    class="no-print flex items-center gap-1.5 text-xs border border-slate-200 rounded-xl px-3 py-1.5 text-slate-500 hover:border-blue-300 hover:text-blue-600 transition-colors">
                    <Printer class="w-3.5 h-3.5"/> Print Report
                </button>
            </div>
        </template>

        <!-- ── Date Range Filter ──────────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm p-4 mb-4 no-print">
            <div class="flex items-center gap-2 flex-wrap">
                <button v-for="p in presets" :key="p.value"
                    @click="preset = p.value; if (p.value !== 'custom') applyFilter()"
                    :class="['text-xs px-3 py-1.5 rounded-lg font-semibold transition-all border',
                        preset === p.value
                            ? 'text-white border-transparent'
                            : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:text-slate-700']"
                    :style="preset === p.value ? 'background:#1B4F9B' : ''">
                    {{ p.label }}
                </button>
                <template v-if="preset === 'custom'">
                    <div class="flex items-center gap-2 ml-1">
                        <input v-model="fromDate" type="date"
                            class="h-8 px-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <span class="text-xs text-slate-400">to</span>
                        <input v-model="toDate" type="date"
                            class="h-8 px-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <button @click="applyFilter" class="h-8 px-3 text-xs text-white rounded-lg font-semibold" style="background:#1B4F9B">
                            Apply
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- ── Tabs ───────────────────────────────────── -->
        <div class="flex items-center gap-1 mb-5 no-print border-b border-slate-200 pb-0">
            <button v-for="tab in tabs" :key="tab.key"
                @click="activeTab = tab.key"
                :class="['flex items-center gap-1.5 px-4 py-2.5 text-xs font-semibold transition-all border-b-2 -mb-px',
                    activeTab === tab.key
                        ? 'border-blue-600 text-blue-700'
                        : 'border-transparent text-slate-500 hover:text-slate-700']">
                <component :is="tab.icon" class="w-3.5 h-3.5"/>
                {{ tab.label }}
            </button>
        </div>

        <!-- ── Print Area (wraps all tab content) ────────── -->
        <div id="print-area">

        <!-- Print-only header -->
        <div class="hidden print:block mb-6 pb-4 border-b-2" style="display:none" id="print-header">
            <h1 style="font-size:20px; font-weight:900; color:#0F2044; margin:0 0 4px 0;">St. Peter Diagnostics & Laboratory</h1>
            <h2 style="font-size:15px; font-weight:700; color:#1B4F9B; margin:0 0 2px 0;">Clinic Report — {{ tabs.find(t => t.key === activeTab)?.label }}</h2>
            <p style="font-size:11px; color:#64748b; margin:0;">{{ dateLabel }} &nbsp;·&nbsp; Printed {{ new Date().toLocaleString('en-PH') }}</p>
        </div>

        <!-- ═══════════════════════════════════════════════
             TAB: OVERVIEW
        ════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'overview'" class="space-y-5">

            <!-- KPI Row 1: Patients + Visits -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <Users class="w-5 h-5 text-blue-600"/>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase tracking-widest">Total Patients</p>
                            <p class="text-2xl font-black text-slate-800">{{ overview.totalPatients.toLocaleString() }}</p>
                            <p class="text-xs text-emerald-600 font-semibold">+{{ overview.newPatients }} new this period</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <CalendarDays class="w-5 h-5 text-purple-600"/>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase tracking-widest">Visits</p>
                            <p class="text-2xl font-black text-slate-800">{{ overview.totalVisits }}</p>
                            <p class="text-xs text-slate-400">{{ overview.completedVisits }} completed · {{ overview.pendingVisits }} pending</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <TrendingUp class="w-5 h-5 text-emerald-600"/>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase tracking-widest">Revenue</p>
                            <p class="text-2xl font-black text-emerald-700">{{ peso(overview.revenue) }}</p>
                            <p class="text-xs text-slate-400">{{ peso(overview.billed) }} billed</p>
                        </div>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                            <AlertTriangle class="w-5 h-5 text-red-500"/>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase tracking-widest">Outstanding</p>
                            <p class="text-2xl font-black text-red-600">{{ peso(overview.outstanding) }}</p>
                            <p class="text-xs text-slate-400">All-time unpaid balance</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Row 2: Clinical Workload -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <FlaskConical class="w-4 h-4 text-blue-600"/>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Lab Requests</p>
                        <p class="text-xl font-black text-slate-800">{{ overview.labRequests }}</p>
                    </div>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                        <ScanLine class="w-4 h-4 text-purple-600"/>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Imaging</p>
                        <p class="text-xl font-black text-slate-800">{{ overview.imagingRequests }}</p>
                    </div>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center flex-shrink-0">
                        <TestTube class="w-4 h-4 text-rose-600"/>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Drug Tests</p>
                        <p class="text-xl font-black text-slate-800">{{ overview.drugTests }}</p>
                    </div>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                        <Stethoscope class="w-4 h-4 text-teal-600"/>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Consultations</p>
                        <p class="text-xl font-black text-slate-800">{{ overview.consultations }}</p>
                    </div>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                        <HeartPulse class="w-4 h-4 text-amber-600"/>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Queue Tickets</p>
                        <p class="text-xl font-black text-slate-800">{{ overview.tickets }}</p>
                    </div>
                </div>
            </div>

            <!-- Trend Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- Daily Visits Trend -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-slate-400"/> Daily Visits
                    </p>
                    <div v-if="trend?.length" class="flex items-end gap-1" style="height:80px">
                        <div v-for="d in trend" :key="d.date" class="flex-1 flex flex-col items-center group relative">
                            <div class="w-full rounded-t transition-all"
                                style="background:#1B4F9B; opacity:0.8; min-height:2px"
                                :style="{ height: Math.max((d.visits / maxTrendVisits) * 64, d.visits > 0 ? 4 : 2) + 'px' }"/>
                            <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10">
                                {{ d.label }}: {{ d.visits }} visits
                            </div>
                        </div>
                    </div>
                    <div v-if="trend?.length > 1" class="flex justify-between text-xs text-slate-400 mt-1">
                        <span>{{ trend[0]?.label }}</span>
                        <span>{{ trend[trend.length - 1]?.label }}</span>
                    </div>
                </div>

                <!-- Daily Revenue Trend -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <TrendingUp class="w-4 h-4 text-slate-400"/> Daily Revenue Collected
                    </p>
                    <div v-if="trend?.length" class="flex items-end gap-1" style="height:80px">
                        <div v-for="d in trend" :key="d.date" class="flex-1 flex flex-col items-center group relative">
                            <div class="w-full rounded-t transition-all"
                                style="background:#10B981; opacity:0.8; min-height:2px"
                                :style="{ height: Math.max((d.revenue / maxTrendRevenue) * 64, d.revenue > 0 ? 4 : 2) + 'px' }"/>
                            <div class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none z-10">
                                {{ d.label }}: {{ peso(d.revenue) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="trend?.length > 1" class="flex justify-between text-xs text-slate-400 mt-1">
                        <span>{{ trend[0]?.label }}</span>
                        <span>{{ trend[trend.length - 1]?.label }}</span>
                    </div>
                </div>
            </div>

            <!-- Pending Workload Alert -->
            <div v-if="clinical.pendingLab > 0 || clinical.pendingImaging > 0 || clinical.pendingDrug > 0"
                class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <AlertTriangle class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"/>
                <div>
                    <p class="text-sm font-bold text-amber-800">Pending Work Items</p>
                    <div class="flex items-center gap-4 mt-1 flex-wrap">
                        <span v-if="clinical.pendingLab > 0" class="text-xs font-semibold text-amber-700">
                            {{ clinical.pendingLab }} lab request{{ clinical.pendingLab !== 1 ? 's' : '' }} pending
                        </span>
                        <span v-if="clinical.pendingImaging > 0" class="text-xs font-semibold text-amber-700">
                            {{ clinical.pendingImaging }} imaging request{{ clinical.pendingImaging !== 1 ? 's' : '' }} pending
                        </span>
                        <span v-if="clinical.pendingDrug > 0" class="text-xs font-semibold text-amber-700">
                            {{ clinical.pendingDrug }} drug test{{ clinical.pendingDrug !== 1 ? 's' : '' }} pending
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════
             TAB: PATIENTS
        ════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'patients'" class="space-y-5">

            <!-- Row 1: Sex + New vs Repeat -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Sex Distribution -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <Users class="w-4 h-4 text-slate-400"/> Sex Distribution
                    </p>
                    <div class="space-y-3">
                        <div v-for="(count, sex) in patients.bySex" :key="sex">
                            <div class="flex justify-between mb-1">
                                <span class="text-xs font-semibold capitalize text-slate-700">{{ sex }}</span>
                                <span class="text-xs font-black text-slate-800">{{ count }}
                                    <span class="text-slate-400 font-normal">({{ pct(count, overview.totalPatients) }})</span>
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all"
                                    :style="{
                                        width: pct(count, overview.totalPatients),
                                        background: sex === 'male' ? '#3B82F6' : '#EC4899',
                                    }"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New vs Repeat Visitors -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <UserCheck class="w-4 h-4 text-slate-400"/> Visitor Type (This Period)
                    </p>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <UserCheck class="w-5 h-5 text-emerald-600"/>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">New Visitors</p>
                                    <p class="text-xl font-black text-emerald-700">{{ patients.newVisitorCount }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <UserX class="w-5 h-5 text-blue-600"/>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Returning Patients</p>
                                    <p class="text-xl font-black text-blue-700">{{ patients.repeatCount }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="patients.newVisitorCount + patients.repeatCount > 0">
                            <div class="w-full bg-slate-100 rounded-full h-3 flex overflow-hidden">
                                <div class="h-3 bg-emerald-500 transition-all"
                                    :style="{ width: pct(patients.newVisitorCount, patients.newVisitorCount + patients.repeatCount) }"/>
                                <div class="h-3 bg-blue-400 transition-all flex-1"/>
                            </div>
                            <div class="flex justify-between text-xs text-slate-400 mt-1">
                                <span class="text-emerald-600 font-semibold">{{ pct(patients.newVisitorCount, patients.newVisitorCount + patients.repeatCount) }} new</span>
                                <span class="text-blue-600 font-semibold">{{ pct(patients.repeatCount, patients.newVisitorCount + patients.repeatCount) }} returning</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit Type Breakdown -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-slate-400"/> Visits by Type
                    </p>
                    <div v-if="patients.byVisitType?.length" class="space-y-2.5">
                        <div v-for="vt in patients.byVisitType" :key="vt.visit_type"
                            class="flex items-center gap-3">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded flex-shrink-0 w-28 text-center truncate"
                                :style="{
                                    background: VISIT_TYPE_BADGE[vt.visit_type]?.bg ?? '#f1f5f9',
                                    color: VISIT_TYPE_BADGE[vt.visit_type]?.color ?? '#64748b',
                                }">
                                {{ VISIT_TYPE_BADGE[vt.visit_type]?.label ?? vt.visit_type }}
                            </span>
                            <div class="flex-1 bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full"
                                    :style="{
                                        width: pct(vt.count, overview.totalVisits),
                                        background: VISIT_TYPE_BADGE[vt.visit_type]?.color ?? '#94A3B8',
                                    }"/>
                            </div>
                            <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ vt.count }}</span>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-slate-300 text-sm">No visits in this period</div>
                </div>
            </div>

            <!-- Age Groups -->
            <div class="bg-card rounded-xl border shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <Activity class="w-4 h-4 text-slate-400"/> Age Group Distribution (All Registered Patients)
                </p>
                <div v-if="patients.ageGroups?.length" class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                    <div v-for="g in patients.ageGroups" :key="g.group" class="text-center">
                        <div class="flex flex-col items-center justify-end mb-2" style="height:80px">
                            <div class="w-full rounded-t-lg transition-all"
                                style="background:#1B4F9B; opacity:0.75; min-height:4px;"
                                :style="{ height: Math.max((g.count / maxAgeCount) * 70, 4) + 'px' }"/>
                        </div>
                        <p class="text-xs font-black text-slate-800">{{ g.count }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ g.group }}</p>
                    </div>
                </div>
            </div>

            <!-- New Registrations Trend + Top Companies -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- New Registrations -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <ArrowUp class="w-4 h-4 text-emerald-500"/> New Patient Registrations
                    </p>
                    <div v-if="patients.newRegistrations?.length">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase">Date</th>
                                        <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">New Patients</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="r in patients.newRegistrations" :key="r.date" class="hover:bg-slate-50">
                                        <td class="py-2.5 text-sm text-slate-700">{{ r.date }}</td>
                                        <td class="py-2.5 text-right">
                                            <span class="text-sm font-bold text-emerald-700">+{{ r.count }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No new registrations in this period</div>
                </div>

                <!-- Top Companies -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <Building2 class="w-4 h-4 text-slate-400"/> Top Employer Companies (PE Visits)
                    </p>
                    <div v-if="patients.topCompanies?.length" class="space-y-3">
                        <div v-for="(c, i) in patients.topCompanies" :key="c.company" class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black flex-shrink-0"
                                :style="i < 3 ? 'background:#1B4F9B; color:white' : 'background:#f1f5f9; color:#64748b'">
                                {{ i + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between mb-0.5">
                                    <span class="text-xs font-semibold text-slate-800 truncate">{{ c.company }}</span>
                                    <span class="text-xs font-black text-slate-700 ml-2 flex-shrink-0">{{ c.visits }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all"
                                        style="background:#1B4F9B; opacity:0.7"
                                        :style="{ width: ((c.visits / maxCompanyVisits) * 100) + '%' }"/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No PE visits in this period</div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════
             TAB: CLINICAL
        ════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'clinical'" class="space-y-5">

            <!-- Workload Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <FlaskConical class="w-4 h-4 text-blue-600"/>
                        <p class="text-xs font-bold text-slate-700">Laboratory</p>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(count, status) in clinical.labByStatus" :key="status"
                            class="flex items-center justify-between">
                            <span class="text-xs px-2 py-0.5 rounded font-semibold capitalize"
                                :class="labStatusColor[status] ?? 'bg-slate-100 text-slate-600'">
                                {{ status.replace('_', ' ') }}
                            </span>
                            <span class="text-sm font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div v-if="!Object.keys(clinical.labByStatus ?? {}).length"
                            class="text-xs text-slate-400 text-center py-2">No data</div>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <ScanLine class="w-4 h-4 text-purple-600"/>
                        <p class="text-xs font-bold text-slate-700">Imaging (X-Ray/UTZ)</p>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(count, status) in clinical.imagingByStatus" :key="status"
                            class="flex items-center justify-between">
                            <span class="text-xs px-2 py-0.5 rounded font-semibold capitalize"
                                :class="labStatusColor[status] ?? 'bg-slate-100 text-slate-600'">
                                {{ status.replace('_', ' ') }}
                            </span>
                            <span class="text-sm font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div v-if="!Object.keys(clinical.imagingByStatus ?? {}).length"
                            class="text-xs text-slate-400 text-center py-2">No data</div>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <TestTube class="w-4 h-4 text-rose-600"/>
                        <p class="text-xs font-bold text-slate-700">Drug Test</p>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(count, status) in clinical.drugTestByStatus" :key="status"
                            class="flex items-center justify-between">
                            <span class="text-xs px-2 py-0.5 rounded font-semibold capitalize"
                                :class="drugStatusColor[status] ?? 'bg-slate-100 text-slate-600'">
                                {{ status.replace('_', ' ') }}
                            </span>
                            <span class="text-sm font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div v-if="!Object.keys(clinical.drugTestByStatus ?? {}).length"
                            class="text-xs text-slate-400 text-center py-2">No data</div>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <ScanLine class="w-4 h-4 text-indigo-600"/>
                        <p class="text-xs font-bold text-slate-700">By Imaging Type</p>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(count, type) in clinical.imagingByModality" :key="type"
                            class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-600">{{ imagingTypeLabel[type] ?? type }}</span>
                            <span class="text-sm font-black text-slate-800">{{ count }}</span>
                        </div>
                        <div v-if="!Object.keys(clinical.imagingByModality ?? {}).length"
                            class="text-xs text-slate-400 text-center py-2">No data</div>
                    </div>
                </div>
            </div>

            <!-- Consultations + PE Classifications -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <Stethoscope class="w-4 h-4 text-slate-400"/> Consultations by Visit Type
                    </p>
                    <div v-if="Object.keys(clinical.consultByType ?? {}).length" class="space-y-2.5">
                        <div v-for="(count, type) in clinical.consultByType" :key="type"
                            class="flex items-center gap-3">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded w-28 text-center flex-shrink-0"
                                :style="{
                                    background: VISIT_TYPE_BADGE[type]?.bg ?? '#f1f5f9',
                                    color: VISIT_TYPE_BADGE[type]?.color ?? '#64748b',
                                }">
                                {{ VISIT_TYPE_BADGE[type]?.label ?? type }}
                            </span>
                            <div class="flex-1 bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full"
                                    :style="{
                                        width: pct(count, overview.consultations),
                                        background: VISIT_TYPE_BADGE[type]?.color ?? '#94A3B8',
                                    }"/>
                            </div>
                            <span class="text-sm font-bold text-slate-700 w-8 text-right">{{ count }}</span>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No consultations in this period</div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4 text-slate-400"/> PE Classifications
                    </p>
                    <div v-if="clinical.peClassifications?.length" class="space-y-3">
                        <div v-for="c in clinical.peClassifications" :key="c.classification"
                            class="flex items-center justify-between p-3 rounded-xl border"
                            :class="{
                                'bg-emerald-50 border-emerald-200': c.classification === 'fit',
                                'bg-amber-50 border-amber-200':    c.classification === 'fit_with_remarks',
                                'bg-red-50 border-red-200':        c.classification === 'unfit',
                                'bg-slate-50 border-slate-200':    !['fit','fit_with_remarks','unfit'].includes(c.classification),
                            }">
                            <span class="text-xs font-semibold"
                                :class="{
                                    'text-emerald-700': c.classification === 'fit',
                                    'text-amber-700':   c.classification === 'fit_with_remarks',
                                    'text-red-700':     c.classification === 'unfit',
                                    'text-slate-600':   !['fit','fit_with_remarks','unfit'].includes(c.classification),
                                }">
                                {{ peClassLabel[c.classification] ?? c.classification }}
                            </span>
                            <span class="text-lg font-black"
                                :class="{
                                    'text-emerald-700': c.classification === 'fit',
                                    'text-amber-700':   c.classification === 'fit_with_remarks',
                                    'text-red-700':     c.classification === 'unfit',
                                    'text-slate-600':   !['fit','fit_with_remarks','unfit'].includes(c.classification),
                                }">
                                {{ c.count }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No PE classifications in this period</div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════
             TAB: FINANCIAL
        ════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'financial'" class="space-y-5">

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Total Billed</p>
                    <p class="text-2xl font-black text-slate-800">{{ peso(financial.totalBilled) }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ financial.invoiceCount }} invoices</p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Collected</p>
                    <p class="text-2xl font-black text-emerald-700">{{ peso(financial.totalCollected) }}</p>
                    <div class="flex items-center gap-1 mt-1">
                        <div class="flex-1 bg-slate-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full bg-emerald-500"
                                :style="{ width: Math.min(financial.collectionRate, 100) + '%' }"/>
                        </div>
                        <span class="text-xs font-bold text-emerald-600">{{ financial.collectionRate }}%</span>
                    </div>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Discounts Given</p>
                    <p class="text-2xl font-black text-amber-600">{{ peso(financial.totalDiscounts) }}</p>
                    <p class="text-xs text-slate-400 mt-1">Applied discounts</p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Outstanding</p>
                    <p class="text-2xl font-black text-red-600">{{ peso(financial.totalOutstanding) }}</p>
                    <p class="text-xs text-slate-400 mt-1">All unpaid/partial</p>
                </div>
            </div>

            <!-- By Method + By Visit Type -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <Banknote class="w-4 h-4 text-slate-400"/> Collections by Payment Method
                    </p>
                    <div v-if="financial.byMethod?.length" class="space-y-3">
                        <div v-for="m in financial.byMethod" :key="m.method" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 bg-slate-50">
                                <component :is="methodIcon[m.method] ?? DollarSign" class="w-4 h-4" :class="methodText[m.method] ?? 'text-slate-500'"/>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between mb-1">
                                    <span class="text-xs font-semibold capitalize text-slate-700">{{ m.method }}</span>
                                    <span class="text-xs font-black" :class="methodText[m.method] ?? 'text-slate-700'">{{ peso(m.total) }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all"
                                        :style="{ width: ((m.total / maxMethodTotal) * 100) + '%', background: methodBar[m.method] ?? '#94A3B8' }"/>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">{{ m.transactions }} transactions</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No payments in this period</div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                        <Receipt class="w-4 h-4 text-slate-400"/> Revenue by Visit Type
                    </p>
                    <div v-if="financial.byVisitType?.length" class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2 text-xs font-semibold text-slate-500 uppercase">Type</th>
                                    <th class="text-center py-2 text-xs font-semibold text-slate-500 uppercase">Inv.</th>
                                    <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Billed</th>
                                    <th class="text-right py-2 text-xs font-semibold text-slate-500 uppercase">Collected</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="vt in financial.byVisitType" :key="vt.visit_type" class="hover:bg-slate-50">
                                    <td class="py-2.5">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                            :style="{
                                                background: VISIT_TYPE_BADGE[vt.visit_type]?.bg ?? '#f1f5f9',
                                                color: VISIT_TYPE_BADGE[vt.visit_type]?.color ?? '#64748b',
                                            }">
                                            {{ VISIT_TYPE_BADGE[vt.visit_type]?.label ?? vt.visit_type }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-center text-sm text-slate-600">{{ vt.count }}</td>
                                    <td class="py-2.5 text-right text-sm font-semibold text-slate-700">{{ peso(vt.billed) }}</td>
                                    <td class="py-2.5 text-right text-sm font-bold text-emerald-700">{{ peso(vt.collected) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No revenue data</div>
                </div>
            </div>

            <!-- Link to detailed billing reports -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Receipt class="w-5 h-5 text-blue-600"/>
                    <div>
                        <p class="text-sm font-bold text-blue-800">Detailed Billing Reports</p>
                        <p class="text-xs text-blue-600">Full daily breakdown, top services, staff collections</p>
                    </div>
                </div>
                <Link :href="route('billing.reports')"
                    class="flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 bg-white border border-blue-200 px-3 py-2 rounded-lg transition-colors">
                    View <ChevronRight class="w-3 h-3"/>
                </Link>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════
             TAB: QUEUE
        ════════════════════════════════════════════════ -->
        <div v-show="activeTab === 'queue'" class="space-y-5">

            <!-- KPI Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-card rounded-xl border shadow-sm p-4 text-center">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Tickets Issued</p>
                    <p class="text-2xl font-black text-slate-800">{{ queue.total }}</p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 text-center">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Completed</p>
                    <p class="text-2xl font-black text-emerald-700">{{ queue.completed }}</p>
                    <p class="text-xs text-emerald-600 font-semibold">{{ pct(queue.completed, queue.total) }}</p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 text-center">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">No-Show</p>
                    <p class="text-2xl font-black text-amber-600">{{ queue.no_show }}</p>
                    <p class="text-xs text-amber-600 font-semibold">{{ pct(queue.no_show, queue.total) }}</p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 text-center">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Cancelled</p>
                    <p class="text-2xl font-black text-slate-500">{{ queue.cancelled }}</p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 text-center">
                    <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Avg Wait</p>
                    <p class="text-2xl font-black text-blue-700">
                        {{ queue.avg_wait_min != null ? queue.avg_wait_min + 'm' : '—' }}
                    </p>
                    <p class="text-xs text-slate-400">Issue → Called</p>
                </div>
            </div>

            <!-- By Counter + Priority -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <!-- By Counter -->
                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b">
                        <p class="text-sm font-bold text-slate-700">Performance by Counter</p>
                    </div>
                    <div v-if="queue.byCounter?.length" class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50 border-b">
                                    <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase">Counter</th>
                                    <th class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase">Issued</th>
                                    <th class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase">Done</th>
                                    <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in queue.byCounter" :key="c.counter_code" class="hover:bg-slate-50">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-xs font-black flex-shrink-0"
                                                style="background:#1B4F9B">
                                                {{ c.counter_code }}
                                            </span>
                                            <span class="text-xs font-semibold text-slate-700 truncate">{{ c.counter_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-slate-700">{{ c.total }}</td>
                                    <td class="px-4 py-3 text-center text-sm font-semibold text-emerald-700">{{ c.completed }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded"
                                            :class="c.total > 0 && (c.completed/c.total) >= 0.8 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                            {{ pct(c.completed, c.total) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="py-10 text-center text-slate-300 text-sm">No queue data</div>
                </div>

                <!-- By Priority -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <p class="text-sm font-bold text-slate-700 mb-4">Tickets by Priority</p>
                    <div v-if="Object.keys(queue.byPriority ?? {}).length" class="space-y-3">
                        <div v-for="(count, priority) in queue.byPriority" :key="priority"
                            class="flex items-center justify-between p-3 rounded-xl"
                            :class="priorityColor[priority] ?? 'bg-slate-50 text-slate-600'">
                            <span class="text-xs font-bold capitalize">{{ priority }}</span>
                            <div class="flex items-center gap-3">
                                <div class="w-20 bg-white/50 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-current opacity-40"
                                        :style="{ width: pct(count, queue.total) }"/>
                                </div>
                                <span class="text-sm font-black w-8 text-right">{{ count }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-slate-300 text-sm">No queue data</div>
                </div>
            </div>

            <!-- Queue by Visit Type -->
            <div class="bg-card rounded-xl border shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <HeartPulse class="w-4 h-4 text-slate-400"/> Queue Tickets by Visit Type
                </p>
                <div v-if="Object.keys(queue.byVisitType ?? {}).length" class="grid grid-cols-2 lg:grid-cols-6 gap-3">
                    <div v-for="(count, type) in queue.byVisitType" :key="type"
                        class="text-center p-3 rounded-xl border"
                        :style="{
                            background: VISIT_TYPE_BADGE[type]?.bg ?? '#f8fafc',
                            borderColor: VISIT_TYPE_BADGE[type]?.color + '40' ?? '#e2e8f0',
                        }">
                        <p class="text-2xl font-black" :style="{ color: VISIT_TYPE_BADGE[type]?.color ?? '#64748b' }">{{ count }}</p>
                        <p class="text-xs font-semibold mt-1" :style="{ color: VISIT_TYPE_BADGE[type]?.color ?? '#64748b' }">
                            {{ VISIT_TYPE_BADGE[type]?.label ?? type }}
                        </p>
                    </div>
                </div>
                <div v-else class="py-6 text-center text-slate-300 text-sm">No queue data</div>
            </div>
        </div>

        </div><!-- /#print-area -->

    </AppLayout>
</template>

