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

const GATED_ROOMS = new Set(['interview_room'])

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

const PE_TYPES = new Set(['pre_employment', 'annual_pe', 'exit_pe'])

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
    const rooms       = ticket.rooms ?? []
    const caseNo      = ticket.case_number ?? 'N/A'
    const isPE        = PE_TYPES.has(ticket.visit_type)

    let extraLabel = ''
    let extraValue = ''
    if (isPE && ticket.employer_company) {
        extraLabel = 'Company'
        extraValue = ticket.employer_company
    } else if (!isPE && ticket.chief_complaint) {
        extraLabel = 'Complaint'
        extraValue = ticket.chief_complaint
    }

    function getRoomServices(roomKey) {
        if (roomKey === 'nurse_station') return ['Vital Signs', 'Nursing Assessment']
        return services.filter(s => SERVICE_ROOM_MAP[s] === roomKey)
    }

    function roomSlipHtml(r) {
        const label    = ROOM_LABEL[r.room] ?? r.room.replace(/_/g, ' ').toUpperCase()
        const isGated  = GATED_ROOMS.has(r.room)
        const roomSvcs = getRoomServices(r.room)
        const svcsText = roomSvcs.join(' &middot; ')

        // Merge company/complaint + services onto one line to save vertical space
        let comboLine = ''
        if (extraLabel && svcsText) {
            comboLine = `<b>${esc(extraLabel)}:</b> ${esc(extraValue)} &nbsp;|&nbsp; <b>Services:</b> ${svcsText}`
        } else if (extraLabel) {
            comboLine = `<b>${esc(extraLabel)}:</b> ${esc(extraValue)}`
        } else if (svcsText) {
            comboLine = `<b>Services:</b> ${svcsText}`
        }

        return `
<div class="room-slip">
  <div class="slip-body">
    <div class="slip-top">
      <div class="slip-left">
        <div class="room-name">${esc(label)}${isGated ? ' <span class="star">&#9733;</span>' : ''}</div>
        <div class="patient-line">${esc(ticket.patient_name)} &nbsp;&bull;&nbsp; <span class="pcode">${esc(ticket.patient_code)}</span></div>
      </div>
      <div class="slip-right">
        <div class="room-qnum">${esc(r.queue_number)}</div>
      </div>
    </div>
    <div class="meta-row">
      <b>Case #:</b> ${esc(caseNo)}<span class="sep">|</span><b>Ticket:</b> ${esc(ticket.ticket_number)}<span class="sep">|</span>${esc(visitLabel)}<span class="sep">|</span><span class="pbadge" style="background:${priorityClr}">${esc(priority)}</span><span class="sep">|</span>${esc(ticket.issued_at)}
    </div>
    ${comboLine ? `<div class="combo-row">${comboLine}</div>` : ''}
    ${isGated ? `<div class="gated-note">&#9733; Proceed here ONLY after completing all other rooms</div>` : ''}
    <div class="sdivider"></div>
    <div class="sig-row">
      <span class="slbl">Received by:</span><span class="sline sline-name"></span>
      <span class="slbl">Date:</span><span class="sline sline-dt"></span>
      <span class="slbl">Time:</span><span class="sline sline-dt"></span>
    </div>
    <div class="sig-row sig-last">
      <span class="slbl">Signature:</span><span class="sline sline-full"></span>
    </div>
  </div>
</div>
<div class="cut-line">&#9988;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash; CUT HERE &mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&#9988;</div>`
    }

    function checklistHtml() {
        const hasGated = rooms.some(r => GATED_ROOMS.has(r.room))
        const rows = rooms.map(r => {
            const label   = ROOM_LABEL[r.room] ?? r.room.replace(/_/g, ' ').toUpperCase()
            const isGated = GATED_ROOMS.has(r.room)
            return `
<div class="cl-row">
  <span class="cl-box">&#9744;</span>
  <span class="cl-room">${esc(label)}${isGated ? ' <span class="star">&#9733;</span>' : ''}</span>
  <span class="cl-qnum">${esc(r.queue_number)}</span>
  <span class="cl-slbl">Staff Signature:</span>
  <span class="cl-sline"></span>
</div>`
        }).join('')

        return `
<div class="pcopy">
  <div class="pc-title">&#9733; PATIENT COPY &mdash; ROUTING CHECKLIST &#9733;</div>
  <div class="pc-info">
    <b>${esc(ticket.patient_name)}</b><span class="sep">|</span>Case #: <b>${esc(caseNo)}</b><span class="sep">|</span>Ticket: <b>${esc(ticket.ticket_number)}</b><span class="sep">|</span>${esc(visitLabel)}<span class="sep">|</span><span class="pbadge" style="background:${priorityClr}">${esc(priority)}</span><span class="sep">|</span>${esc(ticket.issued_at)}
  </div>
  ${extraLabel ? `<div class="pc-extra"><b>${esc(extraLabel)}:</b> ${esc(extraValue)}</div>` : ''}
  <div class="pc-div"></div>
  ${rows}
  ${hasGated ? `<div class="cl-note">&#9733; = Proceed to Interview Room (Doctor) only after ALL other rooms are completed</div>` : ''}
</div>`
    }

    // ── Compute zoom so content always fills top half of A4 ──────────────────
    // A4 usable height (297mm - 6mm top margin - 6mm bottom margin) = 285mm
    // Target = top half = 285mm / 2 = 142mm
    // Estimated natural content height (mm): header(8) + N×slip(31) + N×cut(2) + checklist(27)
    const TARGET_MM   = 142
    const naturalMm   = 8 + rooms.length * 33 + 27
    const zoomFactor  = naturalMm > TARGET_MM
        ? (TARGET_MM / naturalMm).toFixed(4)
        : '1'
    // ────────────────────────────────────────────────────────────────────────

    const slipsHtml   = rooms.map(r => roomSlipHtml(r)).join('')

    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  /*
   * Print on standard A4. CSS zoom scales the content so it always occupies
   * exactly the top half of the A4 sheet — cut along the guide line below.
   */
  @page { size: A4 portrait; margin: 6mm; }

  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 8px;
    color: #1e293b;
    background: white;
    /* zoom forces content to fit in top-half of A4 when there are many rooms */
    zoom: ${zoomFactor};
    width: ${Math.round(197 / Number(zoomFactor))}mm;
  }

  /* ── CLINIC HEADER ─────────────────────────────── */
  .clinic-hdr {
    text-align: center;
    padding-bottom: 3px;
    border-bottom: 1.5px solid #0F2044;
    margin-bottom: 3px;
  }
  .clinic-name { font-size: 11px; font-weight: 900; color: #0F2044; text-transform: uppercase; letter-spacing: 0.4px; line-height: 1.15; }
  .clinic-sub  { font-size: 7px; color: #64748b; line-height: 1.3; }
  .slip-ttl    { font-size: 8px; font-weight: 700; color: #0F2044; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 1px; }

  /* ── ROOM SLIP ─────────────────────────────────── */
  .room-slip { border: 1.5px solid #1e293b; border-radius: 2px; background: white; page-break-inside: avoid; break-inside: avoid; }
  .slip-body { padding: 3px 7px; }

  .slip-top  { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px; }
  .slip-left { flex: 1; min-width: 0; }
  .slip-right{ flex-shrink: 0; padding-left: 6px; text-align: right; }

  .room-name    { font-size: 12px; font-weight: 900; color: #0F2044; line-height: 1.1; }
  .patient-line { font-size: 8.5px; font-weight: 700; color: #1e293b; margin-top: 2px; line-height: 1.2; }
  .pcode        { font-family: monospace; font-weight: 400; color: #64748b; font-size: 7.5px; }
  .room-qnum    { font-size: 26px; font-weight: 900; color: #0F2044; line-height: 1; letter-spacing: 0.5px; }

  .meta-row  { font-size: 7.5px; color: #475569; display: flex; flex-wrap: wrap; align-items: center; gap: 1px 3px; margin-bottom: 1.5px; line-height: 1.3; }
  .combo-row { font-size: 7.5px; color: #334155; margin-bottom: 1.5px; line-height: 1.3; }
  .gated-note{ font-size: 7px; color: #b45309; font-style: italic; margin-bottom: 1px; }

  .pbadge { display: inline-block; font-size: 7px; font-weight: 700; padding: 0.5px 4px; border-radius: 6px; color: white; line-height: 1.4; }
  .sep    { color: #cbd5e1; margin: 0 1px; }
  .star   { color: #d97706; }

  .sdivider { border-top: 1px dashed #94a3b8; margin: 3px 0 2px; }
  .sig-row  { display: flex; align-items: flex-end; gap: 2px; font-size: 7.5px; color: #475569; margin-bottom: 2.5px; }
  .sig-last { margin-bottom: 0; }
  .slbl     { white-space: nowrap; flex-shrink: 0; }
  .sline    { border-bottom: 1px solid #1e293b; display: inline-block; }
  .sline-name { width: 30mm; }
  .sline-dt   { width: 15mm; }
  .sline-full { flex: 1; min-width: 40mm; }

  /* ── CUT LINE BETWEEN SLIPS ────────────────────── */
  .cut-line { text-align: center; font-size: 7px; color: #94a3b8; padding: 1.5px 0; }

  /* ── PATIENT CHECKLIST ─────────────────────────── */
  .pcopy { border: 2px solid #0F2044; border-radius: 2px; padding: 4px 7px; background: #f8fafc; page-break-inside: avoid; break-inside: avoid; }
  .pc-title { font-size: 8.5px; font-weight: 900; text-align: center; color: #0F2044; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 2px; }
  .pc-info  { font-size: 7.5px; color: #475569; display: flex; flex-wrap: wrap; align-items: center; gap: 1px 3px; margin-bottom: 1.5px; line-height: 1.3; }
  .pc-extra { font-size: 7.5px; color: #334155; margin-bottom: 1.5px; }
  .pc-div   { border-top: 1px solid #cbd5e1; margin: 2px 0; }

  .cl-row  { display: flex; align-items: center; gap: 3px; margin-bottom: 3.5px; font-size: 7.5px; }
  .cl-box  { font-size: 11px; flex-shrink: 0; line-height: 1; }
  .cl-room { font-weight: 700; color: #0F2044; min-width: 38mm; }
  .cl-qnum { font-weight: 900; color: #0F2044; font-size: 8.5px; min-width: 12mm; font-family: monospace; }
  .cl-slbl { color: #64748b; flex-shrink: 0; white-space: nowrap; }
  .cl-sline{ flex: 1; border-bottom: 1px solid #1e293b; display: inline-block; min-width: 20mm; }
  .cl-note { font-size: 7px; color: #b45309; font-style: italic; text-align: center; margin-top: 2px; }

  /* ── MID-PAGE CUT GUIDE (fold & cut A4 in half) ── */
  .page-cut-guide {
    margin-top: 6mm;
    text-align: center;
    font-size: 8px;
    color: #94a3b8;
    letter-spacing: 1px;
    border-top: 1.5px dashed #94a3b8;
    padding-top: 3px;
    page-break-inside: avoid;
  }

  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="clinic-hdr">
  <div class="clinic-name">Saint Peter Diagnostics &amp; Laboratory</div>
  <div class="clinic-sub">Claver, Surigao del Norte</div>
  <div class="slip-ttl">Patient Routing Slip</div>
</div>

${slipsHtml}
${checklistHtml()}

<div class="page-cut-guide">
  ✂ &nbsp;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;
  &nbsp;FOLD &amp; CUT HERE — DISCARD LOWER HALF&nbsp;
  &mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash;&mdash; &nbsp;✂
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
