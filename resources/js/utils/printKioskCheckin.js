/**
 * printKioskCheckin — prints a thermal-style check-in reference slip.
 *
 * @param {Object} checkin
 *   patient_name, patient_code, visit_type, priority, services[], checked_in_at
 */

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

export function printKioskCheckin(checkin) {
    if (!checkin) return

    const priority    = (checkin.priority ?? 'regular').toUpperCase()
    const priorityClr = PRIORITY_COLOR[checkin.priority] ?? '#64748b'
    const visitLabel  = VISIT_LABEL[checkin.visit_type]  ?? checkin.visit_type ?? ''
    const servicesHtml = (checkin.services ?? [])
        .map(s => `<span class="svc">${s}</span>`)
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
  .checkin-box {
    text-align: center;
    border: 2px dashed #d97706;
    border-radius: 6px;
    padding: 10px 6px;
    margin-bottom: 6px;
    background: #fffbeb;
  }
  .checkin-label {
    font-size: 8px;
    color: #b45309;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 4px;
    font-weight: 700;
  }
  .checkin-icon {
    font-size: 28px;
    line-height: 1;
    margin-bottom: 4px;
  }
  .checkin-title {
    font-size: 13px;
    font-weight: 900;
    color: #0F2044;
    line-height: 1.2;
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
    margin-bottom: 4px;
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
  .instruction-box {
    border: 1.5px solid #0F2044;
    border-radius: 5px;
    padding: 6px 8px;
    text-align: center;
    margin-bottom: 5px;
  }
  .instruction-label {
    font-size: 7.5px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 2px;
  }
  .instruction-text {
    font-size: 10px;
    font-weight: 900;
    color: #0F2044;
  }
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

  <div class="checkin-box">
    <div class="checkin-label">Kiosk Check-In</div>
    <div class="checkin-icon">&#10003;</div>
    <div class="checkin-title">Check-In Recorded</div>
    <div class="priority-badge">${priority}</div>
  </div>

  <div class="section">
    <div class="patient-name">${checkin.patient_name ?? ''}</div>
    <div class="patient-code">${checkin.patient_code ?? ''}</div>
    <div class="row">
      <span class="lbl">Visit Type</span>
      <span class="val">${visitLabel}</span>
    </div>
    <div class="row">
      <span class="lbl">Checked In</span>
      <span class="val">${checkin.checked_in_at ?? ''}</span>
    </div>
  </div>

  ${servicesHtml ? `
  <div class="section">
    <div style="font-size:8px;color:#888;margin-bottom:3px;">Requested Services</div>
    <div class="services-wrap">${servicesHtml}</div>
  </div>` : ''}

  <div class="instruction-box">
    <div class="instruction-label">Next Step</div>
    <div class="instruction-text">Proceed to Reception Counter</div>
  </div>

  <div class="footer">
    Present this slip to the receptionist.<br/>
    Payment and queue number will be issued at the counter.
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
