// Service-to-room mapping (mirrors RoomRoutingEngine::SERVICE_ROOM_MAP)
const SERVICE_ROOM_MAP = {
    CBC: 'laboratory', UA: 'laboratory', FECALYSIS: 'laboratory',
    BLOODTYPING: 'laboratory', FBS: 'laboratory', BUN: 'laboratory',
    CREATININE: 'laboratory', URICACID: 'laboratory', CHOLESTEROL: 'laboratory',
    TRIGLYCERIDES: 'laboratory', HDLLDL: 'laboratory', SGOT: 'laboratory',
    SGPT: 'laboratory', HBSAG: 'laboratory', VDRL: 'laboratory',
    PREGNANCY: 'laboratory', DENGUE: 'laboratory', THYROID: 'laboratory',
    RBS: 'laboratory', PSA: 'laboratory', HBA1C: 'laboratory',
    OGTT: 'laboratory', BLOOD_CHEMISTRY: 'laboratory',
    CXRPA: 'xray_utz', XRAY: 'xray_utz', UTZ: 'xray_utz',
    UTZ_ABDOMEN: 'xray_utz', UTZ_KUB: 'xray_utz', UTZ_PELVIS: 'xray_utz',
    ECG: 'xray_utz',
    DRUGTEST: 'drug_test', DRUGTEST5: 'drug_test', MET: 'drug_test', THC: 'drug_test',
    OPD: 'interview_room', CONSULTATION: 'interview_room',
    PE_CONSULT: 'interview_room', ANNUAL_PE: 'interview_room',
    EXIT_PE: 'interview_room', FOLLOW_UP: 'interview_room',
}

const ROOM_LABEL = {
    laboratory:     'LABORATORY',
    xray_utz:       'X-RAY & ULTRASOUND',
    drug_test:      'DRUG TEST',
    nurse_station:  'NURSE STATION',
    interview_room: 'INTERVIEW ROOM (DOCTOR)',
}

const VISIT_LABEL = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    annual_pe:      'Annual PE',
    exit_pe:        'Exit PE',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}

const PRIORITY_COLOR = {
    urgent:   '#dc2626',
    pregnant: '#db2777',
    pwd:      '#2563eb',
    senior:   '#d97706',
    regular:  '#64748b',
}

const PE_TYPES   = new Set(['pre_employment', 'annual_pe', 'exit_pe'])
const SLIP_ROOMS = new Set(['drug_test', 'laboratory', 'xray_utz'])

const CHECKLIST_DEFS = [
    {
        label:     'VITAL SIGNS',
        room:      'nurse_station',
        condition: (roomMap) => !!roomMap['nurse_station'],
    },
    {
        label:     'PHYSICAL EXAM / MEDICAL HISTORY',
        room:      'interview_room',
        condition: (roomMap) => !!roomMap['interview_room'],
    },
    {
        label:     'BLOOD EXTRACTION',
        room:      'laboratory',
        condition: (_roomMap, services) =>
            services.some(s => SERVICE_ROOM_MAP[s] === 'laboratory' && s !== 'UA' && s !== 'FECALYSIS'),
    },
    {
        label:     'URINE SAMPLE',
        room:      'laboratory',
        condition: (_roomMap, services) => services.includes('UA'),
    },
    {
        label:     'STOOL SAMPLE',
        room:      'laboratory',
        condition: (_roomMap, services) => services.includes('FECALYSIS'),
    },
    {
        label:     'DRUG TEST',
        room:      'drug_test',
        condition: (roomMap) => !!roomMap['drug_test'],
    },
    {
        label:     'CHEST X-RAY / X-RAY & ULTRASOUND',
        room:      'xray_utz',
        condition: (roomMap) => !!roomMap['xray_utz'],
    },
]

const ROMAN = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X']

function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
}

