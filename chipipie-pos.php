<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ChipiPie Cafe — POS System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --cafe-brown: #3a2f2d;
      --cafe-amber: #838582;
      --cafe-cream: #d9d9d9;
      --cafe-tan: #b1ada6;
      --cafe-light: #d9d9d9;
      --cafe-sidebar: #3a2f2d;
      --cafe-gold: #838582;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: var(--cafe-cream);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      overflow: hidden;
      height: 100vh;
    }

    .app-wrapper {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* SIDEBAR */
    .sidebar {
      width: 230px;
      min-width: 230px;
      background: var(--cafe-sidebar);
      display: flex;
      flex-direction: column;
      padding: 0;
      overflow: hidden;
    }

    .sidebar-brand {
      padding: 20px 20px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-brand .brand-icon {
      width: 40px;
      height: 40px;
      background: var(--cafe-amber);
      color: #fff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      margin-bottom: 10px;
    }

    .sidebar-brand h5 {
      color: #fff;
      font-size: 17px;
      font-weight: 600;
      margin: 0;
    }

    .sidebar-brand small {
      color: rgba(255, 255, 255, 0.45);
      font-size: 11px;
    }

    .sidebar-nav {
      padding: 12px 10px;
      flex: 1;
      overflow-y: auto;
    }

    .nav-section-label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.08em;
      color: rgba(255, 255, 255, 0.3);
      text-transform: uppercase;
      padding: 6px 10px 4px;
      margin-top: 4px;
    }

    .nav-item-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 10px 12px;
      border: none;
      background: transparent;
      color: rgba(255, 255, 255, 0.65);
      border-radius: 8px;
      cursor: pointer;
      font-size: 13.5px;
      text-align: left;
      transition: background 0.15s, color 0.15s;
      margin-bottom: 2px;
    }

    .nav-item-btn i {
      font-size: 15px;
      width: 18px;
    }

    .nav-item-btn:hover {
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
    }

    .nav-item-btn.active {
      background: var(--cafe-amber);
      color: #fff;
      font-weight: 500;
    }

    .sidebar-footer {
      padding: 14px 16px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .cashier-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--cafe-amber);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 600;
      color: #fff;
    }

    .cashier-name {
      color: #fff;
      font-size: 13px;
      font-weight: 500;
    }

    .cashier-role {
      color: rgba(255, 255, 255, 0.4);
      font-size: 11px;
    }

    /* MAIN CONTENT */
    .main-content {
      flex: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    /* TOP BAR */
    .top-bar {
      background: #fff;
      border-bottom: 1px solid var(--cafe-tan);
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .top-bar h4 {
      color: var(--cafe-brown);
      font-size: 18px;
      font-weight: 600;
      margin: 0;
    }

    .top-bar-time {
      font-size: 13px;
      color: #888;
    }

    /* VIEWS */
    .view {
      display: none;
      padding: 24px;
      flex: 1;
    }

    .view.active {
      display: block;
    }

    /* KPI CARDS */
    .kpi-card {
      background: #fff;
      border-radius: 12px;
      border: 1px solid var(--cafe-tan);
      padding: 18px 20px;
    }

    .kpi-stat {
      font-size: 26px;
      font-weight: 700;
      color: var(--cafe-brown);
      line-height: 1.2;
    }

    .kpi-label {
      font-size: 12px;
      color: #888;
      margin-bottom: 4px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .kpi-trend {
      font-size: 12px;
      margin-top: 4px;
    }

    /* PRODUCT GRID */
    .product-card {
      background: #fff;
      border: 1.5px solid var(--cafe-tan);
      border-radius: 12px;
      padding: 14px 10px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.15s, transform 0.12s;
      user-select: none;
      position: relative;
    }

    .product-card:hover {
      border-color: var(--cafe-amber);
      transform: translateY(-2px);
    }

    .product-card:active {
      transform: scale(0.97);
    }

    .product-card.out-of-stock {
      opacity: 0.5;
      cursor: not-allowed;
      border-color: #dc3545;
    }

    .product-card.out-of-stock:hover {
      transform: none;
      border-color: #dc3545;
    }

    .product-card .prod-emoji {
      font-size: 26px;
      display: block;
      margin-bottom: 6px;
    }

    .product-card .prod-name {
      font-size: 11.5px;
      color: var(--cafe-brown);
      font-weight: 600;
      line-height: 1.3;
      margin-bottom: 4px;
    }

    .product-card .prod-price {
      font-size: 14px;
      color: var(--cafe-amber);
      font-weight: 700;
    }

    /* STOCK BADGE on product cards */
    .stock-badge {
      display: inline-block;
      margin-top: 5px;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 0.02em;
    }

    .stock-badge.stock-ok {
      background: #d4edda;
      color: #1a5c2e;
    }

    .stock-badge.stock-low {
      background: #fff3cd;
      color: #856404;
    }

    .stock-badge.stock-out {
      background: #f8d7da;
      color: #842029;
    }

    /* NON-BLOCKING STOCK ALERT NOTIFICATIONS */
    .stock-notif-container {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10001;
      display: flex;
      flex-direction: column;
      gap: 10px;
      pointer-events: none;
      max-width: 420px;
      width: 90%;
    }

    .stock-notif {
      background: #fff;
      border-radius: 14px;
      padding: 16px 20px;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.22), 0 4px 12px rgba(0, 0, 0, 0.1);
      pointer-events: auto;
      cursor: pointer;
      -webkit-tap-highlight-color: transparent;
      transform: scale(0.7) translateY(30px);
      opacity: 0;
      transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.35s ease, background 0.15s;
      border-left: 5px solid #e8a810;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      user-select: none;
    }

    .stock-notif:active {
      transform: scale(0.97) translateY(0) !important;
      background: #f9f5ef;
    }

    .stock-notif.critical {
      border-left-color: #dc3545;
    }

    .stock-notif.critical:active {
      background: #fef1f1;
    }

    .stock-notif.show {
      transform: scale(1) translateY(0);
      opacity: 1;
    }

    .stock-notif.removing {
      transform: scale(0.7) translateY(-30px);
      opacity: 0;
    }

    .stock-notif .notif-icon {
      font-size: 22px;
      color: #e8a810;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .stock-notif.critical .notif-icon {
      color: #dc3545;
    }

    .stock-notif .notif-body {
      flex: 1;
    }

    .stock-notif .notif-title {
      font-weight: 700;
      font-size: 13px;
      color: var(--cafe-brown);
      margin-bottom: 3px;
    }

    .stock-notif .notif-detail {
      font-size: 12px;
      color: #666;
      font-weight: 500;
    }

    .stock-notif .notif-hint {
      font-size: 10px;
      color: #aaa;
      margin-top: 4px;
      font-style: italic;
    }

    .stock-notif .notif-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 3px;
      background: #e8a810;
      border-radius: 0 0 0 12px;
      transition: width linear;
    }

    .stock-notif.critical .notif-progress {
      background: #dc3545;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    /* CATEGORY PILLS */
    .cat-pill {
      display: inline-block;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 12.5px;
      font-weight: 500;
      cursor: pointer;
      border: 1.5px solid var(--cafe-tan);
      background: #fff;
      color: var(--cafe-brown);
      transition: all 0.12s;
    }

    .cat-pill:hover {
      border-color: var(--cafe-amber);
    }

    .cat-pill.active {
      background: var(--cafe-brown);
      border-color: var(--cafe-brown);
      color: #fff;
    }

    /* CART */
    .cart-panel {
      background: #fff;
      border-radius: 12px;
      border: 1px solid var(--cafe-tan);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .cart-header {
      padding: 14px 16px;
      border-bottom: 1px solid var(--cafe-tan);
      font-weight: 600;
      color: var(--cafe-brown);
      font-size: 15px;
    }

    .cart-body {
      flex: 1;
      overflow-y: auto;
      padding: 8px 16px;
      max-height: 320px;
    }

    .cart-item-row {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 0;
      border-bottom: 1px solid #f3ece0;
    }

    .cart-item-row:last-child {
      border-bottom: none;
    }

    .cart-footer {
      padding: 14px 16px;
      border-top: 1px solid var(--cafe-tan);
    }

    .cart-total-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 18px;
      font-weight: 700;
      color: var(--cafe-brown);
      margin-bottom: 12px;
    }

    /* BUTTONS */
    .btn-cafe-primary {
      background: var(--cafe-brown);
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: background 0.15s;
    }

    .btn-cafe-primary:hover {
      background: #4a2509;
    }

    .btn-cafe-secondary {
      background: transparent;
      color: var(--cafe-brown);
      border: 1.5px solid var(--cafe-tan);
      border-radius: 8px;
      padding: 8px 20px;
      font-size: 13px;
      cursor: pointer;
      width: 100%;
      transition: border-color 0.15s;
      margin-top: 6px;
    }

    .btn-cafe-secondary:hover {
      border-color: var(--cafe-amber);
    }

    .qty-btn {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      border: 1.5px solid var(--cafe-tan);
      background: #fff;
      font-size: 13px;
      line-height: 1;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--cafe-brown);
      transition: background 0.1s;
    }

    .qty-btn:hover {
      background: var(--cafe-tan);
    }

    /* TABLE */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13.5px;
    }

    .data-table th {
      color: #888;
      font-weight: 600;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 8px 12px;
      border-bottom: 1px solid var(--cafe-tan);
    }

    .data-table td {
      padding: 10px 12px;
      border-bottom: 1px solid #f3ece0;
      color: #333;
    }

    .data-table tr:last-child td {
      border-bottom: none;
    }

    .data-table tbody tr:hover {
      background: #fdf8f2;
    }

    /* BADGES */
    .badge-success {
      background: #d4edda;
      color: #1a5c2e;
      border-radius: 20px;
      padding: 3px 10px;
      font-size: 11px;
      font-weight: 600;
    }

    .badge-pending {
      background: #fff3cd;
      color: #856404;
      border-radius: 20px;
      padding: 3px 10px;
      font-size: 11px;
      font-weight: 600;
    }

    .badge-cat {
      background: var(--cafe-tan);
      color: var(--cafe-brown);
      border-radius: 20px;
      padding: 3px 10px;
      font-size: 11px;
      font-weight: 600;
    }

    /* PROGRESS BAR */
    .progress-bar-bg {
      background: var(--cafe-tan);
      border-radius: 4px;
      height: 7px;
      margin-top: 4px;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      border-radius: 4px;
      background: var(--cafe-amber);
    }

    /* MODAL */
    .modal-backdrop {
      background: rgba(0, 0, 0, 0.45);
    }

    .modal-content {
      border-radius: 14px;
      border: none;
    }

    .modal-header {
      border-bottom: 1px solid var(--cafe-tan);
    }

    .form-label {
      font-size: 13px;
      font-weight: 500;
      color: var(--cafe-brown);
    }

    .form-control,
    .form-select {
      border-color: var(--cafe-tan);
      border-radius: 8px;
      font-size: 13.5px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--cafe-amber);
      box-shadow: 0 0 0 3px rgba(196, 124, 42, 0.1);
    }

    /* SCROLLBAR */
    ::-webkit-scrollbar {
      width: 5px;
    }

    ::-webkit-scrollbar-track {
      background: transparent;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--cafe-tan);
      border-radius: 4px;
    }

    /* EMPTY STATE */
    .empty-cart {
      text-align: center;
      padding: 32px 20px;
      color: #aaa;
      font-size: 13px;
    }

    .empty-cart i {
      font-size: 36px;
      display: block;
      margin-bottom: 10px;
      opacity: 0.4;
    }

    /* NOTIFICATION TOAST */
    .toast-notif {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: var(--cafe-brown);
      color: #fff;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 13.5px;
      font-weight: 500;
      z-index: 9999;
      transform: translateY(100px);
      opacity: 0;
      transition: transform 0.3s, opacity 0.3s;
      pointer-events: none;
      max-width: 360px;
    }

    .toast-notif.show {
      transform: translateY(0);
      opacity: 1;
    }

    .toast-notif.toast-warning {
      background: #856404;
    }

    /* CHART CONTAINERS */
    .chart-box {
      position: relative;
      width: 100%;
    }

    /* SEARCH BAR */
    .search-input {
      border: 1.5px solid var(--cafe-tan);
      border-radius: 8px;
      padding: 7px 14px 7px 36px;
      font-size: 13px;
      background: #fff;
      width: 220px;
      outline: none;
      transition: border-color 0.15s;
    }

    .search-input:focus {
      border-color: var(--cafe-amber);
    }

    .search-wrap {
      position: relative;
    }

    .search-wrap i {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #aaa;
      font-size: 14px;
    }

    /* REPORT STAT ROW */
    .report-stat {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #f3ece0;
      font-size: 13.5px;
    }

    .report-stat:last-child {
      border-bottom: none;
    }

    /* LOGIN PAGE */
    .login-wrapper {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #1a1514 0%, var(--cafe-brown) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      overflow: hidden;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      width: 100%;
      max-width: 400px;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .login-brand {
      text-align: center;
      margin-bottom: 30px;
    }

    .login-brand h3 {
      font-weight: 700;
      color: var(--cafe-brown);
      margin-bottom: 5px;
    }

    .login-brand p {
      color: #888;
      font-size: 14px;
      margin: 0;
    }

    /* ======================================================
       RESPONSIVE — TABLETS (max-width: 1024px)
       ====================================================== */
    @media (max-width: 1024px) {
      body {
        overflow: auto;
        height: auto;
        min-height: 100vh;
        -webkit-overflow-scrolling: touch;
      }

      .app-wrapper {
        flex-direction: column;
        height: auto;
        min-height: 100vh;
        overflow: visible;
      }

      /* Sidebar → Bottom Navigation */
      .sidebar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        min-width: unset;
        height: auto;
        flex-direction: row;
        z-index: 9998;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
      }

      .sidebar-brand {
        display: none;
      }

      .sidebar-nav {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        padding: 6px 8px;
        gap: 2px;
        flex: 1;
        overflow-x: auto;
        overflow-y: hidden;
      }

      .nav-section-label {
        display: none;
      }

      .nav-item-btn {
        flex-direction: column;
        gap: 2px;
        padding: 8px 12px;
        font-size: 10px;
        min-width: 60px;
        text-align: center;
        justify-content: center;
        align-items: center;
        border-radius: 10px;
        margin-bottom: 0;
        white-space: nowrap;
      }

      .nav-item-btn i {
        font-size: 18px;
        width: auto;
      }

      .sidebar-footer {
        display: none;
      }

      /* Main content gets bottom padding for the nav bar */
      .main-content {
        padding-bottom: 80px;
        overflow: visible;
      }

      /* Top bar */
      .top-bar {
        padding: 10px 16px;
        position: sticky;
        top: 0;
        z-index: 100;
      }

      .top-bar h4 {
        font-size: 16px;
      }

      /* Views */
      .view {
        padding: 16px;
      }

      /* POS VIEW — Stack vertically */
      #view-pos .row {
        height: auto !important;
      }

      #view-pos .col-lg-8 {
        overflow: visible !important;
      }

      /* Larger touch targets for product cards */
      .product-card {
        padding: 16px 12px;
        border-radius: 14px;
        -webkit-tap-highlight-color: transparent;
      }

      .product-card .prod-emoji {
        font-size: 30px;
      }

      .product-card .prod-name {
        font-size: 12.5px;
      }

      .product-card .prod-price {
        font-size: 15px;
      }

      .stock-badge {
        font-size: 10px;
        padding: 3px 10px;
      }

      /* Category pills — bigger for touch */
      .cat-pill {
        padding: 8px 16px;
        font-size: 13px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
      }

      /* Search bar — full width */
      .search-input {
        width: 100%;
        min-width: 0;
        padding: 10px 14px 10px 36px;
        font-size: 14px;
      }

      #view-pos .mb-2 {
        flex-direction: column;
        gap: 10px;
      }

      .search-wrap {
        width: 100%;
      }

      /* Buttons — larger touch area */
      .btn-cafe-primary {
        padding: 14px 20px;
        font-size: 15px;
        min-height: 48px;
      }

      .btn-cafe-secondary {
        padding: 12px 20px;
        font-size: 14px;
        min-height: 44px;
      }

      .qty-btn {
        width: 36px;
        height: 36px;
        font-size: 18px;
      }

      /* Cart */
      .cart-body {
        max-height: none;
      }

      .cart-item-row {
        padding: 12px 0;
        gap: 10px;
      }

      .cart-total-row {
        font-size: 20px;
      }

      /* Tables — horizontal scroll */
      .kpi-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .data-table {
        min-width: 500px;
      }

      .data-table th,
      .data-table td {
        padding: 10px 10px;
        white-space: nowrap;
      }

      /* KPI cards */
      .kpi-stat {
        font-size: 22px;
      }

      /* Form inputs — bigger for touch */
      .form-control,
      .form-select {
        padding: 10px 14px;
        font-size: 15px;
        min-height: 44px;
      }

      /* Toast notification */
      .toast-notif {
        left: 16px;
        right: 16px;
        bottom: 90px;
        max-width: none;
        text-align: center;
        font-size: 14px;
        padding: 14px 20px;
      }

      /* Stock notification */
      .stock-notif-container {
        max-width: 92%;
      }

      .stock-notif {
        padding: 14px 16px;
      }

      /* Login card */
      .login-card {
        margin: 16px;
        padding: 28px 24px;
      }
    }

    /* ======================================================
       RESPONSIVE — PHONES (max-width: 576px)
       ====================================================== */
    @media (max-width: 576px) {

      /* Even more compact nav */
      .nav-item-btn {
        padding: 6px 8px;
        font-size: 9px;
        min-width: 52px;
      }

      .nav-item-btn i {
        font-size: 16px;
      }

      /* Top bar */
      .top-bar h4 {
        font-size: 14px;
      }

      .top-bar-time {
        font-size: 11px;
      }

      /* Views */
      .view {
        padding: 12px;
      }

      /* Product grid — 3 columns on phone */
      #menu-grid .col-4 {
        width: 33.33%;
      }

      .product-card {
        padding: 12px 8px;
      }

      .product-card .prod-emoji {
        font-size: 24px;
        margin-bottom: 4px;
      }

      .product-card .prod-name {
        font-size: 11px;
      }

      .product-card .prod-price {
        font-size: 13px;
      }

      .stock-badge {
        font-size: 9px;
        padding: 2px 6px;
      }

      /* Category pills */
      .cat-pill {
        padding: 6px 12px;
        font-size: 12px;
        min-height: 34px;
      }

      /* KPI grid — always 2 columns */
      .kpi-stat {
        font-size: 18px;
      }

      .kpi-label {
        font-size: 10px;
      }

      .kpi-card {
        padding: 14px 14px;
      }

      /* Cart adjustments */
      .cart-header {
        padding: 12px 14px;
        font-size: 14px;
      }

      .cart-footer {
        padding: 12px 14px;
      }

      .cart-total-row {
        font-size: 18px;
      }

      /* Qty buttons in cart */
      .qty-btn {
        width: 32px;
        height: 32px;
        font-size: 16px;
      }

      /* Toast */
      .toast-notif {
        left: 12px;
        right: 12px;
        bottom: 80px;
        font-size: 13px;
        padding: 12px 16px;
        border-radius: 8px;
      }

      /* Stock notif */
      .stock-notif-container {
        max-width: 96%;
        top: 12px;
      }

      .stock-notif {
        padding: 12px 14px;
        border-radius: 12px;
        gap: 10px;
      }

      .stock-notif .notif-icon {
        font-size: 18px;
      }

      .stock-notif .notif-title {
        font-size: 12px;
      }

      .stock-notif .notif-detail {
        font-size: 11px;
      }

      /* Login */
      .login-card {
        margin: 12px;
        padding: 24px 20px;
        border-radius: 16px;
      }

      .login-brand h3 {
        font-size: 20px;
      }

      /* Menu management — action buttons stack */
      #menu-tbody .btn {
        display: block;
        width: 100%;
        margin-bottom: 4px;
      }

      /* Chart boxes */
      .chart-box {
        height: 180px !important;
      }
    }
  </style>
