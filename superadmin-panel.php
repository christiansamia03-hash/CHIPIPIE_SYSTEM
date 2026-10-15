<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ChipiPie — Super Admin Control Center</title>
  <meta name="description" content="ChipiPie Cafe Super Admin Panel — System-wide management and approval center">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --sa-bg:       #0a0a0f;
      --sa-surface:  #111118;
      --sa-card:     #16161f;
      --sa-border:   #25253a;
      --sa-border-2: #1e1e2e;
      --sa-accent:   #6d28d9;
      --sa-accent2:  #7c3aed;
      --sa-gold:     #f59e0b;
      --sa-red:      #ef4444;
      --sa-green:    #10b981;
      --sa-blue:     #3b82f6;
      --sa-text:     #e2e8f0;
      --sa-muted:    #64748b;
      --sa-muted2:   #475569;
      --sa-sidebar-w: 260px;
      --sa-glow:     0 0 40px rgba(109,40,217,0.15);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', system-ui, sans-serif;
      background: var(--sa-bg);
      color: var(--sa-text);
      height: 100vh;
      overflow: hidden;
      display: flex;
    }

    /* ── SIDEBAR ── */
    .sa-sidebar {
      width: var(--sa-sidebar-w);
      min-width: var(--sa-sidebar-w);
      background: var(--sa-surface);
      border-right: 1px solid var(--sa-border);
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
    }

    .sa-brand {
      padding: 22px 20px 18px;
      border-bottom: 1px solid var(--sa-border);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sa-brand-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(135deg, #6d28d9, #a855f7);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
      box-shadow: 0 4px 15px rgba(109,40,217,0.4);
    }

    .sa-brand-text h5 {
      font-size: 13px;
      font-weight: 800;
      color: #fff;
      letter-spacing: 0.03em;
      line-height: 1.2;
    }

    .sa-brand-text small {
      font-size: 10px;
      color: #a855f7;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.12em;
    }

    .sa-nav {
      flex: 1;
      padding: 14px 10px;
      overflow-y: auto;
    }

    .sa-nav-label {
      font-size: 9.5px;
      font-weight: 700;
      letter-spacing: 0.12em;
      color: var(--sa-muted);
      text-transform: uppercase;
      padding: 8px 10px 5px;
      margin-top: 6px;
    }

    .sa-nav-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 9px 12px;
      border: none;
      background: transparent;
      color: var(--sa-muted);
      border-radius: 9px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      text-align: left;
      transition: all 0.15s;
      margin-bottom: 2px;
      position: relative;
    }

    .sa-nav-btn i {
      font-size: 16px;
      width: 20px;
    }

    .sa-nav-btn:hover {
      background: rgba(255,255,255,0.05);
      color: var(--sa-text);
    }

    .sa-nav-btn.active {
      background: linear-gradient(135deg, rgba(109,40,217,0.25), rgba(168,85,247,0.12));
      color: #c084fc;
      border: 1px solid rgba(109,40,217,0.3);
    }

    .sa-nav-btn .badge {
      margin-left: auto;
      background: var(--sa-red);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 99px;
      min-width: 20px;
      text-align: center;
    }

    .sa-sidebar-footer {
      padding: 14px 16px;
      border-top: 1px solid var(--sa-border);
    }

    .sa-user-card {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sa-avatar {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #6d28d9, #a855f7);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 14px;
      color: #fff;
      flex-shrink: 0;
    }

    .sa-user-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--sa-text);
      line-height: 1.2;
    }

    .sa-user-role {
      font-size: 10px;
      color: #a855f7;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .sa-logout-btn {
      margin-left: auto;
      background: transparent;
      border: none;
      color: var(--sa-muted);
      cursor: pointer;
      padding: 4px;
      border-radius: 6px;
      transition: color 0.15s;
      font-size: 18px;
      flex-shrink: 0;
    }

    .sa-logout-btn:hover { color: var(--sa-red); }

    /* ── MAIN CONTENT ── */
    .sa-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: var(--sa-bg);
    }

    .sa-topbar {
      padding: 14px 28px;
      border-bottom: 1px solid var(--sa-border);
      background: var(--sa-surface);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .sa-topbar-title {
      font-size: 17px;
      font-weight: 700;
      color: var(--sa-text);
    }

    .sa-topbar-time {
      font-size: 12px;
      color: var(--sa-muted);
    }

    .sa-views {
      flex: 1;
      overflow-y: auto;
      padding: 28px;
    }

    .sa-view { display: none; }
    .sa-view.active { display: block; }

    /* ── CARDS ── */
    .sa-card {
      background: var(--sa-card);
      border: 1px solid var(--sa-border);
      border-radius: 14px;
      padding: 22px 24px;
    }

    .sa-card-title {
      font-size: 13px;
      font-weight: 700;
      color: var(--sa-text);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .sa-card-title i {
      color: #a855f7;
    }

    /* ── KPI CARDS ── */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .kpi-tile {
      background: var(--sa-card);
      border: 1px solid var(--sa-border);
      border-radius: 14px;
      padding: 20px 22px;
      position: relative;
      overflow: hidden;
      transition: border-color 0.2s, transform 0.2s;
    }

    .kpi-tile:hover {
      border-color: rgba(109,40,217,0.4);
      transform: translateY(-2px);
    }

    .kpi-tile::before {
      content: '';
      position: absolute;
      top: -30px; right: -30px;
      width: 80px; height: 80px;
      border-radius: 50%;
      opacity: 0.12;
    }

    .kpi-tile.kc-purple::before { background: #7c3aed; }
    .kpi-tile.kc-gold::before   { background: #f59e0b; }
    .kpi-tile.kc-green::before  { background: #10b981; }
    .kpi-tile.kc-blue::before   { background: #3b82f6; }
    .kpi-tile.kc-red::before    { background: #ef4444; }

    .kpi-tile .kt-icon {
      font-size: 22px;
      margin-bottom: 10px;
    }

    .kpi-tile.kc-purple .kt-icon { color: #a855f7; }
    .kpi-tile.kc-gold   .kt-icon { color: #f59e0b; }
    .kpi-tile.kc-green  .kt-icon { color: #10b981; }
    .kpi-tile.kc-blue   .kt-icon { color: #3b82f6; }
    .kpi-tile.kc-red    .kt-icon { color: #ef4444; }

    .kpi-tile .kt-label {
      font-size: 10.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--sa-muted);
      margin-bottom: 4px;
    }

    .kpi-tile .kt-value {
      font-size: 28px;
      font-weight: 800;
      color: #fff;
      line-height: 1.1;
    }

    .kpi-tile .kt-sub {
      font-size: 11px;
      color: var(--sa-muted);
      margin-top: 4px;
    }

    /* ── TABLES ── */
    .sa-table-wrap {
      overflow-x: auto;
    }

    .sa-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .sa-table th {
      padding: 10px 14px;
      font-size: 10.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--sa-muted);
      border-bottom: 1px solid var(--sa-border);
      text-align: left;
      white-space: nowrap;
    }

    .sa-table td {
      padding: 11px 14px;
      border-bottom: 1px solid var(--sa-border-2);
      color: var(--sa-text);
      vertical-align: middle;
    }

    .sa-table tr:last-child td { border-bottom: none; }

    .sa-table tr:hover td {
      background: rgba(255,255,255,0.02);
    }

    /* ── BADGES ── */
    .badge-sa {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 700;
    }

    .badge-pending  { background: rgba(245,158,11,0.15);  color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
    .badge-approved { background: rgba(16,185,129,0.12);  color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .badge-rejected { background: rgba(239,68,68,0.12);   color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .badge-active   { background: rgba(16,185,129,0.12);  color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .badge-inactive { background: rgba(100,116,139,0.15); color: #94a3b8; border: 1px solid rgba(100,116,139,0.3); }
    .badge-admin    { background: rgba(109,40,217,0.2);   color: #c084fc; border: 1px solid rgba(109,40,217,0.4); }
    .badge-cashier  { background: rgba(59,130,246,0.15);  color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
    .badge-super    { background: linear-gradient(135deg, rgba(109,40,217,0.3), rgba(245,158,11,0.2)); color: #fbbf24; border: 1px solid rgba(245,158,11,0.4); }
    .badge-completed{ background: rgba(16,185,129,0.12);  color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    .badge-voided   { background: rgba(239,68,68,0.12);   color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }

    /* ── BUTTONS ── */
    .sa-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid transparent;
      transition: all 0.15s;
      white-space: nowrap;
    }

    .sa-btn-primary {
      background: linear-gradient(135deg, #6d28d9, #7c3aed);
      color: #fff;
      border-color: #7c3aed;
      box-shadow: 0 4px 14px rgba(109,40,217,0.35);
    }

    .sa-btn-primary:hover {
      background: linear-gradient(135deg, #5b21b6, #6d28d9);
      transform: translateY(-1px);
    }

    .sa-btn-approve {
      background: rgba(16,185,129,0.12);
      color: #10b981;
      border-color: rgba(16,185,129,0.35);
    }

    .sa-btn-approve:hover {
      background: rgba(16,185,129,0.22);
    }

    .sa-btn-reject {
      background: rgba(239,68,68,0.12);
      color: #ef4444;
      border-color: rgba(239,68,68,0.35);
    }

    .sa-btn-reject:hover {
      background: rgba(239,68,68,0.22);
    }

    .sa-btn-ghost {
      background: rgba(255,255,255,0.05);
      color: var(--sa-muted);
      border-color: var(--sa-border);
    }

    .sa-btn-ghost:hover {
      background: rgba(255,255,255,0.1);
      color: var(--sa-text);
    }

    .sa-btn-danger {
      background: rgba(239,68,68,0.15);
      color: #ef4444;
      border-color: rgba(239,68,68,0.4);
    }

    .sa-btn-danger:hover { background: rgba(239,68,68,0.25); }

    /* ── INPUTS ── */
    .sa-input {
      width: 100%;
      background: var(--sa-surface);
      border: 1px solid var(--sa-border);
      border-radius: 8px;
      padding: 9px 13px;
      font-size: 13px;
      color: var(--sa-text);
      font-family: inherit;
      outline: none;
      transition: border-color 0.15s;
    }

    .sa-input:focus {
      border-color: #7c3aed;
      box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
    }

    .sa-label {
      font-size: 11.5px;
      font-weight: 600;
      color: var(--sa-muted);
      margin-bottom: 6px;
      display: block;
    }

    .sa-select {
      width: 100%;
      background: var(--sa-surface);
      border: 1px solid var(--sa-border);
      border-radius: 8px;
      padding: 9px 13px;
      font-size: 13px;
      color: var(--sa-text);
      font-family: inherit;
      outline: none;
      cursor: pointer;
      transition: border-color 0.15s;
    }

    .sa-select:focus { border-color: #7c3aed; }

    /* ── DATE & CALENDAR INPUTS ── */
    .sa-input[type="date"],
    .sa-input[type="week"],
    .sa-input[type="month"] {
      color-scheme: dark;
    }
    .sa-input[type="date"]::-webkit-calendar-picker-indicator,
    .sa-input[type="week"]::-webkit-calendar-picker-indicator,
    .sa-input[type="month"]::-webkit-calendar-picker-indicator {
      filter: invert(0.85) brightness(1.2);
      cursor: pointer;
    }

    /* ── MODAL ── */
    .sa-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.75);
      backdrop-filter: blur(6px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .sa-modal-overlay.open { display: flex; }

    .sa-modal {
      background: var(--sa-card);
      border: 1px solid var(--sa-border);
      border-radius: 18px;
      width: 90%;
      max-width: 520px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 80px rgba(0,0,0,0.6), var(--sa-glow);
      animation: modalIn 0.25s cubic-bezier(0.22,1,0.36,1);
    }

    @keyframes modalIn {
      from { opacity: 0; transform: translateY(30px) scale(0.95); }
      to   { opacity: 1; transform: translateY(0)     scale(1);    }
    }

    .sa-modal-header {
      padding: 20px 24px 16px;
      border-bottom: 1px solid var(--sa-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .sa-modal-title {
      font-size: 15px;
      font-weight: 700;
      color: var(--sa-text);
    }

    .sa-modal-close {
      background: transparent;
      border: none;
      color: var(--sa-muted);
      cursor: pointer;
      font-size: 20px;
      line-height: 1;
      transition: color 0.15s;
    }

    .sa-modal-close:hover { color: var(--sa-red); }

    .sa-modal-body {
      padding: 22px 24px;
    }

    .sa-modal-footer {
      padding: 14px 24px 20px;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      border-top: 1px solid var(--sa-border-2);
    }

    /* ── TOAST ── */
    .sa-toast {
      position: fixed;
      bottom: 28px;
      right: 28px;
      z-index: 99999;
      background: var(--sa-card);
      border: 1px solid var(--sa-border);
      border-radius: 12px;
      padding: 14px 20px;
      font-size: 13px;
      font-weight: 600;
      color: var(--sa-text);
      box-shadow: 0 12px 40px rgba(0,0,0,0.5);
      transform: translateY(20px);
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.22,1,0.36,1);
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 240px;
      max-width: 380px;
    }

    .sa-toast.show {
      transform: translateY(0);
      opacity: 1;
    }

    .sa-toast.success { border-left: 3px solid var(--sa-green); }
    .sa-toast.error   { border-left: 3px solid var(--sa-red); }

    /* ── FORM ROW ── */
    .sa-form-row {
      margin-bottom: 16px;
    }

    .sa-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* ── SECTION HEADER ── */
    .sa-section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
      gap: 12px;
      flex-wrap: wrap;
    }

    .sa-section-title {
      font-size: 15px;
      font-weight: 700;
      color: var(--sa-text);
    }

    /* ── LOG ENTRY ── */
    .log-entry {
      display: flex;
      gap: 12px;
      padding: 10px 0;
      border-bottom: 1px solid var(--sa-border-2);
    }

    .log-entry:last-child { border-bottom: none; }

    .log-icon {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      flex-shrink: 0;
    }

    .log-body { flex: 1; min-width: 0; }
    .log-action { font-size: 12.5px; font-weight: 600; color: var(--sa-text); }
    .log-detail { font-size: 11.5px; color: var(--sa-muted); margin-top: 2px; word-break: break-word; }
    .log-time   { font-size: 11px; color: var(--sa-muted2); margin-top: 2px; }

    /* ── AUDIT LOG BREAKDOWN ── */
    .log-breakdown {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
    }
    .log-cat-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11.5px;
      font-weight: 600;
      cursor: pointer;
      border: 1.5px solid transparent;
      transition: all 0.18s ease;
      user-select: none;
    }
    .log-cat-pill:hover { opacity: 0.82; transform: translateY(-1px); }
    .log-cat-pill.active { border-color: currentColor !important; box-shadow: 0 0 0 3px rgba(0,0,0,0.08); }
    .log-cat-pill .pill-count {
      background: rgba(0,0,0,0.12);
      border-radius: 10px;
      padding: 1px 6px;
      font-size: 10.5px;
      font-weight: 700;
    }
    .log-search-bar {
      width: 100%;
      padding: 8px 12px;
      border: 1.5px solid var(--sa-border);
      border-radius: 8px;
      font-size: 12.5px;
      background: var(--sa-bg2);
      color: var(--sa-text);
      outline: none;
      margin-bottom: 14px;
      transition: border-color 0.2s;
    }
    .log-search-bar:focus { border-color: var(--sa-accent); }
    .log-list-wrap { max-height: 520px; overflow-y: auto; }

    /* ── SETTINGS ── */
    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 16px;
    }

    /* ── ALERT BOX ── */
    .sa-alert {
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 16px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    .sa-alert-warning {
      background: rgba(245,158,11,0.1);
      border: 1px solid rgba(245,158,11,0.3);
      color: #fbbf24;
    }

    .sa-alert-info {
      background: rgba(59,130,246,0.1);
      border: 1px solid rgba(59,130,246,0.3);
      color: #60a5fa;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
      .sa-sidebar { display: none; }
      .kpi-grid { grid-template-columns: 1fr 1fr; }
      .sa-form-grid { grid-template-columns: 1fr; }
    }

    /* ── SCROLLBARS ── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--sa-border); border-radius: 99px; }

    /* ── TABS ── */
    .sa-tabs {
      display: flex;
      gap: 4px;
      background: var(--sa-surface);
      border: 1px solid var(--sa-border);
      border-radius: 10px;
      padding: 4px;
      width: fit-content;
      margin-bottom: 20px;
    }

    .sa-tab {
      padding: 7px 16px;
      border-radius: 7px;
      font-size: 12.5px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      background: transparent;
      color: var(--sa-muted);
      transition: all 0.15s;
    }

    .sa-tab.active {
      background: linear-gradient(135deg, #6d28d9, #7c3aed);
      color: #fff;
      box-shadow: 0 3px 10px rgba(109,40,217,0.3);
    }

    .sa-tab:hover:not(.active) {
      color: var(--sa-text);
      background: rgba(255,255,255,0.05);
    }

    /* ── LOGIN SCREEN ── */
    .sa-login-screen {
      position: fixed;
      inset: 0;
      background: radial-gradient(ellipse at 30% 20%, rgba(109,40,217,0.15) 0%, transparent 60%),
                  radial-gradient(ellipse at 80% 80%, rgba(245,158,11,0.08) 0%, transparent 50%),
                  var(--sa-bg);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
    }

    .sa-login-card {
      background: var(--sa-card);
      border: 1px solid var(--sa-border);
      border-radius: 24px;
      padding: 48px 40px;
      width: 420px;
      max-width: 95vw;
      box-shadow: 0 40px 100px rgba(0,0,0,0.6), var(--sa-glow);
    }

    .sa-login-icon {
      width: 64px;
      height: 64px;
      border-radius: 18px;
      background: linear-gradient(135deg, #6d28d9, #a855f7);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      margin: 0 auto 20px;
      box-shadow: 0 8px 30px rgba(109,40,217,0.45);
    }

    .sa-login-title {
      font-size: 22px;
      font-weight: 800;
      color: #fff;
      text-align: center;
      margin-bottom: 4px;
    }

    .sa-login-sub {
      font-size: 13px;
      color: var(--sa-muted);
      text-align: center;
      margin-bottom: 32px;
    }

    /* ── GLOW EFFECT ── */
    .glow-purple {
      box-shadow: 0 0 20px rgba(109,40,217,0.3), 0 0 40px rgba(109,40,217,0.1);
    }

    /* Pulse animation for pending badge */
    @keyframes pulse-red {
      0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
      50%      { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
    }

    .badge-pulse { animation: pulse-red 2s ease infinite; }

    /* Utility & Pill Filter */
    .d-flex { display: flex; }
    .gap-2 { gap: 8px; }
    .flex-wrap { flex-wrap: wrap; }
    
    .sa-cat-pill {
      background: var(--sa-surface);
      border: 1px solid var(--sa-border);
      color: var(--sa-muted);
      border-radius: 99px;
      padding: 6px 14px;
      font-size: 12.5px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .sa-cat-pill:hover {
      background: rgba(109,40,217,0.1);
      color: var(--sa-text);
      border-color: rgba(109,40,217,0.4);
    }
    .sa-cat-pill.active {
      background: var(--sa-accent);
      color: #fff;
      border-color: var(--sa-accent2);
      box-shadow: 0 4px 15px rgba(109,40,217,0.3);
    }

    /* Empty state */
    .sa-empty {
      text-align: center;
      padding: 48px 20px;
      color: var(--sa-muted);
    }

    .sa-empty i { font-size: 40px; opacity: 0.3; margin-bottom: 12px; display: block; }
    .sa-empty p { font-size: 13px; }
  </style>
</head>
<body>

  <!-- ── LOGIN SCREEN ── -->
  <div class="sa-login-screen" id="sa-login-screen">
    <div class="sa-login-card">
      <div class="sa-login-icon">🛡️</div>
      <h1 class="sa-login-title">Super Admin Panel</h1>
      <p class="sa-login-sub">ChipiPie Cafe — System Control Center</p>

      <!-- STEP 1: CREDENTIALS -->
      <div id="sa-step-credentials">
        <form onsubmit="saHandleLogin(event)">
          <div class="sa-form-row">
            <label class="sa-label">Username</label>
            <input type="text" id="sa-login-user" class="sa-input" placeholder="superadmin" autocomplete="username" required>
          </div>
          <div class="sa-form-row">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <label class="sa-label" style="margin-bottom:0;">Password</label>
              <a href="#" onclick="saShowForgotStep1(); return false;" style="font-size:11.5px; color:var(--sa-accent); text-decoration:none;">Forgot Password?</a>
            </div>
            <input type="password" id="sa-login-pass" class="sa-input" placeholder="••••••••" autocomplete="current-password" required>
          </div>
          <button type="submit" id="sa-login-btn" class="sa-btn sa-btn-primary w-100" style="width:100%;justify-content:center;padding:13px;font-size:14px;margin-top:8px;">
            <i class="bi bi-shield-lock-fill"></i> Sign In as Super Admin
          </button>
        </form>
      </div>

      <!-- STEP 2: TWO-FACTOR AUTHENTICATION (2FA) -->
      <div id="sa-step-2fa" style="display:none;">
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:12px; padding:14px; margin-bottom:18px; text-align:center;">
          <div style="font-size:26px; margin-bottom:4px;">📬</div>
          <div style="font-size:14px; font-weight:600; color:var(--sa-text); margin-bottom:3px;">Two-Factor Authentication</div>
          <div style="font-size:12px; color:var(--sa-muted); line-height:1.4;">
            A confirmation token was sent to <strong id="sa-2fa-email-display" style="color:var(--sa-accent); word-break:break-all;"></strong>
          </div>
        </div>

        <form onsubmit="saHandleVerify2FA(event)">
          <div class="sa-form-row">
            <label class="sa-label" style="text-align:center; display:block; font-size:12px;">Enter 6-Digit Code</label>
            <input type="text" id="sa-2fa-code" class="sa-input" maxlength="6" placeholder="000000" autocomplete="one-time-code" required
                   style="text-align:center; font-family:Consolas, Monaco, monospace; font-size:26px; font-weight:700; letter-spacing:8px; padding:12px; text-transform:uppercase;">
          </div>

          <div id="sa-dev-hint" style="display:none; margin-bottom:14px; font-size:11.5px; color:#10b981; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:8px; padding:7px 10px; text-align:center;">
            💡 Dev Mode Token: <code id="sa-dev-code-val" style="font-weight:700;"></code> (auto-filled)
          </div>

          <button type="submit" id="sa-verify-btn" class="sa-btn sa-btn-primary w-100" style="width:100%;justify-content:center;padding:13px;font-size:14px;margin-top:6px;">
            <i class="bi bi-shield-check"></i> Verify & Sign In
          </button>
        </form>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:12px;">
          <button type="button" onclick="saResend2FA()" id="sa-resend-btn" style="background:none; border:none; color:var(--sa-accent); cursor:pointer; padding:4px 0; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
            <i class="bi bi-arrow-clockwise"></i> Resend Code
          </button>
          <button type="button" onclick="saCancel2FA()" style="background:none; border:none; color:var(--sa-muted); cursor:pointer; padding:4px 0; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
            <i class="bi bi-arrow-left"></i> Back to Login
          </button>
        </div>
      </div>

      <!-- STEP 3: FORGOT PASSWORD - REQUEST CODE -->
      <div id="sa-step-forgot-request" style="display:none;">
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:12px; padding:14px; margin-bottom:18px; text-align:center;">
          <div style="font-size:26px; margin-bottom:4px;">🔑</div>
          <div style="font-size:14px; font-weight:600; color:var(--sa-text); margin-bottom:3px;">Reset Super Admin Password</div>
          <div style="font-size:12px; color:var(--sa-muted); line-height:1.4;">
            Enter your Super Admin username or registered email to receive a 6-digit recovery code.
          </div>
        </div>

        <form onsubmit="saHandleSendForgotCode(event)">
          <div class="sa-form-row">
            <label class="sa-label">Username or Email</label>
            <input type="text" id="sa-forgot-user" class="sa-input" placeholder="superadmin or email" required>
          </div>
          <button type="submit" id="sa-forgot-send-btn" class="sa-btn sa-btn-primary w-100" style="width:100%;justify-content:center;padding:13px;font-size:14px;margin-top:8px;">
            <i class="bi bi-send-fill"></i> Send Recovery Code
          </button>
        </form>

        <div style="margin-top:14px; text-align:center;">
          <a href="#" onclick="saHideForgot(); return false;" style="font-size:12px; color:var(--sa-muted); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Back to Login
          </a>
        </div>
      </div>

      <!-- STEP 4: FORGOT PASSWORD - VERIFY & RESET -->
      <div id="sa-step-forgot-reset" style="display:none;">
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:12px; padding:14px; margin-bottom:18px; text-align:center;">
          <div style="font-size:26px; margin-bottom:4px;">📬</div>
          <div style="font-size:14px; font-weight:600; color:var(--sa-text); margin-bottom:3px;">Enter Recovery Code</div>
          <div style="font-size:12px; color:var(--sa-muted); line-height:1.4;">
            Code sent to: <strong id="sa-forgot-email-display" style="color:var(--sa-accent); word-break:break-all;"></strong>
          </div>
        </div>

        <form onsubmit="saHandleResetPassword(event)">
          <div class="sa-form-row">
            <label class="sa-label" style="text-align:center; display:block; font-size:12px;">Enter 6-Digit Code</label>
            <input type="text" id="sa-forgot-code" class="sa-input" maxlength="6" placeholder="000000" required
                   style="text-align:center; font-family:Consolas, Monaco, monospace; font-size:26px; font-weight:700; letter-spacing:8px; padding:12px; text-transform:uppercase;">
          </div>

          <div id="sa-forgot-dev-hint" style="display:none; margin-bottom:14px; font-size:11.5px; color:#10b981; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:8px; padding:7px 10px; text-align:center;">
            💡 Dev Mode Token: <code id="sa-forgot-dev-code" style="font-weight:700;"></code> (auto-filled)
          </div>

          <div class="sa-form-row">
            <label class="sa-label">New Password</label>
            <input type="password" id="sa-forgot-new-pass" class="sa-input" placeholder="••••••••" required minlength="8">
            <div style="font-size:11px; color:var(--sa-muted); margin-top:4px;">
              Min. 8 characters with uppercase, lowercase, and number.
            </div>
          </div>

          <div class="sa-form-row">
            <label class="sa-label">Confirm New Password</label>
            <input type="password" id="sa-forgot-confirm-pass" class="sa-input" placeholder="••••••••" required minlength="8">
          </div>

          <button type="submit" id="sa-forgot-submit-btn" class="sa-btn sa-btn-primary w-100" style="width:100%;justify-content:center;padding:13px;font-size:14px;margin-top:6px;">
            <i class="bi bi-shield-check"></i> Reset Password
          </button>
        </form>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:12px;">
          <button type="button" onclick="saHandleResendForgotCode()" id="sa-forgot-resend-btn" style="background:none; border:none; color:var(--sa-accent); cursor:pointer; padding:4px 0; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
            <i class="bi bi-arrow-clockwise"></i> Resend Code
          </button>
          <button type="button" onclick="saHideForgot()" style="background:none; border:none; color:var(--sa-muted); cursor:pointer; padding:4px 0; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
            <i class="bi bi-arrow-left"></i> Back to Login
          </button>
        </div>
      </div>

      <div id="sa-login-error" style="display:none; margin-top:14px; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:10px; padding:11px 14px; font-size:13px; color:#ef4444;"></div>
      <div id="sa-login-success" style="display:none; margin-top:14px; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:10px; padding:11px 14px; font-size:13px; color:#10b981;"></div>

      <div style="margin-top:20px; text-align:center;">
        <a href="chipipie-pos.php" style="font-size:12px; color:var(--sa-muted); text-decoration:none;">
          <i class="bi bi-arrow-left"></i> Back to POS System
        </a>
      </div>
    </div>
  </div>

  <!-- ── APP WRAPPER ── -->
  <div id="sa-app" style="display:none; width:100%;">

    <!-- SIDEBAR -->
    <aside class="sa-sidebar">
      <div class="sa-brand">
        <div class="sa-brand-icon">🛡️</div>
        <div class="sa-brand-text">
          <h5>ChipiPie Cafe</h5>
          <small>Super Admin</small>
        </div>
      </div>

      <nav class="sa-nav">
        <div class="sa-nav-label">Overview</div>
        <button class="sa-nav-btn active" id="nav-btn-dashboard" onclick="saShowView('dashboard', this)">
          <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </button>

        <div class="sa-nav-label">Approvals</div>
        <button class="sa-nav-btn" id="nav-btn-requests" onclick="saShowView('requests', this)">
          <i class="bi bi-inbox-fill"></i> System Requests
          <span class="badge badge-pulse" id="nav-badge-requests" style="display:none;">0</span>
        </button>
        <button class="sa-nav-btn" id="nav-btn-admin-reqs" onclick="saShowView('admin-reqs', this)">
          <i class="bi bi-person-badge-fill"></i> Admin Approvals
          <span class="badge badge-pulse" id="nav-badge-admin-reqs" style="display:none;">0</span>
        </button>

        <div class="sa-nav-label">Management</div>
        <button class="sa-nav-btn" id="nav-btn-users" onclick="saShowView('users', this)">
          <i class="bi bi-people-fill"></i> All Users
        </button>
        <button class="sa-nav-btn" id="nav-btn-menu" onclick="saShowView('menu', this)">
          <i class="bi bi-cup-hot-fill"></i> Menu Items
        </button>
        <button class="sa-nav-btn" id="nav-btn-orders" onclick="saShowView('orders', this)">
          <i class="bi bi-receipt-cutoff"></i> Orders
        </button>
        <button class="sa-nav-btn" id="nav-btn-analytics" onclick="saShowView('analytics', this)">
          <i class="bi bi-bar-chart-fill"></i> Analytics
        </button>
        <button class="sa-nav-btn" id="nav-btn-logs" onclick="saShowView('logs', this)">
          <i class="bi bi-journal-text"></i> Audit Log
        </button>

        <div class="sa-nav-label">System</div>
        <button class="sa-nav-btn" id="nav-btn-settings" onclick="saShowView('settings', this)">
          <i class="bi bi-gear-fill"></i> Settings
        </button>
      </nav>

      <div class="sa-sidebar-footer">
        <div class="sa-user-card">
          <div class="sa-avatar" id="sa-sidebar-avatar">S</div>
          <div style="flex:1; min-width:0;">
            <div class="sa-user-name" id="sa-sidebar-name">Super Admin</div>
            <div class="sa-user-role">Super Admin</div>
          </div>
          <button class="sa-logout-btn" onclick="saLogout()" title="Logout" style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:#f87171;font-size:12px;font-weight:600;cursor:pointer;">
            <i class="bi bi-box-arrow-right"></i> Exit
          </button>
        </div>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="sa-main">
      <div class="sa-topbar">
        <div class="sa-topbar-title" id="sa-page-title">Dashboard</div>
        <div style="display:flex; align-items:center; gap:16px;">
          <div class="sa-topbar-time" id="sa-live-time"></div>
        </div>
      </div>

      <div class="sa-views">

        <!-- ── DASHBOARD VIEW ── -->
        <div id="sa-view-dashboard" class="sa-view active">
          <div class="kpi-grid" id="sa-kpi-grid">
            <div class="kpi-tile kc-purple">
              <div class="kt-icon"><i class="bi bi-people-fill"></i></div>
              <div class="kt-label">Total Users</div>
              <div class="kt-value" id="kpi-total-users">—</div>
              <div class="kt-sub" id="kpi-users-sub">Loading...</div>
            </div>
            <div class="kpi-tile kc-gold">
              <div class="kt-icon"><i class="bi bi-clock-history"></i></div>
              <div class="kt-label">Pending Requests</div>
              <div class="kt-value" id="kpi-pending">—</div>
              <div class="kt-sub" id="kpi-pending-sub">Awaiting review</div>
            </div>
            <div class="kpi-tile kc-green">
              <div class="kt-icon"><i class="bi bi-currency-exchange"></i></div>
              <div class="kt-label">Today's Revenue</div>
              <div class="kt-value" id="kpi-today-rev">₱0</div>
              <div class="kt-sub" id="kpi-rev-sub">Today's orders</div>
            </div>
            <div class="kpi-tile kc-blue">
              <div class="kt-icon"><i class="bi bi-bag-check-fill"></i></div>
              <div class="kt-label">Orders Today</div>
              <div class="kt-value" id="kpi-today-orders">0</div>
              <div class="kt-sub" id="kpi-orders-sub">Transactions</div>
            </div>
            <div class="kpi-tile kc-red">
              <div class="kt-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <div class="kt-label">Stock Alerts</div>
              <div class="kt-value" id="kpi-stock-alerts">0</div>
              <div class="kt-sub" id="kpi-stock-sub">Low / Out of stock</div>
            </div>
            <div class="kpi-tile kc-purple">
              <div class="kt-icon"><i class="bi bi-graph-up-arrow"></i></div>
              <div class="kt-label">Total Revenue</div>
              <div class="kt-value" id="kpi-total-rev">₱0</div>
              <div class="kt-sub">All time completed</div>
            </div>
          </div>

          <div class="sa-card" style="margin-bottom: 20px;">
            <div class="sa-card-title"><i class="bi bi-bar-chart-fill"></i> Weekly Revenue</div>
            <div style="height:250px; width:100%;">
              <canvas id="saWeeklyChart"></canvas>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="sa-grid-2">
            <div class="sa-card">
              <div class="sa-card-title"><i class="bi bi-journal-text"></i> Recent Activity</div>
              <div id="dashboard-logs"></div>
            </div>
            <div class="sa-card">
              <div class="sa-card-title"><i class="bi bi-clock-history"></i> Pending Items</div>
              <div id="dashboard-pending"></div>
            </div>
          </div>
        </div>

        <!-- ── REQUESTS VIEW ── -->
        <div id="sa-view-requests" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">System Requests</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <select class="sa-select" style="width:auto;font-size:12px;" id="req-status-filter" onchange="loadRequests()">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All</option>
              </select>
              <button class="sa-btn sa-btn-ghost" onclick="loadRequests()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
              <button class="sa-btn sa-btn-ghost" onclick="resetRequestsFilter()" title="Reset to default view">
                <i class="bi bi-funnel-fill"></i> Reset
              </button>
            </div>
          </div>

          <div class="sa-card">
            <div class="sa-table-wrap">
              <table class="sa-table">
                <thead>
                  <tr>
                    <th>#</th><th>Type</th><th>Requested By</th><th>Title</th>
                    <th>Status</th><th>Date</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody id="requests-tbody">
                  <tr><td colspan="7" class="text-center" style="padding:32px;color:var(--sa-muted);">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── ADMIN APPROVALS VIEW ── -->
        <div id="sa-view-admin-reqs" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">Admin Registration Requests</div>
            <button class="sa-btn sa-btn-ghost" onclick="loadAdminRequests()">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>

          <div class="sa-alert sa-alert-info">
            <i class="bi bi-info-circle-fill"></i>
            <span>These are requests from users wanting to become <strong>Admins</strong>. Approving creates their account immediately.</span>
          </div>

          <div class="sa-card">
            <div class="sa-table-wrap">
              <table class="sa-table">
                <thead>
                  <tr>
                    <th>#</th><th>Full Name</th><th>Username</th><th>Email</th>
                    <th>Status</th><th>Submitted</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody id="admin-reqs-tbody">
                  <tr><td colspan="7" class="text-center" style="padding:32px;color:var(--sa-muted);">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── USERS VIEW ── -->
        <div id="sa-view-users" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">All Users</div>
            <div style="display:flex;gap:8px;">
              <button class="sa-btn sa-btn-ghost" onclick="loadUsers()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
              <button class="sa-btn sa-btn-primary" onclick="openCreateUserModal()">
                <i class="bi bi-person-plus-fill"></i> Create User
              </button>
            </div>
          </div>

          <div class="sa-card">
            <div class="sa-table-wrap">
              <table class="sa-table">
                <thead>
                  <tr>
                    <th>#</th><th>Name</th><th>Username</th><th>Email</th>
                    <th>Role</th><th>Status</th><th>Created</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody id="users-tbody">
                  <tr><td colspan="8" class="text-center" style="padding:32px;color:var(--sa-muted);">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── MENU ITEMS VIEW ── -->
        <div id="sa-view-menu" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">Menu & Inventory</div>
            <div style="display:flex;gap:8px;">
              <button class="sa-btn sa-btn-ghost" onclick="saLoadMenu()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
              <button class="sa-btn sa-btn-primary" onclick="saOpenAddMenuModal()">
                <i class="bi bi-plus-circle-fill"></i> Add Menu Item
              </button>
            </div>
          </div>

          <!-- Search & Category Filters -->
          <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
            <div style="position:relative;flex:1;min-width:240px;">
              <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sa-muted);"></i>
              <input type="text" id="sa-menu-search" class="sa-input" style="padding-left:36px;" placeholder="Search menu items..." oninput="saRenderMenuTable()">
            </div>
            <div id="sa-menu-cat-pills" style="display:flex;gap:6px;flex-wrap:wrap;"></div>
          </div>

          <div class="sa-card">
            <div class="sa-table-wrap">
              <table class="sa-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="sa-menu-tbody">
                  <tr><td colspan="7"><div class="sa-empty"><i class="bi bi-cup-hot"></i><p>Loading menu items...</p></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── ORDERS VIEW ── -->
        <div id="sa-view-orders" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">Orders</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
              <select class="sa-select" style="width:auto;font-size:12px;" id="order-status-filter" onchange="loadOrders()">
                <option value="">All Status</option>
                <option value="Completed">Completed</option>
                <option value="Voided">Voided</option>
                <option value="Refunded">Refunded</option>
              </select>
              <button class="sa-btn sa-btn-ghost" onclick="loadOrders()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
              </button>
            </div>
          </div>

          <!-- Orders Calendar & Period Filter Card -->
          <div class="sa-card" style="margin-bottom:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:8px;background:rgba(109,40,217,0.15);display:flex;align-items:center;justify-content:center;color:var(--sa-accent2);">
                  <i class="bi bi-calendar3" style="font-size:16px;"></i>
                </div>
                <div>
                  <div style="font-size:13px;font-weight:700;color:var(--sa-text);line-height:1.2;">Date Filter</div>
                  <div id="sa-order-period-summary" style="font-size:11.5px;color:var(--sa-muted);margin-top:2px;">Showing today's orders</div>
                </div>
              </div>

              <!-- Quick Period Tabs -->
              <div class="d-flex gap-2 flex-wrap" id="sa-order-filter-tabs">
                <button type="button" class="sa-cat-pill active" id="sa-ofilter-today" onclick="setSAOrderFilter('today', this)">Today</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-week"  onclick="setSAOrderFilter('week', this)">This Week</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-month" onclick="setSAOrderFilter('month', this)">This Month</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-year"  onclick="setSAOrderFilter('year', this)">This Year</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-all"   onclick="setSAOrderFilter('all', this)">All Time</button>
                <!-- Separator -->
                <span style="width:1px;background:var(--sa-border);margin:0 4px;align-self:stretch;"></span>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-pick-date"  onclick="setSAOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-pick-week"  onclick="setSAOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-pick-month" onclick="setSAOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
                <button type="button" class="sa-cat-pill" id="sa-ofilter-pick-year"  onclick="setSAOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
              </div>
            </div>

            <!-- Dynamic Picker Controls Box -->
            <div id="sa-order-picker-box" style="display:none;background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:12px 16px;margin-top:4px;">
              <!-- DATE PICKER -->
              <div id="sa-order-picker-date" class="sa-order-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar-date" style="color:var(--sa-accent2);"></i> Choose Date:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderDate(-1)" title="Previous Day"><i class="bi bi-chevron-left"></i></button>
                  <input type="date" id="sa-order-input-date" class="sa-input" style="width:auto;min-width:160px;padding:6px 12px;font-size:13px;" onchange="applySAOrderPickerFilter()">
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderDate(1)" title="Next Day"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setSAOrderPickerToday()">Today</button>
                  <span id="sa-order-date-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>
              <!-- WEEK PICKER -->
              <div id="sa-order-picker-week" class="sa-order-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar-week" style="color:var(--sa-accent2);"></i> Choose Week:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderWeek(-1)" title="Previous Week"><i class="bi bi-chevron-left"></i></button>
                  <input type="week" id="sa-order-input-week" class="sa-input" style="width:auto;min-width:180px;padding:6px 12px;font-size:13px;" onchange="applySAOrderPickerFilter()">
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderWeek(1)" title="Next Week"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setSAOrderThisWeek()">This Week</button>
                  <span id="sa-order-week-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>
              <!-- MONTH PICKER -->
              <div id="sa-order-picker-month" class="sa-order-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar-month" style="color:var(--sa-accent2);"></i> Choose Month:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderMonth(-1)" title="Previous Month"><i class="bi bi-chevron-left"></i></button>
                  <input type="month" id="sa-order-input-month" class="sa-input" style="width:auto;min-width:160px;padding:6px 12px;font-size:13px;" onchange="applySAOrderPickerFilter()">
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderMonth(1)" title="Next Month"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setSAOrderThisMonth()">This Month</button>
                  <span id="sa-order-month-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>
              <!-- YEAR PICKER -->
              <div id="sa-order-picker-year" class="sa-order-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar" style="color:var(--sa-accent2);"></i> Choose Year:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderYear(-1)" title="Previous Year"><i class="bi bi-chevron-left"></i></button>
                  <select id="sa-order-input-year" class="sa-select" style="width:auto;min-width:130px;padding:6px 12px;font-size:13px;" onchange="applySAOrderPickerFilter()"></select>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftSAOrderYear(1)" title="Next Year"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setSAOrderThisYear()">This Year</button>
                  <span id="sa-order-year-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>
            </div>
          </div>

          <div class="sa-card">
            <div class="sa-table-wrap">
              <table class="sa-table">
                <thead>
                  <tr>
                    <th>Order #</th><th>Cashier</th><th>Total</th><th>Payment</th>
                    <th>Status</th><th>Items</th><th>Date/Time</th>
                  </tr>
                </thead>
                <tbody id="sa-orders-tbody">
                  <tr><td colspan="7" class="text-center" style="padding:32px;color:var(--sa-muted);">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── ANALYTICS VIEW ── -->
        <div id="sa-view-analytics" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">Analytics</div>
            <button class="sa-btn sa-btn-ghost" onclick="loadAnalytics()">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>

          <!-- KPI Row 1 -->
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;" id="analytics-kpi-row">
            <div class="kpi-tile kc-purple">
              <div class="kt-icon"><i class="bi bi-currency-exchange"></i></div>
              <div class="kt-label">Total Revenue (Month)</div>
              <div class="kt-value" id="an-month-rev">—</div>
              <div class="kt-sub" id="an-month-rev-sub">Loading...</div>
            </div>
            <div class="kpi-tile kc-blue">
              <div class="kt-icon"><i class="bi bi-bag-check-fill"></i></div>
              <div class="kt-label">Total Orders (Month)</div>
              <div class="kt-value" id="an-month-orders">—</div>
              <div class="kt-sub">This month's transactions</div>
            </div>
            <div class="kpi-tile kc-green">
              <div class="kt-icon"><i class="bi bi-graph-up"></i></div>
              <div class="kt-label">Avg Daily Revenue</div>
              <div class="kt-value" id="an-avg-daily">—</div>
              <div class="kt-sub">Based on 30 days</div>
            </div>
            <div class="kpi-tile kc-gold">
              <div class="kt-icon"><i class="bi bi-trophy-fill"></i></div>
              <div class="kt-label">All-Time Best Seller</div>
              <div class="kt-value" id="an-top-seller" style="font-size:14px;">—</div>
              <div class="kt-sub" id="an-top-seller-sub">No sales yet</div>
            </div>
          </div>

          <!-- Weekly + Hourly Charts -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <div class="sa-card">
              <div class="sa-card-title"><i class="bi bi-bar-chart-fill"></i> Weekly Sales Overview</div>
              <div style="height:230px;"><canvas id="sa-weekly-chart"></canvas></div>
            </div>
            <div class="sa-card">
              <div class="sa-card-title"><i class="bi bi-clock-fill"></i> Hourly Sales — Today</div>
              <div style="height:230px;"><canvas id="sa-hourly-chart"></canvas></div>
            </div>
          </div>

          <!-- Monthly + Item Performance Charts -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <div class="sa-card">
              <div class="sa-card-title"><i class="bi bi-calendar3"></i> Monthly Revenue Trend</div>
              <div style="height:230px;"><canvas id="sa-monthly-chart"></canvas></div>
            </div>
            <div class="sa-card">
              <div class="sa-card-title"><i class="bi bi-bar-chart-steps"></i> Item Sales Performance</div>
              <div style="height:230px;"><canvas id="sa-item-chart"></canvas></div>
            </div>
          </div>

          <!-- Category Breakdown -->
          <div class="sa-card">
            <div class="sa-card-title"><i class="bi bi-pie-chart-fill"></i> Category Breakdown <span style="font-size:11px;font-weight:400;color:var(--sa-muted);">(Last 30 days)</span></div>
            <div id="sa-cat-breakdown" style="display:flex;flex-wrap:wrap;gap:10px;padding-top:8px;"></div>
          </div>
        </div>

        <!-- ── LOGS VIEW ── -->
        <div id="sa-view-logs" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">Audit Log</div>
            <button class="sa-btn sa-btn-ghost" onclick="loadLogs()">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
          </div>

          <!-- Calendar & Period Filter Card -->
          <div class="sa-card" style="margin-bottom:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:8px;background:rgba(109,40,217,0.15);display:flex;align-items:center;justify-content:center;color:var(--sa-accent2);">
                  <i class="bi bi-calendar3" style="font-size:16px;"></i>
                </div>
                <div>
                  <div style="font-size:13px;font-weight:700;color:var(--sa-text);line-height:1.2;">Calendar & Period Filter</div>
                  <div id="log-period-summary" style="font-size:11.5px;color:var(--sa-muted);margin-top:2px;">Showing all logs (All Time)</div>
                </div>
              </div>

              <!-- Period Mode Switcher Tabs -->
              <div class="d-flex gap-2 flex-wrap" id="log-period-tabs">
                <button type="button" class="sa-cat-pill" id="log-tab-date" onclick="setLogPeriodMode('date')">
                  <i class="bi bi-calendar-event"></i> Specific Date
                </button>
                <button type="button" class="sa-cat-pill" id="log-tab-week" onclick="setLogPeriodMode('week')">
                  <i class="bi bi-calendar-range"></i> Specific Week
                </button>
                <button type="button" class="sa-cat-pill" id="log-tab-month" onclick="setLogPeriodMode('month')">
                  <i class="bi bi-calendar-month"></i> Specific Month
                </button>
                <button type="button" class="sa-cat-pill" id="log-tab-year" onclick="setLogPeriodMode('year')">
                  <i class="bi bi-calendar"></i> Specific Year
                </button>
                <button type="button" class="sa-cat-pill active" id="log-tab-all" onclick="setLogPeriodMode('all')">
                  <i class="bi bi-infinity"></i> All Time
                </button>
              </div>
            </div>

            <!-- Dynamic Picker Controls Box -->
            <div id="log-picker-box" style="display:none;background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:12px 16px;margin-top:10px;">
              <!-- DATE PICKER -->
              <div id="log-picker-date" class="log-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar-date" style="color:var(--sa-accent2);"></i> Choose Date:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogDate(-1)" title="Previous Day"><i class="bi bi-chevron-left"></i></button>
                  <input type="date" id="log-input-date" class="sa-input" style="width:auto;min-width:160px;padding:6px 12px;font-size:13px;" onchange="applyLogDateFilter()">
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogDate(1)" title="Next Day"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setLogToday()">Today</button>
                  <span id="log-date-display-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>

              <!-- WEEK PICKER -->
              <div id="log-picker-week" class="log-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar-week" style="color:var(--sa-accent2);"></i> Choose Week:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogWeek(-1)" title="Previous Week"><i class="bi bi-chevron-left"></i></button>
                  <input type="week" id="log-input-week" class="sa-input" style="width:auto;min-width:180px;padding:6px 12px;font-size:13px;" onchange="applyLogDateFilter()">
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogWeek(1)" title="Next Week"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setLogThisWeek()">This Week</button>
                  <span id="log-week-display-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>

              <!-- MONTH PICKER -->
              <div id="log-picker-month" class="log-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar-month" style="color:var(--sa-accent2);"></i> Choose Month:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogMonth(-1)" title="Previous Month"><i class="bi bi-chevron-left"></i></button>
                  <input type="month" id="log-input-month" class="sa-input" style="width:auto;min-width:160px;padding:6px 12px;font-size:13px;" onchange="applyLogDateFilter()">
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogMonth(1)" title="Next Month"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setLogThisMonth()">This Month</button>
                  <span id="log-month-display-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>

              <!-- YEAR PICKER -->
              <div id="log-picker-year" class="log-picker-section" style="display:none;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                  <label class="sa-label" style="margin-bottom:0;display:flex;align-items:center;gap:6px;font-size:12px;">
                    <i class="bi bi-calendar" style="color:var(--sa-accent2);"></i> Choose Year:
                  </label>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogYear(-1)" title="Previous Year"><i class="bi bi-chevron-left"></i></button>
                  <select id="log-input-year" class="sa-select" style="width:auto;min-width:130px;padding:6px 12px;font-size:13px;" onchange="applyLogDateFilter()">
                  </select>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:12px;" onclick="shiftLogYear(1)" title="Next Year"><i class="bi bi-chevron-right"></i></button>
                  <button type="button" class="sa-btn sa-btn-ghost" style="padding:5px 12px;font-size:12px;" onclick="setLogThisYear()">This Year</button>
                  <span id="log-year-display-label" style="font-size:12.5px;font-weight:600;color:var(--sa-accent2);margin-left:6px;"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Category Breakdown -->
          <div class="sa-card" style="margin-bottom:16px;">
            <div class="sa-card-title" style="margin-bottom:12px;"><i class="bi bi-pie-chart-fill"></i> Category Breakdown</div>
            <div class="log-breakdown" id="log-breakdown"><div style="color:var(--sa-muted);font-size:12px;">Loading...</div></div>
          </div>

          <!-- Log List -->
          <div class="sa-card" id="logs-card">
            <input class="log-search-bar" id="log-search" type="text" placeholder="🔍  Search logs by action, user, or details…" oninput="filterLogs()">
            <div class="log-list-wrap">
              <div id="logs-container"><div style="color:var(--sa-muted);font-size:13px;padding:20px 0;text-align:center;">Loading...</div></div>
            </div>
          </div>
        </div>

        <!-- ── SETTINGS VIEW ── -->
        <div id="sa-view-settings" class="sa-view">
          <div class="sa-section-header">
            <div class="sa-section-title">System Settings</div>
          </div>

          <div class="sa-card">
            <div class="sa-card-title"><i class="bi bi-gear-fill"></i> Store Configuration</div>
            <div class="settings-grid" id="settings-grid">
              <div class="sa-form-row">
                <label class="sa-label">Store Name</label>
                <input type="text" class="sa-input" id="setting-store_name" placeholder="ChipiPie Cafe">
              </div>
              <div class="sa-form-row">
                <label class="sa-label">Owner Email</label>
                <input type="email" class="sa-input" id="setting-owner_email" placeholder="owner@example.com">
              </div>
              <div class="sa-form-row">
                <label class="sa-label">Super Admin Email</label>
                <input type="email" class="sa-input" id="setting-superadmin_email" placeholder="superadmin@example.com">
              </div>
              <div class="sa-form-row">
                <label class="sa-label">Token Expiry (minutes)</label>
                <input type="number" class="sa-input" id="setting-token_expiry_minutes" min="5" max="60">
              </div>
              <div class="sa-form-row" style="grid-column: 1 / -1;">
                <label class="sa-label">Void/Refund Notification Email(s)</label>
                <input type="text" class="sa-input" id="setting-void_refund_manager_email" placeholder="manager1@example.com, manager2@example.com">
                <div style="font-size:11px;color:#888;margin-top:4px;">Email addresses (comma-separated) to receive interactive Approve/Reject notifications when a cashier requests an order void or refund.</div>
              </div>
            </div>
            <div style="margin-top:8px;">
              <button class="sa-btn sa-btn-primary" onclick="saveSettings()">
                <i class="bi bi-floppy-fill"></i> Save Settings
              </button>
            </div>
          </div>

          <div class="sa-card" style="margin-top:20px;">
            <div class="sa-card-title"><i class="bi bi-shield-fill"></i> Super Admin Account</div>
            <div class="sa-form-grid">
              <div class="sa-form-row">
                <label class="sa-label">New Password</label>
                <input type="password" class="sa-input" id="sa-new-pass" placeholder="Min. 8 characters" minlength="8">
                <div style="font-size:11px; color:var(--sa-muted); margin-top:4px;">Min. 8 characters with uppercase, lowercase, and number.</div>
              </div>
              <div class="sa-form-row">
                <label class="sa-label">Confirm Password</label>
                <input type="password" class="sa-input" id="sa-new-pass-confirm" placeholder="Repeat password" minlength="8">
              </div>
            </div>
            <button class="sa-btn sa-btn-danger" onclick="changeSAPassword()">
              <i class="bi bi-key-fill"></i> Change My Password
            </button>
          </div>

          <div class="sa-card" style="margin-top:20px;">
            <div class="sa-card-title"><i class="bi bi-credit-card-fill" style="color:#0070f3;"></i> PayMongo (GCash) Integration</div>
            <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 14px;font-size:12px;color:#1e40af;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;">
              <i class="bi bi-info-circle-fill" style="font-size:14px;flex-shrink:0;margin-top:1px;"></i>
              <span>Get your <strong>Secret Key</strong> from <a href="https://dashboard.paymongo.com/developers" target="_blank" style="color:#1d4ed8;font-weight:700;">PayMongo Dashboard → Developers</a>. Use <code style="background:#dbeafe;padding:1px 5px;border-radius:4px;">sk_test_...</code> for testing and <code style="background:#dbeafe;padding:1px 5px;border-radius:4px;">sk_live_...</code> for production.</span>
            </div>
            <div style="display:flex;gap:10px;align-items:flex-end;">
              <div style="flex:1;">
                <label class="sa-label">Secret Key <span id="sa-pm-key-badge" style="display:inline-block;margin-left:8px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:#d1fae5;color:#059669;">&#9679; Configured</span></label>
                <input type="password" class="sa-input" id="sa-pm-secret-key-input" placeholder="sk_test_xxxxxxxxxxxxxxxxxxxx" style="font-family:monospace;">
              </div>
              <button class="sa-btn sa-btn-primary" onclick="saveSAPaymongoKey()" style="white-space:nowrap;flex-shrink:0;">
                <i class="bi bi-floppy-fill"></i> Save Key
              </button>
            </div>
            <div id="sa-pm-key-status" style="display:none;margin-top:10px;font-size:12px;border-radius:8px;padding:8px 12px;"></div>
          </div>
        </div>

      </div> <!-- /sa-views -->
    </div>
  </div>

  <!-- ── MODAL: Approve/Reject Request ── -->
  <div class="sa-modal-overlay" id="review-modal">
    <div class="sa-modal">
      <div class="sa-modal-header">
        <div class="sa-modal-title" id="review-modal-title">Review Request</div>
        <button class="sa-modal-close" onclick="closeModal('review-modal')">&#x2715;</button>
      </div>
      <div class="sa-modal-body">
        <div id="review-modal-details" style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13px;line-height:1.7;"></div>
        <div class="sa-form-row">
          <label class="sa-label">Review Note (optional)</label>
          <textarea class="sa-input" id="review-note" rows="3" placeholder="Add a note or reason..."></textarea>
        </div>
      </div>
      <div class="sa-modal-footer">
        <button class="sa-btn sa-btn-ghost" onclick="closeModal('review-modal')">Cancel</button>
        <button class="sa-btn sa-btn-reject" id="review-reject-btn" onclick="submitReview('reject')">
          <i class="bi bi-x-circle-fill"></i> Reject
        </button>
        <button class="sa-btn sa-btn-approve" id="review-approve-btn" onclick="submitReview('approve')">
          <i class="bi bi-check-circle-fill"></i> Approve
        </button>
      </div>
    </div>
  </div>

  <!-- ── MODAL: Create User ── -->
  <div class="sa-modal-overlay" id="create-user-modal">
    <div class="sa-modal">
      <div class="sa-modal-header">
        <div class="sa-modal-title">Create New User</div>
        <button class="sa-modal-close" onclick="closeModal('create-user-modal')">&#x2715;</button>
      </div>
      <div class="sa-modal-body">
        <div class="sa-form-grid">
          <div class="sa-form-row">
            <label class="sa-label">Full Name *</label>
            <input type="text" class="sa-input" id="cu-fullname" placeholder="Juan Dela Cruz">
          </div>
          <div class="sa-form-row">
            <label class="sa-label">Username *</label>
            <input type="text" class="sa-input" id="cu-username" placeholder="juan">
          </div>
        </div>
        <div class="sa-form-row">
          <label class="sa-label">Email *</label>
          <input type="email" class="sa-input" id="cu-email" placeholder="juan@example.com">
        </div>
        <div class="sa-form-grid">
          <div class="sa-form-row">
            <label class="sa-label">Password *</label>
            <input type="password" class="sa-input" id="cu-pass" placeholder="Min. 8 chars (uppercase, lowercase, number)">
            <div style="font-size:11px; color:var(--sa-muted); margin-top:4px;">Min. 8 characters with uppercase, lowercase, and number.</div>
          </div>
          <div class="sa-form-row">
            <label class="sa-label">Role *</label>
            <select class="sa-select" id="cu-role">
              <option value="cashier">Cashier</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div id="cu-result" style="display:none;margin-top:10px;font-size:13px;padding:10px 14px;border-radius:9px;"></div>
      </div>
      <div class="sa-modal-footer">
        <button class="sa-btn sa-btn-ghost" onclick="closeModal('create-user-modal')">Cancel</button>
        <button class="sa-btn sa-btn-primary" onclick="submitCreateUser()">
          <i class="bi bi-person-plus-fill"></i> Create User
        </button>
      </div>
    </div>
  </div>

  <!-- ── MODAL: Edit User ── -->
  <div class="sa-modal-overlay" id="edit-user-modal">
    <div class="sa-modal">
      <div class="sa-modal-header">
        <div class="sa-modal-title">Edit User</div>
        <button class="sa-modal-close" onclick="closeModal('edit-user-modal')">&#x2715;</button>
      </div>
      <div class="sa-modal-body">
        <input type="hidden" id="eu-id">
        <div class="sa-form-grid">
          <div class="sa-form-row">
            <label class="sa-label">Full Name</label>
            <input type="text" class="sa-input" id="eu-fullname">
          </div>
          <div class="sa-form-row">
            <label class="sa-label">Email</label>
            <input type="email" class="sa-input" id="eu-email">
          </div>
        </div>
        <div class="sa-form-row">
          <label class="sa-label">Role</label>
          <select class="sa-select" id="eu-role">
            <option value="cashier">Cashier</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <hr style="border-color:var(--sa-border);margin:16px 0;">
        <div style="font-size:12px;font-weight:700;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">Reset Password</div>
        <div class="sa-form-row">
          <label class="sa-label">New Password (leave blank to keep current)</label>
          <input type="password" class="sa-input" id="eu-new-pass" placeholder="Min. 8 chars (uppercase, lowercase, number)">
          <div style="font-size:11px; color:var(--sa-muted); margin-top:4px;">Min. 8 characters with uppercase, lowercase, and number.</div>
        </div>
        <div id="eu-result" style="display:none;margin-top:10px;font-size:13px;padding:10px 14px;border-radius:9px;"></div>
      </div>
      <div class="sa-modal-footer">
        <button class="sa-btn sa-btn-ghost" onclick="closeModal('edit-user-modal')">Cancel</button>
        <button class="sa-btn sa-btn-primary" onclick="submitEditUser()">
          <i class="bi bi-floppy-fill"></i> Save Changes
        </button>
      </div>
    </div>
  <!-- ── ADD MENU ITEM MODAL (Super Admin) ── -->
  <div class="sa-modal-overlay" id="sa-add-item-modal">
    <div class="sa-modal" style="max-width:480px;">
      <div class="sa-modal-header">
        <h3><i class="bi bi-cup-hot-fill me-2" style="color:var(--sa-accent2);"></i>Add Menu Item</h3>
        <button class="sa-modal-close" onclick="closeModal('sa-add-item-modal')"><i class="bi bi-x-lg"></i></button>
      </div>
      <form onsubmit="saSubmitAddMenu(event)">
        <div class="sa-modal-body">
          <div class="sa-form-row">
            <label class="sa-label">Item Name</label>
            <input type="text" id="sa-new-menu-name" class="sa-input" placeholder="e.g. Spanish Latte" required>
          </div>
          <div class="sa-form-row">
            <label class="sa-label">Category</label>
            <select id="sa-new-menu-category" class="sa-select" required>
              <option value="1">Hot Coffee</option>
              <option value="2">Iced Coffee</option>
              <option value="3">Classic Milktea</option>
              <option value="4">Virgin Mojito</option>
              <option value="5">Citrus Popping Boba</option>
              <option value="6">Soda Float</option>
              <option value="7">Matcha Series</option>
              <option value="8">Frappé</option>
              <option value="9">Fork and Fun</option>
            </select>
          </div>
          <div class="sa-form-row">
            <label class="sa-label">Base Price (₱)</label>
            <input type="number" id="sa-new-menu-price" class="sa-input" placeholder="e.g. 120" min="1" step="0.5" required>
          </div>
          <div class="sa-form-grid">
            <div class="sa-form-row">
              <label class="sa-label">Initial Stock</label>
              <input type="number" id="sa-new-menu-stock" class="sa-input" value="20" min="0">
            </div>
            <div class="sa-form-row">
              <label class="sa-label">Min Stock Alert</label>
              <input type="number" id="sa-new-menu-min-stock" class="sa-input" value="5" min="1">
            </div>
          </div>
          <div class="sa-form-row">
            <label class="sa-label">Photo (optional)</label>
            <input type="file" id="sa-new-menu-image" class="sa-input" accept="image/*">
          </div>
        </div>
        <div class="sa-modal-footer">
          <button type="button" class="sa-btn sa-btn-ghost" onclick="closeModal('sa-add-item-modal')">Cancel</button>
          <button type="submit" class="sa-btn sa-btn-primary" id="sa-add-item-submit-btn">
            <i class="bi bi-plus-circle-fill"></i> Add to Menu
          </button>
        </div>
      </form>
    </div>
  </div>

    <!-- ── CONFIRMATION MODAL ── -->
  <div class="sa-modal-overlay" id="sa-confirm-modal">
    <div class="sa-modal">
      <div class="sa-modal-header">
        <h3 id="sa-confirm-title">Confirm Action</h3>
        <button class="sa-modal-close" onclick="closeModal('sa-confirm-modal')"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="sa-modal-body">
        <p id="sa-confirm-msg" style="color:var(--sa-text);font-size:14px;margin-bottom:0;"></p>
      </div>
      <div class="sa-modal-footer">
        <button class="sa-btn sa-btn-ghost" onclick="closeModal('sa-confirm-modal')">Cancel</button>
        <button class="sa-btn sa-btn-primary" id="sa-confirm-btn">Confirm</button>
      </div>
    </div>
  </div>

  <!-- ── TOAST ── -->
  <div class="sa-toast" id="sa-toast"></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <script>
    window.saChartIns = null;
    
    /* =============================================
       API HELPER
    ============================================= */
    const BASE = 'api/';
    async function saApi(endpoint, data = null) {
      const opts = {
        method: data ? 'POST' : 'GET',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin'
      };
      if (data) opts.body = JSON.stringify(data);
      const res  = await fetch(BASE + endpoint, opts);
      const text = await res.text();
      // Strip any PHP warnings/notices that may be prepended
      const jsonStart = text.search(/[{[]/);
      const clean     = jsonStart >= 0 ? text.slice(jsonStart) : text;
      return JSON.parse(clean);
    }

    /* =============================================
       TOAST
    ============================================= */
    function saToast(msg, type = 'success') {
      const t = document.getElementById('sa-toast');
      const icon = type === 'success' ? '✓' : '✕';
      t.innerHTML = `<span style="font-size:17px;">${icon}</span> ${msg}`;
      t.className = `sa-toast ${type} show`;
      setTimeout(() => t.classList.remove('show'), 3200);
    }

    /* =============================================
       CLOCK
    ============================================= */
    function updateClock() {
      const now = new Date();
      const el = document.getElementById('sa-live-time');
      if (el) el.textContent = now.toLocaleString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    /* =============================================
       AUTH
    ============================================= */
    let saCurrentUser = null;

    async function saHandleLogin(e) {
      e.preventDefault();
      const u    = document.getElementById('sa-login-user').value.trim();
      const p    = document.getElementById('sa-login-pass').value.trim();
      const errEl= document.getElementById('sa-login-error');
      const succEl= document.getElementById('sa-login-success');
      const btn  = document.getElementById('sa-login-btn');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Signing in...';

      try {
        const res = await saApi('auth.php?action=login', { username: u, password: p });
        if (res.requires_2fa) {
          // Switch to 2FA verification step
          document.getElementById('sa-step-credentials').style.display = 'none';
          document.getElementById('sa-step-2fa').style.display = 'block';
          document.getElementById('sa-2fa-email-display').textContent = res.masked_email || 'your email';
          const codeInput = document.getElementById('sa-2fa-code');
          codeInput.value = '';
          if (res.dev_code) {
            document.getElementById('sa-dev-hint').style.display = 'block';
            document.getElementById('sa-dev-code-val').textContent = res.dev_code;
            codeInput.value = res.dev_code;
          } else {
            document.getElementById('sa-dev-hint').style.display = 'none';
          }
          if (succEl) {
            succEl.textContent = res.message || 'Security code sent to your email!';
            succEl.style.display = 'block';
          }
          setTimeout(() => codeInput.focus(), 100);
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Sign In as Super Admin';
          return;
        } else if (res.success && res.user.role === 'super_admin') {
          saCurrentUser = res.user;
          activateSAApp();
        } else if (res.success && res.user.role !== 'super_admin') {
          errEl.textContent = 'This account does not have Super Admin privileges.';
          errEl.style.display = 'block';
        } else {
          errEl.textContent = res.message || 'Invalid credentials.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Connection error. Make sure the server is running.';
        errEl.style.display = 'block';
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Sign In as Super Admin';
    }

    async function saHandleVerify2FA(e) {
      e.preventDefault();
      const code   = document.getElementById('sa-2fa-code').value.trim();
      const errEl  = document.getElementById('sa-login-error');
      const succEl = document.getElementById('sa-login-success');
      const btn    = document.getElementById('sa-verify-btn');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      if (!code) return;

      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Verifying Code...';

      try {
        const res = await saApi('auth.php?action=verify_2fa', { code: code });
        if (res.success && res.user && res.user.role === 'super_admin') {
          saCurrentUser = res.user;
          activateSAApp();
        } else {
          errEl.textContent = res.message || 'Invalid or expired code.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Verification error. Please try again.';
        errEl.style.display = 'block';
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-shield-check"></i> Verify & Sign In';
    }

    async function saResend2FA() {
      const btn   = document.getElementById('sa-resend-btn');
      const errEl = document.getElementById('sa-login-error');
      const succEl= document.getElementById('sa-login-success');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';

      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Resending...';

      try {
        const res = await saApi('auth.php?action=resend_2fa', {});
        if (res.success) {
          succEl.textContent = res.message || 'New code sent to your email!';
          succEl.style.display = 'block';
          if (res.dev_code) {
            document.getElementById('sa-dev-hint').style.display = 'block';
            document.getElementById('sa-dev-code-val').textContent = res.dev_code;
            document.getElementById('sa-2fa-code').value = res.dev_code;
          }
        } else {
          errEl.textContent = res.message || 'Could not resend code.';
          errEl.style.display = 'block';
        }
      } catch (e) {
        errEl.textContent = 'Error contacting server.';
        errEl.style.display = 'block';
      }

      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Resend Code';
      }, 5000);
    }

    async function saCancel2FA() {
      try {
        await saApi('auth.php?action=cancel_2fa', {});
      } catch(e) {}
      document.getElementById('sa-step-2fa').style.display = 'none';
      document.getElementById('sa-step-credentials').style.display = 'block';
      const errEl = document.getElementById('sa-login-error');
      const succEl= document.getElementById('sa-login-success');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      document.getElementById('sa-login-pass').value = '';
    }

    /* =============================================
       PASSWORD COMPLEXITY HELPER
    ============================================= */
    function checkPasswordComplexity(p) {
      if (!p || p.length < 8) return 'Password must be at least 8 characters long.';
      if (!/[A-Z]/.test(p)) return 'Password must contain at least one uppercase letter (A-Z).';
      if (!/[a-z]/.test(p)) return 'Password must contain at least one lowercase letter (a-z).';
      if (!/[0-9]/.test(p)) return 'Password must contain at least one number (0-9).';
      return '';
    }

    /* =============================================
       FORGOT PASSWORD (Super Admin)
    ============================================= */
    let saForgotPendingUser = '';

    function saShowForgotStep1() {
      document.getElementById('sa-step-credentials').style.display = 'none';
      document.getElementById('sa-step-2fa').style.display = 'none';
      document.getElementById('sa-step-forgot-reset').style.display = 'none';
      document.getElementById('sa-step-forgot-request').style.display = 'block';
      const errEl = document.getElementById('sa-login-error');
      const succEl = document.getElementById('sa-login-success');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      setTimeout(() => {
        const inp = document.getElementById('sa-forgot-user');
        if (inp) inp.focus();
      }, 100);
    }

    function saHideForgot() {
      document.getElementById('sa-step-forgot-request').style.display = 'none';
      document.getElementById('sa-step-forgot-reset').style.display = 'none';
      document.getElementById('sa-step-credentials').style.display = 'block';
      const errEl = document.getElementById('sa-login-error');
      const succEl = document.getElementById('sa-login-success');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
    }

    async function saHandleSendForgotCode(e) {
      e.preventDefault();
      const errEl = document.getElementById('sa-login-error');
      const succEl = document.getElementById('sa-login-success');
      errEl.style.display = 'none'; succEl.style.display = 'none';

      const identifier = document.getElementById('sa-forgot-user').value.trim();
      if (!identifier) {
        errEl.textContent = 'Please enter your username or email.';
        errEl.style.display = 'block';
        return;
      }

      const btn = document.getElementById('sa-forgot-send-btn');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending code...';

      try {
        const res = await saApi('auth.php?action=forgot_password', { identifier });
        if (res.success) {
          saForgotPendingUser = res.identifier || identifier;
          document.getElementById('sa-forgot-email-display').textContent = res.masked_email || 'your email';
          document.getElementById('sa-step-forgot-request').style.display = 'none';
          document.getElementById('sa-step-forgot-reset').style.display = 'block';
          document.getElementById('sa-forgot-code').value = '';
          document.getElementById('sa-forgot-new-pass').value = '';
          document.getElementById('sa-forgot-confirm-pass').value = '';
          if (res.dev_code) {
            document.getElementById('sa-forgot-dev-hint').style.display = 'block';
            document.getElementById('sa-forgot-dev-code').textContent = res.dev_code;
            document.getElementById('sa-forgot-code').value = res.dev_code;
          } else {
            document.getElementById('sa-forgot-dev-hint').style.display = 'none';
          }
          succEl.textContent = res.message;
          succEl.style.display = 'block';
          setTimeout(() => {
            const inp = document.getElementById('sa-forgot-code');
            if (inp) inp.focus();
          }, 100);
        } else {
          errEl.textContent = res.message || 'Could not send recovery code.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Connection error. Please try again.';
        errEl.style.display = 'block';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Recovery Code';
      }
    }

    async function saHandleResendForgotCode() {
      const btn = document.getElementById('sa-forgot-resend-btn');
      const errEl = document.getElementById('sa-login-error');
      const succEl = document.getElementById('sa-login-success');
      errEl.style.display = 'none'; succEl.style.display = 'none';
      if (!saForgotPendingUser) {
        saShowForgotStep1();
        return;
      }
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Resending...';
      try {
        const res = await saApi('auth.php?action=forgot_password', { identifier: saForgotPendingUser });
        if (res.success) {
          succEl.textContent = res.message || 'Recovery code resent.';
          succEl.style.display = 'block';
          if (res.dev_code) {
            document.getElementById('sa-forgot-dev-hint').style.display = 'block';
            document.getElementById('sa-forgot-dev-code').textContent = res.dev_code;
            document.getElementById('sa-forgot-code').value = res.dev_code;
          }
        } else {
          errEl.textContent = res.message || 'Failed to resend code.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Server error. Please try again.';
        errEl.style.display = 'block';
      } finally {
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Resend Code';
        }, 3000);
      }
    }

    async function saHandleResetPassword(e) {
      e.preventDefault();
      const errEl = document.getElementById('sa-login-error');
      const succEl = document.getElementById('sa-login-success');
      errEl.style.display = 'none'; succEl.style.display = 'none';

      const token = document.getElementById('sa-forgot-code').value.trim();
      const newPass = document.getElementById('sa-forgot-new-pass').value.trim();
      const confirmPass = document.getElementById('sa-forgot-confirm-pass').value.trim();

      if (!token) {
        errEl.textContent = 'Please enter the 6-digit recovery code.';
        errEl.style.display = 'block'; return;
      }
      if (newPass !== confirmPass) {
        errEl.textContent = 'Passwords do not match.';
        errEl.style.display = 'block'; return;
      }
      const pwdErr = checkPasswordComplexity(newPass);
      if (pwdErr) {
        errEl.textContent = pwdErr;
        errEl.style.display = 'block'; return;
      }

      const btn = document.getElementById('sa-forgot-submit-btn');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating password...';

      try {
        const res = await saApi('auth.php?action=reset_password_code', {
          identifier: saForgotPendingUser,
          token,
          new_password: newPass
        });
        if (res.success) {
          saHideForgot();
          succEl.textContent = '🎉 ' + (res.message || 'Password reset successful! You can now sign in.');
          succEl.style.display = 'block';
          document.getElementById('sa-login-pass').value = '';
          document.getElementById('sa-login-user').value = saForgotPendingUser;
          saForgotPendingUser = '';
        } else {
          errEl.textContent = res.message || 'Could not reset password.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Connection error. Please try again.';
        errEl.style.display = 'block';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-check"></i> Reset Password';
      }
    }

    function activateSAApp() {
      document.getElementById('sa-login-screen').style.display = 'none';
      document.getElementById('sa-step-2fa').style.display = 'none';
      document.getElementById('sa-step-credentials').style.display = 'block';
      document.getElementById('sa-app').style.display = 'flex';
      document.getElementById('sa-sidebar-name').textContent = saCurrentUser.full_name;
      document.getElementById('sa-sidebar-avatar').textContent = saCurrentUser.full_name.charAt(0).toUpperCase();
      loadDashboard();
    }

    async function saLogout() {
      // 1. Immediately reset client state and show login UI
      saCurrentUser = null;
      try {
        const appEl = document.getElementById('sa-app');
        const loginEl = document.getElementById('sa-login-screen');
        if (appEl) appEl.style.display = 'none';
        if (loginEl) {
          loginEl.style.display = 'flex';
          document.getElementById('sa-step-2fa').style.display = 'none';
          document.getElementById('sa-step-credentials').style.display = 'block';
        }
        const passEl = document.getElementById('sa-login-pass');
        if (passEl) passEl.value = '';
      } catch (e) {}

      // 2. Clear server session via API
      try {
        await saApi('auth.php?action=logout', {});
      } catch (e) {}

      // 3. Force clean browser navigation through logout endpoint
      window.location.href = 'api/auth.php?action=logout&redirect=superadmin-panel.php';
    }

    /* =============================================
       NAVIGATION
    ============================================= */
    const VIEW_TITLES = {
      dashboard: 'Dashboard',
      requests: 'System Requests',
      'admin-reqs': 'Admin Approval Requests',
      users: 'All Users',
      menu: 'Menu & Inventory',
      orders: 'Orders',
      logs: 'Audit Log',
      analytics: 'Analytics',
      settings: 'System Settings',
    };

    const VIEW_LOADERS = {
      dashboard:   loadDashboard,
      requests:    loadRequests,
      'admin-reqs':loadAdminRequests,
      users:       loadUsers,
      menu:        saLoadMenu,
      orders:      loadOrders,
      analytics:   loadAnalytics,
      logs:        loadLogs,
      settings:    loadSettings,
    };

    function saShowView(name, btn) {
      document.querySelectorAll('.sa-view').forEach(v => v.classList.remove('active'));
      document.querySelectorAll('.sa-nav-btn').forEach(b => b.classList.remove('active'));
      document.getElementById(`sa-view-${name}`)?.classList.add('active');
      if (btn) btn.classList.add('active');
      document.getElementById('sa-page-title').textContent = VIEW_TITLES[name] || name;
      if (VIEW_LOADERS[name]) VIEW_LOADERS[name]();
    }

    /* =============================================
       DASHBOARD
    ============================================= */
    async function loadDashboard() {
      try {
        const res = await saApi('superadmin.php?action=dashboard');
        if (!res.success) return;

        document.getElementById('kpi-total-users').textContent  = res.users.total;
        document.getElementById('kpi-users-sub').textContent    = `${res.users.admins} admins · ${res.users.cashiers} cashiers`;
        document.getElementById('kpi-pending').textContent      = res.requests.pending + res.requests.pending_admins;
        document.getElementById('kpi-pending-sub').textContent  = `${res.requests.pending} system · ${res.requests.pending_admins} admin regs`;
        document.getElementById('kpi-today-rev').textContent    = '₱' + res.orders.today_revenue.toLocaleString();
        document.getElementById('kpi-rev-sub').textContent      = `${res.orders.today} orders today`;
        document.getElementById('kpi-today-orders').textContent = res.orders.today;
        document.getElementById('kpi-orders-sub').textContent   = `₱${res.orders.total_revenue.toLocaleString()} all time`;
        document.getElementById('kpi-stock-alerts').textContent = res.menu.low_stock + res.menu.out_of_stock;
        document.getElementById('kpi-stock-sub').textContent    = `${res.menu.low_stock} low · ${res.menu.out_of_stock} out`;
        document.getElementById('kpi-total-rev').textContent    = '₱' + res.orders.total_revenue.toLocaleString();

        // Update badge
        const pendingTotal = res.requests.pending + res.requests.pending_admins;
        updateNavBadge('nav-badge-requests', res.requests.pending);
        updateNavBadge('nav-badge-admin-reqs', res.requests.pending_admins);

        // Recent logs
        const logEl = document.getElementById('dashboard-logs');
        if (res.recent_logs && res.recent_logs.length) {
          logEl.innerHTML = res.recent_logs.slice(0, 6).map(renderLogEntry).join('');
        } else {
          logEl.innerHTML = '<div class="sa-empty"><i class="bi bi-journal"></i><p>No activity yet</p></div>';
        }

        // Pending summary
        const pendEl = document.getElementById('dashboard-pending');
        if (pendingTotal === 0) {
          pendEl.innerHTML = '<div class="sa-empty"><i class="bi bi-check2-all"></i><p>All caught up! No pending items.</p></div>';
        } else {
          pendEl.innerHTML = `
            ${res.requests.pending > 0 ? `<div class="log-entry" style="cursor:pointer;" onclick="saShowView('requests', document.getElementById('nav-btn-requests'))">
              <div class="log-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b;"><i class="bi bi-clock-history"></i></div>
              <div class="log-body">
                <div class="log-action">${res.requests.pending} System Request${res.requests.pending !== 1 ? 's' : ''}</div>
                <div class="log-detail">Click to review pending system requests</div>
              </div>
            </div>` : ''}
            ${res.requests.pending_admins > 0 ? `<div class="log-entry" style="cursor:pointer;" onclick="saShowView('admin-reqs', document.getElementById('nav-btn-admin-reqs'))">
              <div class="log-icon" style="background:rgba(109,40,217,0.15);color:#a855f7;"><i class="bi bi-person-badge-fill"></i></div>
              <div class="log-body">
                <div class="log-action">${res.requests.pending_admins} Admin Registration${res.requests.pending_admins !== 1 ? 's' : ''}</div>
                <div class="log-detail">Pending admin account approvals</div>
              </div>
            </div>` : ''}
          `;
        }

        // Render Weekly Chart
        if (res.weekly_chart) {
          const wdata = res.weekly_chart;
          if (window.saChartIns) {
            window.saChartIns.data.datasets[0].data = wdata;
            window.saChartIns.update();
          } else {
            const ctx = document.getElementById('saWeeklyChart');
            if (ctx) {
              window.saChartIns = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                  labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
                  datasets: [{
                    label: 'Revenue (₱)',
                    data: wdata,
                    borderColor: '#a855f7',
                    backgroundColor: 'rgba(168, 85, 247, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#a855f7',
                    pointBorderWidth: 2,
                    pointRadius: 4
                  }]
                },
                options: { 
                  responsive: true, 
                  maintainAspectRatio: false,
                  plugins: { legend: { display: false } },
                  scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { dash: [4, 4] } },
                    x: { grid: { display: false } }
                  }
                }
              });
            }
          }
        }

      } catch (e) { console.error('Dashboard load failed:', e); }
    }

    function updateNavBadge(id, count) {
      const el = document.getElementById(id);
      if (!el) return;
      if (count > 0) {
        el.textContent = count;
        el.style.display = 'inline-block';
      } else {
        el.style.display = 'none';
      }
    }

    function renderLogEntry(log) {
      const icons = {
        approve_admin_registration: { icon: 'bi-check-circle-fill', color: 'rgba(16,185,129,0.15)', iconColor: '#10b981' },
        reject_admin_registration:  { icon: 'bi-x-circle-fill',     color: 'rgba(239,68,68,0.15)',  iconColor: '#ef4444' },
        create_user:                { icon: 'bi-person-plus-fill',   color: 'rgba(59,130,246,0.15)', iconColor: '#3b82f6' },
        delete_user:                { icon: 'bi-trash-fill',         color: 'rgba(239,68,68,0.15)',  iconColor: '#ef4444' },
        reset_password:             { icon: 'bi-key-fill',           color: 'rgba(245,158,11,0.15)', iconColor: '#f59e0b' },
        approve_system_request:     { icon: 'bi-check2-all',         color: 'rgba(16,185,129,0.15)', iconColor: '#10b981' },
        reject_system_request:      { icon: 'bi-slash-circle-fill',  color: 'rgba(239,68,68,0.15)',  iconColor: '#ef4444' },
        save_system_settings:       { icon: 'bi-gear-fill',          color: 'rgba(109,40,217,0.15)', iconColor: '#a855f7' },
        superadmin_login:           { icon: 'bi-shield-check',       color: 'rgba(16,185,129,0.15)', iconColor: '#10b981' },
        admin_login:                { icon: 'bi-shield-lock-fill',   color: 'rgba(5,150,105,0.15)',  iconColor: '#059669' },
      };
      const style = icons[log.action] || { icon: 'bi-activity', color: 'rgba(100,116,139,0.15)', iconColor: '#64748b' };
      const time  = new Date(log.created_at).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

      return `<div class="log-entry">
        <div class="log-icon" style="background:${style.color};color:${style.iconColor};"><i class="bi ${style.icon}"></i></div>
        <div class="log-body">
          <div class="log-action">${log.action.replace(/_/g,' ')}</div>
          <div class="log-detail">${log.details || log.actor_username || '—'}</div>
          <div class="log-time">${time}</div>
        </div>
      </div>`;
    }

    /* =============================================
       REQUESTS
    ============================================= */
    let pendingReviewId   = null;
    let pendingReviewType = null;

    async function loadRequests() {
      const status = document.getElementById('req-status-filter')?.value || 'pending';
      try {
        const res = await saApi(`superadmin.php?action=requests&status=${status}`);
        if (!res.success) return;

        updateNavBadge('nav-badge-requests', res.requests.filter(r => r.status === 'pending').length);

        const tbody = document.getElementById('requests-tbody');
        if (!res.requests.length) {
          tbody.innerHTML = `<tr><td colspan="7"><div class="sa-empty"><i class="bi bi-inbox"></i><p>No requests found</p></div></td></tr>`;
          return;
        }

        const REQ_TYPE_LABELS = {
          admin_approval:  { label: 'Admin Approval',   color: '#7c3aed' },
          void_order:      { label: 'Void Order',        color: '#dc2626' },
          refund_order:    { label: 'Refund Order',      color: '#ea580c' },
          menu_change:     { label: 'Menu Change',       color: '#0891b2' },
          setting_change:  { label: 'Setting Change',    color: '#0284c7' },
          user_deactivate: { label: 'Deactivate Account',color: '#d97706' },
          user_activate:   { label: 'Activate Account',  color: '#059669' },
          user_delete:     { label: 'Delete Account',    color: '#b91c1c' },
          other:           { label: 'Other',             color: '#64748b' },
        };

        tbody.innerHTML = res.requests.map((r, index) => {
          const typeInfo = REQ_TYPE_LABELS[r.type] || { label: r.type.replace(/_/g,' '), color: '#64748b' };
          return `
          <tr>
            <td style="color:var(--sa-muted);font-size:12px;">${index + 1}</td>
            <td><span style="background:${typeInfo.color}22;color:${typeInfo.color};border:1px solid ${typeInfo.color}44;border-radius:6px;padding:3px 8px;font-size:10.5px;font-weight:600;white-space:nowrap;">${typeInfo.label}</span></td>
            <td style="font-weight:500;">${r.requester_name || '—'}</td>
            <td>${r.title}</td>
            <td>
              <span class="badge-sa badge-${r.status}">${r.status}</span>
              ${(r.status !== 'pending' && r.review_note) ? '<div style=\'font-size:10px;color:var(--sa-muted);margin-top:2px;\'>' + (r.review_note.includes('Email') ? '\uD83D\uDCE7 via Email' : r.review_note.includes('PIN') ? '\uD83D\uDD11 via PIN' : '\uD83D\uDC64 by SA') + '</div>' : ''}
            </td>
            <td style="color:var(--sa-muted);font-size:12px;">${new Date(r.created_at).toLocaleDateString('en-PH')}</td>
            <td>
              ${r.status === 'pending' ? `
                <div style="display:flex;gap:6px;">
                  <button class="sa-btn sa-btn-approve" style="padding:5px 10px;font-size:11px;" onclick="openReviewModal('system', ${r.id}, ${JSON.stringify(r).replace(/"/g,'&quot;')})">
                    <i class="bi bi-check-circle-fill"></i> Approve
                  </button>
                  <button class="sa-btn sa-btn-reject" style="padding:5px 10px;font-size:11px;" onclick="quickReject('system', ${r.id})">
                    <i class="bi bi-x-circle"></i>
                  </button>
                </div>
              ` : `<span style="font-size:11px;color:var(--sa-muted);">${r.reviewed_at ? new Date(r.reviewed_at).toLocaleDateString('en-PH') : '—'}</span>`}
            </td>
          </tr>`;
        }).join('');

      } catch (e) { console.error(e); }
    }

    function resetRequestsFilter() {
      const filter = document.getElementById('req-status-filter');
      if (filter) filter.value = 'pending';
      loadRequests();
    }

    async function loadAdminRequests() {
      try {
        const res = await saApi('superadmin.php?action=requests');
        if (!res.success) return;

        const pending = res.admin_requests.filter(r => r.status === 'pending').length;
        updateNavBadge('nav-badge-admin-reqs', pending);

        const tbody = document.getElementById('admin-reqs-tbody');
        if (!res.admin_requests.length) {
          tbody.innerHTML = `<tr><td colspan="7"><div class="sa-empty"><i class="bi bi-person-check"></i><p>No admin requests found</p></div></td></tr>`;
          return;
        }

        tbody.innerHTML = res.admin_requests.map((r, index) => {
          const safeName = (r.full_name || '').replace(/'/g, "\\'");
          const safeUser = (r.username || '').replace(/'/g, "\\'");
          const safeEmail = (r.email || '').replace(/'/g, "\\'");
          return `
          <tr>
            <td style="color:var(--sa-muted);font-size:12px;">${index + 1}</td>
            <td style="font-weight:600;">${r.full_name}</td>
            <td><code style="color:#a855f7;background:rgba(109,40,217,0.1);padding:2px 8px;border-radius:5px;">${r.username}</code></td>
            <td style="font-size:12px;color:var(--sa-muted);">${r.email}</td>
            <td><span class="badge-sa badge-${r.status === 'approved' ? 'approved' : r.status === 'pending' ? 'pending' : 'rejected'}">${r.status}</span></td>
            <td style="color:var(--sa-muted);font-size:12px;">${new Date(r.created_at).toLocaleString('en-PH', {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})}</td>
            <td>
              <div style="display:flex;gap:6px;align-items:center;">
                ${r.status === 'pending' ? `
                  <button class="sa-btn sa-btn-approve" style="padding:5px 10px;font-size:11px;" onclick="openReviewModal('admin_reg', ${r.id}, {full_name:'${safeName}',username:'${safeUser}',email:'${safeEmail}'})">
                    <i class="bi bi-check-circle-fill"></i> Approve
                  </button>
                  <button class="sa-btn sa-btn-reject" style="padding:5px 10px;font-size:11px;" onclick="quickReject('admin_reg', ${r.id})">
                    <i class="bi bi-x-circle"></i> Reject
                  </button>
                ` : ''}
                <button class="sa-btn sa-btn-reject" style="padding:5px 10px;font-size:11px;background:rgba(239,68,68,0.1);color:#ef4444;border-color:rgba(239,68,68,0.3);" onclick="deleteAdminRequest(${r.id}, '${safeName}')" title="Delete request">
                  <i class="bi bi-trash-fill"></i> Delete
                </button>
              </div>
            </td>
          </tr>
        `;
        }).join('');
      } catch (e) { console.error(e); }
    }

    function openReviewModal(type, id, data) {
      pendingReviewId   = id;
      pendingReviewType = type;
      document.getElementById('review-note').value = '';

      let details = '';
      if (type === 'admin_reg') {
        document.getElementById('review-modal-title').textContent = 'Approve Admin Registration';
        details = `<strong>Name:</strong> ${data.full_name || '—'}<br>
                   <strong>Username:</strong> ${data.username || '—'}<br>
                   <strong>Email:</strong> ${data.email || '—'}<br>
                   <br><em style="color:var(--sa-green);">Approving will create this user's admin account immediately.</em>`;
      } else if (data.type === 'void_order' || data.type === 'refund_order') {
        const isVoid = data.type === 'void_order';
        const pl = typeof data.payload === 'string' ? JSON.parse(data.payload||'{}') : (data.payload||{});
        const color = isVoid ? '#dc2626' : '#ea580c';
        const icon  = isVoid ? 'bi-slash-circle-fill' : 'bi-arrow-counterclockwise';
        document.getElementById('review-modal-title').textContent = isVoid ? 'Review Void Request' : 'Review Refund Request';
        document.getElementById('review-approve-btn').innerHTML  = `<i class="bi bi-check-circle-fill"></i> ${isVoid ? 'Approve Void' : 'Approve Refund'}`;
        document.getElementById('review-reject-btn').innerHTML   = `<i class="bi bi-x-circle-fill"></i> Reject`;
        details = `
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:38px;height:38px;border-radius:10px;background:${color}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi ${icon}" style="color:${color};font-size:18px;"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:15px;color:var(--sa-text);">${isVoid ? 'Order Void Request' : 'Order Refund Request'}</div>
              <div style="font-size:12px;color:var(--sa-muted);">Submitted by <strong>${data.requester_name || '—'}</strong></div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
              <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Order ID</div>
              <div style="font-weight:700;font-size:15px;color:var(--sa-text);">#${pl.order_id || '—'}</div>
            </div>
            <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
              <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Amount</div>
              <div style="font-weight:700;font-size:15px;color:${color};">₱${pl.amount ? parseFloat(pl.amount).toFixed(2) : '0.00'}</div>
            </div>
          </div>
          <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
            <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Reason</div>
            <div style="font-size:13px;color:var(--sa-text);">${pl.reason || data.description || '—'}</div>
          </div>
          <div style="margin-top:10px;padding:8px 12px;border-radius:8px;background:${color}11;border:1px solid ${color}33;font-size:12px;color:${color};">
            <i class="bi bi-exclamation-triangle-fill"></i> 
            ${isVoid ? 'Approving this will mark the order as Voided and restore all stock.' : 'Approving this will mark the order as Refunded and restore all stock.'}
          </div>`;
      } else if (data.type === 'menu_change') {
        const pl = typeof data.payload === 'string' ? JSON.parse(data.payload||'{}') : (data.payload||{});
        const isAdd = (pl.action === 'add');
        const color = isAdd ? '#10b981' : '#dc2626';
        const icon  = isAdd ? 'bi-plus-circle-fill' : 'bi-trash-fill';
        const actionLabel = isAdd ? 'Add Menu Item' : 'Remove Menu Item';
        document.getElementById('review-modal-title').textContent = `Review: ${actionLabel}`;
        document.getElementById('review-approve-btn').innerHTML  = `<i class="bi bi-check-circle-fill"></i> ${isAdd ? 'Approve Addition' : 'Approve Removal'}`;
        document.getElementById('review-reject-btn').innerHTML   = `<i class="bi bi-x-circle-fill"></i> Reject`;
        details = `
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="width:38px;height:38px;border-radius:10px;background:${color}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi ${icon}" style="color:${color};font-size:18px;"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:15px;color:var(--sa-text);">${actionLabel}</div>
              <div style="font-size:12px;color:var(--sa-muted);">Requested by <strong>${data.requester_name || 'Staff'}</strong></div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
              <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Item Name</div>
              <div style="font-weight:700;font-size:14px;color:var(--sa-text);">${pl.name || '—'}</div>
            </div>
            <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
              <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Price</div>
              <div style="font-weight:700;font-size:14px;color:var(--sa-gold);">₱${pl.price ? parseFloat(pl.price).toFixed(2) : '0.00'}</div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
            <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
              <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Category</div>
              <div style="font-size:13px;color:var(--sa-text);">${pl.category_name || pl.category || 'General'}</div>
            </div>
            <div style="background:var(--sa-bg);border-radius:8px;padding:10px;">
              <div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Stock</div>
              <div style="font-size:13px;color:var(--sa-text);">${pl.stock !== undefined ? pl.stock + ' units' : '—'}</div>
            </div>
          </div>
          ${pl.reason ? `<div style="background:var(--sa-bg);border-radius:8px;padding:10px;margin-bottom:10px;"><div style="font-size:10px;color:var(--sa-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Reason</div><div style="font-size:13px;color:var(--sa-text);">${pl.reason}</div></div>` : ''}
          <div style="padding:8px 12px;border-radius:8px;background:${color}11;border:1px solid ${color}33;font-size:12px;color:${color};">
            <i class="bi bi-info-circle-fill"></i> 
            ${isAdd ? 'Approving this will immediately insert the new item into the menu.' : 'Approving this will permanently remove the item from the active menu.'}
          </div>`;
      } else {
        document.getElementById('review-modal-title').textContent = 'Review System Request';
        document.getElementById('review-approve-btn').innerHTML = '<i class="bi bi-check-circle-fill"></i> Approve';
        document.getElementById('review-reject-btn').innerHTML  = '<i class="bi bi-x-circle-fill"></i> Reject';
        const REQ_LABELS = {
          user_deactivate: 'Deactivate Account', user_activate: 'Activate Account',
          user_delete: 'Delete Account', menu_change: 'Menu Change',
          setting_change: 'Setting Change', admin_approval: 'Admin Approval', other: 'Other'
        };
        details = `<strong>Type:</strong> ${REQ_LABELS[data.type] || (data.type||'').replace(/_/g,' ')}<br>
                   <strong>Title:</strong> ${data.title || '—'}<br>
                   <strong>Requester:</strong> ${data.requester_name || '—'}<br>
                   <strong>Description:</strong> ${data.description || '—'}`;
      }

      document.getElementById('review-modal-details').innerHTML = details;
      openModal('review-modal');
    }

    async function submitReview(action) {
      if (!pendingReviewId) return;
      const note = document.getElementById('review-note').value.trim();
      const type = pendingReviewType === 'admin_reg' ? 'admin_reg' : 'system';
      const endpoint = action === 'approve' ? 'superadmin.php?action=approve_request' : 'superadmin.php?action=reject_request';

      try {
        const res = await saApi(endpoint, { id: pendingReviewId, type, note });
        closeModal('review-modal');
        saToast(res.message, res.success ? 'success' : 'error');
        if (res.success) {
          loadDashboard();
          if (type === 'admin_reg') loadAdminRequests();
          else loadRequests();
        }
      } catch (e) {
        saToast('Connection error', 'error');
      }
    }

    async function quickReject(type, id) {
      if (!confirm('Reject this request?')) return;
      const saType = type === 'admin_reg' ? 'admin_reg' : 'system';
      const res = await saApi('superadmin.php?action=reject_request', { id, type: saType, note: 'Rejected by Super Admin' });
      saToast(res.message, res.success ? 'success' : 'error');
      if (res.success) {
        loadDashboard();
        if (type === 'admin_reg') loadAdminRequests();
        else loadRequests();
      }
    }

    /* =============================================
       USERS
    ============================================= */
    async function loadUsers() {
      try {
        const res = await saApi('superadmin.php?action=users');
        if (!res.success) return;

        const tbody = document.getElementById('users-tbody');
        if (!res.users.length) {
          tbody.innerHTML = `<tr><td colspan="8"><div class="sa-empty"><i class="bi bi-people"></i><p>No users found</p></div></td></tr>`;
          return;
        }

        tbody.innerHTML = res.users.map(u => {
          const roleBadge = u.role === 'super_admin'
            ? `<span class="badge-sa badge-super">⚡ Super Admin</span>`
            : u.role === 'admin'
              ? `<span class="badge-sa badge-admin">Admin</span>`
              : `<span class="badge-sa badge-cashier">Cashier</span>`;

          const isMe = saCurrentUser && u.id == saCurrentUser.id;
          const isSA = u.role === 'super_admin';

          return `<tr>
            <td style="color:var(--sa-muted);font-size:12px;">${u.id}</td>
            <td style="font-weight:600;">${u.full_name}${isMe ? ' <span style="font-size:10px;color:#a855f7;">(you)</span>' : ''}</td>
            <td><code style="color:#94a3b8;font-size:12px;">${u.username}</code></td>
            <td style="font-size:12px;color:var(--sa-muted);">${u.email || '—'}</td>
            <td>${roleBadge}</td>
            <td><span class="badge-sa badge-${u.is_active == 1 ? 'active' : 'inactive'}">${u.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
            <td style="font-size:12px;color:var(--sa-muted);">${new Date(u.created_at).toLocaleDateString('en-PH')}</td>
            <td>
              ${!isSA ? `<div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:11px;" onclick="openEditUserModal(${u.id},'${u.full_name}','${u.email}','${u.role}')">
                  <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="sa-btn ${u.is_active == 1 ? 'sa-btn-reject' : 'sa-btn-approve'}" style="padding:5px 9px;font-size:11px;" onclick="toggleUser(${u.id}, ${u.is_active})">
                  <i class="bi bi-${u.is_active == 1 ? 'pause-circle' : 'play-circle'}-fill"></i>
                </button>
                <button class="sa-btn sa-btn-danger" style="padding:5px 9px;font-size:11px;" onclick="deleteUser(${u.id},'${u.full_name}')">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>` : '<span style="font-size:11px;color:var(--sa-muted);">Protected</span>'}
            </td>
          </tr>`;
        }).join('');
      } catch (e) { console.error(e); }
    }

    function openCreateUserModal() {
      document.getElementById('cu-fullname').value = '';
      document.getElementById('cu-username').value = '';
      document.getElementById('cu-email').value    = '';
      document.getElementById('cu-pass').value     = '';
      document.getElementById('cu-role').value     = 'cashier';
      document.getElementById('cu-result').style.display = 'none';
      openModal('create-user-modal');
    }

    async function submitCreateUser() {
      const resEl = document.getElementById('cu-result');
      const data  = {
        full_name: document.getElementById('cu-fullname').value.trim(),
        username:  document.getElementById('cu-username').value.trim(),
        email:     document.getElementById('cu-email').value.trim(),
        password:  document.getElementById('cu-pass').value.trim(),
        role:      document.getElementById('cu-role').value,
      };

      if (!data.full_name || !data.username || !data.email || !data.password) {
        resEl.style.display = 'block';
        resEl.style.background = 'rgba(239,68,68,0.1)';
        resEl.style.color      = '#ef4444';
        resEl.style.border     = '1px solid rgba(239,68,68,0.3)';
        resEl.textContent      = 'All fields are required.';
        return;
      }

      const pwdErr = checkPasswordComplexity(data.password);
      if (pwdErr) {
        resEl.style.display = 'block';
        resEl.style.background = 'rgba(239,68,68,0.1)';
        resEl.style.color      = '#ef4444';
        resEl.style.border     = '1px solid rgba(239,68,68,0.3)';
        resEl.textContent      = pwdErr;
        return;
      }

      try {
        const res = await saApi('superadmin.php?action=create_user', data);
        resEl.style.display = 'block';
        resEl.style.background = res.success ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
        resEl.style.color      = res.success ? '#10b981' : '#ef4444';
        resEl.style.border     = `1px solid ${res.success ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'}`;
        resEl.textContent      = res.message;
        if (res.success) { loadUsers(); loadDashboard(); }
      } catch (e) {
        resEl.style.display = 'block';
        resEl.textContent   = 'Connection error.';
      }
    }

    function openEditUserModal(id, fullName, email, role) {
      document.getElementById('eu-id').value       = id;
      document.getElementById('eu-fullname').value = fullName;
      document.getElementById('eu-email').value    = email;
      document.getElementById('eu-role').value     = role;
      document.getElementById('eu-new-pass').value = '';
      document.getElementById('eu-result').style.display = 'none';
      openModal('edit-user-modal');
    }

    async function submitEditUser() {
      const userId  = document.getElementById('eu-id').value;
      const newPass = document.getElementById('eu-new-pass').value.trim();
      const resEl   = document.getElementById('eu-result');

      if (newPass) {
        const pwdErr = checkPasswordComplexity(newPass);
        if (pwdErr) {
          resEl.style.display = 'block';
          resEl.style.background = 'rgba(239,68,68,0.1)';
          resEl.style.color      = '#ef4444';
          resEl.style.border     = '1px solid rgba(239,68,68,0.3)';
          resEl.textContent      = pwdErr;
          return;
        }
      }

      try {
        const res = await saApi('superadmin.php?action=update_user', {
          user_id:   parseInt(userId),
          full_name: document.getElementById('eu-fullname').value.trim(),
          email:     document.getElementById('eu-email').value.trim(),
          role:      document.getElementById('eu-role').value,
        });

        if (res.success && newPass) {
          await saApi('superadmin.php?action=reset_password', { user_id: parseInt(userId), new_password: newPass });
        }

        resEl.style.display = 'block';
        resEl.style.background = res.success ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
        resEl.style.color      = res.success ? '#10b981' : '#ef4444';
        resEl.style.border     = `1px solid ${res.success ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'}`;
        resEl.textContent      = res.message;
        if (res.success) { saToast(res.message); loadUsers(); }
      } catch (e) {
        resEl.style.display = 'block';
        resEl.textContent   = 'Connection error.';
      }
    }

    async function toggleUser(id, currentStatus) {
      const actionTxt = currentStatus == 1 ? 'Deactivate' : 'Activate';
      openConfirmModal(
        `${actionTxt} User`,
        `Are you sure you want to ${actionTxt.toLowerCase()} this account?`,
        async () => {
          const res = await saApi('superadmin.php?action=toggle_user', { user_id: id });
          saToast(res.message, res.success ? 'success' : 'error');
          if (res.success) loadUsers();
        },
        currentStatus == 1 ? 'var(--sa-danger)' : 'var(--sa-primary)'
      );
    }

    async function deleteUser(id, name) {
      openConfirmModal(
        'Delete User',
        `Are you sure you want to permanently delete user "<b>${name}</b>"?<br><br><span style="color:var(--sa-danger);font-size:12px;">⚠ This action cannot be undone.</span>`,
        async () => {
          const res = await saApi('superadmin.php?action=delete_user', { user_id: id });
          saToast(res.message, res.success ? 'success' : 'error');
          if (res.success) { loadUsers(); loadAdminRequests(); loadDashboard(); }
        },
        'var(--sa-danger)'
      );
    }

    async function deleteAdminRequest(id, name) {
      openConfirmModal(
        'Delete Admin Request',
        `Are you sure you want to remove the admin approval request for "<b>${name}</b>"?<br><br><span style="color:var(--sa-danger);font-size:12px;">⚠ This record will be permanently deleted from admin approvals.</span>`,
        async () => {
          const res = await saApi('superadmin.php?action=delete_admin_request', { id: id });
          saToast(res.message, res.success ? 'success' : 'error');
          if (res.success) { loadAdminRequests(); loadDashboard(); }
        },
        'var(--sa-danger)'
      );
    }

    function openConfirmModal(title, msg, onConfirm, btnColor = 'var(--sa-primary)') {
      document.getElementById('sa-confirm-title').innerHTML = title;
      document.getElementById('sa-confirm-msg').innerHTML = msg;
      
      const btn = document.getElementById('sa-confirm-btn');
      btn.style.background = btnColor;
      btn.style.borderColor = btnColor;
      
      btn.onclick = () => {
        closeModal('sa-confirm-modal');
        onConfirm();
      };
      
      openModal('sa-confirm-modal');
    }

    /* =============================================
       MENU MANAGEMENT (Super Admin)
    ============================================= */
    let saMenuItems = [];
    let saMenuCategories = [];
    let saSelectedCategory = 'All';
    let saPendingDeleteIds = new Set();

    async function saLoadMenu() {
      try {
        const res = await saApi('menu.php');
        if (!res.success) return;
        saMenuItems = res.items || [];
        saMenuCategories = res.categories || [];
        saPendingDeleteIds = new Set((res.pending_deletions || []).map(Number));
        saRenderCategoryPills();
        saRenderMenuTable();
      } catch (err) {
        console.error(err);
      }
    }

    function saRenderCategoryPills() {
      const pillBox = document.getElementById('sa-menu-cat-pills');
      if (!pillBox) return;
      const cats = ['All', ...saMenuCategories.map(c => c.name)];
      pillBox.innerHTML = cats.map(cat => `
        <button type="button" class="sa-cat-pill ${saSelectedCategory === cat ? 'active' : ''}" onclick="saFilterCat('${cat}', this)">
          ${cat}
        </button>
      `).join('');
    }

    function saFilterCat(cat, btn) {
      saSelectedCategory = cat;
      document.querySelectorAll('#sa-menu-cat-pills .sa-cat-pill').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      saRenderMenuTable();
    }

    function saRenderMenuTable() {
      const tbody = document.getElementById('sa-menu-tbody');
      if (!tbody) return;
      const q = (document.getElementById('sa-menu-search')?.value || '').toLowerCase().trim();

      let filtered = saMenuItems.filter(item => {
        const matchCat = (saSelectedCategory === 'All' || item.category === saSelectedCategory);
        const matchQ = !q || item.name.toLowerCase().includes(q) || (item.category || '').toLowerCase().includes(q);
        return matchCat && matchQ;
      });

      if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="sa-empty"><i class="bi bi-cup-hot"></i><p>No menu items found</p></div></td></tr>`;
        return;
      }

      tbody.innerHTML = filtered.map((item, index) => {
        const isAvail = !!item.is_available;
        const isLow = item.stock <= item.min_stock && item.stock > 0;
        const isOut = item.stock <= 0;
        const isPendingDel = saPendingDeleteIds.has(item.id);
        const stockBadge = isOut 
          ? `<span class="badge-sa badge-voided">Out of Stock</span>`
          : isLow 
            ? `<span class="badge-sa badge-pending">Low: ${item.stock}</span>`
            : `<span style="font-weight:600;color:var(--sa-text);">${item.stock}</span>`;

        return `
          <tr>
            <td style="color:var(--sa-muted);font-size:12px;">${index + 1}</td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                ${item.image 
                  ? `<img src="${item.image}" style="width:34px;height:34px;border-radius:8px;object-fit:cover;border:1px solid var(--sa-border);">`
                  : `<div style="width:34px;height:34px;border-radius:8px;background:rgba(109,40,217,0.15);display:flex;align-items:center;justify-content:center;font-size:16px;">☕</div>`}
                <div>
                  <span style="font-weight:600;color:var(--sa-text);">${item.name}</span>
                  ${isPendingDel ? `<div style="font-size:10px;color:#f59e0b;font-weight:700;"><i class="bi bi-hourglass-split me-1"></i>Pending Removal Req.</div>` : ''}
                </div>
              </div>
            </td>
            <td><span style="font-size:12px;color:var(--sa-muted);">${item.category || '—'}</span></td>
            <td style="font-weight:700;color:var(--sa-gold);">₱${parseFloat(item.price).toFixed(2)}</td>
            <td>
              <div style="display:flex;align-items:center;gap:6px;">
                <button class="sa-btn sa-btn-ghost" style="padding:2px 7px;font-size:11px;" onclick="saAdjustStock(${item.id}, -1)">−</button>
                ${stockBadge}
                <button class="sa-btn sa-btn-ghost" style="padding:2px 7px;font-size:11px;" onclick="saAdjustStock(${item.id}, 1)">+</button>
              </div>
            </td>
            <td>
              <span class="badge-sa ${isAvail ? 'badge-active' : 'badge-inactive'}">${isAvail ? 'Available' : 'Unavailable'}</span>
            </td>
            <td>
              <div style="display:flex;gap:6px;align-items:center;">
                <button class="sa-btn sa-btn-ghost" style="padding:5px 9px;font-size:11.5px;" onclick="saToggleMenuAvail(${item.id})">
                  ${isAvail ? '<i class="bi bi-eye-slash"></i> Disable' : '<i class="bi bi-eye"></i> Enable'}
                </button>
                <button class="sa-btn sa-btn-danger" style="padding:5px 9px;font-size:11.5px;" onclick="saDeleteMenuItem(${item.id}, '${item.name.replace(/'/g, "\\'")}')" title="Delete permanently">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    async function saToggleMenuAvail(id) {
      try {
        const res = await saApi('menu.php?action=toggle', { id });
        if (res.success) {
          saToast(res.message, 'success');
          saLoadMenu();
        } else {
          saToast(res.message, 'error');
        }
      } catch (e) {
        saToast('Failed to toggle availability', 'error');
      }
    }

    async function saAdjustStock(id, delta) {
      try {
        const res = await saApi('menu.php?action=adjust_stock', { id, delta });
        if (res.success) {
          const item = saMenuItems.find(m => m.id == id);
          if (item) { item.stock = res.stock; item.is_available = res.is_available; }
          saRenderMenuTable();
        } else {
          saToast(res.message, 'error');
        }
      } catch (e) {
        saToast('Failed to adjust stock', 'error');
      }
    }

    async function saDeleteMenuItem(id, name) {
      if (!confirm(`Are you sure you want to permanently delete "${name}" from the menu?\n\nThis will take effect immediately without requiring codes.`)) return;
      try {
        const res = await saApi('menu.php?action=delete', { id });
        if (res.success) {
          saToast(res.message, 'success');
          saLoadMenu();
        } else {
          saToast(res.message, 'error');
        }
      } catch (e) {
        saToast('Failed to delete item', 'error');
      }
    }

    function saOpenAddMenuModal() {
      document.getElementById('sa-new-menu-name').value = '';
      document.getElementById('sa-new-menu-price').value = '';
      document.getElementById('sa-new-menu-stock').value = '20';
      document.getElementById('sa-new-menu-min-stock').value = '5';
      document.getElementById('sa-new-menu-image').value = '';
      openModal('sa-add-item-modal');
    }

    async function saSubmitAddMenu(e) {
      e.preventDefault();
      const name = document.getElementById('sa-new-menu-name').value.trim();
      const categoryId = parseInt(document.getElementById('sa-new-menu-category').value) || 1;
      const price = parseFloat(document.getElementById('sa-new-menu-price').value) || 0;
      const stock = parseInt(document.getElementById('sa-new-menu-stock').value) || 0;
      const minStock = parseInt(document.getElementById('sa-new-menu-min-stock').value) || 5;
      const imageInput = document.getElementById('sa-new-menu-image');

      if (!name || price <= 0) {
        saToast('Please enter a valid item name and price.', 'error');
        return;
      }

      let imageSrc = null;
      if (imageInput.files && imageInput.files[0]) {
        imageSrc = await new Promise(resolve => {
          const reader = new FileReader();
          reader.onload = ev => resolve(ev.target.result);
          reader.readAsDataURL(imageInput.files[0]);
        });
      }

      const btn = document.getElementById('sa-add-item-submit-btn');
      btn.disabled = true;

      try {
        const res = await saApi('menu.php?action=add', {
          name, category_id: categoryId, price, stock, min_stock: minStock, image: imageSrc
        });
        if (res.success) {
          closeModal('sa-add-item-modal');
          saToast(res.message, 'success');
          saLoadMenu();
        } else {
          saToast(res.message, 'error');
        }
      } catch (err) {
        saToast('Failed to add menu item', 'error');
      } finally {
        btn.disabled = false;
      }
    }

    /* =============================================
       ORDERS
    ============================================= */
    /* ── SA Order picker state ── */
    let _saOrderPickerMode = ''; // '' | 'date' | 'week' | 'month' | 'year'
    let _saOrderPickerVal  = '';

    function initSAOrderPickerControls() {
      const now = new Date();
      const todayStr = formatLocalDate(now);
      const dateInput = document.getElementById('sa-order-input-date');
      if (dateInput && !dateInput.value) dateInput.value = todayStr;
      const weekInput = document.getElementById('sa-order-input-week');
      if (weekInput && !weekInput.value) weekInput.value = getISOWeekString(now);
      const monthInput = document.getElementById('sa-order-input-month');
      if (monthInput && !monthInput.value) {
        monthInput.value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
      }
      const yearSelect = document.getElementById('sa-order-input-year');
      if (yearSelect && yearSelect.options.length === 0) {
        const curYear = now.getFullYear();
        for (let yr = curYear + 1; yr >= 2022; yr--) {
          const opt = new Option(yr, yr);
          if (yr === curYear) opt.selected = true;
          yearSelect.add(opt);
        }
      }
    }

    function setSAOrderFilter(val, btn) {
      // Clear picker mode when a quick tab is clicked
      _saOrderPickerMode = '';
      _saOrderPickerVal  = '';
      const box = document.getElementById('sa-order-picker-box');
      if (box) box.style.display = 'none';
      // Deactivate picker buttons
      ['pick-date','pick-week','pick-month','pick-year'].forEach(id => {
        const el = document.getElementById('sa-ofilter-' + id);
        if (el) el.classList.remove('active');
      });
      document.querySelectorAll('#sa-order-filter-tabs .sa-cat-pill').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      // Update summary
      const summaryLabels = { today: 'Showing today\'s orders', week: 'Showing this week\'s orders', month: 'Showing this month\'s orders', year: 'Showing this year\'s orders', all: 'Showing all orders (All Time)' };
      const sumEl = document.getElementById('sa-order-period-summary');
      if (sumEl) sumEl.textContent = summaryLabels[val] || 'Showing orders';
      loadOrders(val);
    }

    function setSAOrderPickerMode(mode) {
      _saOrderPickerMode = mode;
      initSAOrderPickerControls();
      // Deactivate quick tabs
      document.querySelectorAll('#sa-order-filter-tabs .sa-cat-pill').forEach(b => b.classList.remove('active'));
      const activeBtn = document.getElementById('sa-ofilter-pick-' + mode);
      if (activeBtn) activeBtn.classList.add('active');
      const box = document.getElementById('sa-order-picker-box');
      if (box) box.style.display = 'block';
      ['date','week','month','year'].forEach(m => {
        const el = document.getElementById('sa-order-picker-' + m);
        if (el) el.style.display = m === mode ? 'block' : 'none';
      });
      applySAOrderPickerFilter();
    }

    function applySAOrderPickerFilter() {
      initSAOrderPickerControls();
      if (_saOrderPickerMode === 'date') {
        _saOrderPickerVal = document.getElementById('sa-order-input-date')?.value || formatLocalDate(new Date());
        const parts = (_saOrderPickerVal || '').split('-');
        let txt = _saOrderPickerVal;
        if (parts.length === 3) {
          const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
          txt = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        }
        const lbl = document.getElementById('sa-order-date-label');
        if (lbl) lbl.textContent = txt;
        const sumEl = document.getElementById('sa-order-period-summary');
        if (sumEl) sumEl.textContent = `📅 Filtered by Date: ${txt}`;
      } else if (_saOrderPickerMode === 'week') {
        _saOrderPickerVal = document.getElementById('sa-order-input-week')?.value || getISOWeekString(new Date());
        const txt = getISOWeekRange(_saOrderPickerVal) || _saOrderPickerVal;
        const lbl = document.getElementById('sa-order-week-label');
        if (lbl) lbl.textContent = txt;
        const sumEl = document.getElementById('sa-order-period-summary');
        if (sumEl) sumEl.textContent = `📆 Filtered by: ${txt}`;
      } else if (_saOrderPickerMode === 'month') {
        _saOrderPickerVal = document.getElementById('sa-order-input-month')?.value || `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}`;
        const parts = (_saOrderPickerVal || '').split('-');
        let txt = _saOrderPickerVal;
        if (parts.length === 2) {
          const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, 1);
          txt = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }
        const lbl = document.getElementById('sa-order-month-label');
        if (lbl) lbl.textContent = txt;
        const sumEl = document.getElementById('sa-order-period-summary');
        if (sumEl) sumEl.textContent = `🗓️ Filtered by Month: ${txt}`;
      } else if (_saOrderPickerMode === 'year') {
        _saOrderPickerVal = document.getElementById('sa-order-input-year')?.value || String(new Date().getFullYear());
        const lbl = document.getElementById('sa-order-year-label');
        if (lbl) lbl.textContent = `Year ${_saOrderPickerVal}`;
        const sumEl = document.getElementById('sa-order-period-summary');
        if (sumEl) sumEl.textContent = `📅 Filtered by Year: ${_saOrderPickerVal}`;
      }
      loadOrders();
    }

    /* Shift helpers */
    function shiftSAOrderDate(delta) {
      const input = document.getElementById('sa-order-input-date');
      const val = input.value || formatLocalDate(new Date());
      const parts = val.split('-');
      const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
      d.setDate(d.getDate() + delta);
      input.value = formatLocalDate(d);
      applySAOrderPickerFilter();
    }
    function setSAOrderPickerToday() {
      document.getElementById('sa-order-input-date').value = formatLocalDate(new Date());
      applySAOrderPickerFilter();
    }
    function shiftSAOrderWeek(delta) {
      const input = document.getElementById('sa-order-input-week');
      const val = input.value || getISOWeekString(new Date());
      const parts = val.split('-W');
      let y = parseInt(parts[0], 10), w = parseInt(parts[1], 10) + delta;
      if (w < 1) { y--; w = 52; } else if (w > 52) { y++; w = 1; }
      input.value = `${y}-W${String(w).padStart(2, '0')}`;
      applySAOrderPickerFilter();
    }
    function setSAOrderThisWeek() {
      document.getElementById('sa-order-input-week').value = getISOWeekString(new Date());
      applySAOrderPickerFilter();
    }
    function shiftSAOrderMonth(delta) {
      const input = document.getElementById('sa-order-input-month');
      const val = input.value || `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}`;
      const parts = val.split('-');
      let y = parseInt(parts[0], 10), m = parseInt(parts[1], 10) - 1 + delta;
      const d = new Date(y, m, 1);
      input.value = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}`;
      applySAOrderPickerFilter();
    }
    function setSAOrderThisMonth() {
      const now = new Date();
      document.getElementById('sa-order-input-month').value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
      applySAOrderPickerFilter();
    }
    function shiftSAOrderYear(delta) {
      const select = document.getElementById('sa-order-input-year');
      const cur = parseInt(select.value || new Date().getFullYear(), 10);
      const target = cur + delta;
      let found = false;
      for (let opt of select.options) {
        if (parseInt(opt.value, 10) === target) { select.value = target; found = true; break; }
      }
      if (!found) { const opt = new Option(target, target); select.add(opt); select.value = target; }
      applySAOrderPickerFilter();
    }
    function setSAOrderThisYear() {
      document.getElementById('sa-order-input-year').value = new Date().getFullYear();
      applySAOrderPickerFilter();
    }

    async function loadOrders(quickFilter) {
      const status = document.getElementById('order-status-filter')?.value || '';
      let url;
      if (_saOrderPickerMode && _saOrderPickerVal) {
        url = `superadmin.php?action=orders&date_mode=${_saOrderPickerMode}&date_val=${encodeURIComponent(_saOrderPickerVal)}&status=${status}&limit=200`;
      } else {
        const date = quickFilter || (document.querySelector('#sa-order-filter-tabs .sa-cat-pill.active')?.id?.replace('sa-ofilter-','') || 'today');
        url = `superadmin.php?action=orders&date=${date}&status=${status}&limit=200`;
      }

      try {
        const res = await saApi(url);
        if (!res.success) return;

        const tbody = document.getElementById('sa-orders-tbody');
        if (!res.orders.length) {
          tbody.innerHTML = `<tr><td colspan="7"><div class="sa-empty"><i class="bi bi-receipt"></i><p>No orders found</p></div></td></tr>`;
          return;
        }

        tbody.innerHTML = res.orders.map(o => {
          const statusBadge = o.status === 'Completed' ? 'badge-completed'
            : o.status === 'Voided'    ? 'badge-voided'
            : 'badge-pending';
          const items = Array.isArray(o.items) ? o.items.map(i => `${i.quantity}x ${i.name}`).join(', ') : (o.items || '—');
          const dt = new Date(o.created_at).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
          return `<tr>
            <td style="font-weight:700;color:#a855f7;">${o.order_number}</td>
            <td style="font-size:12px;">${o.cashier_name || '—'}</td>
            <td style="font-weight:700;color:var(--sa-green);">₱${parseFloat(o.total).toFixed(2)}</td>
            <td style="font-size:12px;">${o.payment_method}</td>
            <td><span class="badge-sa ${statusBadge}">${o.status}</span></td>
            <td style="font-size:11.5px;color:var(--sa-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${items}">${items || '—'}</td>
            <td style="font-size:12px;color:var(--sa-muted);">${dt}</td>
          </tr>`;
        }).join('');
      } catch (e) { console.error(e); }
    }

    /* =============================================
       ANALYTICS
    ============================================= */
    window.saAnCharts = {};
    const SA_CHART_COLORS = ['#a855f7','#3b82f6','#10b981','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#ec4899','#14b8a6'];

    function saUpsertChart(id, config) {
      const ctx = document.getElementById(id);
      if (!ctx) return;
      if (window.saAnCharts[id]) { window.saAnCharts[id].destroy(); }
      window.saAnCharts[id] = new Chart(ctx.getContext('2d'), config);
    }

    async function loadAnalytics() {
      try {
        const res = await saApi('superadmin.php?action=dashboard');
        if (!res.success) return;

        // ── KPIs ──
        const fmt = v => '₱' + parseFloat(v).toLocaleString('en-PH', {minimumFractionDigits:0});
        document.getElementById('an-month-rev').textContent    = fmt(res.month_revenue || 0);
        document.getElementById('an-month-orders').textContent = res.month_orders || 0;
        document.getElementById('an-avg-daily').textContent    = fmt(res.avg_daily || 0);
        if (res.top_seller) {
          document.getElementById('an-top-seller').textContent     = res.top_seller.name;
          document.getElementById('an-top-seller-sub').textContent = `${res.top_seller.sold} sold (all time)`;
        } else {
          document.getElementById('an-top-seller').textContent     = '—';
          document.getElementById('an-top-seller-sub').textContent = 'No sales yet';
        }

        const baseOpts = {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#94a3b8', font:{size:10} } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8', font:{size:10} } }
          }
        };

        // ── Weekly ──
        saUpsertChart('sa-weekly-chart', {
          type: 'line',
          data: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            datasets: [{ label:'Revenue', data: res.weekly_chart, borderColor:'#a855f7', backgroundColor:'rgba(168,85,247,0.15)', borderWidth:3, fill:true, tension:0.4, pointRadius:4, pointBackgroundColor:'#fff', pointBorderColor:'#a855f7', pointBorderWidth:2 }] },
          options: { ...baseOpts }
        });

        // ── Hourly ──
        const hourLabels = ['9am','10am','11am','12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm','11pm','12am','1am','2am'];
        saUpsertChart('sa-hourly-chart', {
          type: 'bar',
          data: { labels: hourLabels,
            datasets: [{ label:'Revenue', data: res.hourly_chart, backgroundColor:'rgba(59,130,246,0.7)', borderRadius:4 }] },
          options: { ...baseOpts }
        });

        // ── Monthly ──
        saUpsertChart('sa-monthly-chart', {
          type: 'bar',
          data: { labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{ label:'Revenue', data: res.monthly_chart, backgroundColor:'rgba(16,185,129,0.7)', borderRadius:4 }] },
          options: { ...baseOpts }
        });

        // ── Item Performance ──
        if (res.item_performance && res.item_performance.length) {
          saUpsertChart('sa-item-chart', {
            type: 'bar',
            data: {
              labels: res.item_performance.map(i => i.name),
              datasets: [{ label:'Units Sold', data: res.item_performance.map(i => parseInt(i.sold)),
                backgroundColor: SA_CHART_COLORS.slice(0, res.item_performance.length), borderRadius:4 }]
            },
            options: { indexAxis: 'y', ...baseOpts }
          });
        }

        // ── Category Breakdown ──
        const catEl = document.getElementById('sa-cat-breakdown');
        if (catEl) {
          if (res.cat_breakdown && res.cat_breakdown.length) {
            const total = res.cat_breakdown.reduce((s, c) => s + parseInt(c.qty), 0);
            catEl.innerHTML = res.cat_breakdown.map((c, i) => {
              const pct = total > 0 ? Math.round(parseInt(c.qty) / total * 100) : 0;
              const color = SA_CHART_COLORS[i % SA_CHART_COLORS.length];
              return `<div style="display:flex;align-items:center;gap:10px;background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:10px 16px;flex:1;min-width:160px;">
                <span style="width:12px;height:12px;border-radius:50%;background:${color};flex-shrink:0;"></span>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:12px;font-weight:700;color:var(--sa-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${c.name}</div>
                  <div style="font-size:11px;color:var(--sa-muted);">${c.qty} units · ${pct}%</div>
                </div>
              </div>`;
            }).join('');
          } else {
            catEl.innerHTML = '<div style="color:var(--sa-muted);font-size:13px;padding:20px;">No category data yet.</div>';
          }
        }

      } catch (e) { console.error('Analytics load failed:', e); }
    }

    /* =============================================
       AUDIT LOGS
    ============================================= */
    /* ── Audit log state ── */
    let _allLogs        = [];
    let _activeLogCat   = 'all';
    let _logPeriodMode  = 'all'; // 'all' | 'date' | 'week' | 'month' | 'year'
    let _logPeriodVal   = '';

    /* Category meta — maps action key → display label + color */
    const LOG_CATEGORIES = {
      all:                        { label: 'All',                 color: '#64748b', icon: 'bi-list-ul' },
      approve_admin_registration: { label: 'Approved',            color: '#10b981', icon: 'bi-check-circle-fill' },
      reject_admin_registration:  { label: 'Rejected',            color: '#ef4444', icon: 'bi-x-circle-fill' },
      delete_admin_registration_request: { label: 'Deleted Req',  color: '#dc2626', icon: 'bi-trash-fill' },
      approve_system_request:     { label: 'Req Approved',        color: '#059669', icon: 'bi-check2-all' },
      reject_system_request:      { label: 'Req Rejected',        color: '#dc2626', icon: 'bi-slash-circle-fill' },
      save_system_settings:       { label: 'Save Settings',       color: '#a855f7', icon: 'bi-gear-fill' },
      create_user:                { label: 'Create User',         color: '#3b82f6', icon: 'bi-person-plus-fill' },
      delete_user:                { label: 'Delete User',         color: '#ef4444', icon: 'bi-trash-fill' },
      reset_password:             { label: 'Reset Password',      color: '#f59e0b', icon: 'bi-key-fill' },
      toggle_user:                { label: 'Toggle User',         color: '#0891b2', icon: 'bi-person-dash-fill' },
      submit_request:             { label: 'Submit Request',      color: '#6366f1', icon: 'bi-send-fill' },
      superadmin_login:           { label: 'Super Admin Login',   color: '#10b981', icon: 'bi-shield-check' },
      admin_login:                { label: 'Admin Login (2FA)',   color: '#059669', icon: 'bi-shield-lock-fill' },
    };

    function getISOWeekString(date) {
      const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
      const dayNum = d.getUTCDay() || 7;
      d.setUTCDate(d.getUTCDate() + 4 - dayNum);
      const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
      const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
      const weekStr = String(weekNo).padStart(2, '0');
      return `${d.getUTCFullYear()}-W${weekStr}`;
    }

    function getISOWeekRange(weekStr) {
      if (!weekStr) return '';
      const parts = weekStr.split('-W');
      if (parts.length !== 2) return '';
      const year = parseInt(parts[0], 10);
      const week = parseInt(parts[1], 10);
      const simple = new Date(year, 0, 4);
      const dayOfWeek = simple.getDay() || 7;
      const monday = new Date(simple);
      monday.setDate(simple.getDate() - dayOfWeek + 1 + (week - 1) * 7);
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);
      const opt = { month: 'short', day: 'numeric' };
      return `Week ${week} (${monday.toLocaleDateString('en-US', opt)} – ${sunday.toLocaleDateString('en-US', opt)}, ${year})`;
    }

    function formatLocalDate(d) {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    }

    function initLogCalendarControls() {
      const now = new Date();
      const todayStr = formatLocalDate(now);
      const dateInput = document.getElementById('log-input-date');
      if (dateInput && !dateInput.value) dateInput.value = todayStr;

      const weekInput = document.getElementById('log-input-week');
      if (weekInput && !weekInput.value) weekInput.value = getISOWeekString(now);

      const monthInput = document.getElementById('log-input-month');
      if (monthInput && !monthInput.value) {
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        monthInput.value = `${y}-${m}`;
      }

      const yearSelect = document.getElementById('log-input-year');
      if (yearSelect && yearSelect.options.length === 0) {
        const curYear = now.getFullYear();
        for (let yr = curYear + 1; yr >= 2022; yr--) {
          const opt = new Option(yr, yr);
          if (yr === curYear) opt.selected = true;
          yearSelect.add(opt);
        }
      }
    }

    function setLogPeriodMode(mode) {
      _logPeriodMode = mode;
      initLogCalendarControls();

      // Update pill active classes
      document.querySelectorAll('#log-period-tabs .sa-cat-pill').forEach(b => b.classList.remove('active'));
      const activeTab = document.getElementById(`log-tab-${mode}`);
      if (activeTab) activeTab.classList.add('active');

      const box = document.getElementById('log-picker-box');
      if (mode === 'all') {
        if (box) box.style.display = 'none';
        _logPeriodVal = '';
        updateLogPeriodSummary();
        loadLogs();
        return;
      }

      if (box) box.style.display = 'block';

      // Show/hide picker sections
      ['date', 'week', 'month', 'year'].forEach(m => {
        const el = document.getElementById(`log-picker-${m}`);
        if (el) el.style.display = m === mode ? 'block' : 'none';
      });

      applyLogDateFilter();
    }

    function applyLogDateFilter() {
      initLogCalendarControls();
      if (_logPeriodMode === 'date') {
        _logPeriodVal = document.getElementById('log-input-date')?.value || formatLocalDate(new Date());
      } else if (_logPeriodMode === 'week') {
        _logPeriodVal = document.getElementById('log-input-week')?.value || getISOWeekString(new Date());
      } else if (_logPeriodMode === 'month') {
        _logPeriodVal = document.getElementById('log-input-month')?.value || `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}`;
      } else if (_logPeriodMode === 'year') {
        _logPeriodVal = document.getElementById('log-input-year')?.value || String(new Date().getFullYear());
      } else {
        _logPeriodVal = '';
      }

      updateLogPeriodSummary();
      loadLogs();
    }

    function updateLogPeriodSummary() {
      const summaryEl = document.getElementById('log-period-summary');
      if (!summaryEl) return;

      if (_logPeriodMode === 'all') {
        summaryEl.textContent = 'Showing all logs (All Time)';
        return;
      }

      if (_logPeriodMode === 'date') {
        const parts = (_logPeriodVal || '').split('-');
        let txt = _logPeriodVal;
        if (parts.length === 3) {
          const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
          txt = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        }
        summaryEl.textContent = `📅 Filtered by Date: ${txt}`;
        const lbl = document.getElementById('log-date-display-label');
        if (lbl) lbl.textContent = txt;
      } else if (_logPeriodMode === 'week') {
        const txt = getISOWeekRange(_logPeriodVal) || _logPeriodVal;
        summaryEl.textContent = `📆 Filtered by: ${txt}`;
        const lbl = document.getElementById('log-week-display-label');
        if (lbl) lbl.textContent = txt;
      } else if (_logPeriodMode === 'month') {
        const parts = (_logPeriodVal || '').split('-');
        let txt = _logPeriodVal;
        if (parts.length === 2) {
          const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, 1);
          txt = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }
        summaryEl.textContent = `🗓️ Filtered by Month: ${txt}`;
        const lbl = document.getElementById('log-month-display-label');
        if (lbl) lbl.textContent = txt;
      } else if (_logPeriodMode === 'year') {
        summaryEl.textContent = `📅 Filtered by Year: ${_logPeriodVal}`;
        const lbl = document.getElementById('log-year-display-label');
        if (lbl) lbl.textContent = `Year ${_logPeriodVal}`;
      }
    }

    function shiftLogDate(delta) {
      const input = document.getElementById('log-input-date');
      const val = input.value || formatLocalDate(new Date());
      const parts = val.split('-');
      const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
      d.setDate(d.getDate() + delta);
      input.value = formatLocalDate(d);
      applyLogDateFilter();
    }

    function setLogToday() {
      const input = document.getElementById('log-input-date');
      input.value = formatLocalDate(new Date());
      applyLogDateFilter();
    }

    function shiftLogWeek(delta) {
      const input = document.getElementById('log-input-week');
      const val = input.value || getISOWeekString(new Date());
      const parts = val.split('-W');
      let y = parseInt(parts[0], 10);
      let w = parseInt(parts[1], 10) + delta;
      if (w < 1) {
        y--;
        w = 52;
      } else if (w > 52) {
        y++;
        w = 1;
      }
      input.value = `${y}-W${String(w).padStart(2, '0')}`;
      applyLogDateFilter();
    }

    function setLogThisWeek() {
      const input = document.getElementById('log-input-week');
      input.value = getISOWeekString(new Date());
      applyLogDateFilter();
    }

    function shiftLogMonth(delta) {
      const input = document.getElementById('log-input-month');
      const val = input.value || `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}`;
      const parts = val.split('-');
      let y = parseInt(parts[0], 10);
      let m = parseInt(parts[1], 10) - 1 + delta;
      const d = new Date(y, m, 1);
      input.value = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}`;
      applyLogDateFilter();
    }

    function setLogThisMonth() {
      const input = document.getElementById('log-input-month');
      const now = new Date();
      input.value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2, '0')}`;
      applyLogDateFilter();
    }

    function shiftLogYear(delta) {
      const select = document.getElementById('log-input-year');
      const cur = parseInt(select.value || new Date().getFullYear(), 10);
      const target = cur + delta;
      let found = false;
      for (let opt of select.options) {
        if (parseInt(opt.value, 10) === target) {
          select.value = target;
          found = true;
          break;
        }
      }
      if (!found) {
        const opt = new Option(target, target);
        select.add(opt);
        select.value = target;
      }
      applyLogDateFilter();
    }

    function setLogThisYear() {
      const select = document.getElementById('log-input-year');
      const nowY = new Date().getFullYear();
      select.value = nowY;
      applyLogDateFilter();
    }

    async function loadLogs() {
      try {
        initLogCalendarControls();
        let url = `superadmin.php?action=logs&limit=500&date_mode=${_logPeriodMode}`;
        if (_logPeriodMode !== 'all' && _logPeriodVal) {
          url += `&date_val=${encodeURIComponent(_logPeriodVal)}`;
        }
        const res = await saApi(url);
        if (!res.success) return;

        _allLogs = res.logs || [];
        _activeLogCat = 'all';
        document.getElementById('log-search').value = '';
        renderLogBreakdown();
        renderLogList();
      } catch (e) { console.error(e); }
    }

    function renderLogBreakdown() {
      /* Count per action */
      const counts = {};
      _allLogs.forEach(l => {
        counts[l.action] = (counts[l.action] || 0) + 1;
      });

      const breakdown = document.getElementById('log-breakdown');
      if (!_allLogs.length) {
        breakdown.innerHTML = '<span style="color:var(--sa-muted);font-size:12px;">No data yet</span>';
        return;
      }

      /* Build pills: All first, then known categories, then any unknown actions */
      const knownKeys = Object.keys(LOG_CATEGORIES).filter(k => k !== 'all');
      const unknownKeys = Object.keys(counts).filter(k => !LOG_CATEGORIES[k]);

      const allMeta = LOG_CATEGORIES['all'];
      let pills = `<span class="log-cat-pill ${_activeLogCat === 'all' ? 'active' : ''}"
        style="background:${allMeta.color}18;color:${allMeta.color};"
        onclick="setLogCat('all')">
        <i class="bi ${allMeta.icon}"></i> ${allMeta.label}
        <span class="pill-count">${_allLogs.length}</span>
      </span>`;

      [...knownKeys, ...unknownKeys].forEach(key => {
        if (!counts[key]) return; /* skip if no entries for this category */
        const meta = LOG_CATEGORIES[key] || { label: key.replace(/_/g,' '), color: '#64748b', icon: 'bi-activity' };
        const isActive = _activeLogCat === key;
        pills += `<span class="log-cat-pill ${isActive ? 'active' : ''}"
          style="background:${meta.color}18;color:${meta.color};"
          onclick="setLogCat('${key}')">
          <i class="bi ${meta.icon}"></i> ${meta.label}
          <span class="pill-count">${counts[key]}</span>
        </span>`;
      });

      breakdown.innerHTML = pills;
    }

    function setLogCat(cat) {
      _activeLogCat = cat;
      document.getElementById('log-search').value = '';
      renderLogBreakdown();
      renderLogList();
    }

    function filterLogs() {
      renderLogList();
    }

    function renderLogList() {
      const el      = document.getElementById('logs-container');
      const query   = (document.getElementById('log-search')?.value || '').toLowerCase();
      const catKey  = _activeLogCat;

      let filtered = _allLogs.filter(l => {
        const catMatch = catKey === 'all' || l.action === catKey;
        if (!catMatch) return false;
        if (!query) return true;
        return (
          (l.action         || '').toLowerCase().includes(query) ||
          (l.actor_username || '').toLowerCase().includes(query) ||
          (l.details        || '').toLowerCase().includes(query) ||
          (l.target_type    || '').toLowerCase().includes(query)
        );
      });

      if (!filtered.length) {
        el.innerHTML = '<div class="sa-empty"><i class="bi bi-calendar-x"></i><p style="font-weight:600;margin-bottom:4px;">No logs match this filter</p><p style="font-size:12px;color:var(--sa-muted);">Try selecting a different date, week, month, year, or switch to All Time.</p></div>';
        return;
      }
      el.innerHTML = filtered.map(renderLogEntry).join('');
    }

    /* =============================================
       SETTINGS
    ============================================= */
    async function loadSettings() {
      try {
        const res = await saApi('superadmin.php?action=settings');
        if (!res.success) return;

        res.settings.forEach(s => {
          const el = document.getElementById(`setting-${s.setting_key}`);
          if (el) el.value = s.setting_value;
        });
      } catch (e) { console.error(e); }
    }

    async function saveSettings() {
      const keys   = ['store_name','owner_email','superadmin_email','token_expiry_minutes','void_refund_manager_email'];
      const settings = {};
      keys.forEach(k => {
        const el = document.getElementById(`setting-${k}`);
        if (el && el.value.trim()) settings[k] = el.value.trim();
      });

      const res = await saApi('superadmin.php?action=save_settings', { settings });
      saToast(res.message, res.success ? 'success' : 'error');
    }

    async function saveSAPaymongoKey() {
      const key = document.getElementById('sa-pm-secret-key-input').value.trim();
      const statusEl = document.getElementById('sa-pm-key-status');
      if (!key) {
        statusEl.style.display = 'block';
        statusEl.style.background = 'rgba(239,68,68,0.1)';
        statusEl.style.color = '#ef4444';
        statusEl.textContent = 'Please enter your PayMongo secret key.';
        return;
      }
      try {
        const res = await fetch('api/paymongo.php?action=save_key', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ secret_key: key })
        });
        const data = await res.json();
        statusEl.style.display = 'block';
        if (data.success) {
          statusEl.style.background = 'rgba(16,185,129,0.1)';
          statusEl.style.color = '#10b981';
          statusEl.textContent = '✓ API key saved successfully.';
          document.getElementById('sa-pm-secret-key-input').value = '';
          const badge = document.getElementById('sa-pm-key-badge');
          if (badge) { badge.style.background = '#d1fae5'; badge.style.color = '#059669'; badge.textContent = '● Configured'; }
        } else {
          statusEl.style.background = 'rgba(239,68,68,0.1)';
          statusEl.style.color = '#ef4444';
          statusEl.textContent = data.message || 'Failed to save key.';
        }
      } catch(e) {
        statusEl.style.display = 'block';
        statusEl.style.background = 'rgba(239,68,68,0.1)';
        statusEl.style.color = '#ef4444';
        statusEl.textContent = 'Network error. Please try again.';
      }
    }

    async function changeSAPassword() {
      const newPass     = document.getElementById('sa-new-pass').value.trim();
      const confirmPass = document.getElementById('sa-new-pass-confirm').value.trim();

      if (!newPass) { saToast('Enter a new password', 'error'); return; }
      if (newPass !== confirmPass) { saToast('Passwords do not match', 'error'); return; }
      const pwdErr = checkPasswordComplexity(newPass);
      if (pwdErr) { saToast(pwdErr, 'error'); return; }

      if (!confirm('Change your Super Admin password?')) return;

      const res = await saApi('superadmin.php?action=reset_password', {
        user_id: saCurrentUser.id,
        new_password: newPass,
      });
      saToast(res.message, res.success ? 'success' : 'error');
      if (res.success) {
        document.getElementById('sa-new-pass').value        = '';
        document.getElementById('sa-new-pass-confirm').value = '';
      }
    }

    /* =============================================
       MODAL HELPERS
    ============================================= */
    function openModal(id) {
      document.getElementById(id).classList.add('open');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('open');
    }

    // Close modal on overlay click
    document.querySelectorAll('.sa-modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
      });
    });

    // Escape key
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.sa-modal-overlay.open').forEach(m => m.classList.remove('open'));
      }
    });

    /* =============================================
       INIT — Check Existing Session
    ============================================= */
    window.addEventListener('DOMContentLoaded', () => {
      saApi('auth.php?action=session').then(res => {
        if (res.success && res.loggedIn && res.user && res.user.role === 'super_admin') {
          saCurrentUser = res.user;
          activateSAApp();
        } else if (res.pending_2fa) {
          // Resume pending 2FA step if page reloaded
          document.getElementById('sa-step-credentials').style.display = 'none';
          document.getElementById('sa-step-2fa').style.display = 'block';
          document.getElementById('sa-2fa-email-display').textContent = res.masked_email || 'your email';
          const codeInput = document.getElementById('sa-2fa-code');
          codeInput.value = '';
          if (res.dev_code) {
            document.getElementById('sa-dev-hint').style.display = 'block';
            document.getElementById('sa-dev-code-val').textContent = res.dev_code;
            codeInput.value = res.dev_code;
          }
        }
      }).catch(() => {});
    });
  </script>
</body>
</html>