export function printQueueSlip(ticket) {
    if (!ticket) return

    const priority    = (ticket.priority ?? 'regular').toUpperCase()
    const priorityClr = PRIORITY_COLOR[ticket.priority] ?? '#64748b'
    const visitLabel  = VISIT_LABEL[ticket.visit_type] ?? ticket.visit_type ?? ''
    const services    = ticket.services ?? []
    const allRooms    = ticket.rooms ?? []
    const caseNo      = ticket.case_number ?? 'N/A'
    const isPE        = PE_TYPES.has(ticket.visit_type)
    const logoSrc     = `${window.location.origin}/images/spdl-logo.png`

    let extraLabel = ''
    let extraValue = ''
    if (isPE && ticket.employer_company) {
        extraLabel = 'Company'
        extraValue = ticket.employer_company
    } else if (!isPE && ticket.chief_complaint) {
        extraLabel = 'Complaint'
        extraValue = ticket.chief_complaint
    }

    const roomMap = {}
    allRooms.forEach(r => { roomMap[r.room] = r })

    const SLIP_ORDER = ['drug_test', 'laboratory', 'xray_utz']
    const slipRooms  = SLIP_ORDER.map(key => roomMap[key]).filter(Boolean)

    // ── LEFT: room slips ────────────────────────────────────────────────────

    function roomSlipHtml(r) {
        const label    = ROOM_LABEL[r.room] ?? r.room.replace(/_/g, ' ').toUpperCase()
        const roomSvcs = services.filter(s => SERVICE_ROOM_MAP[s] === r.room)
        const svcsText = roomSvcs.join(' &middot; ')

        return `
<div class="room-slip">
  <div class="slip-header">
    <div class="slip-room-name">${esc(label)}</div>
    <div class="slip-qnum">${esc(r.queue_number)}</div>
  </div>
  <div class="slip-patient-name">${esc(ticket.patient_name)}</div>
  <div class="slip-patient-code">${esc(ticket.patient_code)}</div>
  <div class="slip-meta">
    <b>Case #:</b>&nbsp;${esc(caseNo)}&nbsp;<span class="sep">|</span>&nbsp;<b>Ticket:</b>&nbsp;${esc(ticket.ticket_number)}&nbsp;<span class="sep">|</span>&nbsp;<span class="sl-visit">${esc(visitLabel)}</span>&nbsp;<span class="sep">|</span>&nbsp;<span class="pbadge" style="background:${priorityClr}">${esc(priority)}</span>
  </div>
  ${extraLabel ? `<div class="slip-extra"><b>${esc(extraLabel)}:</b> ${esc(extraValue)}</div>` : ''}
  ${svcsText ? `<div class="slip-svcs"><b>Svcs:</b> ${svcsText}</div>` : ''}
  <div class="slip-sdiv"></div>
  <div class="sig-block">
    <div class="sig-row">
      <span class="sig-lbl">Received by:</span><span class="sig-line sig-name"></span>
      &nbsp;<span class="sig-lbl">Date:</span><span class="sig-line sig-date"></span>
      &nbsp;<span class="sig-lbl">Time:</span><span class="sig-line sig-time"></span>
    </div>
    <div class="sig-row">
      <span class="sig-lbl">Signature:</span><span class="sig-line sig-full"></span>
    </div>
  </div>
</div>`
    }

    const leftHtml = slipRooms.length > 0
        ? slipRooms.map(r => roomSlipHtml(r)).join(`
<div class="slip-cut">
  <span class="slip-cut-label">&#9988;&nbsp; CUT HERE &nbsp;&#9988;</span>
</div>`)
        : '<div class="no-slips">No applicable rooms</div>'

    // ── RIGHT: checklist ────────────────────────────────────────────────────

    let romanIdx = 0
    const checklistItems = CHECKLIST_DEFS
        .filter(def => def.condition(roomMap, services))
        .map(def => ({
            roman: ROMAN[romanIdx++],
            label: def.label,
            qnum:  roomMap[def.room]?.queue_number ?? '—',
        }))

    // Each item is flex:1 so items spread evenly to fill remaining height
    const checklistHtml = checklistItems.map(item => `
<div class="cc-item">
  <div class="cc-item-body">
    <div class="cc-item-top">
      <span class="cc-roman">${item.roman}.</span>
      <span class="cc-item-name">${esc(item.label)}</span>
      <span class="cc-qnum">Q#&nbsp;<b>${esc(item.qnum)}</b></span>
    </div>
    <div class="cc-item-sig">
      <span class="cc-sig-lbl">Staff Signature:</span>
      <span class="cc-sig-line"></span>
    </div>
  </div>
</div>`).join('')

    const vitalSignsHtml = roomMap['nurse_station'] ? `
<div class="vs-section">
  <div class="vs-title">Vital Signs</div>
  <div class="vs-grid">
    <div class="vs-row">
      <span class="vs-lbl">A. Weight (kg.)</span>
      <span class="vs-line"></span>
    </div>
    <div class="vs-row">
      <span class="vs-lbl">B. Height (m)</span>
      <span class="vs-line"></span>
    </div>
    <div class="vs-row">
      <span class="vs-lbl">D. Pulse (beats/min)</span>
      <span class="vs-line"></span>
    </div>
    <div class="vs-row vs-bp-row">
      <span class="vs-lbl">E. BP</span>
      <span class="vs-line vs-bp"></span>
      <span class="vs-slash">/</span>
      <span class="vs-line vs-bp"></span>
      <span class="vs-unit">mmHg</span>
    </div>
  </div>
  <div class="vs-row vs-lmp">
    <span class="vs-lbl">Last Normal Menstrual Period</span>
    <span class="vs-line"></span>
  </div>
</div>` : ''

    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  @page { size: A4 portrait; margin: 6mm; }

  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10px;
    color: #1e293b;
    background: white;
    width: 198mm;
  }

  /* ── TWO-COLUMN WRAPPER — top half of A4 (140mm) ─── */
  .page-wrap {
    display: flex;
    flex-direction: row;
    width: 198mm;
    height: 140mm;
    align-items: stretch;
    overflow: hidden;
  }

  /* ── LEFT COLUMN ─────────────────────────────────── */
  .left-col {
    width: 88mm;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  /* ── CENTER: vertical cut line ───────────────────── */
  .cut-col {
    width: 9mm;
    flex-shrink: 0;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .cut-vline {
    position: absolute;
    top: 0; bottom: 0; left: 50%;
    transform: translateX(-50%);
    border-left: 1.5px dashed #94a3b8;
  }
  .cut-scissors {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12mm;
    background: white;
    padding: 4mm 1mm;
  }
  .cut-icon { font-size: 11px; color: #94a3b8; transform: rotate(90deg); display: block; }

  /* ── RIGHT COLUMN ─────────────────────────────────── */
  .right-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding-left: 2mm;
    overflow: hidden;
    min-width: 0;
  }

  /* ══ LEFT: ROOM SLIP STYLES ════════════════════════ */
  .room-slip {
    height: 44mm;
    flex-shrink: 0;
    border: 2px solid #1e293b;
    border-radius: 3px;
    padding: 2.5mm 3mm 2mm;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: white;
  }
  .slip-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1mm;
  }
  .slip-room-name { font-size: 13px; font-weight: 900; color: #0F2044; line-height: 1.1; flex: 1; }
  .slip-qnum      { font-size: 32px; font-weight: 900; color: #0F2044; line-height: 1; letter-spacing: -0.5px; text-align: right; margin-left: 2mm; flex-shrink: 0; }
  .slip-patient-name { font-size: 10.5px; font-weight: 700; color: #1e293b; line-height: 1.2; margin-bottom: 0.3mm; }
  .slip-patient-code { font-size: 8px; font-family: monospace; color: #64748b; margin-bottom: 1mm; }
  .slip-meta {
    font-size: 7.5px; color: #475569; line-height: 1.5; margin-bottom: 0.8mm;
    display: flex; flex-wrap: wrap; align-items: center; gap: 0 1px;
  }
  .sep      { color: #cbd5e1; }
  .sl-visit { font-weight: 700; }
  .pbadge   { display: inline-block; font-size: 7px; font-weight: 700; padding: 1px 4px; border-radius: 6px; color: white; line-height: 1.4; }
  .slip-extra { font-size: 7.5px; color: #334155; margin-bottom: 0.5mm; line-height: 1.3; }
  .slip-svcs  { font-size: 7px; color: #475569; margin-bottom: 0.5mm; line-height: 1.3; }
  .slip-sdiv  { border-top: 1px dashed #94a3b8; margin: 1mm 0; flex-shrink: 0; }
  .sig-block  { display: flex; flex-direction: column; gap: 1mm; flex-shrink: 0; }
  .sig-row    { display: flex; align-items: flex-end; gap: 1px; font-size: 7.5px; color: #475569; }
  .sig-lbl    { white-space: nowrap; flex-shrink: 0; }
  .sig-line   { border-bottom: 1px solid #1e293b; display: inline-block; }
  .sig-name   { width: 20mm; }
  .sig-date   { width: 12mm; }
  .sig-time   { width: 10mm; }
  .sig-full   { flex: 1; min-width: 30mm; }

  .slip-cut {
    height: 4mm; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; position: relative;
  }
  .slip-cut::before {
    content: ''; position: absolute; left: 0; right: 0; top: 50%;
    border-top: 1.5px dashed #94a3b8;
  }
  .slip-cut-label {
    position: relative; z-index: 1; background: white; padding: 0 3px;
    font-size: 6.5px; color: #94a3b8; letter-spacing: 0.3px; font-style: italic;
  }
  .no-slips { font-size: 10px; color: #94a3b8; text-align: center; padding: 8mm; }

  /* ══ RIGHT: CUSTOMER'S COPY STYLES ════════════════ */

  /* Header */
  .cc-header {
    display: flex; align-items: center; gap: 2mm;
    border-bottom: 2px solid #0F2044; padding-bottom: 1mm; margin-bottom: 1mm;
    flex-shrink: 0;
  }
  .cc-logo img     { width: 12mm; height: 12mm; object-fit: contain; flex-shrink: 0; }
  .cc-clinic       { flex: 1; line-height: 1.2; }
  .cc-clinic-name  { font-size: 11px; font-weight: 900; color: #0F2044; text-transform: uppercase; letter-spacing: 0.2px; line-height: 1.1; }
  .cc-clinic-sub   { font-size: 8.5px; color: #475569; }
  .cc-clinic-addr  { font-size: 7.5px; color: #64748b; line-height: 1.25; }
  .cc-title-block  { text-align: center; border: 2px solid #0F2044; padding: 1.5mm 2mm; border-radius: 3px; flex-shrink: 0; background: #0F2044; }
  .cc-title        { font-size: 12px; font-weight: 900; color: white; letter-spacing: 0.8px; text-transform: uppercase; line-height: 1.2; }
  .cc-subtitle     { font-size: 7px; color: #93c5fd; }

  /* Patient info — 3-column grid */
  .cc-patient {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.5mm 3mm;
    margin-bottom: 1mm;
    flex-shrink: 0;
  }
  .cc-field      { display: flex; flex-direction: column; border-bottom: 1px solid #cbd5e1; padding-bottom: 0.3mm; }
  .cc-field-full { grid-column: 1 / -1; }
  .cc-field-two  { grid-column: span 2; }
  .cc-lbl        { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.2px; line-height: 1.2; }
  .cc-val        { font-size: 11px; font-weight: 700; color: #1e293b; line-height: 1.25; min-height: 2mm; }

  /* Vital signs — 2-column compact grid */
  .vs-section {
    border: 1.5px solid #0F2044; border-radius: 3px;
    padding: 1mm 2.5mm 1mm; margin-bottom: 1mm; flex-shrink: 0;
  }
  .vs-title {
    font-size: 11px; font-weight: 900; color: #0F2044; text-align: center;
    text-transform: uppercase; letter-spacing: 0.5px;
    border-bottom: 1.5px solid #cbd5e1; padding-bottom: 0.5mm; margin-bottom: 0.8mm;
  }
  .vs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1mm 4mm; margin-bottom: 0.8mm; }
  .vs-row      { display: flex; align-items: flex-end; gap: 2mm; font-size: 10px; color: #1e293b; }
  .vs-lbl      { flex-shrink: 0; line-height: 1.4; white-space: nowrap; font-weight: 600; }
  .vs-line     { flex: 1; border-bottom: 1.5px solid #1e293b; min-height: 2.5mm; }
  .vs-bp       { width: 13mm; flex: none; }
  .vs-slash    { flex-shrink: 0; font-weight: 900; font-size: 11px; }
  .vs-unit     { flex-shrink: 0; color: #64748b; font-size: 9px; }
  .vs-lmp      { margin-top: 0; }

  .cc-divider { border-top: 1.5px solid #0F2044; margin-bottom: 1mm; flex-shrink: 0; }

  /* Checklist title */
  .cc-checklist-title {
    font-size: 8px; font-weight: 900; color: #0F2044;
    text-transform: uppercase; letter-spacing: 0.4px;
    text-align: center; margin-bottom: 0.8mm; flex-shrink: 0;
  }

  /* Checklist container — flex:1 fills remaining height */
  .cc-checklist {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
    min-height: 0;
  }

  /* Each item stretches evenly in remaining space */
  .cc-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border-bottom: 1px dashed #e2e8f0;
    padding: 0;
  }
  .cc-item:last-child { border-bottom: none; }

  .cc-item-top {
    display: flex; align-items: baseline; gap: 1mm; margin-bottom: 0.3mm;
  }
  .cc-roman     { font-size: 8px; font-weight: 900; color: #0F2044; min-width: 6.5mm; text-align: right; flex-shrink: 0; }
  .cc-item-name { font-size: 8px; font-weight: 700; color: #1e293b; flex: 1; line-height: 1.2; }
  .cc-qnum      { font-size: 7.5px; color: #475569; flex-shrink: 0; white-space: nowrap; }
  .cc-item-sig  { display: flex; align-items: center; gap: 1mm; padding-left: 7.5mm; }
  .cc-sig-lbl   { font-size: 7px; color: #64748b; flex-shrink: 0; white-space: nowrap; }
  .cc-sig-line  { flex: 1; border-bottom: 1px solid #1e293b; }

  /* ── HORIZONTAL CUT GUIDE ─────────────────────────── */
  .h-cut-guide {
    margin-top: 3mm; border-top: 1.5px dashed #94a3b8;
    text-align: center; padding-top: 2px;
    font-size: 7.5px; color: #94a3b8; letter-spacing: 0.5px;
  }

  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="page-wrap">

  <!-- LEFT: Room slips (Drug Test / Laboratory / X-Ray & UTZ) -->
  <div class="left-col">
    ${leftHtml}
  </div>

  <!-- CENTER: Vertical cut line -->
  <div class="cut-col">
    <div class="cut-vline"></div>
    <div class="cut-scissors">
      <span class="cut-icon">&#9988;</span>
      <span class="cut-icon">&#9988;</span>
    </div>
  </div>

  <!-- RIGHT: Customer's Copy -->
  <div class="right-col">

    <div class="cc-header">
      <div class="cc-logo"><img src="${logoSrc}" alt="SPDL Logo"></div>
      <div class="cc-clinic">
        <div class="cc-clinic-name">Saint Peter Diagnostics and Laboratory</div>
        <div class="cc-clinic-sub">Medical and Dental Clinic</div>
        <div class="cc-clinic-addr">Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</div>
        <div class="cc-clinic-addr">Tel. 09516832212 &nbsp;|&nbsp; spdl.claver2007@gmail.com</div>
      </div>
      <div class="cc-title-block">
        <div class="cc-title">CUSTOMER'S<br>COPY</div>
        <div class="cc-subtitle">Routing Slip</div>
      </div>
    </div>

    <div class="cc-patient">
      <div class="cc-field cc-field-full">
        <span class="cc-lbl">Patient Name</span>
        <span class="cc-val">${esc(ticket.patient_name)}</span>
      </div>
      <div class="cc-field">
        <span class="cc-lbl">Age / Sex</span>
        <span class="cc-val">${esc(ticket.age_sex ?? '—')}</span>
      </div>
      <div class="cc-field">
        <span class="cc-lbl">Birthdate</span>
        <span class="cc-val">${esc(ticket.date_of_birth ?? '—')}</span>
      </div>
      <div class="cc-field">
        <span class="cc-lbl">Civil Status</span>
        <span class="cc-val">${esc(ticket.civil_status ?? '—')}</span>
      </div>
      <div class="cc-field">
        <span class="cc-lbl">Control No.</span>
        <span class="cc-val">${esc(caseNo)}</span>
      </div>
      <div class="cc-field">
        <span class="cc-lbl">Ticket No.</span>
        <span class="cc-val">${esc(ticket.ticket_number)}</span>
      </div>
      <div class="cc-field">
        <span class="cc-lbl">Exam Type</span>
        <span class="cc-val">${esc(visitLabel)}</span>
      </div>
      ${extraLabel ? `
      <div class="cc-field cc-field-two">
        <span class="cc-lbl">${esc(extraLabel)}</span>
        <span class="cc-val">${esc(extraValue)}</span>
      </div>` : ''}
      <div class="cc-field" style="grid-column:3">
        <span class="cc-lbl">Date / Time Issued</span>
        <span class="cc-val">${esc(ticket.issued_at ?? '—')}</span>
      </div>
      ${isPE ? `
      <div class="cc-field cc-field-full">
        <span class="cc-lbl">Position Applied</span>
        <span class="cc-val">${esc(ticket.position_applied ?? '')}</span>
      </div>` : ''}
      <div class="cc-field cc-field-full">
        <span class="cc-lbl">Address</span>
        <span class="cc-val">${esc(ticket.address ?? '')}</span>
      </div>
    </div>

    ${vitalSignsHtml}

    <div class="cc-divider"></div>

    <div class="cc-checklist-title">&#9472;&#9472; Routing Checklist &#9472;&#9472;</div>

    <div class="cc-checklist">
      ${checklistHtml}
    </div>

  </div>

</div>

<div class="h-cut-guide">
  &#9988;&nbsp;
  &mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;
  &nbsp;FOLD &amp; CUT HERE — DISCARD LOWER HALF&nbsp;
  &mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;
  &nbsp;&#9988;
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
        setTimeout(() => {
            if (document.body.contains(iframe)) document.body.removeChild(iframe)
        }, 3000)
    }
}