</head>

<body>

  <!-- LOGIN SCREEN -->
  <div id="login-wrapper" class="login-wrapper">
    <div class="login-card">
      <div class="login-brand">
        <div class="brand-icon mx-auto mb-3"
          style="width: 60px; height: 60px; background: var(--cafe-amber); color: #fff; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 30px;">
          <i class="bi bi-cup-hot-fill"></i>
        </div>
        <h3>ChipiPie Cafe</h3>
        <p>Point of Sales Login</p>
      </div>
      <form onsubmit="handleLogin(event)">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" id="login-user" class="form-control" required>
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" id="login-pass" class="form-control" required>
        </div>
        <button type="submit" class="btn-cafe-primary btn-lg w-100" style="font-size:16px; padding:12px;">Sign
          In</button>
      </form>
      <div id="login-error"
        style="display:none; color:#842029; background:#f8d7da; padding:10px; border-radius:8px; font-size:13px; margin-top:12px;">
      </div>
      <div class="mt-4 text-center"
        style="font-size: 12px; color: #888; background: #f9f9f9; padding: 12px; border-radius: 8px; border: 1px solid var(--cafe-tan);">
        <strong>Default Accounts</strong><br>
        Admin: <code>admin</code> / <code>admin123</code><br>
        Cashier: <code>cashier1</code> / <code>cashier123</code>
      </div>
      <div class="mt-3 text-center">
        <a href="#" onclick="showRegisterForm()"
          style="font-size:13px; color:var(--cafe-brown); font-weight:500;">Register New Account</a>
      </div>
    </div>
  </div>

  <!-- REGISTER SCREEN -->
  <div id="register-wrapper" class="login-wrapper" style="display:none;">
    <div class="login-card">
      <div class="login-brand">
        <div class="brand-icon mx-auto mb-3"
          style="width:60px;height:60px;background:var(--cafe-amber);color:#fff;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:30px;">
          <i class="bi bi-person-plus-fill"></i>
        </div>
        <h3>Register Account</h3>
        <p>ChipiPie Cafe POS</p>
      </div>
      <form onsubmit="handleRegister(event)">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" id="reg-name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" id="reg-email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" id="reg-user" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" id="reg-pass" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <input type="password" id="reg-pass-confirm" class="form-control" required minlength="6">
        </div>
        <div class="mb-4">
          <label class="form-label">Role</label>
          <select class="form-select" id="reg-role">
            <option value="cashier">Cashier</option>
            <option value="admin">Admin (requires owner approval)</option>
          </select>
        </div>
        <button type="submit" class="btn-cafe-primary btn-lg w-100" style="font-size:16px;padding:12px;"
          id="reg-submit-btn">Register</button>
      </form>
      <div id="reg-error"
        style="display:none; color:#842029; background:#f8d7da; padding:10px; border-radius:8px; font-size:13px; margin-top:12px;">
      </div>
      <div id="reg-success"
        style="display:none; color:#1a5c2e; background:#d4edda; padding:10px; border-radius:8px; font-size:13px; margin-top:12px;">
      </div>
      <div class="mt-3 text-center">
        <a href="#" onclick="showLoginForm()" style="font-size:13px; color:var(--cafe-brown); font-weight:500;">← Back
          to Login</a>
      </div>
    </div>
  </div>

  <div class="app-wrapper" id="app-wrapper" style="display: none;">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="brand-icon">
          <i class="bi bi-cup-hot-fill"></i>
        </div>
        <h5>ChipiPie Cafe</h5>
        <small>Point of Sales System</small>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <button class="nav-item-btn active" onclick="showView('dashboard', this)">
          <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </button>
        <button class="nav-item-btn" onclick="showView('pos', this)">
          <i class="bi bi-cart3"></i> New Order
        </button>
        <button class="nav-item-btn" onclick="showView('orders', this)">
          <i class="bi bi-receipt"></i> Orders
        </button>

        <div class="nav-section-label" id="nav-mgmt-label">Management</div>
        <button class="nav-item-btn" id="nav-menu-btn" onclick="showView('menu', this)">
          <i class="bi bi-menu-button-wide-fill"></i> Menu Items
        </button>
        <button class="nav-item-btn" id="nav-reports-btn" onclick="showView('reports', this)">
          <i class="bi bi-bar-chart-fill"></i> Analytics
        </button>
        <button class="nav-item-btn" id="nav-users-btn" onclick="showView('users', this)">
          <i class="bi bi-people-fill"></i> Users
        </button>
      </nav>

      <div class="sidebar-footer">
        <div class="cashier-avatar" id="sidebar-avatar">A</div>
        <div style="flex: 1;">
          <div class="cashier-name" id="sidebar-name">Admin</div>
          <div class="cashier-role" id="sidebar-role">Cashier</div>
        </div>
        <button class="btn btn-sm" style="color:#aaa; background:transparent; border:none; padding:4px;"
          onclick="logout()" title="Logout">
          <i class="bi bi-box-arrow-right" style="font-size: 18px;"></i>
        </button>
      </div>
    </aside>

    <!-- MAIN -->
    <div class="main-content">

      <!-- TOP BAR -->
      <div class="top-bar">
        <h4 id="page-title">Dashboard</h4>
        <div class="d-flex align-items-center gap-3">
          <span class="top-bar-time" id="live-time"></span>
          <span class="badge-success" id="status-badge">● Open</span>
        </div>
      </div>

      <!-- DASHBOARD VIEW -->
      <div id="view-dashboard" class="view active">
        <div class="row g-3 mb-4">
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label"><i class="bi bi-currency-exchange me-1"></i>Today's Revenue</div>
              <div class="kpi-stat" id="kpi-sales">₱0</div>
              <div class="kpi-trend text-muted" id="kpi-sales-trend">From today's orders</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label"><i class="bi bi-bag-check me-1"></i>Orders Today</div>
              <div class="kpi-stat" id="kpi-orders">0</div>
              <div class="kpi-trend text-muted">Transactions completed</div>
            </div>
          </div>
          <div class="col-6 col-lg-2">
            <div class="kpi-card">
              <div class="kpi-label"><i class="bi bi-cup-hot me-1"></i>Cups Sold</div>
              <div class="kpi-stat" id="kpi-cups">0</div>
              <div class="kpi-trend text-muted" id="kpi-cups-trend">Drinks today</div>
            </div>
          </div>
          <div class="col-6 col-lg-2">
            <div class="kpi-card">
              <div class="kpi-label"><i class="bi bi-calculator me-1"></i>Avg. Order</div>
              <div class="kpi-stat" id="kpi-avg">₱0</div>
              <div class="kpi-trend text-muted">Per transaction</div>
            </div>
          </div>
          <div class="col-12 col-lg-2">
            <div class="kpi-card">
              <div class="kpi-label"><i class="bi bi-trophy me-1"></i>Best Seller</div>
              <div class="kpi-stat" style="font-size:17px;" id="kpi-top">—</div>
              <div class="kpi-trend text-muted" id="kpi-top-trend">No sales yet</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-lg-8">
            <div class="kpi-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span style="font-weight:600; color:var(--cafe-brown); font-size:14px;">Weekly Sales Overview</span>
                <span class="badge-cat">This Week</span>
              </div>
              <div class="chart-box" style="height:230px;">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Sales by
                Category</div>
              <div class="chart-box" style="height:230px;">
                <canvas id="categoryChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-lg-7">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Recent Orders
              </div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Time</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="recent-orders-tbody"></tbody>
              </table>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:14px;">Top Selling
                Items</div>
              <div id="top-items-list"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- POS VIEW -->
      <div id="view-pos" class="view" style="padding: 16px;">
        <div class="row g-3" style="height: calc(100vh - 80px);">
          <div class="col-lg-8 d-flex flex-column" style="overflow: hidden;">
            <div class="mb-2 d-flex align-items-center justify-content-between">
              <div class="d-flex gap-2 flex-wrap" id="cat-filters"></div>
              <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" placeholder="Search menu..." id="menu-search"
                  oninput="renderPOS()">
              </div>
            </div>
            <div class="flex-grow-1 overflow-y-auto pe-1">
              <div class="row g-2" id="menu-grid"></div>
            </div>
          </div>

          <div class="col-lg-4 d-flex flex-column" style="height: 100%;">
            <div class="cart-panel" style="height: 100%;">
              <div class="cart-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart3 me-2"></i>Current Order</span>
                <span class="badge-cat" id="cart-count">0 items</span>
              </div>
              <div class="cart-body" id="cart-items">
                <div class="empty-cart">
                  <i class="bi bi-bag"></i>
                  No items yet.<br>Tap a menu item to add.
                </div>
              </div>
              <div class="cart-footer">
                <div class="cart-total-row">
                  <span>Total</span>
                  <span id="cart-total">₱0.00</span>
                </div>
                <button class="btn-cafe-primary" onclick="checkout()">
                  <i class="bi bi-check-circle me-2"></i>Checkout
                </button>
                <button class="btn-cafe-secondary" onclick="clearCart()">
                  <i class="bi bi-x me-1"></i>Clear Order
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ORDERS VIEW -->
      <div id="view-orders" class="view">
        <!-- Filter Tabs + Print -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div class="d-flex gap-2 flex-wrap" id="order-filter-tabs">
            <button id="ofilter-today" class="cat-pill active" onclick="setOrderFilter('today', this)">Today</button>
            <button id="ofilter-week" class="cat-pill" onclick="setOrderFilter('week',  this)">This Week</button>
            <button id="ofilter-month" class="cat-pill" onclick="setOrderFilter('month', this)">This Month</button>
            <button id="ofilter-year" class="cat-pill" onclick="setOrderFilter('year',  this)">This Year</button>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-success" id="orders-count-badge">0 orders</span>
            <button onclick="printOrders()"
              style="background:var(--cafe-brown);color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
              <i class="bi bi-printer-fill"></i> Print Report
            </button>
          </div>
        </div>

        <!-- Summary Bar -->
        <div class="row g-2 mb-3" id="orders-summary-bar">
          <div class="col-6 col-md-3">
            <div class="kpi-card" style="padding:12px 16px;">
              <div class="kpi-label">Total Orders</div>
              <div class="kpi-stat" id="osum-orders" style="font-size:22px;">0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="kpi-card" style="padding:12px 16px;">
              <div class="kpi-label">Total Revenue</div>
              <div class="kpi-stat" id="osum-revenue" style="font-size:22px;">₱0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="kpi-card" style="padding:12px 16px;">
              <div class="kpi-label">Avg. Order Value</div>
              <div class="kpi-stat" id="osum-avg" style="font-size:22px;">₱0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="kpi-card" style="padding:12px 16px;">
              <div class="kpi-label">Period</div>
              <div class="kpi-stat" id="osum-period" style="font-size:14px;font-weight:600;color:var(--cafe-brown);">
                Today</div>
            </div>
          </div>
        </div>

        <div class="kpi-card">
          <div id="orders-loading" style="text-align:center;padding:32px;color:#aaa;display:none;">
            <i class="bi bi-hourglass-split" style="font-size:28px;"></i>
            <div style="margin-top:8px;font-size:13px;">Loading orders...</div>
          </div>
          <div style="overflow-x:auto;">
            <table class="data-table" id="orders-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Payment</th>
                  <th>Cashier</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="orders-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MENU MANAGEMENT VIEW -->
      <div id="view-menu" class="view">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" class="search-input" id="menu-mgmt-search" oninput="renderMenuTable()">
          </div>
          <button class="btn-cafe-primary" style="width: auto; padding: 8px 18px;" data-bs-toggle="modal"
            data-bs-target="#addItemModal">
            <i class="bi bi-plus-circle me-1"></i>Add Item
          </button>
        </div>
        <div class="kpi-card">
          <table class="data-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Availability</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="menu-tbody"></tbody>
          </table>
        </div>
      </div>

      <!-- REPORTS VIEW -->
      <div id="view-reports" class="view">
        <div class="row g-3 mb-3">
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Total Revenue (Month)</div>
              <div class="kpi-stat">₱82,400</div>
              <div class="kpi-trend text-success"><i class="bi bi-arrow-up-short"></i>+18% vs last month</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Total Orders (Month)</div>
              <div class="kpi-stat">1,204</div>
              <div class="kpi-trend text-success"><i class="bi bi-arrow-up-short"></i>+8% vs last month</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Avg Daily Revenue</div>
              <div class="kpi-stat">₱2,747</div>
              <div class="kpi-trend text-muted">Based on 30 days</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Best Revenue Day</div>
              <div class="kpi-stat">Saturday</div>
              <div class="kpi-trend text-muted">Avg ₱4,320/week</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-lg-6">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Hourly Sales —
                Today</div>
              <div class="chart-box" style="height:210px;">
                <canvas id="hourlyChart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Monthly Revenue
                Trend</div>
              <div class="chart-box" style="height:210px;">
                <canvas id="monthlyChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-lg-8">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Item Sales
                Performance</div>
              <div class="chart-box" style="height:280px;">
                <canvas id="itemChart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:14px;">Category
                Breakdown</div>
              <div id="cat-breakdown"></div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Sales Volume
                Breakdown</div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Time Period</th>
                    <th>Cups Sold (Drinks)</th>
                    <th>Pastries Sold (Food)</th>
                  </tr>
                </thead>
                <tbody id="rep-vol-tbody">
                  <tr>
                    <td style="font-weight:500;">Today</td>
                    <td style="font-weight:600; color:var(--cafe-brown);">31</td>
                    <td style="font-weight:600; color:var(--cafe-amber);">49</td>
                  </tr>
                  <tr>
                    <td style="font-weight:500;">This Week</td>
                    <td style="font-weight:600; color:var(--cafe-brown);">215</td>
                    <td style="font-weight:600; color:var(--cafe-amber);">310</td>
                  </tr>
                  <tr>
                    <td style="font-weight:500;">This Month</td>
                    <td style="font-weight:600; color:var(--cafe-brown);">980</td>
                    <td style="font-weight:600; color:var(--cafe-amber);">1,420</td>
                  </tr>
                  <tr>
                    <td style="font-weight:500;">This Year</td>
                    <td style="font-weight:600; color:var(--cafe-brown);">11,400</td>
                    <td style="font-weight:600; color:var(--cafe-amber);">16,500</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- USERS MANAGEMENT VIEW (Admin only) -->
      <div id="view-users" class="view">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <button class="btn-cafe-primary" style="width:auto;padding:8px 18px;" onclick="showRegisterUserModal()">
              <i class="bi bi-person-plus me-1"></i>Add User
            </button>
            <button class="btn-cafe-secondary" style="width:auto;padding:8px 18px;margin-top:0;margin-left:8px;"
              onclick="requestBypassToken()">
              <i class="bi bi-key me-1"></i>Request Bypass Token
            </button>
          </div>
        </div>

        <!-- Active Users -->
        <div class="kpi-card mb-3">
          <div style="font-weight:600;color:var(--cafe-brown);font-size:14px;margin-bottom:12px;">Registered Users</div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="users-tbody"></tbody>
          </table>
        </div>

        <!-- Pending Admin Requests -->
        <div class="kpi-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div style="font-weight:600;color:var(--cafe-brown);font-size:14px;">Pending Admin Requests</div>
          </div>
          <div
            style="background:#fff8e6;border:1px solid #ffe08a;border-radius:8px;padding:10px 14px;font-size:12px;color:#856404;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-envelope-check" style="font-size:15px;"></i>
            <span>Approvals are handled <strong>exclusively by the owner</strong> via a secure link sent to their Gmail.
              Use <em>Resend to Owner</em> if the email was missed.</span>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="pending-tbody"></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <!-- TOKEN REQUEST MODAL -->
  <div class="modal fade" id="tokenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom:1px solid var(--cafe-tan);">
          <h5 class="modal-title" style="color:var(--cafe-brown);font-size:16px;"><i class="bi bi-key me-2"></i>Request
            Bypass Token</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div style="font-size:13px;color:#666;margin-bottom:16px;">
            <p style="margin-bottom:8px;"><strong>How it works:</strong></p>
            <ul style="font-size:12px;padding-left:18px;margin:0;">
              <li><strong>Delete / Stock / Users</strong> — token sent to <em>your email</em></li>
              <li><strong>General Bypass</strong> — token sent to the <em>owner's email</em></li>
            </ul>
          </div>
          <div class="mb-3">
            <label class="form-label">Purpose</label>
            <select class="form-select" id="token-purpose">
              <option value="delete_menu_item">Delete Menu Item (→ your email)</option>
              <option value="manage_users">Manage Users (→ your email)</option>
              <option value="override_stock">Override Stock (→ your email)</option>
              <option value="bypass">General Bypass (→ owner's email)</option>
            </select>
          </div>
          <div id="token-result" style="display:none;"></div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--cafe-tan);gap:8px;">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-sm" style="background:var(--cafe-brown);color:#fff;" onclick="submitTokenRequest()"
            id="token-submit-btn">Request Token</button>
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRM MODAL (requires token) -->
  <div class="modal fade" id="deleteTokenModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom:1px solid var(--cafe-tan);">
          <h5 class="modal-title" style="color:#842029;font-size:15px;"><i class="bi bi-shield-lock me-2"></i>Enter
            Bypass Token</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p style="font-size:12px;color:#666;margin-bottom:12px;">Enter the token sent to your email to confirm
            deletion.</p>
          <input type="text" class="form-control text-center" id="delete-token-input" placeholder="XXXXXXXX"
            style="font-size:18px;letter-spacing:3px;font-weight:700;font-family:monospace;" maxlength="8">
          <div id="delete-token-error" style="display:none;color:#842029;font-size:12px;margin-top:8px;"></div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--cafe-tan);gap:8px;">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-sm" style="background:#842029;color:#fff;" onclick="confirmDeleteWithToken()"
            id="delete-confirm-btn">Confirm Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- REGISTER USER MODAL (from admin panel) -->
  <div class="modal fade" id="registerUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom:1px solid var(--cafe-tan);">
          <h5 class="modal-title" style="color:var(--cafe-brown);font-size:16px;">Register New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" id="modal-reg-name">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="modal-reg-email">
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" id="modal-reg-user">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" id="modal-reg-pass" minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="modal-reg-pass-confirm" minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" id="modal-reg-role">
              <option value="cashier">Cashier</option>
              <option value="admin">Admin (requires owner approval)</option>
            </select>
          </div>
          <div id="modal-reg-result" style="display:none;"></div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--cafe-tan);gap:8px;">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-sm" style="background:var(--cafe-brown);color:#fff;"
            onclick="submitRegisterUser()">Register</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADD ITEM MODAL -->
  <div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom: 1px solid var(--cafe-tan);">
          <h5 class="modal-title" style="color: var(--cafe-brown); font-size:16px;">Add Menu Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label">Item Name</label>
            <input type="text" class="form-control" id="new-name">
          </div>
          <div class="mb-3">
            <label class="form-label">Item Image</label>
            <input type="file" class="form-control" id="new-image" accept="image/*">
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" id="new-category">
              <option>Pies</option>
              <option>Drinks</option>
              <option>Snacks</option>
              <option>Add-ons</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Price (₱)</label>
            <input type="number" class="form-control" id="new-price" min="1">
          </div>
          <div class="row g-2">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label">Initial Stock</label>
                <input type="number" class="form-control" id="new-stock" min="0" value="20">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label">Min Stock (alert)</label>
                <input type="number" class="form-control" id="new-min-stock" min="1" value="5">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--cafe-tan); gap: 8px;">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-sm" style="background: var(--cafe-brown); color:#fff;" onclick="addMenuItem()">Add to
            Menu</button>
        </div>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div class="toast-notif" id="toast"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <script>

    /* ========== API HELPERS ========== */
    const API = 'api/';

    async function api(endpoint, data = null, method = null) {
      const opts = { credentials: 'include' };
      if (data) {
        opts.method = method || 'POST';
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(data);
      } else {
        opts.method = method || 'GET';
      }
      const res = await fetch(API + endpoint, opts);
      return await res.json();
    }

    /* ========== DATA ========== */
    let menuItems = [
      { id: 1, name: 'Buko Pie (Whole)', category: 'Pies', price: 280, emoji: '🥧', available: true, stock: 20, minStock: 5 },
      { id: 2, name: 'Ube Pie Slice', category: 'Pies', price: 75, emoji: '🫐', available: true, stock: 30, minStock: 8 },
      { id: 3, name: 'Egg Pie Slice', category: 'Pies', price: 60, emoji: '🥚', available: true, stock: 25, minStock: 8 },
      { id: 4, name: 'Buko Pie Slice', category: 'Pies', price: 65, emoji: '🥥', available: true, stock: 35, minStock: 10 },
      { id: 5, name: 'Caramel Apple Pie', category: 'Pies', price: 90, emoji: '🍎', available: true, stock: 15, minStock: 5 },
      { id: 6, name: 'Strawberry Pie', category: 'Pies', price: 85, emoji: '🍓', available: true, stock: 3, minStock: 5 },
      { id: 7, name: 'Brewed Coffee', category: 'Drinks', price: 80, emoji: '☕', available: true, stock: 50, minStock: 10 },
      { id: 8, name: 'Iced Latte', category: 'Drinks', price: 110, emoji: '🧊', available: true, stock: 40, minStock: 10 },
      { id: 9, name: 'Buko Juice', category: 'Drinks', price: 65, emoji: '🥥', available: true, stock: 20, minStock: 5 },
      { id: 10, name: 'Hot Chocolate', category: 'Drinks', price: 95, emoji: '🍫', available: true, stock: 30, minStock: 8 },
      { id: 11, name: 'Iced Tea', category: 'Drinks', price: 55, emoji: '🍵', available: true, stock: 45, minStock: 10 },
      { id: 12, name: 'Cheese Bread', category: 'Snacks', price: 45, emoji: '🧀', available: true, stock: 25, minStock: 8 },
      { id: 13, name: 'Ensaymada', category: 'Snacks', price: 55, emoji: '🍞', available: true, stock: 20, minStock: 5 },
      { id: 14, name: 'Empanada', category: 'Snacks', price: 50, emoji: '🥟', available: false, stock: 0, minStock: 5 },
      { id: 15, name: 'Whipped Cream', category: 'Add-ons', price: 20, emoji: '🍦', available: true, stock: 60, minStock: 15 },
      { id: 16, name: 'Extra Sauce', category: 'Add-ons', price: 15, emoji: '🍯', available: true, stock: 50, minStock: 10 },
    ];

    const _now = new Date();
    const dToday = _now.toDateString();
    const _yst = new Date(_now); _yst.setDate(_yst.getDate() - 2);
    const dWeek = _yst.toDateString();
    const _mon = new Date(_now); _mon.setMonth(_mon.getMonth() - 1);
    const dMonth = _mon.toDateString();
    const _yr = new Date(_now); _yr.setFullYear(_yr.getFullYear() - 1);
    const dYear = _yr.toDateString();

    let orders = [];

    let cart = {};
    let activeCategory = 'All';
    let orderCounter = orders.length;

    /* ========== NAVIGATION ========== */
    function showView(name, btn) {
      document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
      document.querySelectorAll('.nav-item-btn').forEach(b => b.classList.remove('active'));
      document.getElementById('view-' + name).classList.add('active');
      if (btn) btn.classList.add('active');

      const titles = { dashboard: 'Dashboard', pos: 'New Order', orders: 'Orders', menu: 'Menu Items', reports: 'Analytics & Reports', users: 'User Management' };
      document.getElementById('page-title').textContent = titles[name] || '';

      if (name === 'pos') renderPOS();
      if (name === 'orders') renderOrders();
      if (name === 'menu') renderMenuTable();
      if (name === 'dashboard') updateDashboard();
      if (name === 'reports') renderReports();
      if (name === 'users') renderUsers();
    }

    /* ========== LIVE CLOCK ========== */
    function updateClock() {
      const now = new Date();
      document.getElementById('live-time').textContent = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    /* ========== TOAST ========== */
    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2800);
    }

    /* ========== DASHBOARD ========== */
    function updateDashboard() {
      const todayOrders = orders.filter(o => o.date === new Date().toDateString());
      const total = todayOrders.reduce((s, o) => s + o.total, 0);
      document.getElementById('kpi-sales').textContent = '₱' + total.toLocaleString();
      document.getElementById('kpi-orders').textContent = todayOrders.length;
      document.getElementById('kpi-avg').textContent = todayOrders.length ? '₱' + Math.round(total / todayOrders.length) : '₱0';

      // Count cups sold (drinks category) today
      let cupsSold = 0;
      let foodSold = 0;
      todayOrders.forEach(o => {
        if (!o.cartData) return;
        for (let pid in o.cartData) {
          const product = menuItems.find(m => m.id == pid);
          if (!product) continue;
          if (product.category === 'Drinks') {
            cupsSold += o.cartData[pid];
          } else {
            foodSold += o.cartData[pid];
          }
        }
      });
      const cupsEl = document.getElementById('kpi-cups');
      if (cupsEl) cupsEl.textContent = cupsSold;
      const cupsTrendEl = document.getElementById('kpi-cups-trend');
      if (cupsTrendEl) cupsTrendEl.textContent = `${foodSold} food items sold`;

      // Recent orders table
      document.getElementById('recent-orders-tbody').innerHTML = orders.slice(0, 5).map(o => `
    <tr>
      <td style="font-weight:600;">${o.id}</td>
      <td style="color:#888;">${o.time}</td>
      <td>${o.items.length > 30 ? o.items.slice(0, 30) + '…' : o.items}</td>
      <td style="font-weight:600; color:var(--cafe-amber);">₱${o.total}</td>
      <td><span class="badge-success">${o.status}</span></td>
    </tr>
  `).join('');

      // Top items dynamically aggregated
      const itemStats = {};
      orders.forEach(o => {
        if (!o.cartData) return;
        for (let pid in o.cartData) {
          const product = menuItems.find(m => m.id == pid);
          if (!product) continue;
          if (!itemStats[product.name]) itemStats[product.name] = { name: product.name, sold: 0, revenue: 0 };
          itemStats[product.name].sold += o.cartData[pid];
          itemStats[product.name].revenue += o.cartData[pid] * product.price;
        }
      });
      let dbTopItems = Object.values(itemStats).sort((a, b) => b.sold - a.sold).slice(0, 5);
      if (dbTopItems.length === 0) dbTopItems = [{ name: 'No sales yet', sold: 0, revenue: 0 }];
      const maxSold = Math.max(dbTopItems[0].sold, 1);

      const bestSellerEl = document.getElementById('kpi-top');
      if (bestSellerEl) {
        bestSellerEl.textContent = dbTopItems[0].name !== 'No sales yet' ? dbTopItems[0].name : '—';
      }
      const topTrendEl = document.getElementById('kpi-top-trend');
      if (topTrendEl) {
        topTrendEl.textContent = dbTopItems[0].sold > 0 ? `${dbTopItems[0].sold} units sold` : 'No sales yet';
      }

      document.getElementById('top-items-list').innerHTML = dbTopItems.map(i => `
    <div class="mb-3">
      <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:3px;">
        <span style="font-weight:500; color:#333;">${i.name}</span>
        <span style="color:var(--cafe-amber); font-weight:600;">₱${i.revenue.toLocaleString()}</span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <div class="progress-bar-bg" style="flex:1;">
          <div class="progress-bar-fill" style="width:${Math.round(i.sold / maxSold * 100)}%;"></div>
        </div>
        <span style="font-size:11px; color:#888; min-width:42px;">${i.sold} sold</span>
      </div>
    </div>
  `).join('');
    }

    /* ========== POS ========== */
    function renderPOS() {
      const categories = ['All', ...new Set(menuItems.map(m => m.category))];
      document.getElementById('cat-filters').innerHTML = categories.map(c =>
        `<span class="cat-pill ${c === activeCategory ? 'active' : ''}" onclick="filterCat('${c}')">${c}</span>`
      ).join('');

      const q = (document.getElementById('menu-search')?.value || '').toLowerCase();
      const filtered = menuItems
        .filter(m => activeCategory === 'All' || m.category === activeCategory)
        .filter(m => !q || m.name.toLowerCase().includes(q))
        .filter(m => m.available);

      document.getElementById('menu-grid').innerHTML = filtered.map(item => {
        const isOutOfStock = item.stock <= 0;
        const isLowStock = item.stock > 0 && item.stock <= item.minStock;
        const stockClass = isOutOfStock ? 'stock-out' : (isLowStock ? 'stock-low' : 'stock-ok');
        const stockLabel = isOutOfStock ? 'Out of Stock' : (isLowStock ? `Low: ${item.stock} left` : `In Stock: ${item.stock}`);
        const cardClass = isOutOfStock ? 'product-card out-of-stock' : 'product-card';
        const clickHandler = isOutOfStock ? `showToast('⚠ ${item.name} is out of stock!')` : `addToCart(${item.id})`;
        return `
    <div class="col-4 col-md-3 col-xl-2">
      <div class="${cardClass}" onclick="${clickHandler}">
        ${item.image ? `<img src="${item.image}" style="width:40px; height:40px; object-fit:cover; border-radius:50%; margin: 0 auto 6px auto; display:block;">` : `<span class="prod-emoji">${item.emoji}</span>`}
        <div class="prod-name">${item.name}</div>
        <div class="prod-price">₱${item.price}</div>
        <div class="stock-badge ${stockClass}">${stockLabel}</div>
      </div>
    </div>`;
      }).join('') || '<div class="col-12 text-center text-muted py-4 small">No items found.</div>';

      renderCart();
    }

    function filterCat(cat) {
      activeCategory = cat;
      renderPOS();
    }

    function addToCart(id) {
      const item = menuItems.find(m => m.id == id);
      if (!item) return;

      // Check if adding one more would exceed stock
      const currentInCart = cart[id] || 0;
      if (currentInCart + 1 > item.stock) {
        showToast(`⚠ Only ${item.stock} ${item.name} left in stock!`);
        return;
      }

      cart[id] = currentInCart + 1;
      renderCart();
      showToast(`✓ ${item.name} added`);
    }

    function renderCart() {
      const keys = Object.keys(cart);
      const totalQty = keys.reduce((s, id) => s + cart[id], 0);
      document.getElementById('cart-count').textContent = totalQty + ' item' + (totalQty !== 1 ? 's' : '');

      if (!keys.length) {
        document.getElementById('cart-items').innerHTML = `
      <div class="empty-cart">
        <i class="bi bi-bag"></i>
        No items yet.<br>Tap a menu item to add.
      </div>`;
        document.getElementById('cart-total').textContent = '₱0.00';
        return;
      }

      let total = 0;
      document.getElementById('cart-items').innerHTML = keys.map(id => {
        const item = menuItems.find(m => m.id == id);
        const sub = item.price * cart[id];
        total += sub;
        return `
      <div class="cart-item-row">
        ${item.image ? `<img src="${item.image}" style="width:24px; height:24px; object-fit:cover; border-radius:50%;">` : `<span style="font-size:18px; display:inline-block; width:24px; text-align:center;">${item.emoji}</span>`}
        <div style="flex:1; font-size:13px; font-weight:500; color:#333;">${item.name}</div>
        <div style="display:flex; align-items:center; gap:4px;">
          <button class="qty-btn" onclick="changeQty(${id}, -1)">−</button>
          <span style="font-size:13px; min-width:18px; text-align:center; font-weight:600;">${cart[id]}</span>
          <button class="qty-btn" onclick="changeQty(${id}, 1)">+</button>
        </div>
        <div style="font-size:13px; min-width:52px; text-align:right; font-weight:600; color:var(--cafe-amber);">₱${sub}</div>
      </div>`;
      }).join('');

      document.getElementById('cart-total').textContent = '₱' + total.toFixed(2);
    }

    function changeQty(id, delta) {
      const item = menuItems.find(m => m.id == id);
      const newQty = (cart[id] || 0) + delta;

      if (delta > 0 && item && newQty > item.stock) {
        showToast(`⚠ Only ${item.stock} ${item.name} left in stock!`);
        return;
      }

      cart[id] = newQty;
      if (cart[id] <= 0) delete cart[id];
      renderCart();
    }

    function clearCart() {
      cart = {};
      renderCart();
    }

    async function checkout() {
      const keys = Object.keys(cart);
      if (!keys.length) { showToast('⚠ Cart is empty!'); return; }

      // Build cart items for API
      const items = keys.map(id => ({ id: parseInt(id), quantity: cart[id] }));

      try {
        const res = await api('orders.php?action=checkout', { items, payment_method: 'Cash' });
        if (res.success) {
          showToast('✅ ' + res.message);

          clearCart();

          // Reload menu from DB to get updated stock
          await loadMenuFromDB();

          // Reload today's orders from DB for accurate dashboard data
          try {
            const ordRes = await api('orders.php?date=today');
            if (ordRes.success) {
              orders = ordRes.orders.map(o => ({
                id: o.order_number, time: o.time, date: new Date(o.created_at).toDateString(),
                ts: new Date(o.created_at).getTime(), items: o.items || '',
                cartData: null, total: parseFloat(o.total),
                payment: o.payment_method, status: o.status
              }));
            }
          } catch (e) { console.warn('Could not refresh orders:', e); }

          updateDashboard();
          if (document.getElementById('orders-tbody') && currentOrderFilter === 'today') renderOrders();
          renderPOS();

          // Show stock alerts
          if (res.stock_alerts && res.stock_alerts.length > 0) {
            showStockAlert(res.stock_alerts);
          }
        } else {
          showToast('⚠ ' + res.message);
        }
      } catch (err) {
        showToast('⚠ Checkout failed. Check server connection.');
        console.error(err);
      }
    }

    /* ========== STOCK ALERT NOTIFICATIONS (Non-blocking) ========== */
    function ensureNotifContainer() {
      let container = document.getElementById('stock-notif-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'stock-notif-container';
        container.className = 'stock-notif-container';
        document.body.appendChild(container);
      }
      return container;
    }

    function showStockAlert(alerts) {
      const container = ensureNotifContainer();
      const DISPLAY_TIME = 6000; // auto-dismiss after 6 seconds

      for (let i = 0; i < alerts.length; i++) {
        const a = alerts[i];
        const isCritical = a.critical;
        const stockText = isCritical ? 'OUT OF STOCK' : (a.stock + ' remaining (min: ' + a.minStock + ')');
        const titleText = isCritical ? '🚫 Out of Stock' : '⚠️ Low Stock Warning';

        const notif = document.createElement('div');
        notif.className = 'stock-notif' + (isCritical ? ' critical' : '');
        notif.style.position = 'relative';
        notif.style.overflow = 'hidden';
        notif.innerHTML = '<div class="notif-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>'
          + '<div class="notif-body">'
          + '<div class="notif-title">' + titleText + '</div>'
          + '<div class="notif-detail"><strong>' + a.name + '</strong> — ' + stockText + '</div>'
          + '<div class="notif-hint">Tap to dismiss</div>'
          + '</div>'
          + '<div class="notif-progress" style="width:100%;"></div>';

        container.appendChild(notif);

        // Tap/click anywhere on the card to dismiss
        notif.addEventListener('click', function () {
          dismissNotif(notif);
        });

        // Stagger the slide-in animation
        setTimeout(function () {
          notif.classList.add('show');
          // Start the progress bar countdown
          const progressBar = notif.querySelector('.notif-progress');
          progressBar.style.transitionDuration = DISPLAY_TIME + 'ms';
          setTimeout(function () { progressBar.style.width = '0%'; }, 50);
        }, i * 150);

        // Auto-dismiss
        setTimeout(function () {
          dismissNotif(notif);
        }, DISPLAY_TIME + (i * 150));
      }
    }

    function dismissNotif(notif) {
      if (!notif || !notif.parentNode) return;
      notif.classList.remove('show');
      notif.classList.add('removing');
      setTimeout(function () {
        if (notif.parentNode) notif.parentNode.removeChild(notif);
      }, 400);
    }

    /* ========== ORDERS ========== */
    let currentOrderFilter = 'today';

    function setOrderFilter(filter, btn) {
      currentOrderFilter = filter;
      document.querySelectorAll('#order-filter-tabs .cat-pill').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      renderOrders();
    }

    async function renderOrders() {
      const loadingEl = document.getElementById('orders-loading');
      const tableEl = document.getElementById('orders-table');
      if (loadingEl) { loadingEl.style.display = 'block'; tableEl.style.display = 'none'; }

      try {
        const res = await api(`orders.php?date=${currentOrderFilter}`);
        if (!res.success) { if (loadingEl) loadingEl.style.display = 'none'; return; }

        const filterLabels = { today: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' };
        const ordCount = res.total_orders || 0;
        const ordRev = res.total_revenue || 0;
        const ordAvg = ordCount > 0 ? (ordRev / ordCount) : 0;

        document.getElementById('orders-count-badge').textContent = ordCount + ' order' + (ordCount !== 1 ? 's' : '');
        document.getElementById('osum-orders').textContent = ordCount.toLocaleString();
        document.getElementById('osum-revenue').textContent = '₱' + ordRev.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('osum-avg').textContent = '₱' + ordAvg.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('osum-period').textContent = filterLabels[currentOrderFilter] || 'Today';

        document.getElementById('orders-tbody').innerHTML = res.orders.length ? res.orders.map(o => `
          <tr>
            <td style="font-weight:700; color:var(--cafe-brown);">${o.order_number}</td>
            <td style="color:#888; font-size:12px;">${o.date}</td>
            <td style="color:#888; font-size:12px;">${o.time}</td>
            <td style="font-size:12.5px; max-width:240px;">${o.items || '—'}</td>
            <td style="font-weight:600; color:var(--cafe-amber);">₱${parseFloat(o.total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
            <td><span class="badge-cat">${o.payment_method}</span></td>
            <td style="font-size:12px; color:#666;">${o.cashier || '—'}</td>
            <td><span class="badge-success">${o.status}</span></td>
          </tr>
        `).join('') : '<tr><td colspan="8" class="text-center text-muted py-4" style="font-size:13px;">No orders found for this period.</td></tr>';

        // Also sync local orders array for dashboard (today only)
        if (currentOrderFilter === 'today') {
          orders = res.orders.map(o => ({
            id: o.order_number, time: o.time, date: new Date(o.created_at).toDateString(),
            ts: new Date(o.created_at).getTime(), items: o.items || '',
            total: parseFloat(o.total), payment: o.payment_method, status: o.status
          }));
        }

      } catch (err) { console.error('Orders load error:', err); }

      if (loadingEl) { loadingEl.style.display = 'none'; tableEl.style.display = ''; }
    }

    /* ========== PRINT ORDERS REPORT ========== */
    async function printOrders() {
      const filterLabels = { today: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' };
      const label = filterLabels[currentOrderFilter] || 'Today';

      try {
        const res = await api(`orders.php?action=print_summary&date=${currentOrderFilter}`);
        if (!res.success) { showToast('⚠ Could not load print data.'); return; }

        const now = new Date().toLocaleString('en-PH', { dateStyle: 'full', timeStyle: 'short' });

        // Build top items rows
        const topItemsHtml = res.top_items.length ? res.top_items.map((item, i) => `
          <tr>
            <td>${i + 1}</td>
            <td>${item.name}</td>
            <td style="text-align:center;">${item.qty}</td>
            <td style="text-align:right;">₱${parseFloat(item.rev).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
          </tr>`).join('') : '<tr><td colspan="4" style="text-align:center;color:#999;">No items sold</td></tr>';

        // Build payment breakdown
        const paymentsHtml = res.payments.map(p => `
          <tr>
            <td>${p.payment_method}</td>
            <td style="text-align:center;">${p.cnt}</td>
            <td style="text-align:right;">₱${parseFloat(p.rev).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
          </tr>`).join('');

        // Build orders rows
        const ordersHtml = res.orders.map(o => `
          <tr>
            <td>${o.order_number}</td>
            <td>${o.date}</td>
            <td>${o.time}</td>
            <td style="max-width:240px;font-size:11px;">${o.items || '—'}</td>
            <td>${o.cashier || '—'}</td>
            <td>${o.payment_method}</td>
            <td style="text-align:right;">₱${parseFloat(o.total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
          </tr>`).join('');

        const avgOrder = res.summary.orders > 0 ? (res.summary.revenue / res.summary.orders) : 0;

        const printContent = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>ChipiPie Cafe — Order Report (${label})</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size:12px; color:#222; padding:24px 28px; }
    .header { text-align:center; margin-bottom:20px; border-bottom:2px solid #3a2f2d; padding-bottom:14px; }
    .header h1 { font-size:22px; color:#3a2f2d; font-weight:700; }
    .header .subtitle { font-size:13px; color:#666; margin-top:4px; }
    .header .print-time { font-size:11px; color:#999; margin-top:6px; }
    .period-badge { display:inline-block; background:#3a2f2d; color:#fff; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:600; margin-top:8px; }
    .section { margin-bottom:20px; }
    .section-title { font-size:13px; font-weight:700; color:#3a2f2d; border-bottom:1px solid #d9d9d9; padding-bottom:5px; margin-bottom:10px; text-transform:uppercase; letter-spacing:0.05em; }
    .summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
    .summary-card { background:#f9f6f2; border:1px solid #d9d9d9; border-radius:8px; padding:10px 14px; text-align:center; }
    .summary-card .val { font-size:20px; font-weight:700; color:#3a2f2d; }
    .summary-card .lbl { font-size:10px; color:#888; text-transform:uppercase; margin-bottom:3px; }
    table { width:100%; border-collapse:collapse; font-size:11.5px; }
    th { background:#3a2f2d; color:#fff; padding:7px 9px; text-align:left; font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; }
    td { padding:6px 9px; border-bottom:1px solid #f0ece6; }
    tr:last-child td { border-bottom:none; }
    tr:nth-child(even) td { background:#faf8f5; }
    .footer { text-align:center; font-size:10px; color:#aaa; margin-top:24px; border-top:1px solid #d9d9d9; padding-top:10px; }
    @media print {
      body { padding:10px; }
      @page { margin:12mm 14mm; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1>☕ ChipiPie Cafe</h1>
    <div class="subtitle">Point of Sales — Order Report</div>
    <div><span class="period-badge">${res.label}</span></div>
    <div class="print-time">Printed: ${now}</div>
  </div>

  <div class="summary-grid">
    <div class="summary-card">
      <div class="lbl">Total Orders</div>
      <div class="val">${res.summary.orders.toLocaleString()}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Total Revenue</div>
      <div class="val">₱${res.summary.revenue.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
    </div>
    <div class="summary-card">
      <div class="lbl">Avg. Order Value</div>
      <div class="val">₱${avgOrder.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Payment Method Breakdown</div>
    <table>
      <thead><tr><th>Method</th><th>Orders</th><th>Revenue</th></tr></thead>
      <tbody>${paymentsHtml}</tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Top Selling Items</div>
    <table>
      <thead><tr><th>#</th><th>Item</th><th style="text-align:center;">Qty Sold</th><th style="text-align:right;">Revenue</th></tr></thead>
      <tbody>${topItemsHtml}</tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Order Transactions (${res.summary.orders})</div>
    <table>
      <thead><tr><th>Order #</th><th>Date</th><th>Time</th><th>Items</th><th>Cashier</th><th>Payment</th><th style="text-align:right;">Total</th></tr></thead>
      <tbody>${ordersHtml}</tbody>
    </table>
  </div>

  <div class="footer">ChipiPie Cafe POS System &mdash; Confidential &mdash; ${now}</div>

  <script>window.onload = function() { window.print(); };<\/script>
</body>
</html>`;

        const printWin = window.open('', '_blank', 'width=900,height=700');
        printWin.document.write(printContent);
        printWin.document.close();

      } catch (err) {
        showToast('⚠ Print failed. Check connection.');
        console.error(err);
      }
    }

    /* ========== MENU TABLE ========== */
    function renderMenuTable() {
      const q = (document.getElementById('menu-mgmt-search')?.value || '').toLowerCase();
      const filtered = menuItems.filter(m => !q || m.name.toLowerCase().includes(q));

      document.getElementById('menu-tbody').innerHTML = filtered.map(item => {
        const stockBadgeClass = item.stock <= 0 ? 'stock-out' : (item.stock <= item.minStock ? 'stock-low' : 'stock-ok');
        const stockText = item.stock <= 0 ? 'Out' : item.stock + ' / min ' + item.minStock;
        return `
    <tr>
      <td>
        ${item.image ? `<img src="${item.image}" style="width:24px; height:24px; object-fit:cover; border-radius:50%; margin-right:8px; vertical-align:middle;">` : `<span style="font-size:18px; margin-right:8px; vertical-align:middle;">${item.emoji}</span>`}
        <span style="font-weight:500; vertical-align:middle;">${item.name}</span>
      </td>
      <td><span class="badge-cat">${item.category}</span></td>
      <td style="font-weight:600; color:var(--cafe-amber);">₱${item.price}</td>
      <td>
        <span class="stock-badge ${stockBadgeClass}" style="font-size:11px;">${stockText}</span>
        <div style="margin-top:4px; display:flex; align-items:center; gap:4px;">
          <button class="qty-btn" onclick="adjustStock(${item.id}, -1)" title="Decrease stock">−</button>
          <span style="font-size:12px; font-weight:600; min-width:20px; text-align:center;">${item.stock}</span>
          <button class="qty-btn" onclick="adjustStock(${item.id}, 1)" title="Increase stock">+</button>
          <input type="number" min="0" value="${item.stock}" style="width:50px; font-size:11px; border:1px solid var(--cafe-tan); border-radius:5px; padding:2px 4px; text-align:center;" onchange="setStock(${item.id}, this.value)">
        </div>
      </td>
      <td>
        <span class="${item.available ? 'badge-success' : 'badge-pending'}">${item.available ? 'Available' : 'Unavailable'}</span>
      </td>
      <td>
        <button class="btn btn-sm me-1" style="background:var(--cafe-tan); color:var(--cafe-brown); border:none; border-radius:6px; padding:4px 10px; font-size:12px;" onclick="toggleAvail(${item.id})">
          ${item.available ? 'Mark Unavail.' : 'Mark Avail.'}
        </button>
        <button class="btn btn-sm" style="background:#f8d7da; color:#842029; border:none; border-radius:6px; padding:4px 10px; font-size:12px;" onclick="deleteMenuItem(${item.id})">
          Remove
        </button>
      </td>
    </tr>`;
      }).join('');
    }

    async function toggleAvail(id) {
      const res = await api('menu.php?action=toggle', { id });
      if (res.success) {
        const item = menuItems.find(m => m.id == id);
        if (item) item.available = !!res.is_available;
        renderMenuTable();
        showToast(res.message);
      }
    }

    // deleteMenuItem is now defined in the TOKEN-PROTECTED DELETE section above (uses modal + token)

    async function adjustStock(id, delta) {
      const res = await api('menu.php?action=adjust_stock', { id, delta });
      if (res.success) {
        const item = menuItems.find(m => m.id == id);
        if (item) { item.stock = res.stock; item.available = !!res.is_available; }
        renderMenuTable();
      } else {
        showToast('⚠ ' + res.message);
      }
    }

    async function setStock(id, value) {
      const val = parseInt(value);
      if (isNaN(val) || val < 0) return;
      const res = await api('menu.php?action=set_stock', { id, stock: val });
      if (res.success) {
        const item = menuItems.find(m => m.id == id);
        if (item) { item.stock = res.stock; item.available = !!res.is_available; }
        renderMenuTable();
        showToast('Stock updated → ' + res.stock);
      }
    }

    async function addMenuItem() {
      const name = document.getElementById('new-name').value.trim();
      const imageInput = document.getElementById('new-image');
      const category = document.getElementById('new-category').value;
      const price = parseFloat(document.getElementById('new-price').value);
      const stock = parseInt(document.getElementById('new-stock').value) || 0;
      const minStock = parseInt(document.getElementById('new-min-stock').value) || 5;

      if (!name || !price || price <= 0) { showToast('⚠ Please fill in name and a valid price.'); return; }

      // Map category name to ID
      const catMap = { 'Pies': 1, 'Drinks': 2, 'Snacks': 3, 'Add-ons': 4 };
      const categoryId = catMap[category] || 1;

      let imageSrc = null;
      if (imageInput.files && imageInput.files[0]) {
        imageSrc = await new Promise(resolve => {
          const reader = new FileReader();
          reader.onload = e => resolve(e.target.result);
          reader.readAsDataURL(imageInput.files[0]);
        });
      }

      const res = await api('menu.php?action=add', {
        name, category_id: categoryId, price, stock, min_stock: minStock,
        emoji: '🍽️', image: imageSrc
      });

      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
        document.getElementById('new-name').value = '';
        document.getElementById('new-image').value = '';
        document.getElementById('new-price').value = '';
        document.getElementById('new-stock').value = '20';
        document.getElementById('new-min-stock').value = '5';
        await loadMenuFromDB();
        renderMenuTable();
        showToast(`✓ "${name}" added to menu`);
      } else {
        showToast('⚠ ' + res.message);
      }
    }

    /* ========== REPORTS ========== */
    function renderReports() {
      const now = new Date().getTime();
      const todayTS = now - 86400000;
      const weekTS = now - 86400000 * 7;
      const monthTS = now - 86400000 * 30;
      const yearTS = now - 86400000 * 365;

      let vol = {
        today: { cups: 0, food: 0 },
        week: { cups: 0, food: 0 },
        month: { cups: 0, food: 0 },
        year: { cups: 0, food: 0 }
      };

      let revMonth = 0;
      let ordMonth = 0;
      const catTotals = { 'Pies': 0, 'Drinks': 0, 'Snacks': 0, 'Add-ons': 0 };
      let totalItemsMonth = 0;

      let weeklyData = [0, 0, 0, 0, 0, 0, 0];
      let hourlyData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      let monthlyData = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

      orders.forEach(o => {
        if (!o.ts || !o.cartData) return;
        const isToday = o.ts > todayTS;
        const isWeek = o.ts > weekTS;
        const isMonth = o.ts > monthTS;
        const isYear = o.ts > yearTS;

        if (isMonth) { revMonth += o.total; ordMonth++; }

        if (isYear) {
          const m = new Date(o.ts).getMonth();
          monthlyData[m] += o.total || 0;
        }

        if (isWeek) {
          const d = new Date(o.ts).getDay();
          const mappedDay = d === 0 ? 6 : d - 1; // 0=Mon, 6=Sun
          weeklyData[mappedDay] += o.total || 0;
        }

        if (isToday) {
          const h = new Date(o.ts).getHours();
          if (h >= 7 && h <= 18) {
            hourlyData[h - 7] += o.total || 0;
          }
        }

        for (let pid in o.cartData) {
          const product = menuItems.find(m => m.id == pid);
          if (!product) continue;
          const isCup = product.category === 'Drinks';
          const qty = o.cartData[pid];

          if (isYear) {
            if (isCup) vol.year.cups += qty; else vol.year.food += qty;
            if (isMonth) {
              if (isCup) vol.month.cups += qty; else vol.month.food += qty;
              if (catTotals[product.category] !== undefined) {
                catTotals[product.category] += qty;
                totalItemsMonth += qty;
              }
              if (isWeek) {
                if (isCup) vol.week.cups += qty; else vol.week.food += qty;
                if (isToday) {
                  if (isCup) vol.today.cups += qty; else vol.today.food += qty;
                }
              }
            }
          }
        }
      });

      const kpis = document.querySelectorAll('#view-reports .kpi-stat');
      if (kpis.length >= 2) {
        kpis[0].textContent = '₱' + revMonth.toLocaleString();
        kpis[1].textContent = ordMonth.toLocaleString();
      }

      const tbody = document.getElementById('rep-vol-tbody');
      if (tbody) {
        tbody.innerHTML = `
          <tr><td style="font-weight:500;">Today</td><td style="font-weight:600; color:var(--cafe-brown);">${vol.today.cups}</td><td style="font-weight:600; color:var(--cafe-amber);">${vol.today.food}</td></tr>
          <tr><td style="font-weight:500;">This Week</td><td style="font-weight:600; color:var(--cafe-brown);">${vol.week.cups}</td><td style="font-weight:600; color:var(--cafe-amber);">${vol.week.food}</td></tr>
          <tr><td style="font-weight:500;">This Month</td><td style="font-weight:600; color:var(--cafe-brown);">${vol.month.cups}</td><td style="font-weight:600; color:var(--cafe-amber);">${vol.month.food}</td></tr>
          <tr><td style="font-weight:500;">This Year</td><td style="font-weight:600; color:var(--cafe-brown);">${vol.year.cups}</td><td style="font-weight:600; color:var(--cafe-amber);">${vol.year.food}</td></tr>
        `;
      }

      const colors = { 'Pies': '#3a2f2d', 'Drinks': '#838582', 'Snacks': '#b1ada6', 'Add-ons': '#d9d9d9' };
      const cats = Object.keys(catTotals).map(k => ({
        name: k,
        pct: totalItemsMonth ? Math.round((catTotals[k] / totalItemsMonth) * 100) : 0,
        color: colors[k]
      })).filter(c => c.pct > 0);

      document.getElementById('cat-breakdown').innerHTML = cats.map(c => `
    <div class="report-stat">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="width:10px; height:10px; border-radius:50%; background:${c.color}; display:inline-block;"></span>
        <span>${c.name}</span>
      </div>
      <span style="font-weight:700; color:var(--cafe-brown);">${c.pct}%</span>
    </div>
  `).join('') || '<div class="text-muted p-2 text-center">No data this month</div>';

      if (window.categoryChartIns && cats.length > 0) {
        window.categoryChartIns.data.labels = cats.map(c => c.name);
        window.categoryChartIns.data.datasets[0].data = cats.map(c => c.pct);
        window.categoryChartIns.data.datasets[0].backgroundColor = cats.map(c => c.color);
        window.categoryChartIns.update();
      } else if (window.categoryChartIns) {
        window.categoryChartIns.data.labels = ['No Data Yet'];
        window.categoryChartIns.data.datasets[0].data = [1];
        window.categoryChartIns.data.datasets[0].backgroundColor = ['#e9ecef'];
        window.categoryChartIns.update();
      }

      if (window.itemChartIns) {
        const itemSels = {};
        orders.forEach(o => {
          if (!o.cartData) return;
          for (let pid in o.cartData) {
            const m = menuItems.find(x => x.id == pid);
            if (m) itemSels[m.name] = (itemSels[m.name] || 0) + o.cartData[pid];
          }
        });
        const sortedItems = Object.keys(itemSels).map(k => ({ n: k, v: itemSels[k] })).sort((a, b) => b.v - a.v).slice(0, 9);
        const barColors = ['#3a2f2d', '#838582', '#3a2f2d', '#838582', '#3a2f2d', '#838582', '#3a2f2d', '#838582', '#3a2f2d'];
        if (sortedItems.length > 0) {
          window.itemChartIns.data.labels = sortedItems.map(i => i.n);
          window.itemChartIns.data.datasets[0].data = sortedItems.map(i => i.v);
          window.itemChartIns.data.datasets[0].backgroundColor = barColors.slice(0, sortedItems.length);
        } else {
          window.itemChartIns.data.labels = ['No Data Yet'];
          window.itemChartIns.data.datasets[0].data = [0];
        }
        window.itemChartIns.update();
      }

      if (window.salesChartIns) {
        window.salesChartIns.data.datasets[0].data = weeklyData;
        window.salesChartIns.update();
      }

      if (window.hourlyChartIns) {
        window.hourlyChartIns.data.datasets[0].data = hourlyData;
        window.hourlyChartIns.update();
      }

      if (window.monthlyChartIns) {
        window.monthlyChartIns.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        window.monthlyChartIns.data.datasets[0].data = monthlyData;
        window.monthlyChartIns.update();
      }
    }

    /* ========== CHARTS ========== */
    const BROWN = '#3a2f2d';
    const AMBER = '#838582';
    const TAN = '#d9d9d9';
    const LIGHT = '#d9d9d9';
    const MID = '#b1ada6';

    function buildCharts() {
      const gridColor = '#f3ece0';
      const tickColor = '#888';
      const baseFont = { family: "'Segoe UI', system-ui, sans-serif", size: 11 };

      /* Weekly Sales Bar */
      window.salesChartIns = new Chart(document.getElementById('salesChart'), {
        type: 'bar',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
            data: [3200, 4100, 3800, 5200, 6100, 7800, 5400],
            backgroundColor: [AMBER, AMBER, AMBER, AMBER, AMBER, BROWN, AMBER],
            borderRadius: 7,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } }
          },
          scales: {
            y: { grid: { color: gridColor }, ticks: { color: tickColor, font: baseFont, callback: v => '₱' + (v / 1000) + 'k' } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont } }
          }
        }
      });

      /* Category Doughnut */
      window.categoryChartIns = new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
          labels: ['Pies', 'Drinks', 'Snacks', 'Add-ons'],
          datasets: [{ data: [55, 30, 12, 3], backgroundColor: [BROWN, AMBER, MID, TAN], borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
          responsive: true, maintainAspectRatio: false, cutout: '65%',
          plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, color: BROWN, padding: 12, boxWidth: 10 } }
          }
        }
      });

      /* Hourly Line */
      window.hourlyChartIns = new Chart(document.getElementById('hourlyChart'), {
        type: 'line',
        data: {
          labels: ['7am', '8am', '9am', '10am', '11am', '12pm', '1pm', '2pm', '3pm', '4pm', '5pm', '6pm'],
          datasets: [{
            data: [120, 450, 680, 520, 810, 1200, 980, 650, 430, 760, 890, 540],
            borderColor: AMBER, backgroundColor: 'rgba(131,133,130,0.08)', fill: true, tension: 0.4,
            pointRadius: 3, pointBackgroundColor: AMBER
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } }
          },
          scales: {
            y: { grid: { color: gridColor }, ticks: { color: tickColor, font: baseFont, callback: v => '₱' + v } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont, maxRotation: 0 } }
          }
        }
      });

      /* Monthly Line */
      window.monthlyChartIns = new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
          labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
          datasets: [{
            data: [48000, 52000, 49000, 61000, 58000, 75000, 68000, 71000, 66000, 82000],
            borderColor: BROWN, backgroundColor: 'rgba(58,47,45,0.07)', fill: true, tension: 0.4,
            pointRadius: 3, pointBackgroundColor: BROWN
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } }
          },
          scales: {
            y: { grid: { color: gridColor }, ticks: { color: tickColor, font: baseFont, callback: v => '₱' + (v / 1000) + 'k' } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont } }
          }
        }
      });

      /* Item Horizontal Bar */
      window.itemChartIns = new Chart(document.getElementById('itemChart'), {
        type: 'bar',
        indexAxis: 'y',
        data: {
          labels: ['Buko Pie (Whole)', 'Iced Latte', 'Ube Pie Slice', 'Brewed Coffee', 'Hot Chocolate', 'Buko Pie Slice', 'Caramel Apple Pie', 'Cheese Bread', 'Ensaymada'],
          datasets: [{
            data: [42, 38, 35, 34, 28, 24, 20, 18, 15],
            backgroundColor: [BROWN, AMBER, BROWN, AMBER, BROWN, AMBER, BROWN, AMBER, BROWN],
            borderRadius: 5, borderSkipped: false,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { color: gridColor }, ticks: { color: tickColor, font: baseFont } },
            y: { grid: { display: false }, ticks: { color: BROWN, font: { size: 12 } } }
          }
        }
      });
    }

    /* ========== AUTH ========== */
    let chartsBuilt = false;
    let currentUser = null;

    function safeBuildCharts() {
      if (!chartsBuilt) {
        buildCharts();
        chartsBuilt = true;
      }
    }

    async function handleLogin(e) {
      e.preventDefault();
      const u = document.getElementById('login-user').value.trim();
      const p = document.getElementById('login-pass').value.trim();
      const errEl = document.getElementById('login-error');
      errEl.style.display = 'none';

      try {
        const res = await api('auth.php?action=login', { username: u, password: p });
        if (res.success) {
          currentUser = res.user;
          document.getElementById('sidebar-name').textContent = res.user.full_name;
          document.getElementById('sidebar-role').textContent = res.user.role === 'admin' ? 'Administrator' : 'Cashier';
          document.getElementById('sidebar-avatar').textContent = res.user.full_name.charAt(0).toUpperCase();
          await loadMenuFromDB();
          activateApp(res.user.role === 'admin');
        } else {
          errEl.textContent = res.message || 'Invalid credentials.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Connection error. Make sure the server is running.';
        errEl.style.display = 'block';
      }
    }

    /* ========== LOAD MENU FROM DB ========== */
    async function loadMenuFromDB() {
      try {
        const res = await api('menu.php');
        if (res.success) {
          menuItems = res.items.map(i => ({
            id: i.id, name: i.name, category: i.category,
            category_id: i.category_id,
            price: parseFloat(i.price), emoji: i.emoji, image: i.image,
            available: !!parseInt(i.is_available),
            stock: parseInt(i.stock), minStock: parseInt(i.min_stock)
          }));
        }
      } catch (e) { console.error('Failed to load menu:', e); }
    }

    async function activateApp(isAdmin) {
      document.getElementById('login-wrapper').style.display = 'none';
      document.getElementById('register-wrapper').style.display = 'none';
      document.getElementById('app-wrapper').style.display = 'flex';

      const mgmtLabel = document.getElementById('nav-mgmt-label');
      const menuBtn = document.getElementById('nav-menu-btn');
      const reportsBtn = document.getElementById('nav-reports-btn');
      const usersBtn = document.getElementById('nav-users-btn');

      // Load today's orders from DB to populate dashboard
      try {
        const ordRes = await api('orders.php?date=today');
        if (ordRes.success) {
          orders = ordRes.orders.map(o => ({
            id: o.order_number, time: o.time, date: new Date(o.created_at).toDateString(),
            ts: new Date(o.created_at).getTime(), items: o.items || '',
            cartData: null, total: parseFloat(o.total),
            payment: o.payment_method, status: o.status
          }));
        }
      } catch (e) { console.warn('Could not load orders on init:', e); }

      if (!isAdmin) {
        mgmtLabel.style.display = 'none';
        menuBtn.style.display = 'none';
        reportsBtn.style.display = 'none';
        if (usersBtn) usersBtn.style.display = 'none';
        showView('pos', document.querySelectorAll('.nav-item-btn')[1]);
      } else {
        mgmtLabel.style.display = 'block';
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'flex';
        if (usersBtn) usersBtn.style.display = 'flex';
        showView('dashboard', document.querySelectorAll('.nav-item-btn')[0]);
        setTimeout(safeBuildCharts, 80);
      }
      updateDashboard();
    }

    async function logout() {
      try { await api('auth.php?action=logout', {}); } catch (e) { }
      currentUser = null;
      document.getElementById('login-user').value = '';
      document.getElementById('login-pass').value = '';
      document.getElementById('app-wrapper').style.display = 'none';
      document.getElementById('login-wrapper').style.display = 'flex';
    }

    /* ========== REGISTER (Login page) ========== */
    function showRegisterForm() {
      document.getElementById('login-wrapper').style.display = 'none';
      document.getElementById('register-wrapper').style.display = 'flex';
    }
    function showLoginForm() {
      document.getElementById('register-wrapper').style.display = 'none';
      document.getElementById('login-wrapper').style.display = 'flex';
    }

    async function handleRegister(e) {
      e.preventDefault();
      const errEl = document.getElementById('reg-error');
      const sucEl = document.getElementById('reg-success');
      errEl.style.display = 'none'; sucEl.style.display = 'none';
      const data = {
        full_name: document.getElementById('reg-name').value.trim(),
        email: document.getElementById('reg-email').value.trim(),
        username: document.getElementById('reg-user').value.trim(),
        password: document.getElementById('reg-pass').value.trim(),
        role: document.getElementById('reg-role').value
      };
      const confirmPass = document.getElementById('reg-pass-confirm').value.trim();
      if (data.password !== confirmPass) {
        errEl.textContent = 'Passwords do not match.';
        errEl.style.display = 'block'; return;
      }
      try {
        const res = await api('auth.php?action=register', data);
        if (res.success) {
          sucEl.textContent = res.message;
          sucEl.style.display = 'block';
          document.getElementById('reg-submit-btn').disabled = true;
        } else {
          errEl.textContent = res.message;
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Connection error.';
        errEl.style.display = 'block';
      }
    }

    /* ========== USER MANAGEMENT (Admin) ========== */
    async function renderUsers() {
      try {
        const res = await api('auth.php?action=users');
        if (!res.success) return;
        document.getElementById('users-tbody').innerHTML = res.users.map(u => `
          <tr>
            <td style="font-weight:500;">${u.full_name}</td>
            <td><code>${u.username}</code></td>
            <td style="font-size:12px;color:#666;">${u.email || '—'}</td>
            <td><span class="badge-cat">${u.role}</span></td>
            <td><span class="${u.is_active == 1 ? 'badge-success' : 'badge-pending'}">${u.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
            <td>
              <button class="btn btn-sm" style="background:var(--cafe-tan);color:var(--cafe-brown);border:none;border-radius:6px;padding:4px 10px;font-size:12px;" onclick="toggleUser(${u.id})">
                ${u.is_active == 1 ? 'Deactivate' : 'Activate'}
              </button>
            </td>
          </tr>
        `).join('') || '<tr><td colspan="6" class="text-center text-muted py-3">No users found</td></tr>';

        document.getElementById('pending-tbody').innerHTML = res.pending_admin_requests.map(r => `
          <tr>
            <td style="font-weight:500;">${r.full_name}</td>
            <td><code>${r.username}</code></td>
            <td style="font-size:12px;color:#666;">${r.email}</td>
            <td><span class="badge-${r.status === 'approved' ? 'success' : (r.status === 'pending' ? 'pending' : 'danger')}">${r.status}</span></td>
            <td style="font-size:12px;color:#888;">${r.created_at}</td>
            <td>
              ${r.status === 'pending' ? `
                <button class="btn btn-sm" style="background:var(--cafe-brown);color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:12px;" onclick="resendApprovalEmail(${r.id})" title="Re-send the approval link to the owner's Gmail">
                  <i class="bi bi-envelope"></i> Resend to Owner
                </button>
              ` : '<span style="font-size:11px;color:#999;">—</span>'}
            </td>
          </tr>
        `).join('') || '<tr><td colspan="6" class="text-center text-muted py-3">No pending requests</td></tr>';
      } catch (e) { console.error(e); }
    }

    async function resendApprovalEmail(requestId) {
      if (!confirm('Re-send the approval email to the owner? The owner must click the link in their Gmail to approve this admin.')) return;
      const res = await api('auth.php?action=resend_approval', { request_id: requestId });
      showToast(res.success ? '📧 ' + res.message : '⚠ ' + res.message);
    }

    async function toggleUser(userId) {
      const res = await api('auth.php?action=toggle_user', { user_id: userId });
      showToast(res.message);
      renderUsers();
    }

    function showRegisterUserModal() {
      document.getElementById('modal-reg-result').style.display = 'none';
      new bootstrap.Modal(document.getElementById('registerUserModal')).show();
    }

    async function submitRegisterUser() {
      const resEl = document.getElementById('modal-reg-result');
      resEl.style.display = 'none';
      const data = {
        full_name: document.getElementById('modal-reg-name').value.trim(),
        email: document.getElementById('modal-reg-email').value.trim(),
        username: document.getElementById('modal-reg-user').value.trim(),
        password: document.getElementById('modal-reg-pass').value.trim(),
        role: document.getElementById('modal-reg-role').value
      };
      if (!data.full_name || !data.email || !data.username || !data.password) {
        resEl.style.display = 'block'; resEl.style.color = '#842029'; resEl.style.background = '#f8d7da';
        resEl.textContent = 'All fields are required.'; return;
      }
      const confirmPass = document.getElementById('modal-reg-pass-confirm').value.trim();
      if (data.password !== confirmPass) {
        resEl.style.display = 'block'; resEl.style.color = '#842029'; resEl.style.background = '#f8d7da';
        resEl.textContent = 'Passwords do not match.'; return;
      }
      const res = await api('auth.php?action=register', data);
      resEl.style.display = 'block';
      if (res.success) {
        resEl.style.color = '#1a5c2e'; resEl.style.background = '#d4edda';
        resEl.textContent = res.message;
        renderUsers();
      } else {
        resEl.style.color = '#842029'; resEl.style.background = '#f8d7da';
        resEl.textContent = res.message;
      }
    }

    /* ========== TOKEN BYPASS ========== */
    function requestBypassToken() {
      document.getElementById('token-result').style.display = 'none';
      new bootstrap.Modal(document.getElementById('tokenModal')).show();
    }

    async function submitTokenRequest() {
      const btn = document.getElementById('token-submit-btn');
      btn.disabled = true; btn.textContent = 'Requesting...';
      const purpose = document.getElementById('token-purpose').value;
      try {
        const res = await api('token.php?action=request', { purpose });
        const resEl = document.getElementById('token-result');
        resEl.style.display = 'block';
        if (res.success) {
          let html = '';
          if (res.dev_token) {
            // Token available on screen
            html += '<div style="background:#d4edda;padding:16px;border-radius:10px;font-size:13px;color:#1a5c2e;">';
            html += '<div style="font-weight:600;margin-bottom:8px;"><i class="bi bi-check-circle me-1"></i>Token Generated</div>';
            html += '<div style="font-size:12px;color:#555;margin-bottom:12px;">' + res.message + '</div>';
            if (res.is_owner) {
              html += '<div style="background:#fff3cd;padding:8px 12px;border-radius:8px;font-size:12px;color:#856404;margin-bottom:12px;">🔒 General bypass — token also sent to owner for approval</div>';
            }
            html += '<div style="text-align:center;margin:16px 0;">';
            html += '<div style="display:inline-block;background:#3a2f2d;color:#fff;padding:12px 28px;border-radius:10px;font-size:24px;font-weight:700;letter-spacing:4px;font-family:monospace;cursor:pointer;" onclick="navigator.clipboard.writeText(\'' + res.dev_token + '\');showToast(\'✓ Token copied!\')" title="Click to copy">';
            html += res.dev_token;
            html += '</div></div>';
            html += '<div style="font-size:11px;color:#888;text-align:center;">Click the token to copy • Expires: ' + res.expires_at + '</div>';
            html += '</div>';
          } else {
            html += '<div style="background:#d4edda;padding:12px;border-radius:8px;font-size:13px;color:#1a5c2e;">';
            html += '<i class="bi bi-envelope-check me-1"></i>' + res.message;
            html += '</div>';
          }
          resEl.innerHTML = html;
        } else {
          resEl.innerHTML = '<div style="background:#f8d7da;padding:12px;border-radius:8px;font-size:13px;color:#842029;">' + res.message + '</div>';
        }
      } catch (err) {
        const resEl = document.getElementById('token-result');
        resEl.style.display = 'block';
        resEl.innerHTML = '<div style="background:#f8d7da;padding:12px;border-radius:8px;font-size:13px;color:#842029;">Connection error.</div>';
      }
      btn.disabled = false; btn.textContent = 'Request Token';
    }

    /* ========== TOKEN-PROTECTED DELETE ========== */
    let pendingDeleteItemId = null;

    function deleteMenuItem(id) {
      pendingDeleteItemId = id;
      document.getElementById('delete-token-input').value = '';
      document.getElementById('delete-token-error').style.display = 'none';
      new bootstrap.Modal(document.getElementById('deleteTokenModal')).show();
    }

    async function confirmDeleteWithToken() {
      const token = document.getElementById('delete-token-input').value.trim();
      const errEl = document.getElementById('delete-token-error');
      if (!token) { errEl.textContent = 'Please enter a token.'; errEl.style.display = 'block'; return; }
      const btn = document.getElementById('delete-confirm-btn');
      btn.disabled = true;
      const res = await api('menu.php?action=delete', { id: pendingDeleteItemId, token });
      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('deleteTokenModal')).hide();
        showToast('✓ ' + res.message);
        await loadMenuFromDB();
        renderMenuTable();
        renderPOS();
      } else {
        errEl.textContent = res.message;
        errEl.style.display = 'block';
      }
      btn.disabled = false;
    }

    /* ========== INIT ========== */
    window.addEventListener('DOMContentLoaded', () => {
      // Check for existing session
      api('auth.php?action=session').then(res => {
        if (res.success && res.loggedIn) {
          currentUser = res.user;
          document.getElementById('sidebar-name').textContent = res.user.full_name;
          document.getElementById('sidebar-role').textContent = res.user.role === 'admin' ? 'Administrator' : 'Cashier';
          document.getElementById('sidebar-avatar').textContent = res.user.full_name.charAt(0).toUpperCase();
          loadMenuFromDB().then(() => activateApp(res.user.role === 'admin'));
        }
      }).catch(() => { });
    });
  </script>
</body>

</html>