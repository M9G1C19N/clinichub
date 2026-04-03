/**
 * printQueueSlip — prints a thermal-style queue number slip.
 *
 * @param {Object} ticket
 *   ticket_number, patient_name, patient_code, visit_type,
 *   priority, services[], rooms[{room, queue_number}], issued_at
 */

const ROOM_LABEL = {
    laboratory:     'Laboratory',
    xray_utz:       'X-Ray / UTZ',
    drug_test:      'Drug Test',
    interview_room: 'Doctor',
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

export function printQueueSlip(ticket) {
    if (!ticket) return

    const priority     = (ticket.priority ?? 'regular').toUpperCase()
    const priorityClr  = PRIORITY_COLOR[ticket.priority] ?? '#64748b'
    const visitLabel   = VISIT_LABEL[ticket.visit_type]  ?? ticket.visit_type ?? ''
    const servicesHtml = (ticket.services ?? [])
        .map(s => `<span class="svc">${s}</span>`)
        .join('')
    const roomsHtml = (ticket.rooms ?? [])
        .map((r, i) => {
            const label = ROOM_LABEL[r.room] ?? r.room
            return `${i > 0 ? '<span class="arrow">→</span>' : ''}<span class="room">${label}<br/><strong>${r.queue_number}</strong></span>`
        })
        .join('')

    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  @page { size: 80mm auto; margin: 4mm; }
  body {
    font-family: Arial, sans-serif;
    background: white;
    width: 72mm;
    font-size: 10px;
  }
  .clinic-name {
    text-align: center;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #0F2044;
    padding-bottom: 3px;
    border-bottom: 1px solid #ccc;
    margin-bottom: 6px;
  }
  .clinic-sub {
    font-size: 7.5px;
    font-weight: 400;
    color: #666;
    display: block;
    letter-spacing: 0;
    text-transform: none;
  }
  .ticket-box {
    text-align: center;
    border: 2px solid #0F2044;
    border-radius: 6px;
    padding: 8px 6px;
    margin-bottom: 6px;
  }
  .ticket-label {
    font-size: 8px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 2px;
  }
  .ticket-number {
    font-size: 42px;
    font-weight: 900;
    color: #0F2044;
    line-height: 1;
    letter-spacing: 2px;
  }
  .priority-badge {
    display: inline-block;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    margin-top: 5px;
    color: white;
    background: ${priorityClr};
  }
  .section {
    margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px dashed #ddd;
  }
  .row {
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    margin-bottom: 2px;
  }
  .lbl { color: #888; }
  .val { font-weight: 700; color: #1e293b; text-align: right; }
  .patient-name {
    font-size: 11px;
    font-weight: 900;
    color: #0F2044;
    text-align: center;
    margin-bottom: 2px;
  }
  .patient-code {
    font-size: 8px;
    color: #888;
    text-align: center;
    font-family: monospace;
    margin-bottom: 5px;
  }
  .services-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
    margin-bottom: 5px;
  }
  .svc {
    font-size: 8px;
    font-weight: 700;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 3px;
    padding: 2px 5px;
    color: #334155;
  }
  .rooms-wrap {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;
    margin: 5px 0;
  }
  .room {
    text-align: center;
    font-size: 8px;
    color: #475569;
    line-height: 1.3;
  }
  .room strong {
    font-size: 11px;
    color: #0F2044;
    display: block;
  }
  .arrow { font-size: 10px; color: #94a3b8; }
  .footer {
    text-align: center;
    font-size: 7.5px;
    color: #aaa;
    margin-top: 5px;
    border-top: 1px solid #eee;
    padding-top: 4px;
  }
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
  <div class="clinic-name">
    Saint Peter Diagnostics<span class="clinic-sub">& Laboratory — Claver, SDN</span>
  </div>

  <div class="ticket-box">
    <div class="ticket-label">Queue Number</div>
    <div class="ticket-number">${ticket.ticket_number}</div>
    <div class="priority-badge">${priority}</div>
  </div>

  <div class="section">
    <div class="patient-name">${ticket.patient_name ?? ''}</div>
    <div class="patient-code">${ticket.patient_code ?? ''}</div>
    <div class="row">
      <span class="lbl">Visit Type</span>
      <span class="val">${visitLabel}</span>
    </div>
  </div>

  ${servicesHtml ? `
  <div class="section">
    <div style="font-size:8px;color:#888;margin-bottom:3px;">Services</div>
    <div class="services-wrap">${servicesHtml}</div>
  </div>` : ''}

  ${roomsHtml ? `
  <div class="section">
    <div style="font-size:8px;color:#888;text-align:center;margin-bottom:3px;">Room Routing</div>
    <div class="rooms-wrap">${roomsHtml}</div>
  </div>` : ''}

  <div class="footer">
    Issued: ${ticket.issued_at ?? ''}<br/>
    Please wait for your number to be called.
  </div>
</body>
</html>`

    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:80mm;height:200mm;border:none;'
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
