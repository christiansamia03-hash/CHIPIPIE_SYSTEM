<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>ChipiPie Cafe — POS System</title>

  <!-- ===== iOS / Apple Touch Icon ===== -->
  <!-- 180×180 for iPhone Retina & iPad -->
  <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">

  <!-- ===== Android / PWA Icons ===== -->
  <link rel="icon" type="image/png" sizes="512x512" href="assets/img/icon-512.png">
  <link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
  <link rel="icon" type="image/png" sizes="144x144" href="assets/img/icon-144.png">
  <link rel="icon" type="image/png" sizes="96x96"   href="assets/img/icon-96.png">
  <link rel="icon" type="image/png" sizes="72x72"   href="assets/img/icon-72.png">
  <link rel="icon" type="image/png" sizes="48x48"   href="assets/img/icon-48.png">

  <!-- ===== Web App Manifest (Android "Add to Home Screen" + PWA) ===== -->
  <link rel="manifest" href="site.webmanifest">

  <!-- ===== Theme Colors ===== -->
  <!-- Android Chrome toolbar & status bar -->
  <meta name="theme-color" content="#3a2918">
  <!-- iOS Safari status bar style (black-translucent shows under notch) -->
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <!-- Enable full-screen standalone mode when added to iOS home screen -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="ChipiPie">
  <!-- Windows tiles -->
  <meta name="msapplication-TileImage" content="assets/img/icon-144.png">
  <meta name="msapplication-TileColor" content="#3a2918">

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

    /* ====== PIZZA HOUSE THEME ====== */
    body.pizza-theme {
      --cafe-brown:   #8b3a0f;   /* deep terracotta */
      --cafe-amber:   #c4601a;   /* warm muted amber */
      --cafe-cream:   #f5f2ee;   /* warm stone — soft on the eye */
      --cafe-tan:     #ddd0c0;   /* muted warm border */
      --cafe-light:   #f5f2ee;
      --cafe-sidebar: #3d1c00;
      --cafe-gold:    #c4601a;
      background: #f5f2ee;
    }
    body.pizza-theme .sidebar {
      /* gradient handled by .sidebar::after pseudo-element overlay */
      border-right-color: #5c2a00 !important;
    }
    body.pizza-theme .sidebar .brand-name { color: #e8a87c !important; }
    body.pizza-theme .sidebar .nav-link   { color: rgba(255,255,255,0.72) !important; }
    body.pizza-theme .sidebar .nav-link.active,
    body.pizza-theme .sidebar .nav-link:hover {
      background: rgba(255,255,255,0.09) !important;
      border-left-color: #e8a87c !important;
      color: #fff !important;
    }
    body.pizza-theme .top-nav {
      border-bottom-color: #5c2a00 !important;
    }
    body.pizza-theme .top-nav .user-info,
    body.pizza-theme .top-nav .view-title,
    body.pizza-theme .top-nav i { color: #fff !important; }
    /* Branch switcher (Sliding Toggle) */
    .branch-switcher-container {
      position: relative;
      display: flex;
      border-bottom: 2px solid var(--cafe-tan);
      background: #f5f1ee;
    }
    #branch-slider {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 50%;
      background: linear-gradient(135deg, #3a2f2d 0%, #5c4033 100%);
      transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
      z-index: 1;
    }
    #branch-slider::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #8b3a0f 0%, #6b2d0a 100%);
      opacity: 0;
      transition: opacity 1.5s ease;
      z-index: -1;
    }
    body.pizza-theme #branch-slider {
      transform: translateX(100%);
    }
    body.pizza-theme #branch-slider::after {
      opacity: 1;
    }
    #branch-btn-coffee, #branch-btn-pizza {
      flex: 1;
      padding: 11px 0;
      font-weight: 700;
      font-size: 13px;
      border: none;
      cursor: pointer;
      letter-spacing: 0.04em;
      background: transparent;
      position: relative;
      z-index: 2;
      transition: color 0.3s;
    }
    #branch-btn-coffee { color: #fff; }
    #branch-btn-pizza { color: #9ca3af; }

    body.pizza-theme #branch-btn-pizza {
      color: #fff !important;
    }
    body.pizza-theme #branch-btn-coffee {
      color: #9a7a68 !important;
    }
    body.pizza-theme .pos-cat-shortcut-bar {
      background: #f5f2ee;
      border-bottom-color: #ddd0c0;
    }

    /* pos-cat-shortcut-btn.active pizza gradient handled by ::after overlay */

    /* pos-supergroup-header pizza gradient handled by ::after overlay */
    body.pizza-theme .pos-supergroup-header {
      color: #fff !important;
    }
    body.pizza-theme .pos-supergroup-header i { color: #e8c5a0 !important; }
    body.pizza-theme .pos-supergroup-header .sg-chevron { color: #e8c5a0 !important; }
    body.pizza-theme .product-card {
      border-color: #e6ddd2;
      background: #ffffff;
    }
    body.pizza-theme .product-card:hover {
      border-color: #c4601a;
      box-shadow: 0 4px 18px rgba(139,58,15,0.10);
    }
    body.pizza-theme .product-card.out-of-stock,
    body.pizza-theme .product-card.out-of-stock:hover {
      border-color: #dc3545 !important;
      transform: none !important;
      box-shadow: none !important;
      opacity: 0.5 !important;
      cursor: not-allowed !important;
    }
    body.pizza-theme .prod-price { color: #c4601a !important; }
    body.pizza-theme .prod-name  { color: #3d1c00 !important; }
    /* cart-header keeps coffee styling in pizza mode — no override */
    /* btn-cafe-primary pizza gradient handled by ::after overlay */
    body.pizza-theme .search-input:focus {
      border-color: #c4601a !important;
      box-shadow: 0 0 0 3px rgba(196,96,26,0.12) !important;
    }
    body.pizza-theme .pos-subcat-btn {
      color: #8b3a0f;
      border-color: #ddd0c0;
    }
    /* pos-subcat-btn.active pizza gradient handled by ::after overlay */
    body.pizza-theme .pos-subcat-btn.active {
      border-color: #8b3a0f;
      color: #fff;
    }

    /* ====== GRADIENT OVERLAY TRANSITIONS (Buttons & Headers) ====== */
    /* Base: position:relative + isolation so ::after sits behind content */
    .pos-cat-shortcut-btn.active,
    .pos-supergroup-header,
    .pos-subcat-btn.active,
    .top-nav {
      position: relative;
      overflow: hidden;
      isolation: isolate;
    }

    /* Pizza-coloured gradient overlays — z-index:-1 keeps them BEHIND text */
    .pos-cat-shortcut-btn.active::after,
    .pos-supergroup-header::after,
    .pos-subcat-btn.active::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #8b3a0f, #6b2d0a);
      opacity: 0;
      transition: opacity 1.5s ease;
      pointer-events: none;
      z-index: -1;
      border-radius: inherit;
    }
    .pos-supergroup-header::after {
      background: linear-gradient(135deg, #6b2d0a 0%, #3d1c00 100%);
    }
    .top-nav::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #7a3210 0%, #5c2a00 100%);
      opacity: 0;
      transition: opacity 1.5s ease;
      pointer-events: none;
      z-index: -1;
      border-radius: inherit;
    }



    /* Show pizza overlays when pizza-theme is active */
    body.pizza-theme .pos-cat-shortcut-btn.active::after,
    body.pizza-theme .pos-supergroup-header::after,
    body.pizza-theme .pos-subcat-btn.active::after,
    body.pizza-theme .top-nav::after { 
      opacity: 1; 
    }

    /* ====== SMOOTH THEME TRANSITIONS ====== */
    /* Only color/background properties animate — layout stays instant */
    body,
    .sidebar,
    .sidebar .sidebar-brand,
    .cart-header,
    #branch-btn-coffee,
    #branch-btn-pizza,
    .pos-supergroup-header,
    .pos-cat-shortcut-btn,
    .pos-cat-shortcut-btn.active,
    .pos-subcat-btn,
    .kpi-card {
      transition:
        background 1.5s ease,
        background-color 1.5s ease,
        border-color 1.5s ease,
        color 1.5s ease;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      touch-action: manipulation;
    }

    html, body {
      background: var(--cafe-cream);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      overflow: hidden;
      height: 100vh;
      touch-action: manipulation;
      -webkit-touch-callout: none;
      -webkit-text-size-adjust: 100%;
      text-size-adjust: 100%;
    }

    button, .btn, .cat-pill, .nav-item-btn, .qty-btn, .product-card {
      -webkit-user-select: none;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
      touch-action: manipulation;
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
      background: linear-gradient(175deg, #4a3728 0%, #3a2918 35%, #2e1f10 65%, #1a1008 100%);
      display: flex;
      flex-direction: column;
      padding: 0;
      overflow: hidden;
      position: relative;
    }
    /* Pizza gradient overlay — fades in on top of the coffee gradient */
    .sidebar::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(175deg, #7a3a12 0%, #5c2a00 28%, #3d1c00 60%, #1e0e00 100%);
      opacity: 0;
      transition: opacity 1.5s ease;
      pointer-events: none;
      z-index: 0;
    }
    body.pizza-theme .sidebar::after { opacity: 1; }
    /* Keep all sidebar content above the overlay */
    .sidebar > * { position: relative; z-index: 1; }

    .sidebar-brand {
      padding: 20px 20px 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .sidebar-brand .brand-icon {
      width: 64px;
      height: 64px;
      margin-bottom: 10px;
    }
    .sidebar-brand .brand-icon img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      border-radius: 50%;
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

    .nav-link, .nav-item-btn {
      transition: background 1.5s ease, background-color 1.5s ease, border-left-color 1.5s ease, color 1.5s ease;
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

    .mobile-only-nav-item {
      display: none !important;
    }
    .mobile-topbar-actions {
      display: none !important;
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

    /* POS view needs flex layout, not block */
    #view-pos {
      display: none;
    }
    #view-pos.active {
      display: flex;
    }

    .pos-view-container {
      padding: 0 !important;
      position: relative;
      height: calc(100vh - 56px);
      height: calc(100dvh - 56px);
      overflow: hidden;
      flex-direction: row;
      width: 100%;
    }

    .pos-menu-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-width: 0;
      position: relative;
    }

    .pos-scroll-area {
      flex: 1;
      overflow-y: auto;
      padding: 16px;
      -webkit-overflow-scrolling: touch;
    }

    .pos-cart-col {
      width: 320px;
      min-width: 290px;
      flex-shrink: 0;
      height: 100%;
      display: flex;
      flex-direction: column;
      border-left: 1px solid var(--cafe-tan);
      background: #fff;
    }

    .pos-mobile-nav-tabs,
    .pos-mobile-float-bar,
    .pos-cart-back-btn {
      display: none !important;
    }

    /* KPI CARDS */
    .kpi-card {
      background: #fff;
      border-radius: 16px;
      border: 1px solid var(--cafe-tan);
      padding: 20px 22px;
    }

    /* Equal-height dashboard stat cards only */
    .kpi-colored {
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .kpi-stat {
      font-size: 34px;
      font-weight: 800;
      color: var(--cafe-brown);
      line-height: 1.15;
    }

    .kpi-label {
      font-size: 11.5px;
      color: #888;
      margin-bottom: 6px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.07em;
    }

    .kpi-trend {
      font-size: 12px;
      margin-top: 5px;
    }

    /* Colored KPI card variants */
    .kpi-colored { border: none; box-shadow: 0 6px 24px rgba(0,0,0,0.14); }
    .kpi-colored .kpi-label,
    .kpi-colored .kpi-stat,
    .kpi-colored .kpi-trend { color: rgba(255,255,255,0.95) !important; }
    .kpi-colored .text-muted { color: rgba(255,255,255,0.72) !important; }
    .kc-blue   { background: linear-gradient(135deg,#2563eb,#1d4ed8); }
    .kc-purple { background: linear-gradient(135deg,#7c3aed,#6d28d9); }
    .kc-green  { background: linear-gradient(135deg,#059669,#047857); }
    .kc-orange { background: linear-gradient(135deg,#f59e0b,#b45309); }
    .kc-red    { background: linear-gradient(135deg,#dc2626,#b91c1c); }

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
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
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
      min-height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
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
      padding: 8px 18px;
      border-radius: 22px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border: 1.5px solid var(--cafe-tan);
      background: #fff;
      color: var(--cafe-brown);
      transition: all 0.12s;
      white-space: nowrap;
    }

    .cat-pill:hover {
      border-color: var(--cafe-amber);
    }

    .cat-pill.active {
      background: var(--cafe-brown);
      border-color: var(--cafe-brown);
      color: #fff;
    }

    /* MENU MANAGEMENT TOOLBAR & RESPONSIVE TABLE */
    .menu-mgmt-toolbar {
      margin-bottom: 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .menu-mgmt-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      width: 100%;
    }
    .menu-mgmt-search-wrap {
      flex: 1;
      max-width: 320px;
      position: relative;
    }
    .menu-mgmt-search-wrap .search-input {
      width: 100%;
      height: 38px;
    }
    .menu-mgmt-add-btn {
      width: auto !important;
      height: 38px !important;
      padding: 0 18px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      border-radius: 9px !important;
      font-size: 13.5px !important;
      font-weight: 700 !important;
    }
    .menu-mgmt-tabs-scroll {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      flex-wrap: nowrap;
      padding: 2px 2px 6px 2px;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;
    }
    .menu-mgmt-tabs-scroll::-webkit-scrollbar {
      height: 4px;
    }
    .menu-mgmt-tabs-scroll::-webkit-scrollbar-thumb {
      background: var(--cafe-tan);
      border-radius: 4px;
    }
    .menu-table-card {
      padding: 14px 16px;
      overflow: hidden;
    }
    .table-responsive-wrapper {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .table-responsive-wrapper::-webkit-scrollbar {
      height: 5px;
    }
    .table-responsive-wrapper::-webkit-scrollbar-thumb {
      background: var(--cafe-tan);
      border-radius: 4px;
    }
    .menu-mgmt-table {
      width: 100%;
      min-width: 660px;
    }
    .btn-menu-action {
      border: none !important;
      border-radius: 6px !important;
      padding: 4px 8px !important;
      font-size: 11.5px !important;
      font-weight: 600 !important;
      line-height: 1.3 !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
      white-space: nowrap !important;
      transition: opacity 0.15s ease;
    }
    .btn-menu-action:hover {
      opacity: 0.88;
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
      flex-shrink: 0;
    }

    /* POS category section headers */
    .pos-cat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0 8px 0;
      margin-bottom: 10px;
      border-bottom: 2px solid var(--cafe-tan);
      font-size: 15px;
      font-weight: 700;
      color: var(--cafe-brown);
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    /* Super-group header: COFFEE / NON-COFFEE / SNACK */
    .pos-supergroup-header {
      display: flex;
      align-items: center;
      padding: 10px 14px;
      margin-bottom: 12px;
      background: var(--cafe-brown);
      color: #fff;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      position: relative;
      isolation: isolate;
    }

    /* Sub-category header: Hot Coffee / Iced Coffee etc. */
    .pos-subcat-header {
      font-size: 13px;
      font-weight: 700;
      color: var(--cafe-amber);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 6px 8px 8px 4px;
      margin-bottom: 4px;
      border-bottom: 1.5px solid var(--cafe-tan);
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      border-radius: 6px;
      user-select: none;
      transition: background 0.15s;
    }
    .pos-subcat-header:hover {
      background: rgba(131,133,130,0.08);
    }
    .pos-subcat-header .subcat-chevron {
      font-size: 14px;
      transition: transform 0.25s ease;
      color: var(--cafe-tan);
      flex-shrink: 0;
    }
    .pos-subcat-header.collapsed .subcat-chevron {
      transform: rotate(-90deg);
    }
    .pos-subcat-items {
      overflow: hidden;
      transition: max-height 0.3s ease, opacity 0.25s ease;
      max-height: 2000px;
      opacity: 1;
    }
    .pos-subcat-items.collapsed {
      max-height: 0;
      opacity: 0;
    }
    /* Supergroup header also clickable */
    .pos-supergroup-header {
      cursor: pointer;
      user-select: none;
    }
    .pos-supergroup-header:hover {
      background: #4a3b38;
    }
    .pos-supergroup-header .sg-chevron {
      font-size: 15px;
      transition: transform 0.25s ease;
      margin-left: auto;
      opacity: 0.7;
    }
    .pos-supergroup-header.collapsed .sg-chevron {
      transform: rotate(-90deg);
    }
    .pos-supergroup-body {
      overflow: hidden;
      transition: max-height 0.35s ease, opacity 0.3s ease;
      max-height: 5000px;
      opacity: 1;
    }
    .pos-supergroup-body.collapsed {
      max-height: 0;
      opacity: 0;
    }

    /* CATEGORY SHORTCUT BAR */
    .pos-cat-shortcut-bar {
      display: flex;
      padding: 10px 16px;
      background: #fff;
      border-bottom: 1.5px solid var(--cafe-tan);
      flex-shrink: 0;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
    }
    .pos-cat-shortcut-bar::-webkit-scrollbar { display: none; }
    #coffee-shortcuts {
      display: flex;
      width: 100%;
      min-width: 350px;
      flex-shrink: 0;
    }
    #pizza-shortcuts {
      display: flex;
      width: 100%;
      min-width: 750px;
      flex-shrink: 0;
    }
    .shortcut-group {
      display: flex;
      position: relative;
      background: #fff;
      border-radius: 8px;
      border: 1.5px solid var(--cafe-tan);
      overflow: hidden;
    }
    .shortcut-slider {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      background: var(--cafe-brown);
      transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1), width 0.3s cubic-bezier(0.25, 1, 0.5, 1);
      z-index: 1;
      pointer-events: none;
    }
    .shortcut-slider::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #8b3a0f, #6b2d0a);
      opacity: 0;
      transition: opacity 1.5s ease;
      z-index: -1;
    }
    body.pizza-theme .shortcut-slider::after {
      opacity: 1;
    }
    .pos-cat-shortcut-btn {
      flex: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 8px 10px;
      border: none;
      border-right: 1.5px solid var(--cafe-tan);
      background: transparent;
      color: #222;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      min-width: 0;
      position: relative;
      z-index: 2;
      transition: color 0.2s;
    }
    .pos-cat-shortcut-btn:last-child { border-right: none; }
    .pos-cat-shortcut-btn:not(.active):hover {
      color: #222;
    }
    .pos-cat-shortcut-btn.active {
      color: #fff;
    }
    /* Sub-category button bar inside each supergroup */
    .pos-subcat-btn-bar {
      display: flex;
      margin-bottom: 12px;
      border: 1.5px solid var(--cafe-tan);
      border-radius: 8px;
      overflow: hidden;
    }
    .pos-subcat-btn {
      flex: 1;
      padding: 7px 10px;
      border: none;
      border-right: 1.5px solid var(--cafe-tan);
      background: #fff;
      color: var(--cafe-brown);
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      transition: background 0.15s, color 0.15s;
      user-select: none;
      text-align: center;
    }
    .pos-subcat-btn:last-child { border-right: none; }
    .pos-subcat-btn:hover { background: #faf8f5; }
    .pos-subcat-btn.active {
      background: var(--cafe-amber);
      color: #fff;
      border-right-color: var(--cafe-amber);
    }

    /* ORDER CARDS (Orders view) */
    .order-card {
      border: 1.5px solid var(--cafe-tan);
      border-radius: 10px;
      margin-bottom: 10px;
      overflow: hidden;
      background: #fff;
      transition: box-shadow 0.15s;
    }
    .order-card:hover { box-shadow: 0 2px 12px rgba(58,47,45,0.08); }
    .order-card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      cursor: pointer;
      user-select: none;
      background: #faf8f5;
      transition: background 0.12s;
    }
    .order-card-header:hover { background: #f3ede6; }
    .order-card-num { font-weight: 700; font-size: 14px; color: var(--cafe-brown); min-width: 52px; }
    .order-card-meta { flex: 1; font-size: 12px; color: #888; }
    .order-card-total { font-weight: 700; font-size: 14px; color: var(--cafe-amber); min-width: 72px; text-align: right; }
    .order-card-chevron { font-size: 13px; color: #bbb; transition: transform 0.22s; }
    .order-card.open .order-card-chevron { transform: rotate(180deg); }
    .order-card-body { display: none; padding: 10px 16px 14px; border-top: 1px solid var(--cafe-tan); }
    .order-card.open .order-card-body { display: block; }
    .order-item-line { display: flex; font-size: 13px; padding: 5px 0; border-bottom: 1px solid #f5f0ea; color: #333; }
    .order-item-line:last-child { border-bottom: none; }

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
      background: #5c3d2e;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: background 1.5s ease, background-color 1.5s ease;
    }
    body.pizza-theme .btn-cafe-primary {
      background: #8b3a0f;
    }
    .btn-cafe-primary:hover {
      background: #4a2509;
    }
    body.pizza-theme .btn-cafe-primary:hover {
      background: #6b2d0a;
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

    /* SIZE SELECTION MODAL */
    .size-modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.45);
      z-index: 10002;
      display: none;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.15s ease;
    }
    .size-modal-overlay.show {
      display: flex;
    }
    .size-modal-card {
      background: #fff;
      border-radius: 18px;
      width: 92%;
      max-width: 380px;
      padding: 28px 24px 24px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      text-align: center;
      transform: scale(0.9);
      opacity: 0;
      transition: transform 0.25s cubic-bezier(0.22,1,0.36,1), opacity 0.2s ease;
    }
    .size-modal-overlay.show .size-modal-card {
      transform: scale(1);
      opacity: 1;
    }
    .size-modal-card .sm-title {
      font-size: 16px;
      font-weight: 700;
      color: var(--cafe-brown);
      margin-bottom: 4px;
    }
    .size-modal-card .sm-subtitle {
      font-size: 12px;
      color: #888;
      margin-bottom: 18px;
    }
    .size-btn-grid {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }
    .size-btn {
      flex: 1;
      min-width: 80px;
      max-width: 120px;
      padding: 14px 10px;
      border: 2px solid var(--cafe-tan);
      border-radius: 12px;
      background: #fff;
      cursor: pointer;
      transition: all 0.15s ease;
      text-align: center;
    }
    .size-btn:hover {
      border-color: var(--cafe-amber);
      background: #faf8f5;
    }
    .size-btn:active {
      transform: scale(0.95);
    }
    .size-btn .size-label {
      font-size: 15px;
      font-weight: 700;
      color: var(--cafe-brown);
      display: block;
      margin-bottom: 4px;
    }
    .size-btn .size-price {
      font-size: 14px;
      font-weight: 600;
      color: var(--cafe-amber);
    }
    .size-modal-cancel {
      background: none;
      border: none;
      color: #999;
      font-size: 13px;
      cursor: pointer;
      padding: 8px 20px;
      border-radius: 8px;
      transition: color 0.15s, background 0.15s;
    }
    .size-modal-cancel:hover {
      color: var(--cafe-brown);
      background: #f5f2ef;
    }

    /* VOID / REFUND SELECT TABLE */
    .void-circle {
      width: 24px; height: 24px;
      border: 2.5px solid #d1d5db;
      border-radius: 50%;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: #fff;
      transition: border-color 0.15s, background 0.15s;
    }
    .void-circle:hover { border-color: #dc2626; }
    .void-circle.selected {
      background: #dc2626;
      border-color: #dc2626;
    }
    .void-circle.selected::after {
      content: '';
      width: 9px; height: 9px;
      background: #fff;
      border-radius: 50%;
      display: block;
    }
    .void-row.selected td { background: #fff5f5; }
    .void-row { cursor: pointer; transition: background 0.12s; }
    .void-row:hover td { background: #fef2f2; }

    /* VOID AUTH MODAL */
    .void-modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.55);
      z-index: 11000;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .void-modal-overlay.show { display: flex; }
    .void-modal-card {
      background: #fff;
      border-radius: 20px;
      width: 94%;
      max-width: 480px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .void-modal-header {
      background: linear-gradient(135deg,#dc2626,#b91c1c);
      color: #fff;
      padding: 18px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .void-modal-header h5 { margin: 0; font-size: 16px; font-weight: 700; }
    .void-modal-body { padding: 22px; }
    .void-step-bar {
      display: flex;
      gap: 6px;
      margin-bottom: 18px;
    }
    .void-step {
      flex: 1;
      height: 4px;
      border-radius: 2px;
      background: #f3f4f6;
      transition: background 0.3s;
    }
    .void-step.active { background: #dc2626; }

    /* ---- Adj Radio Circles ---- */
    .adj-radio {
      width: 22px; height: 22px; border-radius: 50%;
      border: 2px solid #d1d5db; display: inline-flex;
      align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.18s; margin: auto;
      position: relative;
    }
    .adj-radio:hover { border-color: #9ca3af; transform: scale(1.1); }
    .adj-radio::after {
      content: ''; width: 9px; height: 9px; border-radius: 50%;
      background: transparent; transition: background 0.18s;
    }
    .adj-void-radio:hover { border-color: #dc2626; }
    .adj-void-sel { border-color: #dc2626 !important; background: #dc2626 !important; }
    .adj-void-sel::after { background: #fff !important; }
    .adj-ref-radio:hover { border-color: #1d4ed8; }
    .adj-ref-sel { border-color: #1d4ed8 !important; background: #1d4ed8 !important; }
    .adj-ref-sel::after { background: #fff !important; }
    .adj-auth-panel { border: 1.5px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
    .adj-panel-hdr { padding: 16px 18px; color: #fff; background: linear-gradient(135deg,#dc2626,#b91c1c); }
    @media(max-width:1024px){
      .adj-split { grid-template-columns: 1fr !important; gap: 12px !important; }
    }


    .pay-modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 10003;
      display: none;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.15s ease;
    }
    .pay-modal-overlay.show { display: flex; }
    .pay-modal-card {
      background: #fff;
      border-radius: 20px;
      width: 92%;
      max-width: 420px;
      box-shadow: 0 24px 70px rgba(0,0,0,0.35);
      overflow: hidden;
      transform: scale(0.92);
      opacity: 0;
      transition: transform 0.28s cubic-bezier(0.22,1,0.36,1), opacity 0.22s ease;
    }
    .pay-modal-overlay.show .pay-modal-card {
      transform: scale(1);
      opacity: 1;
    }
    .pay-modal-header {
      background: var(--cafe-brown);
      color: #fff;
      padding: 18px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .pay-modal-header h5 {
      font-size: 16px;
      font-weight: 700;
      margin: 0;
    }
    .pay-modal-header .pm-close {
      background: none;
      border: none;
      color: rgba(255,255,255,0.7);
      font-size: 20px;
      cursor: pointer;
      line-height: 1;
      padding: 0 4px;
      transition: color 0.15s;
    }
    .pay-modal-header .pm-close:hover { color: #fff; }
    .pay-modal-body { padding: 22px; }
    .pay-method-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
    .pay-method-btn {
      flex: 1;
      padding: 14px 10px;
      border: 2.5px solid var(--cafe-tan);
      border-radius: 14px;
      background: #fff;
      cursor: pointer;
      text-align: center;
      transition: all 0.18s ease;
      font-weight: 700;
      font-size: 13px;
      color: var(--cafe-brown);
    }
    .pay-method-btn i { display: block; font-size: 22px; margin-bottom: 6px; }
    .pay-method-btn:hover { border-color: var(--cafe-amber); background: #faf8f5; }
    .pay-method-btn.active {
      border-color: var(--cafe-brown);
      background: var(--cafe-brown);
      color: #fff;
    }
    .pay-amount-due {
      background: #f5f2ef;
      border-radius: 12px;
      padding: 14px 18px;
      text-align: center;
      margin-bottom: 18px;
    }
    .pay-amount-due .pad-label {
      font-size: 11px;
      font-weight: 600;
      color: #888;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 4px;
    }
    .pay-amount-due .pad-value {
      font-size: 28px;
      font-weight: 800;
      color: var(--cafe-brown);
      line-height: 1.1;
    }
    .pay-section { margin-bottom: 16px; }
    .pay-section label {
      font-size: 12px;
      font-weight: 600;
      color: var(--cafe-brown);
      display: block;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .pay-input {
      width: 100%;
      border: 2px solid var(--cafe-tan);
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 20px;
      font-weight: 700;
      color: var(--cafe-brown);
      outline: none;
      transition: border-color 0.15s;
      text-align: right;
    }
    .pay-input:focus { border-color: var(--cafe-amber); }
    .pay-change-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #d4edda;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 16px;
    }
    .pay-change-row.insufficient {
      background: #f8d7da;
    }
    .pay-change-row .pcr-label {
      font-size: 12px;
      font-weight: 600;
      color: #1a5c2e;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .pay-change-row.insufficient .pcr-label { color: #842029; }
    .pay-change-row .pcr-value {
      font-size: 20px;
      font-weight: 800;
      color: #1a5c2e;
    }
    .pay-change-row.insufficient .pcr-value { color: #842029; }
    .pay-gcash-info {
      background: #e8f4fd;
      border: 2px solid #2196f3;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 18px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }
    .pay-gcash-info i { color: #2196f3; font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .pay-gcash-info .pgi-text { font-size: 13px; color: #1565c0; font-weight: 500; line-height: 1.4; }
    .pay-gcash-info .pgi-text strong { display: block; margin-bottom: 2px; }
    .pay-confirm-btn {
      width: 100%;
      padding: 15px;
      border: none;
      border-radius: 12px;
      background: var(--cafe-brown);
      color: #fff;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.15s, transform 0.1s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .pay-confirm-btn:hover { background: #4a2509; }
    .pay-confirm-btn:active { transform: scale(0.98); }
    .pay-confirm-btn:disabled {
      background: var(--cafe-tan);
      color: #aaa;
      cursor: not-allowed;
      transform: none;
    }
    .pay-confirm-btn.gcash-btn {
      background: #2196f3;
    }
    .pay-confirm-btn.gcash-btn:hover { background: #1565c0; }
    .pay-cancel-link {
      display: block;
      text-align: center;
      margin-top: 12px;
      font-size: 13px;
      color: #999;
      cursor: pointer;
      padding: 6px;
      border-radius: 8px;
      transition: color 0.15s, background 0.15s;
    }
    .pay-cancel-link:hover { color: var(--cafe-brown); background: #f5f2ef; }

    /* GCASH PAYMONGO SPINNER */
    @keyframes gcash-spin {
      to { transform: rotate(360deg); }
    }
    .gcash-spinner {
      display: inline-block;
      width: 16px; height: 16px;
      border: 2.5px solid rgba(0,112,243,0.2);
      border-top-color: #0070f3;
      border-radius: 50%;
      animation: gcash-spin 0.7s linear infinite;
      flex-shrink: 0;
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
      overflow-y: auto;
      padding: 24px 16px;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      width: 100%;
      max-width: 400px;
      padding: 36px 32px;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.2);
      margin: auto;
    }

    .register-card {
      max-width: 620px;
      padding: 28px 36px;
    }

    @media (max-width: 576px) {
      .register-card {
        padding: 22px 20px;
      }
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
       RESPONSIVE — TABLET / IPAD LANDSCAPE (769px to 1200px)
       ====================================================== */
    @media (min-width: 769px) and (max-width: 1200px) {
      .sidebar {
        width: 195px !important;
        min-width: 195px !important;
      }
      .sidebar-brand {
        padding: 16px 10px 14px 10px !important;
      }
      .sidebar-brand h5 {
        font-size: 14px !important;
      }
      .sidebar-brand small {
        font-size: 9.5px !important;
      }
      .sidebar-brand .brand-icon {
        width: 48px !important;
        height: 48px !important;
      }
      .nav-item-btn {
        padding: 8px 10px !important;
        font-size: 12px !important;
        gap: 8px !important;
      }
      .nav-item-btn i {
        font-size: 15px !important;
      }
      .sidebar-footer {
        padding: 10px 10px !important;
      }
      .cashier-avatar {
        width: 32px !important;
        height: 32px !important;
        font-size: 13px !important;
      }
      .cashier-name {
        font-size: 11.5px !important;
      }
      .cashier-role {
        font-size: 9.5px !important;
      }
      .top-bar {
        padding: 10px 16px !important;
      }
      .top-bar h4 {
        font-size: 16px !important;
      }
      .top-bar-time {
        font-size: 12px !important;
      }
      .view {
        padding: 14px 16px !important;
      }
      .kpi-card {
        padding: 14px 16px !important;
      }
      .cat-pill {
        padding: 6px 14px !important;
        font-size: 12.5px !important;
      }
    }

    /* ======================================================
       RESPONSIVE — TABLETS & MOBILE (max-width: 1024px)
       ====================================================== */
    @media (max-width: 1024px) {
      :root {
        --mobile-top-bar-h: 46px;
        --mobile-nav-h: calc(58px + env(safe-area-inset-bottom, 0px));
      }

      html, body {
        overflow: hidden !important;
        height: 100% !important;
        height: 100dvh !important;
        width: 100% !important;
        width: 100dvw !important;
        margin: 0 !important;
        padding: 0 !important;
        position: fixed !important;
        inset: 0 !important;
        -webkit-overflow-scrolling: touch;
      }

      .app-wrapper {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        height: 100dvh !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
      }

      .mobile-only-nav-item {
        display: flex !important;
      }

      .mobile-topbar-actions {
        display: flex !important;
        align-items: center;
        gap: 6px;
        margin-left: 2px;
      }

      .mobile-topbar-avatar {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: var(--cafe-amber);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .mobile-topbar-name {
        color: #4a3728;
        font-size: 11px;
        font-weight: 700;
        max-width: 75px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .mobile-topbar-logout-btn {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        color: #dc2626;
        border-radius: 6px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        line-height: 1.2;
        transition: all 0.2s ease;
      }
      .mobile-topbar-logout-btn:active {
        background: #fecaca;
        transform: scale(0.96);
      }

      /* Sidebar → Bottom Navigation Bar */
      .sidebar {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        min-width: unset !important;
        height: var(--mobile-nav-h) !important;
        min-height: var(--mobile-nav-h) !important;
        flex-direction: row !important;
        z-index: 1000 !important;
        border-top: 1px solid rgba(255, 255, 255, 0.14) !important;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.25) !important;
        padding-bottom: env(safe-area-inset-bottom, 0px) !important;
        box-sizing: border-box !important;
        flex-shrink: 0 !important;
        overflow: hidden !important;
      }

      .sidebar-brand {
        display: none !important;
      }

      .sidebar-nav {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-around !important;
        padding: 4px 6px !important;
        gap: 3px !important;
        flex: 1 !important;
        width: 100% !important;
        overflow: hidden !important;
        overflow-x: hidden !important;
        overflow-y: hidden !important;
        touch-action: none !important;
      }
      .sidebar-nav::-webkit-scrollbar { display: none !important; }

      .nav-section-label {
        display: none !important;
      }

      .nav-item-btn {
        flex-direction: column !important;
        gap: 2px !important;
        padding: 4px 3px !important;
        font-size: 10px !important;
        line-height: 1.15 !important;
        min-width: 0 !important;
        flex: 1 1 0 !important;
        text-align: center !important;
        justify-content: center !important;
        align-items: center !important;
        border-radius: 8px !important;
        margin-bottom: 0 !important;
        white-space: nowrap !important;
        height: 48px !important;
        box-sizing: border-box !important;
      }

      .nav-item-btn span {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        max-width: 100% !important;
      }

      .nav-item-btn i {
        font-size: 17px !important;
        width: auto !important;
        line-height: 1 !important;
        margin: 0 !important;
      }

      .nav-item-btn.nav-logout-btn {
        color: #fca5a5 !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
      }
      .nav-item-btn.nav-logout-btn:hover,
      .nav-item-btn.nav-logout-btn:active {
        background: rgba(239, 68, 68, 0.25) !important;
        color: #ef4444 !important;
      }

      .sidebar-footer {
        display: none !important;
      }

      /* Main content strictly fills viewport above the bottom nav */
      .main-content {
        flex: 1 !important;
        min-height: 0 !important;
        width: 100% !important;
        height: calc(100dvh - var(--mobile-nav-h)) !important;
        max-height: calc(100dvh - var(--mobile-nav-h)) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding-bottom: 0 !important;
        position: relative !important;
      }

      /* Top bar */
      .top-bar {
        padding: 6px 14px !important;
        position: relative !important;
        top: 0 !important;
        z-index: 100 !important;
        height: var(--mobile-top-bar-h, 46px) !important;
        min-height: var(--mobile-top-bar-h, 46px) !important;
        flex-shrink: 0 !important;
      }

      .top-bar h4 {
        font-size: 15px !important;
        margin: 0 !important;
      }

      .top-bar-time {
        font-size: 11px !important;
      }

      /* Standard Views (Dashboard, Orders, Menu Items, Analytics, Users) */
      .view:not(#view-pos) {
        flex: 1 !important;
        min-height: 0 !important;
        height: 100% !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
        padding: 12px 14px 20px 14px !important;
      }

      /* POS Container — Flex container taking remaining height */
      #view-pos.active {
        display: flex !important;
        flex: 1 !important;
        min-height: 0 !important;
        height: 100% !important;
        max-height: 100% !important;
        width: 100% !important;
        overflow: hidden !important;
        position: relative !important;
      }

      /* Tables — horizontal scroll */
      .kpi-card {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      .data-table {
        min-width: 0;
        width: 100%;
      }

      .data-table th,
      .data-table td {
        padding: 7px 6px;
        white-space: nowrap;
      }

      .kpi-stat {
        font-size: 22px;
      }

      /* Form inputs */
      .form-control,
      .form-select {
        padding: 9px 12px;
        font-size: 14px;
        min-height: 42px;
      }

      /* Toast notification */
      .toast-notif {
        left: 14px;
        right: 14px;
        bottom: 75px;
        max-width: none;
        text-align: center;
        font-size: 13.5px;
        padding: 12px 18px;
      }

      /* Stock notification */
      .stock-notif-container {
        max-width: 94%;
        top: 10px;
      }

      .stock-notif {
        padding: 12px 14px;
      }

      /* Login card */
      .login-card {
        margin: 16px;
        padding: 26px 20px;
      }

      /* Order cards — prevent meta text from squeezing price/badge off screen */
      .order-card-header {
        flex-wrap: wrap !important;
        gap: 6px 10px !important;
        padding: 10px 12px !important;
      }
      .order-card-num {
        font-size: 13px !important;
        min-width: 44px !important;
      }
      .order-card-meta {
        font-size: 11px !important;
        min-width: 0 !important;
        flex: 1 1 120px !important;
        overflow: hidden !important;
      }
      .order-card-meta div {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
      }
      .order-card-total {
        font-size: 13px !important;
        min-width: 60px !important;
        text-align: right !important;
        flex-shrink: 0 !important;
      }

      /* void/refund table: ensure the table container allows horizontal scroll */
      #void-refund-section .adj-split > div:last-child {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        max-width: 100% !important;
      }
    }

    /* ======================================================
       RESPONSIVE — SMARTPHONES (PORTRAIT: max-width: 768px)
       ====================================================== */
    @media (max-width: 768px) and (orientation: portrait), (max-width: 600px) {
      :root {
        --mobile-top-bar-h: 46px;
        --mobile-nav-h: calc(58px + env(safe-area-inset-bottom, 0px));
      }

      .top-bar {
        height: var(--mobile-top-bar-h) !important;
        min-height: var(--mobile-top-bar-h) !important;
        padding: 6px 10px !important;
      }
      .top-bar h4 {
        font-size: 14px !important;
      }
      .top-bar-time {
        font-size: 10.5px !important;
      }

      .sidebar {
        height: var(--mobile-nav-h) !important;
        min-height: var(--mobile-nav-h) !important;
        padding-bottom: env(safe-area-inset-bottom, 0px) !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
      }
      .sidebar-nav {
        overflow: hidden !important;
        overflow-x: hidden !important;
        overflow-y: hidden !important;
        touch-action: none !important;
        justify-content: space-around !important;
      }
      .nav-item-btn {
        padding: 4px 2px !important;
        font-size: 9px !important;
        min-width: 0 !important;
        flex: 1 1 0 !important;
        height: 48px !important;
      }
      .nav-item-btn i {
        font-size: 16px !important;
      }

      .main-content {
        height: calc(100dvh - var(--mobile-nav-h)) !important;
        max-height: calc(100dvh - var(--mobile-nav-h)) !important;
        padding-bottom: 0 !important;
      }

      /* POS Container — Takes full remaining height via flex */
      .pos-view-container {
        flex: 1 !important;
        min-height: 0 !important;
        height: 100% !important;
        max-height: 100% !important;
        flex-direction: column !important;
        overflow: hidden !important;
        position: relative !important;
        width: 100% !important;
      }

      /* Mobile Navigation Tabs (Menu / Order) */
      .pos-mobile-nav-tabs {
        display: flex !important;
        background: #eae4dd;
        padding: 5px 8px;
        gap: 6px;
        border-bottom: 1.5px solid var(--cafe-tan);
        flex-shrink: 0;
        z-index: 10;
      }
      .pos-mtab-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 10px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 700;
        color: #555;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .pos-mtab-btn.active {
        background: #fff;
        color: var(--cafe-brown);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
      .pos-mtab-count {
        background: var(--cafe-brown);
        color: #fff;
        font-size: 10.5px;
        padding: 1px 7px;
        border-radius: 12px;
        font-weight: 800;
      }
      .pos-mtab-total {
        font-size: 11.5px;
        color: #059669;
        font-weight: 800;
      }

      /* Column toggling in portrait */
      .pos-menu-col {
        display: none !important;
        flex: 1;
        width: 100% !important;
        height: 100%;
        overflow: hidden;
      }
      .pos-menu-col.mobile-active {
        display: flex !important;
      }

      .pos-cart-col {
        display: none !important;
        width: 100% !important;
        min-width: 0 !important;
        flex: 1;
        height: 100%;
        border-left: none !important;
      }
      .pos-cart-col.mobile-active {
        display: flex !important;
      }

      .pos-cart-back-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        width: 32px;
        height: 32px;
        font-size: 14px;
        color: #374151;
        cursor: pointer;
      }

      /* Branch switcher & Category shortcuts */
      .branch-switcher-container button {
        padding: 8px 12px;
        font-size: 13px;
      }
      .pos-cat-shortcut-bar {
        padding: 6px 10px;
      }
      .pos-cat-shortcut-btn {
        padding: 6px 12px;
        font-size: 12px;
      }

      /* Menu scroll area */
      .pos-scroll-area {
        padding: 12px 10px 85px 10px !important;
      }

      /* Floating Cart Bar (Bottom of menu) */
      .pos-mobile-float-bar {
        display: none;
        position: absolute;
        bottom: 10px;
        left: 10px;
        right: 10px;
        background: linear-gradient(135deg, #2b1f1d, #422d28);
        color: #fff;
        border-radius: 14px;
        padding: 10px 14px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.38);
        z-index: 95;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        animation: floatBarIn 0.22s cubic-bezier(0.16,1,0.3,1);
      }
      .pos-mobile-float-bar.visible {
        display: flex !important;
      }
      @keyframes floatBarIn {
        from { transform: translateY(25px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
      }
      .pmf-info {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .pmf-badge {
        background: rgba(255,255,255,0.18);
        border-radius: 10px;
        padding: 5px 10px;
        font-size: 13px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 5px;
      }
      .pmf-prices {
        display: flex;
        flex-direction: column;
      }
      .pmf-title {
        font-size: 10px;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.4px;
      }
      .pmf-amount {
        font-size: 15px;
        font-weight: 900;
        color: #34d399;
        line-height: 1.2;
      }
      .pmf-action-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: #fff;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 4px 14px rgba(16,185,129,0.4);
        cursor: pointer;
      }

      /* Cart Panel Styling */
      .cart-header {
        padding: 10px 14px;
        font-size: 14px;
      }
      .cart-footer {
        padding: 12px 14px;
      }
      .cart-total-row {
        font-size: 19px;
      }
      .btn-cafe-primary {
        padding: 12px 18px;
        font-size: 14px;
        min-height: 44px;
      }
      .btn-cafe-secondary {
        padding: 10px 16px;
        font-size: 13px;
        min-height: 40px;
      }

      /* Modals */
      .pay-modal-overlay,
      .size-modal-overlay,
      .print-opts-overlay,
      .receipt-modal-overlay,
      .void-modal-overlay {
        padding: 8px;
      }
      .pay-modal-card,
      .size-modal-card,
      .print-opts-card,
      .receipt-modal-card,
      .void-modal-card {
        max-height: 92dvh;
        overflow-y: auto;
        width: 100%;
        max-width: 400px;
        border-radius: 16px;
      }
    }

    /* ======================================================
       RESPONSIVE — COMPACT PHONES (max-width: 480px)
       ====================================================== */
    @media (max-width: 480px) {
      .product-card {
        padding: 10px 6px;
        min-height: 100px;
      }
      .product-card .prod-emoji {
        font-size: 22px;
      }
      .product-card .prod-name {
        font-size: 11px;
        margin-bottom: 2px;
      }
      .product-card .prod-price {
        font-size: 12.5px;
      }
      .stock-badge {
        font-size: 8.5px;
        padding: 2px 5px;
      }

      .kpi-stat {
        font-size: 17px;
      }
      .kpi-label {
        font-size: 9.5px;
      }
    }

    /* ======================================================
       RESPONSIVE — LANDSCAPE PHONES (max-height: 520px & landscape)
       ====================================================== */
    @media (max-height: 520px) and (orientation: landscape), (max-height: 520px) and (min-aspect-ratio: 1/1), (max-width: 950px) and (orientation: landscape) {
      :root {
        --mobile-top-bar-h: 36px;
        --mobile-nav-h: calc(44px + env(safe-area-inset-bottom, 0px));
      }

      html, body {
        overflow: hidden !important;
        height: 100% !important;
        height: 100dvh !important;
        width: 100% !important;
        width: 100dvw !important;
        margin: 0 !important;
        padding: 0 !important;
        position: fixed !important;
        inset: 0 !important;
      }

      .app-wrapper {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        height: 100dvh !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
      }

      .top-bar {
        height: var(--mobile-top-bar-h) !important;
        min-height: var(--mobile-top-bar-h) !important;
        padding: 2px 10px !important;
      }
      .top-bar h4 { font-size: 13px !important; margin: 0 !important; }
      .top-bar-time { font-size: 10px !important; }

      .sidebar {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        min-width: unset !important;
        height: var(--mobile-nav-h) !important;
        min-height: var(--mobile-nav-h) !important;
        flex-direction: row !important;
        z-index: 1000 !important;
        border-top: 1px solid rgba(255, 255, 255, 0.14) !important;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.25) !important;
        padding-bottom: env(safe-area-inset-bottom, 0px) !important;
        box-sizing: border-box !important;
        flex-shrink: 0 !important;
        overflow: hidden !important;
      }
      .sidebar-brand, .sidebar-footer {
        display: none !important;
      }
      .nav-section-label {
        display: none !important;
      }
      .sidebar-nav {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-around !important;
        padding: 2px 6px !important;
        gap: 4px !important;
        flex: 1 !important;
        width: 100% !important;
        overflow: hidden !important;
        overflow-x: hidden !important;
        overflow-y: hidden !important;
        touch-action: none !important;
      }
      .sidebar-nav::-webkit-scrollbar { display: none !important; }

      .nav-item-btn {
        flex-direction: row !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5px !important;
        padding: 4px 6px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        min-width: 0 !important;
        flex: 1 1 0 !important;
        white-space: nowrap !important;
        height: 32px !important;
        border-radius: 7px !important;
        margin-bottom: 0 !important;
        box-sizing: border-box !important;
      }
      .nav-item-btn span {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
      }
      .nav-item-btn i {
        font-size: 14px !important;
        width: auto !important;
        line-height: 1 !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
      }
      .nav-item-btn.nav-logout-btn {
        color: #fca5a5 !important;
        border: 1px solid rgba(239, 68, 68, 0.35) !important;
      }
      .nav-item-btn.nav-logout-btn:hover,
      .nav-item-btn.nav-logout-btn:active {
        background: rgba(239, 68, 68, 0.25) !important;
        color: #ef4444 !important;
      }

      .main-content {
        flex: 1 !important;
        min-height: 0 !important;
        width: 100% !important;
        height: calc(100dvh - var(--mobile-nav-h)) !important;
        max-height: calc(100dvh - var(--mobile-nav-h)) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding-bottom: 0 !important;
      }

      /* In landscape, side-by-side fills 100% width with NO grey void! */
      .pos-view-container {
        flex: 1 !important;
        min-height: 0 !important;
        height: 100% !important;
        max-height: 100% !important;
        flex-direction: row !important;
        width: 100% !important;
        overflow: hidden !important;
      }

      .pos-mobile-nav-tabs,
      .pos-mobile-float-bar {
        display: none !important;
      }

      .pos-menu-col {
        display: flex !important;
        flex: 1 !important;
        width: auto !important;
        min-width: 0 !important;
        height: 100% !important;
        overflow: hidden !important;
      }

      .pos-cart-col {
        display: flex !important;
        width: 280px !important;
        max-width: 40% !important;
        min-width: 230px !important;
        height: 100% !important;
        border-left: 1px solid var(--cafe-tan) !important;
      }

      .pos-cart-back-btn {
        display: none !important;
      }

      .branch-switcher-container button {
        padding: 4px 8px !important;
        font-size: 11px !important;
      }

      .pos-cat-shortcut-bar {
        padding: 3px 6px !important;
      }
      .pos-cat-shortcut-btn {
        padding: 3px 8px !important;
        font-size: 11px !important;
      }

      .pos-scroll-area {
        padding: 6px 8px 24px 8px !important;
        overflow-y: auto !important;
      }

      .product-card {
        padding: 6px 4px !important;
        min-height: 80px !important;
      }
      .product-card .prod-name { font-size: 10.5px !important; }
      .product-card .prod-price { font-size: 11.5px !important; }
      .stock-badge { font-size: 8px !important; padding: 1px 4px !important; }

      .cart-header { padding: 5px 8px !important; font-size: 12px !important; }
      .cart-footer { padding: 5px 8px !important; }
      .cart-total-row { font-size: 15px !important; margin-bottom: 3px !important; }
      .btn-cafe-primary { padding: 6px 10px !important; font-size: 12px !important; min-height: 32px !important; }
      .btn-cafe-secondary { padding: 4px 8px !important; font-size: 10.5px !important; min-height: 26px !important; }

      /* ======================================================
         MODALS IN LANDSCAPE MODE (PHONE)
         Fit comfortably within short landscape screens (~360-400px height)
         ====================================================== */
      .pay-modal-overlay,
      .size-modal-overlay,
      .void-modal-overlay,
      .print-opts-overlay,
      .receipt-modal-overlay {
        padding: 6px 12px !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 12000 !important;
      }

      /* Base card constraints in landscape */
      .pay-modal-card,
      .size-modal-card,
      .void-modal-card,
      .print-opts-card,
      .receipt-modal-card {
        max-height: calc(100dvh - 12px) !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding: 0 !important;
        border-radius: 14px !important;
        margin: auto !important;
      }

      /* 1. PAYMENT MODAL */
      .pay-modal-card {
        max-width: 480px !important;
        width: 95% !important;
      }
      .pay-modal-header {
        padding: 8px 14px !important;
        flex-shrink: 0 !important;
      }
      .pay-modal-header h5 {
        font-size: 14px !important;
      }
      .pay-modal-header .pm-close {
        font-size: 18px !important;
      }
      .pay-modal-body {
        padding: 8px 14px 12px 14px !important;
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      .pay-amount-due {
        padding: 5px 10px !important;
        margin-bottom: 8px !important;
        border-radius: 8px !important;
      }
      .pay-amount-due .pad-label {
        font-size: 9.5px !important;
        margin-bottom: 1px !important;
      }
      .pay-amount-due .pad-value {
        font-size: 20px !important;
      }
      .pay-method-tabs {
        gap: 8px !important;
        margin-bottom: 8px !important;
      }
      .pay-method-btn {
        padding: 6px 8px !important;
        font-size: 11.5px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        border-width: 1.5px !important;
      }
      .pay-method-btn i {
        display: inline-block !important;
        font-size: 16px !important;
        margin-bottom: 0 !important;
      }
      .pay-section {
        margin-bottom: 6px !important;
      }
      .pay-section label {
        font-size: 10.5px !important;
        margin-bottom: 2px !important;
      }
      .pay-input {
        padding: 6px 10px !important;
        font-size: 17px !important;
        border-radius: 8px !important;
        height: 38px !important;
      }
      .pay-change-row {
        padding: 5px 10px !important;
        margin-bottom: 6px !important;
        border-radius: 8px !important;
      }
      .pay-change-row .pcr-label {
        font-size: 10.5px !important;
      }
      .pay-change-row .pcr-value {
        font-size: 16px !important;
      }
      .pay-confirm-btn {
        padding: 8px 14px !important;
        font-size: 12.5px !important;
        border-radius: 8px !important;
        min-height: 36px !important;
      }
      .pay-cancel-link {
        margin-top: 4px !important;
        font-size: 11.5px !important;
        padding: 2px !important;
      }
      /* GCash states in landscape */
      #gcash-ready-state {
        padding: 4px 0 !important;
      }
      #gcash-ready-state > div:first-child {
        padding: 5px 12px !important;
        font-size: 11.5px !important;
        margin-bottom: 8px !important;
      }
      #gcash-ready-state > div:nth-child(2) {
        padding: 8px 12px !important;
        margin-bottom: 8px !important;
      }
      #gcash-pm-amount {
        font-size: 22px !important;
      }
      #gcash-generate-btn {
        padding: 8px 16px !important;
        font-size: 12.5px !important;
        border-radius: 8px !important;
      }
      #gcash-qr-canvas {
        width: 120px !important;
        height: 120px !important;
      }

      /* 2. VOID MODAL */
      .void-modal-card {
        max-width: 480px !important;
        width: 95% !important;
      }
      .void-modal-header {
        padding: 8px 14px !important;
        flex-shrink: 0 !important;
      }
      .void-modal-header h5 {
        font-size: 14px !important;
      }
      .void-modal-header button {
        width: 26px !important;
        height: 26px !important;
        font-size: 14px !important;
      }
      .void-modal-body {
        padding: 8px 14px 12px 14px !important;
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      #void-modal-info {
        font-size: 11px !important;
        margin-bottom: 6px !important;
        line-height: 1.3 !important;
      }
      #void-dev-token-box {
        padding: 6px 10px !important;
        margin-bottom: 6px !important;
        border-radius: 6px !important;
      }
      #void-dev-token-box > div:first-child {
        font-size: 10px !important;
        margin-bottom: 2px !important;
      }
      #void-dev-token-val {
        font-size: 18px !important;
        letter-spacing: 3px !important;
      }
      #void-token-input {
        padding: 6px 10px !important;
        font-size: 18px !important;
        letter-spacing: 3px !important;
        height: 38px !important;
        margin-bottom: 4px !important;
        border-radius: 8px !important;
      }
      #void-modal-polling {
        font-size: 10px !important;
        margin-bottom: 6px !important;
      }
      #void-confirm-btn {
        padding: 8px 14px !important;
        font-size: 12.5px !important;
        border-radius: 8px !important;
        margin-top: 3px !important;
      }
      .void-modal-body > button:last-child {
        padding: 5px !important;
        font-size: 11.5px !important;
        margin-top: 2px !important;
      }

      /* 3. SIZE MODAL */
      .size-modal-card {
        max-width: 420px !important;
        padding: 12px 16px 8px !important;
      }
      .size-modal-card .sm-title {
        font-size: 14px !important;
        margin-bottom: 2px !important;
      }
      .size-modal-card .sm-subtitle {
        font-size: 11px !important;
        margin-bottom: 8px !important;
      }
      .size-btn-grid {
        gap: 6px !important;
        margin-bottom: 8px !important;
      }
      .size-btn {
        padding: 6px 8px !important;
        min-width: 70px !important;
        border-radius: 8px !important;
      }
      .size-btn .size-label {
        font-size: 12.5px !important;
        margin-bottom: 2px !important;
      }
      .size-btn .size-price {
        font-size: 11.5px !important;
      }
      .size-modal-cancel {
        padding: 4px 12px !important;
        font-size: 11.5px !important;
      }

      /* 4. RECEIPT & PRINT OPTIONS MODALS */
      .receipt-modal-card,
      .print-opts-card {
        max-width: 440px !important;
        width: 95% !important;
      }
      .receipt-modal-header {
        padding: 8px 14px !important;
        flex-shrink: 0 !important;
      }
      .receipt-modal-body {
        padding: 8px 14px !important;
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      .receipt-modal-footer {
        padding: 8px 14px !important;
        flex-shrink: 0 !important;
      }
      .print-opts-card {
        padding: 12px 16px !important;
      }
    }

    /* =============================================
       PRINT OPTIONS MODAL (AFTER PAYMENT)
    ============================================= */
    .print-opts-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.82);
      backdrop-filter: blur(6px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10600;
      padding: 20px;
    }
    .print-opts-overlay.open { display: flex !important; }
    .print-opts-card {
      background: #1a1a22;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.7);
      overflow: hidden;
      animation: printOptsPop 0.22s cubic-bezier(0.16,1,0.3,1);
    }
    @keyframes printOptsPop {
      from { opacity:0; transform:scale(0.90) translateY(20px); }
      to   { opacity:1; transform:scale(1)    translateY(0); }
    }
    .print-opts-header {
      padding: 18px 22px 14px;
      background: #222230;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      text-align: center;
    }
    .print-opts-header .poi-icon {
      width: 52px; height: 52px;
      background: linear-gradient(135deg,#f59e0b,#d97706);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 10px;
      box-shadow: 0 6px 18px rgba(245,158,11,0.35);
    }
    .print-opts-header .poi-icon i { font-size: 22px; color: #fff; }
    .print-opts-header h5 {
      font-size: 16px; font-weight: 800; color: #fff; margin: 0 0 4px;
    }
    .print-opts-header p {
      font-size: 12px; color: #9ca3af; margin: 0;
    }
    .print-opts-body {
      padding: 20px 22px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .print-opt-btn {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px 18px;
      border-radius: 14px;
      border: 2px solid transparent;
      cursor: pointer;
      transition: all 0.18s;
      text-align: left;
      width: 100%;
      background: #242430;
    }
    .print-opt-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.4); }
    .print-opt-btn .pob-num {
      width: 38px; height: 38px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; font-weight: 900; color: #fff; flex-shrink: 0;
    }
    .print-opt-btn .pob-text { flex: 1; }
    .print-opt-btn .pob-title { font-size: 14px; font-weight: 800; color: #fff; display: block; margin-bottom: 2px; }
    .print-opt-btn .pob-desc  { font-size: 11.5px; color: #9ca3af; line-height: 1.4; }
    .print-opt-btn .pob-icon  { font-size: 22px; flex-shrink: 0; }
    .print-opt-btn.kitchen-btn {
      border-color: rgba(251,146,60,0.35);
      background: linear-gradient(135deg, rgba(251,146,60,0.08), rgba(234,88,12,0.05));
    }
    .print-opt-btn.kitchen-btn:hover { border-color: #fb923c; background: linear-gradient(135deg,rgba(251,146,60,0.18),rgba(234,88,12,0.12)); }
    .print-opt-btn.kitchen-btn .pob-num { background: linear-gradient(135deg,#fb923c,#ea580c); }
    .print-opt-btn.kitchen-btn .pob-icon { color: #fb923c; }
    .print-opt-btn.full-btn {
      border-color: rgba(16,185,129,0.35);
      background: linear-gradient(135deg,rgba(16,185,129,0.08),rgba(5,150,105,0.05));
    }
    .print-opt-btn.full-btn:hover { border-color: #10b981; background: linear-gradient(135deg,rgba(16,185,129,0.18),rgba(5,150,105,0.12)); }
    .print-opt-btn.full-btn .pob-num { background: linear-gradient(135deg,#10b981,#059669); }
    .print-opt-btn.full-btn .pob-icon { color: #10b981; }
    .print-opt-btn.receipt-btn {
      border-color: rgba(59,130,246,0.35);
      background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(37,99,235,0.05));
    }
    .print-opt-btn.receipt-btn:hover { border-color: #3b82f6; background: linear-gradient(135deg,rgba(59,130,246,0.18),rgba(37,99,235,0.12)); }
    .print-opt-btn.receipt-btn .pob-num { background: linear-gradient(135deg,#3b82f6,#2563eb); }
    .print-opt-btn.receipt-btn .pob-icon { color: #3b82f6; }
    .print-opts-footer {
      padding: 12px 22px 18px;
      text-align: center;
    }
    .print-opts-skip {
      background: none; border: none; color: #6b7280;
      font-size: 12px; cursor: pointer; text-decoration: underline;
      padding: 4px 8px; transition: color 0.15s;
    }
    .print-opts-skip:hover { color: #9ca3af; }

    /* KITCHEN TICKET PRINT STYLES */
    .kitchen-ticket-paper {
      width: 58mm;
      max-width: 58mm;
      min-width: 58mm;
      box-sizing: border-box;
      background: #fff;
      color: #000;
      padding: 3mm 2.5mm 4mm 2.5mm;
      font-family: 'Courier New', Courier, monospace;
      font-size: 11px;
      line-height: 1.4;
    }

    /* =============================================
       58MM THERMAL RECEIPT MODAL & PRINT STYLES
    ============================================= */
    .receipt-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.78);
      backdrop-filter: blur(5px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10500;
      padding: 16px;
      overflow-y: auto;
    }
    .receipt-modal-overlay.open {
      display: flex;
    }
    .receipt-modal-card {
      background: #1e1e24;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 18px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.65);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      animation: rcptPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes rcptPop {
      from { opacity: 0; transform: scale(0.92) translateY(16px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .receipt-modal-header {
      padding: 14px 20px;
      background: #272730;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .receipt-modal-title {
      font-size: 14.5px;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .receipt-modal-close {
      background: transparent;
      border: none;
      color: #9ca3af;
      font-size: 20px;
      cursor: pointer;
      line-height: 1;
      padding: 4px 8px;
      border-radius: 6px;
      transition: all 0.15s;
    }
    .receipt-modal-close:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.1);
    }
    .receipt-modal-body {
      padding: 22px 18px;
      background: #141418;
      display: flex;
      justify-content: center;
      max-height: 66vh;
      overflow-y: auto;
    }
    .receipt-modal-footer {
      padding: 14px 20px;
      background: #272730;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* AUTHENTIC 58MM THERMAL RECEIPT SLIP */
    .thermal-receipt-paper {
      width: 100%;
      max-width: 290px;
      box-sizing: border-box;
      background: #ffffff;
      color: #000000;
      padding: 16px 14px 20px 14px;
      font-family: 'Courier New', Courier, Monaco, monospace;
      font-size: 11.5px;
      line-height: 1.4;
      font-weight: 600;
      box-shadow: 0 10px 35px rgba(0,0,0,0.5), 0 0 0 1px rgba(0,0,0,0.1);
      border-radius: 4px;
      position: relative;
      user-select: text;
    }
    .thermal-receipt-paper::before,
    .thermal-receipt-paper::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      height: 4px;
      background-size: 8px 4px;
    }
    .thermal-receipt-paper::before {
      top: -4px;
      background-image: radial-gradient(circle at 4px 4px, transparent 4px, #ffffff 4px);
    }
    .thermal-receipt-paper::after {
      bottom: -4px;
      background-image: radial-gradient(circle at 4px 0px, transparent 4px, #ffffff 4px);
    }

    /* PRINT RULES FOR STANDARD 58MM THERMAL PRINTER (43mm Printable Head) */
    @media print {
      @page {
        size: 58mm auto;
        margin: 0mm;
      }
      html, body {
        width: 43mm !important;
        max-width: 43mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #000000 !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      body * { visibility: hidden !important; }

      /* === KITCHEN TICKET PRINT MODE (body[data-print='kitchen']) === */
      body[data-print='kitchen'] #pos-kitchen-printable,
      body[data-print='kitchen'] #pos-kitchen-printable * {
        visibility: visible !important;
      }
      body[data-print='kitchen'] #pos-kitchen-printable {
        position: absolute !important;
        left: 0 !important; top: 0 !important;
        width: 43mm !important; max-width: 43mm !important;
        min-width: 43mm !important;
        box-sizing: border-box !important;
        margin: 0 !important; padding: 1mm 2mm 5mm 1.5mm !important;
        background: #ffffff !important; color: #000000 !important;
        font-family: 'Courier New', Courier, Monaco, monospace !important;
        font-size: 10px !important; line-height: 1.25 !important;
        box-shadow: none !important; border-radius: 0 !important;
      }

      /* === RECEIPT PRINT MODE (body[data-print='receipt'] or default) === */
      body:not([data-print='kitchen']) #pos-receipt-printable,
      body:not([data-print='kitchen']) #pos-receipt-printable * {
        visibility: visible !important;
      }
      body:not([data-print='kitchen']) #receiptModal {
        position: static !important;
        display: block !important;
        background: none !important;
        backdrop-filter: none !important;
        padding: 0 !important; margin: 0 !important;
      }
      .receipt-modal-card {
        border: none !important; box-shadow: none !important;
        background: none !important;
        max-width: 43mm !important; width: 43mm !important;
        padding: 0 !important; margin: 0 !important;
        overflow: visible !important;
      }
      .receipt-modal-header, .receipt-modal-footer, .no-print {
        display: none !important;
      }
      .receipt-modal-body {
        padding: 0 !important; margin: 0 !important;
        background: none !important;
        max-height: none !important; overflow: visible !important;
        display: block !important;
      }
      #pos-receipt-printable {
        position: absolute !important;
        left: 0 !important; top: 0 !important;
        width: 43mm !important; max-width: 43mm !important;
        min-width: 43mm !important;
        box-sizing: border-box !important;
        margin: 0 !important; padding: 1mm 2mm 5mm 1.5mm !important;
        background: #ffffff !important; color: #000000 !important;
        font-family: 'Courier New', Courier, Monaco, monospace !important;
        font-size: 10px !important; line-height: 1.25 !important;
        font-weight: 600 !important;
        box-shadow: none !important; border-radius: 0 !important;
      }
      #pos-receipt-printable::before, #pos-receipt-printable::after {
        display: none !important;
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
          style="width: 90px; height: 90px; display: flex; align-items: center; justify-content: center;">
          <img src="assets/img/logo.png" alt="ChipiPie Logo" style="width:100%;height:100%;object-fit:contain;">
        </div>
        <h3>ChipiPie Cafe</h3>
        <p>Point of Sales Login</p>
      </div>
      <!-- STEP 1: CREDENTIALS -->
      <div id="pos-step-credentials">
        <form onsubmit="handleLogin(event)">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" id="login-user" class="form-control" required>
          </div>
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0">Password</label>
              <a href="#" onclick="showPosForgotStep1(); return false;" style="font-size:12px; color:var(--cafe-brown); text-decoration:none;">Forgot Password?</a>
            </div>
            <input type="password" id="login-pass" class="form-control" required>
          </div>
          <button type="submit" id="pos-login-btn" class="btn-cafe-primary btn-lg w-100" style="font-size:16px; padding:12px;">Sign In</button>
        </form>
      </div>

      <!-- STEP 2: 2FA VERIFICATION (Manager Approval) -->
      <div id="pos-step-2fa" style="display:none;">
        <div style="background:#fdf8f4; border:1px solid #ebd9cc; border-radius:12px; padding:14px; margin-bottom:16px; text-align:center;">
          <div style="font-size:26px; margin-bottom:4px;">🔐</div>
          <div style="font-size:14px; font-weight:700; color:var(--cafe-dark, #2b1f1d); margin-bottom:3px;">Manager Authorization (2FA)</div>
          <div style="font-size:12px; color:#777; line-height:1.4;">
            A confirmation code was sent to the Manager email:<br>
            <strong id="pos-2fa-email" style="color:var(--cafe-brown, #5c3d2e); word-break:break-all;"></strong>
          </div>
        </div>

        <form onsubmit="handlePosVerify2FA(event)">
          <div class="mb-3 text-center">
            <label class="form-label" style="font-size:12px; font-weight:600; color:#555;">Enter 6-Digit Code</label>
            <input type="text" id="pos-2fa-code" class="form-control text-center" maxlength="6" placeholder="000000" autocomplete="one-time-code" required
                   style="font-family:Consolas, Monaco, monospace; font-size:26px; font-weight:700; letter-spacing:8px; padding:10px; text-transform:uppercase;">
          </div>

          <div id="pos-dev-hint" style="display:none; margin-bottom:14px; font-size:11.5px; color:#0f5132; background:#d1e7dd; border:1px solid #badbcc; border-radius:8px; padding:7px 10px; text-align:center;">
            💡 Dev Mode Token: <code id="pos-dev-code-val" style="font-weight:700;"></code> (auto-filled)
          </div>

          <button type="submit" id="pos-verify-btn" class="btn-cafe-primary btn-lg w-100" style="font-size:15px; padding:11px;">
            <i class="bi bi-shield-check"></i> Verify & Sign In
          </button>
        </form>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:12px;">
          <button type="button" onclick="handlePosResend2FA()" id="pos-resend-btn" style="background:none; border:none; color:var(--cafe-brown, #5c3d2e); cursor:pointer; padding:4px 0; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px;">
            <i class="bi bi-arrow-clockwise"></i> Resend Code
          </button>
          <button type="button" onclick="handlePosCancel2FA()" style="background:none; border:none; color:#888; cursor:pointer; padding:4px 0; font-size:12px; display:inline-flex; align-items:center; gap:4px;">
            <i class="bi bi-arrow-left"></i> Back to Login
          </button>
        </div>
      </div>

      <!-- STEP 3: FORGOT PASSWORD - REQUEST RESET LINK -->
      <div id="pos-step-forgot-request" style="display:none;">
        <div style="background:#fdf8f4; border:1px solid #ebd9cc; border-radius:12px; padding:14px; margin-bottom:16px; text-align:center;">
          <div style="font-size:26px; margin-bottom:4px;">🔑</div>
          <div style="font-size:14px; font-weight:700; color:var(--cafe-dark, #2b1f1d); margin-bottom:3px;">Forgot Password</div>
          <div style="font-size:12px; color:#777; line-height:1.4;" id="pos-forgot-instruction">
            Enter your username or email address and we'll send a password reset link to your email.
          </div>
        </div>

        <form id="pos-forgot-form" onsubmit="handlePosSendForgotLink(event)">
          <div class="mb-3 text-start">
            <label class="form-label" style="font-size:12.5px;">Username or Email</label>
            <input type="text" id="pos-forgot-user" class="form-control" placeholder="Enter username or email" required>
          </div>
          <button type="submit" id="pos-forgot-send-btn" class="btn-cafe-primary btn-lg w-100" style="font-size:15px; padding:11px;">
            <i class="bi bi-send-fill me-1"></i> Send Recovery Link
          </button>
        </form>

        <div id="pos-forgot-sent-msg" style="display:none; background:#d1e7dd; border:1px solid #badbcc; border-radius:12px; padding:18px; text-align:center; color:#0f5132;">
          <div style="font-size:30px; margin-bottom:6px;">📬</div>
          <div style="font-size:15px; font-weight:700; margin-bottom:4px;">The email has been sent.</div>
          <div style="font-size:12px; color:#555; line-height:1.4;">If this account is registered, a password reset link has been sent to the associated email. Please check your inbox or spam folder.</div>
        </div>

        <div class="mt-3 text-center">
          <a href="#" onclick="hidePosForgot(); return false;" style="font-size:12px; color:var(--cafe-brown); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Back to Sign In
          </a>
        </div>
      </div>

      <!-- STEP 4: FORGOT PASSWORD - SET NEW PASSWORD VIA LINK -->
      <div id="pos-step-forgot-reset" style="display:none;">
        <div style="background:#fdf8f4; border:1px solid #ebd9cc; border-radius:12px; padding:14px; margin-bottom:16px; text-align:center;">
          <div style="font-size:26px; margin-bottom:4px;">🔒</div>
          <div style="font-size:14px; font-weight:700; color:var(--cafe-dark, #2b1f1d); margin-bottom:3px;">Set New Password</div>
          <div style="font-size:12px; color:#777; line-height:1.4;">
            Enter and confirm your new account password.
          </div>
        </div>

        <form onsubmit="handlePosResetPasswordViaLink(event)">
          <div class="mb-3 text-start">
            <label class="form-label mb-1" style="font-size:12.5px;">New Password *</label>
            <input type="password" id="pos-reset-new-pass" class="form-control" required minlength="8" placeholder="••••••••">
            <div class="form-text" style="font-size:11px; color:#777; margin-top:4px;">
              <i class="bi bi-shield-lock me-1"></i>Min. 8 characters with uppercase, lowercase & number.
            </div>
          </div>

          <div class="mb-3 text-start">
            <label class="form-label mb-1" style="font-size:12.5px;">Confirm New Password *</label>
            <input type="password" id="pos-reset-confirm-pass" class="form-control" required minlength="8" placeholder="••••••••">
          </div>

          <button type="submit" id="pos-reset-submit-btn" class="btn-cafe-primary btn-lg w-100" style="font-size:15px; padding:11px;">
            <i class="bi bi-check-circle-fill me-1"></i> Save New Password
          </button>
        </form>

        <div class="mt-3 text-center">
          <a href="#" onclick="hidePosForgot(); return false;" style="font-size:12px; color:var(--cafe-brown); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Cancel & Back to Sign In
          </a>
        </div>
      </div>

      <div id="login-error"
        style="display:none; color:#842029; background:#f8d7da; padding:10px; border-radius:8px; font-size:13px; margin-top:12px;">
      </div>
      <div id="login-success"
        style="display:none; color:#0f5132; background:#d1e7dd; padding:10px; border-radius:8px; font-size:13px; margin-top:12px;">
      </div>
      <div id="pos-login-footer">
        <div class="mt-3 text-center">
          <a href="#" onclick="showRegisterForm()"
            style="font-size:13px; color:var(--cafe-brown); font-weight:500;">Register New Account</a>
        </div>
      </div>
    </div>
  </div>

  <!-- REGISTER SCREEN -->
  <div id="register-wrapper" class="login-wrapper" style="display:none;">
    <div class="login-card register-card">
      <div class="login-brand" style="margin-bottom:18px;">
        <div class="brand-icon mx-auto mb-2"
          style="width:48px;height:48px;background:var(--cafe-amber);color:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;">
          <i class="bi bi-person-plus-fill"></i>
        </div>
        <h4 style="font-weight:700;color:var(--cafe-brown);margin-bottom:2px;">Register Account</h4>
        <p style="color:#888;font-size:12px;margin:0;">ChipiPie Cafe POS</p>
      </div>

      <form onsubmit="handleRegister(event)">
        <!-- Name Row: 4 fields on 1 line on desktop, clean wrapping on small screens -->
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-7">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">First Name *</label>
            <input type="text" id="reg-first-name" class="form-control" placeholder="Juan" required style="padding:7px 10px;font-size:13px;">
          </div>
          <div class="col-md-2 col-5">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">M.I.</label>
            <input type="text" id="reg-middle-initial" class="form-control" placeholder="D" maxlength="1"
              style="padding:7px 10px;font-size:13px;text-transform:uppercase;"
              oninput="this.value=this.value.replace(/[^a-zA-Z]/g,'').toUpperCase()">
          </div>
          <div class="col-md-4 col-8">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Last Name *</label>
            <input type="text" id="reg-last-name" class="form-control" placeholder="Dela Cruz" required style="padding:7px 10px;font-size:13px;">
          </div>
          <div class="col-md-2 col-4">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Suffix</label>
            <select id="reg-suffix" class="form-select" style="padding:7px 10px;font-size:13px;">
              <option value="">None</option>
              <option value="Jr.">Jr.</option>
              <option value="Sr.">Sr.</option>
              <option value="II">II</option>
              <option value="III">III</option>
              <option value="IV">IV</option>
              <option value="V">V</option>
            </select>
          </div>
        </div>

        <!-- Contact & Username Row -->
        <div class="row g-2 mb-2">
          <div class="col-md-6 col-12">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Email *</label>
            <input type="email" id="reg-email" class="form-control" placeholder="juan@example.com" required
              style="padding:7px 10px;font-size:13px;"
              oninput="suggestUsernameFromEmail('reg-email','reg-user')">
          </div>
          <div class="col-md-6 col-12">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Username *</label>
            <div style="position:relative;">
              <input type="text" id="reg-user" class="form-control" placeholder="juandc" required style="padding:7px 10px;font-size:13px;">
              <span id="reg-user-hint" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:10px;color:#888;pointer-events:none;">auto-filled</span>
            </div>
          </div>
        </div>

        <!-- Password Row -->
        <div class="row g-2 mb-2">
          <div class="col-md-6 col-12">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Password *</label>
            <input type="password" id="reg-pass" class="form-control" required minlength="8" placeholder="••••••••" style="padding:7px 10px;font-size:13px;">
          </div>
          <div class="col-md-6 col-12">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Confirm Password *</label>
            <input type="password" id="reg-pass-confirm" class="form-control" required minlength="8" placeholder="••••••••" style="padding:7px 10px;font-size:13px;">
          </div>
          <div class="col-12">
            <div style="font-size:11px;color:#777;margin-top:1px;line-height:1.3;">
              <i class="bi bi-shield-lock me-1"></i>Min. 8 characters with uppercase, lowercase & number.
            </div>
          </div>
        </div>

        <!-- Role Row -->
        <div class="mb-3">
          <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Role *</label>
          <select class="form-select" id="reg-role" style="padding:7px 10px;font-size:13px;">
            <option value="cashier">Cashier</option>
            <option value="admin">Admin (requires owner approval)</option>
          </select>
        </div>

        <button type="submit" class="btn-cafe-primary w-100" style="font-size:15px;padding:10px 14px;font-weight:600;border-radius:10px;"
          id="reg-submit-btn">Register</button>
      </form>

      <div id="reg-error"
        style="display:none; color:#842029; background:#f8d7da; padding:8px 12px; border-radius:8px; font-size:12.5px; margin-top:10px;">
      </div>
      <div id="reg-success"
        style="display:none; color:#1a5c2e; background:#d4edda; padding:8px 12px; border-radius:8px; font-size:12.5px; margin-top:10px;">
      </div>

      <div class="mt-2 text-center">
        <a href="#" onclick="showLoginForm()" style="font-size:12.5px; color:var(--cafe-brown); font-weight:500; text-decoration:none;">
          <i class="bi bi-arrow-left"></i> Back to Login
        </a>
      </div>
    </div>
  </div>

  <div class="app-wrapper" id="app-wrapper" style="display: none;">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="brand-icon">
          <img src="assets/img/logo.png" alt="ChipiPie Logo">
        </div>
        <h5>ChipiPie Cafe</h5>
        <small>Point of Sales System</small>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <button class="nav-item-btn active" id="nav-dash-btn" onclick="showView('dashboard', this)">
          <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
        </button>
        <button class="nav-item-btn" id="nav-pos-btn" onclick="showView('pos', this)">
          <i class="bi bi-cart3"></i> <span>New Order</span>
        </button>
        <button class="nav-item-btn" id="nav-orders-btn" onclick="showView('orders', this)">
          <i class="bi bi-receipt"></i> <span>Orders</span>
        </button>
        <button class="nav-item-btn" id="nav-menu-btn" onclick="showView('menu', this)">
          <i class="bi bi-menu-button-wide-fill"></i> <span>Menu Items</span>
        </button>

        <div class="nav-section-label" id="nav-mgmt-label">Management</div>
        <button class="nav-item-btn" id="nav-reports-btn" onclick="showView('reports', this)">
          <i class="bi bi-bar-chart-fill"></i> <span>Analytics</span>
        </button>
        <button class="nav-item-btn" id="nav-users-btn" onclick="showView('users', this)">
          <i class="bi bi-people-fill"></i> <span>Users</span>
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
        <div class="d-flex align-items-center gap-2">
          <span class="top-bar-time" id="live-time"></span>
          <span class="badge-success" id="status-badge">● Open</span>
          
          <!-- Mobile user pill & Sign Out button in Top Bar -->
          <div class="mobile-topbar-actions" id="mobile-topbar-actions">
            <span class="mobile-topbar-avatar" id="topbar-user-avatar">A</span>
            <span class="mobile-topbar-name" id="topbar-user-name">Admin</span>
            <button class="mobile-topbar-logout-btn" onclick="logout()" title="Sign Out">
              <i class="bi bi-box-arrow-right"></i> <span>Sign Out</span>
            </button>
          </div>
        </div>
      </div>

      <!-- DASHBOARD VIEW -->
      <div id="view-dashboard" class="view active">
        <div class="row g-3 mb-4">
          <div class="col-6 col-lg">
            <div class="kpi-card kpi-colored kc-blue">
              <div class="kpi-label"><i class="bi bi-currency-exchange me-1"></i>Today's Revenue</div>
              <div class="kpi-stat" id="kpi-sales">₱0</div>
              <div class="kpi-trend" id="kpi-sales-trend">From today's orders</div>
            </div>
          </div>
          <div class="col-6 col-lg">
            <div class="kpi-card kpi-colored kc-purple">
              <div class="kpi-label"><i class="bi bi-bag-check me-1"></i>Orders Today</div>
              <div class="kpi-stat" id="kpi-orders">0</div>
              <div class="kpi-trend">Transactions completed</div>
            </div>
          </div>
          <div class="col-6 col-lg">
            <div class="kpi-card kpi-colored kc-green">
              <div class="kpi-label"><i class="bi bi-cup-hot me-1"></i>Cups Sold</div>
              <div class="kpi-stat" id="kpi-cups">0</div>
              <div class="kpi-trend" id="kpi-cups-trend">Drinks today</div>
            </div>
          </div>
          <div class="col-6 col-lg">
            <div class="kpi-card kpi-colored kc-orange">
              <div class="kpi-label"><i class="bi bi-calculator me-1"></i>Avg. Order</div>
              <div class="kpi-stat" id="kpi-avg">₱0</div>
              <div class="kpi-trend">Per transaction</div>
            </div>
          </div>
          <div class="col-12 col-lg">
            <div class="kpi-card kpi-colored kc-red">
              <div class="kpi-label"><i class="bi bi-trophy me-1"></i>Best Seller</div>
              <div class="kpi-stat" style="font-size:20px;" id="kpi-top">—</div>
              <div class="kpi-trend" id="kpi-top-trend">No sales yet</div>
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

        <div class="row g-3 mb-3">
          <div class="col-lg-12">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Hourly Sales — Today</div>
              <div class="chart-box" style="height:210px;">
                <canvas id="dashHourlyChart"></canvas>
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
      <div id="view-pos" class="view pos-view-container">

        <!-- Mobile View Switcher (Menu / Cart) - visible on mobile phones -->
        <div class="pos-mobile-nav-tabs" id="pos-mobile-nav-tabs">
          <button class="pos-mtab-btn active" id="pos-mtab-menu" onclick="switchMobilePosTab('menu')">
            <i class="bi bi-grid-fill me-1"></i><span>Menu Catalog</span>
          </button>
          <button class="pos-mtab-btn" id="pos-mtab-cart" onclick="switchMobilePosTab('cart')">
            <i class="bi bi-bag-fill me-1"></i><span>Current Order</span>
            <span class="pos-mtab-count" id="pos-mtab-count">0</span>
            <span class="pos-mtab-total" id="pos-mtab-total">₱0.00</span>
          </button>
        </div>

        <!-- LEFT: Scrollable menu by category -->
        <div class="pos-menu-col mobile-active" id="pos-menu-col">

          <!-- Branch switcher (Coffee / Pizza) -->
          <div class="branch-switcher-container">
            <div id="branch-slider"></div>
            <button id="branch-btn-coffee" onclick="switchBranch('coffee')">
              ☕ Coffee
            </button>
            <button id="branch-btn-pizza" onclick="switchBranch('pizza')">
              🍕 Pizza House
            </button>
          </div>

          <!-- Sticky category shortcut bar -->
          <div class="pos-cat-shortcut-bar" id="pos-cat-shortcut-bar">
            <div id="coffee-shortcuts" class="shortcut-group">
              <div class="shortcut-slider"></div>
              <button class="pos-cat-shortcut-btn active" id="scbtn-coffee" onclick="scrollToCategory('coffee')">
                <i class="bi bi-cup-hot-fill"></i> Coffee
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-non-coffee" onclick="scrollToCategory('non-coffee')">
                <i class="bi bi-cup-straw"></i> Non-Coffee
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-snack" onclick="scrollToCategory('snack')">
                <i class="bi bi-egg-fried"></i> Snacks
              </button>
            </div>
            <div id="pizza-shortcuts" class="shortcut-group" style="display:none;">
              <div class="shortcut-slider"></div>
              <button class="pos-cat-shortcut-btn active" id="scbtn-pizza" onclick="scrollToCategory('pizza')">
                <i class="bi bi-circle"></i> Pizza
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-quesadilla" onclick="scrollToCategory('quesadilla')">
                <i class="bi bi-grid-3x3-gap"></i> Quesadilla
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-patatas" onclick="scrollToCategory('patatas')">
                <i class="bi bi-bag-heart"></i> Patatas
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-pasta" onclick="scrollToCategory('pasta')">
                <i class="bi bi-stars"></i> Pasta
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-chickenpops" onclick="scrollToCategory('chickenpops')">
                <i class="bi bi-lightning"></i> Chickenpops
              </button>
              <button class="pos-cat-shortcut-btn" id="scbtn-combo" onclick="scrollToCategory('combo')">
                <i class="bi bi-collection"></i> Combo & Gipit Meal
              </button>
            </div>
          </div>
          <!-- Scrollable area -->
          <div class="pos-scroll-area" id="pos-scroll-area">
            <!-- Search bar -->
            <div class="search-wrap mb-3">
              <i class="bi bi-search"></i>
              <input type="text" class="search-input" placeholder="Search menu..." id="menu-search" oninput="renderPOS()">
            </div>
            <!-- Category sections injected here -->
            <div id="menu-grid"></div>
          </div>

          <!-- Mobile Floating Cart Bar (visible in menu view when items in cart) -->
          <div class="pos-mobile-float-bar" id="pos-mobile-float-bar" onclick="switchMobilePosTab('cart')">
            <div class="pmf-info">
              <div class="pmf-badge">
                <i class="bi bi-bag-check-fill"></i>
                <span id="pmf-badge-qty">0</span>
              </div>
              <div class="pmf-prices">
                <span class="pmf-title">Current Order</span>
                <span class="pmf-amount" id="pmf-badge-total">₱0.00</span>
              </div>
            </div>
            <button class="pmf-action-btn" onclick="event.stopPropagation(); checkout();">
              <span>Pay</span> <i class="bi bi-arrow-right-short"></i>
            </button>
          </div>

        </div>

        <!-- RIGHT: Fixed cart panel -->
        <div class="pos-cart-col" id="pos-cart-col">
              <div class="cart-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <button class="pos-cart-back-btn" onclick="switchMobilePosTab('menu')" title="Back to menu">
                    <i class="bi bi-arrow-left"></i>
                  </button>
                  <span><i class="bi bi-cart3 me-1"></i>Current Order</span>
                </div>
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

      <!-- ORDERS VIEW -->
      <div id="view-orders" class="view">
        <!-- Filter Tabs + Print -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div class="d-flex gap-2 flex-wrap" id="order-filter-tabs">
            <button id="ofilter-today" class="cat-pill active" onclick="setOrderFilter('today', this)">Today</button>
            <button id="ofilter-week" class="cat-pill" onclick="setOrderFilter('week',  this)">This Week</button>
            <button id="ofilter-month" class="cat-pill" onclick="setOrderFilter('month', this)">This Month</button>
            <button id="ofilter-year" class="cat-pill" onclick="setOrderFilter('year',  this)">This Year</button>
            <!-- Separator -->
            <span style="width:1px;background:#ddd;margin:0 4px;align-self:stretch;"></span>
            <button id="ofilter-pick-date"  class="cat-pill" onclick="setOrderPickerMode('date')"><i class="bi bi-calendar-event"></i> Date</button>
            <button id="ofilter-pick-week"  class="cat-pill" onclick="setOrderPickerMode('week')"><i class="bi bi-calendar-range"></i> Week</button>
            <button id="ofilter-pick-month" class="cat-pill" onclick="setOrderPickerMode('month')"><i class="bi bi-calendar-month"></i> Month</button>
            <button id="ofilter-pick-year"  class="cat-pill" onclick="setOrderPickerMode('year')"><i class="bi bi-calendar"></i> Year</button>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-success" id="orders-count-badge">0 orders</span>
            <button onclick="printOrders()"
              style="background:var(--cafe-brown);color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
              <i class="bi bi-printer-fill"></i> Print Report
            </button>
          </div>
        </div>

        <!-- Orders Date Picker Card -->
        <div id="order-picker-card" class="kpi-card mb-3" style="display:none;padding:12px 16px;">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <i class="bi bi-calendar3" style="color:var(--cafe-brown);font-size:15px;"></i>
            <span style="font-size:13px;font-weight:700;color:var(--cafe-brown);">Custom Date Filter</span>
            <span id="order-picker-summary" style="font-size:12px;color:#888;margin-left:6px;"></span>
          </div>
          <!-- DATE -->
          <div id="order-picker-date" class="order-picker-section" style="display:none;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <label style="font-size:12px;font-weight:600;color:#555;">Choose Date:</label>
              <button type="button" onclick="shiftOrderDate(-1)" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-left"></i></button>
              <input type="date" id="order-input-date" style="border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;font-size:13px;outline:none;" onchange="applyOrderPickerFilter()">
              <button type="button" onclick="shiftOrderDate(1)"  style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-right"></i></button>
              <button type="button" onclick="setOrderPickerToday()" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;">Today</button>
              <span id="order-date-label" style="font-size:12px;font-weight:600;color:var(--cafe-brown);"></span>
            </div>
          </div>
          <!-- WEEK -->
          <div id="order-picker-week" class="order-picker-section" style="display:none;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <label style="font-size:12px;font-weight:600;color:#555;">Choose Week:</label>
              <button type="button" onclick="shiftOrderWeek(-1)" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-left"></i></button>
              <input type="week" id="order-input-week" style="border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;font-size:13px;outline:none;" onchange="applyOrderPickerFilter()">
              <button type="button" onclick="shiftOrderWeek(1)"  style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-right"></i></button>
              <button type="button" onclick="setOrderPickerThisWeek()" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;">This Week</button>
              <span id="order-week-label" style="font-size:12px;font-weight:600;color:var(--cafe-brown);"></span>
            </div>
          </div>
          <!-- MONTH -->
          <div id="order-picker-month" class="order-picker-section" style="display:none;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <label style="font-size:12px;font-weight:600;color:#555;">Choose Month:</label>
              <button type="button" onclick="shiftOrderMonth(-1)" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-left"></i></button>
              <input type="month" id="order-input-month" style="border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;font-size:13px;outline:none;" onchange="applyOrderPickerFilter()">
              <button type="button" onclick="shiftOrderMonth(1)"  style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-right"></i></button>
              <button type="button" onclick="setOrderPickerThisMonth()" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;">This Month</button>
              <span id="order-month-label" style="font-size:12px;font-weight:600;color:var(--cafe-brown);"></span>
            </div>
          </div>
          <!-- YEAR -->
          <div id="order-picker-year" class="order-picker-section" style="display:none;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <label style="font-size:12px;font-weight:600;color:#555;">Choose Year:</label>
              <button type="button" onclick="shiftOrderYear(-1)" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-left"></i></button>
              <select id="order-input-year" style="border:1px solid #e5e7eb;border-radius:6px;padding:5px 10px;font-size:13px;outline:none;" onchange="applyOrderPickerFilter()"></select>
              <button type="button" onclick="shiftOrderYear(1)"  style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:13px;"><i class="bi bi-chevron-right"></i></button>
              <button type="button" onclick="setOrderPickerThisYear()" style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;">This Year</button>
              <span id="order-year-label" style="font-size:12px;font-weight:600;color:var(--cafe-brown);"></span>
            </div>
          </div>
        </div>

        <!-- Summary Bar -->
        <div class="row g-2 mb-3 align-items-stretch" id="orders-summary-bar">
          <div class="col-6 col-md-3">
            <div class="kpi-card h-100" style="padding:12px 16px;">
              <div class="kpi-label">Total Orders</div>
              <div class="kpi-stat" id="osum-orders" style="font-size:22px;">0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="kpi-card h-100" style="padding:12px 16px;">
              <div class="kpi-label">Total Revenue</div>
              <div class="kpi-stat" id="osum-revenue" style="font-size:22px;">₱0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="kpi-card h-100" style="padding:12px 16px;">
              <div class="kpi-label">Avg. Order Value</div>
              <div class="kpi-stat" id="osum-avg" style="font-size:22px;">₱0</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="kpi-card h-100" style="padding:12px 16px;display:flex;flex-direction:column;justify-content:center;">
              <div class="kpi-label">Period</div>
              <div class="kpi-stat" id="osum-period" style="font-size:14px;font-weight:600;color:var(--cafe-brown);">
                Today</div>
            </div>
          </div>
        </div>

        <!-- VOID / REFUND SECTION -->
        <div class="kpi-card mb-3" id="void-refund-section" style="display:none;">

          <!-- Header row -->
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
            <div style="font-weight:700;color:#dc2626;font-size:15px;">
              <i class="bi bi-shield-exclamation me-2"></i>Void &amp; Refund Orders
              <span style="font-size:11px;font-weight:400;color:#888;margin-left:8px;">Mark orders with &#9675; then authorize above the table.</span>
            </div>
          </div>

          <!-- TOP: Auth panel (hidden until orders are selected) -->
          <div id="adj-top-panel" style="display:none;background:#fafafa;border:1.5px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:16px;">
            <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
              <!-- Summary -->
              <div style="min-width:160px;">
                <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Selected</div>
                <div id="adj-sel-summary" style="font-size:12px;color:#555;line-height:1.5;"></div>
                <div id="adj-sel-total" style="font-size:20px;font-weight:900;color:#b91c1c;margin-top:2px;"></div>
              </div>
              <!-- Staff / Requester name -->
              <div style="flex:1;min-width:180px;">
                <label style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:3px;">
                  <i class="bi bi-person-badge me-1"></i>Staff / Requester Name <span style="color:#dc2626;">*</span>
                </label>
                <input type="text" id="adj-manager-name" placeholder="Cashier or Manager name" maxlength="80"
                  style="width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:13px;font-family:inherit;box-sizing:border-box;"
                  onfocus="this.style.borderColor='#374151'" onblur="this.style.borderColor='#e5e7eb'">
                <div id="adj-manager-error" style="display:none;color:#dc2626;font-size:11px;margin-top:2px;">Name is required.</div>
                <div style="font-size:11px;color:#9ca3af;margin-top:5px;"><i class="bi bi-info-circle me-1"></i>Enter a reason for each order below.</div>
              </div>
              <!-- Buttons -->
              <div style="display:flex;flex-direction:column;gap:6px;justify-content:flex-end;">
                <button id="adj-req-btn" onclick="requestVoidCode()"
                  style="padding:9px 16px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;">
                  <i class="bi bi-envelope-fill me-1"></i>Submit Request for Approval
                </button>
                <button onclick="clearAdjSelection()"
                  style="padding:6px 10px;background:transparent;border:1px solid #e5e7eb;border-radius:7px;color:#9ca3af;font-size:12px;cursor:pointer;text-align:center;">
                  ✕ Clear selection
                </button>
              </div>
            </div>
            <div id="adj-req-status" style="display:none;font-size:12px;color:#dc2626;margin-top:8px;"></div>
          </div>

          <!-- Two-column: side panel + table -->
          <div class="adj-split" style="display:grid;grid-template-columns:290px 1fr;gap:18px;align-items:start;">

            <!-- LEFT: Side panel with per-order reason cards -->
            <div class="adj-auth-panel" id="adj-side-panel">
              <div id="adj-side-empty" style="padding:36px 20px;text-align:center;color:#bbb;">
                <i class="bi bi-circle" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.4;"></i>
                <div style="font-size:13px;color:#9ca3af;">Mark orders using<br>&#9675; Void or &#9675; Refund</div>
              </div>
              <div id="adj-side-list" style="display:none;padding:14px;"></div>
            </div>

            <!-- RIGHT: Orders table -->
            <div style="overflow-x:auto;">
              <table class="data-table" style="font-size:13px;">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Date &amp; Time</th>
                    <th>Items</th>
                    <th style="text-align:right;">Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th style="text-align:center;color:#b91c1c;font-size:12px;">&#9675; Void</th>
                    <th style="text-align:center;color:#1d4ed8;font-size:12px;">&#9675; Refund</th>
                  </tr>
                </thead>
                <tbody id="void-orders-tbody">
                  <tr><td colspan="8" style="text-align:center;color:#aaa;padding:20px;">No orders to display.</td></tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>

        <!-- ADJUSTMENT HISTORY (admin only) -->
        <div class="kpi-card mb-3" id="adj-history-section" style="display:none;">
          <div style="font-weight:700;color:#374151;font-size:14px;margin-bottom:12px;"><i class="bi bi-clock-history me-2" style="color:#7c3aed;"></i>Adjustment History <span style="font-size:12px;font-weight:400;color:#888;">(last 100)</span></div>
          <div style="overflow-x:auto;">
            <table class="data-table" style="font-size:13px;">
              <thead>
                <tr>
                  <th>Type</th><th>Order #</th><th>Amount</th><th>Reason / Authorized By</th><th>Date</th>
                </tr>
              </thead>
              <tbody id="adj-history-tbody">
                <tr><td colspan="5" style="text-align:center;color:#aaa;padding:16px;">No adjustments yet.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="kpi-card">
          <div id="orders-loading" style="text-align:center;padding:32px;color:#aaa;display:none;">
            <i class="bi bi-hourglass-split" style="font-size:28px;"></i>
            <div style="margin-top:8px;font-size:13px;">Loading orders...</div>
          </div>
          <div id="orders-list"></div>
        </div>
      </div>

      <!-- MENU MANAGEMENT VIEW -->
      <div id="view-menu" class="view">
        <div class="menu-mgmt-toolbar">
          <div class="menu-mgmt-top-row">
            <div class="search-wrap menu-mgmt-search-wrap">
              <i class="bi bi-search"></i>
              <input type="text" class="search-input" id="menu-mgmt-search" placeholder="Search menu..." oninput="renderMenuTable()">
            </div>
            <button class="btn-cafe-primary menu-mgmt-add-btn" onclick="populateAddItemCategorySelect()" data-bs-toggle="modal"
              data-bs-target="#addItemModal">
              <i class="bi bi-plus-circle me-1"></i><span>Add Item</span>
            </button>
          </div>
          <div class="menu-mgmt-tabs-scroll" id="menu-mgmt-filter-tabs">
            <!-- Dynamically populated in renderMenuTable -->
          </div>
        </div>
        <div class="kpi-card menu-table-card">
          <div class="table-responsive-wrapper">
            <table class="data-table menu-mgmt-table">
              <thead>
                <tr>
                  <th style="min-width:130px;">Item</th>
                  <th style="min-width:85px;">Category</th>
                  <th style="min-width:65px;">Price</th>
                  <th style="min-width:115px;">Stock</th>
                  <th style="min-width:90px;">Availability</th>
                  <th style="min-width:170px; text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="menu-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- REPORTS VIEW -->
      <div id="view-reports" class="view">
        <div class="row g-3 mb-3">
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Total Revenue (Month)</div>
              <div class="kpi-stat" id="rep-kpi-month-rev">—</div>
              <div class="kpi-trend" id="rep-kpi-month-rev-trend">Loading...</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Total Orders (Month)</div>
              <div class="kpi-stat" id="rep-kpi-month-orders">—</div>
              <div class="kpi-trend" id="rep-kpi-month-orders-trend">Loading...</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Avg Daily Revenue</div>
              <div class="kpi-stat" id="rep-kpi-avg-daily">—</div>
              <div class="kpi-trend" id="rep-kpi-avg-daily-trend">Based on 30 days</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="kpi-card">
              <div class="kpi-label">Best Revenue Day</div>
              <div class="kpi-stat" style="font-size:22px;" id="rep-kpi-best-day">—</div>
              <div class="kpi-trend" id="rep-kpi-best-day-trend">This month</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-lg-12">
            <div class="kpi-card">
              <div style="font-weight:600; color:var(--cafe-brown); font-size:14px; margin-bottom:12px;">Weekly Sales Overview</div>
              <div class="chart-box" style="height:210px;">
                <canvas id="repWeeklyChart"></canvas>
              </div>
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

  <!-- VOID AUTH MODAL (Step 2: Enter Code) -->
  <div class="void-modal-overlay" id="void-auth-modal">
    <div class="void-modal-card">
      <div class="void-modal-header" id="void-modal-hdr">
        <h5 id="void-modal-title"><i class="bi bi-shield-lock me-2"></i>Enter Authorization Code</h5>
        <button onclick="closeVoidAuthModal()" style="background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:8px;width:30px;height:30px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;">&#x2715;</button>
      </div>
      <div class="void-modal-body">
        <div id="void-modal-info" style="font-size:12px;color:#6b7280;margin-bottom:14px;">
          <i class="bi bi-info-circle me-1"></i>An authorization code has been sent to the manager / admin email. Enter the 6-digit code below, or wait for approval.
        </div>
        <!-- Dev mode token display -->
        <div id="void-dev-token-box" style="display:none;background:#fef9c3;border:1px solid #fde047;border-radius:8px;padding:12px;margin-bottom:12px;text-align:center;">
          <div style="font-size:11px;color:#92400e;font-weight:600;margin-bottom:4px;">DEV MODE — Token (not emailed):</div>
          <div id="void-dev-token-val" style="font-size:22px;font-weight:800;letter-spacing:4px;font-family:monospace;color:#b45309;"></div>
        </div>
        <input type="text" id="void-token-input" placeholder="XXXXXX" maxlength="8"
          style="width:100%;padding:14px;font-size:22px;letter-spacing:4px;font-weight:800;font-family:monospace;text-align:center;border:2px solid #e5e7eb;border-radius:10px;margin-bottom:6px;text-transform:uppercase;box-sizing:border-box;"
          oninput="this.value=this.value.toUpperCase()">
        <div id="void-token-error" style="display:none;color:#dc2626;font-size:12px;text-align:center;margin-bottom:10px;"></div>
        <div id="void-modal-polling" style="font-size:11px;color:#6b7280;margin-bottom:10px;text-align:center;">
          <span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;border-width:2px;color:var(--cafe-amber);vertical-align:middle;"></span>
          Waiting for authorization... Auto-approves once accepted by manager/admin.
        </div>
        <button onclick="confirmVoidWithCode()" id="void-confirm-btn"
          style="width:100%;padding:12px;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;margin-top:6px;">
          <i class="bi bi-check-circle-fill me-1"></i> Confirm
        </button>
        <button onclick="closeVoidAuthModal()"
          style="width:100%;padding:10px;background:transparent;border:none;color:#6b7280;font-size:13px;cursor:pointer;margin-top:6px;">&#8592; Cancel</button>
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

  <!-- REMOVE MENU ITEM MODAL -->
  <div class="modal fade" id="deleteItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:16px;overflow:hidden;border:1px solid var(--cafe-tan);">
        <div class="modal-header" style="background:#fdf2f2;border-bottom:1px solid #fecaca;padding:16px 20px;">
          <h5 class="modal-title" style="color:#991b1b;font-size:16px;font-weight:700;">
            <i class="bi bi-trash3-fill me-2" style="color:#dc2626;"></i>Delete Menu Item
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div style="background:#faf8f5;border:1px solid var(--cafe-tan);border-radius:12px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
            <div id="del-modal-img-wrap" style="width:48px;height:48px;border-radius:10px;background:var(--cafe-cream);display:flex;align-items:center;justify-content:center;font-size:22px;border:1px solid var(--cafe-tan);flex-shrink:0;">
              ☕
            </div>
            <div style="flex:1;min-width:0;">
              <div id="del-modal-item-name" style="font-weight:700;font-size:15px;color:var(--cafe-brown);line-height:1.2;">Item Name</div>
              <div style="font-size:12px;color:#78716c;margin-top:2px;">
                <span id="del-modal-item-cat">Category</span> · <span id="del-modal-item-price" style="font-weight:600;color:var(--cafe-brown);">₱0.00</span> · <span id="del-modal-item-stock">0 in stock</span>
              </div>
            </div>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; margin-bottom:14px; font-size:12.5px; color:#64748b;">
            <span>Requested By:</span>
            <strong id="del-modal-requester" style="color:#1e293b;">Staff</strong>
          </div>

          <div class="mb-3">
            <label class="form-label" style="font-size:12.5px;font-weight:600;color:var(--cafe-brown);">Reason for Removal (optional)</label>
            <input type="text" class="form-control" id="del-modal-reason" placeholder="e.g. Out of season, discontinued, shortage" style="font-size:13px;border-radius:8px;">
          </div>

          <div style="background:#fff8e6;border:1px solid #ffe08a;border-radius:10px;padding:10px 14px;font-size:12px;color:#856404;display:flex;align-items:flex-start;gap:10px;">
            <i class="bi bi-info-circle-fill" style="font-size:15px;margin-top:1px;color:#d97706;flex-shrink:0;"></i>
            <span>This request will be sent directly to the <strong>admin email</strong> for approval, or the <strong>Super Admin</strong> can approve it in the Control Center without using codes.</span>
          </div>
          <div id="del-modal-error" style="display:none;color:#dc2626;font-size:12.5px;margin-top:10px;text-align:center;font-weight:600;"></div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--cafe-tan);padding:12px 20px;gap:8px;">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" style="padding:7px 16px;border-radius:8px;">Cancel</button>
          <button type="button" class="btn btn-sm" id="del-modal-confirm-btn" onclick="submitRemoveMenuItem()" style="background:linear-gradient(135deg, #dc2626, #b91c1c);color:#fff;border:none;border-radius:8px;padding:7px 18px;font-weight:600;">
            <i class="bi bi-send-fill me-1"></i> Submit Removal Request
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- CANCEL REMOVAL REQUEST MODAL -->
  <div class="modal fade" id="cancelRemovalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
      <div class="modal-content" style="border-radius:16px;overflow:hidden;border:1px solid var(--cafe-tan);box-shadow:0 12px 36px rgba(0,0,0,0.18);">
        <div class="modal-header" style="background:#fffbeb;border-bottom:1px solid #fef3c7;padding:16px 20px;">
          <h5 class="modal-title" style="color:#92400e;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-x-circle-fill" style="color:#d97706;font-size:18px;"></i> Cancel Removal Request
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div style="background:#faf8f5;border:1px solid var(--cafe-tan);border-radius:12px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
            <div id="cancel-modal-img-wrap" style="width:48px;height:48px;border-radius:10px;background:var(--cafe-cream);display:flex;align-items:center;justify-content:center;font-size:22px;border:1px solid var(--cafe-tan);flex-shrink:0;">
              ☕
            </div>
            <div style="flex:1;min-width:0;">
              <div id="cancel-modal-item-name" style="font-weight:700;font-size:15px;color:var(--cafe-brown);line-height:1.2;">Item Name</div>
              <div style="font-size:12px;color:#78716c;margin-top:2px;">
                <span id="cancel-modal-item-cat">Category</span> · <span id="cancel-modal-item-price" style="font-weight:600;color:var(--cafe-brown);">₱0.00</span>
              </div>
            </div>
          </div>

          <p style="font-size:13.5px;color:#44403c;margin-bottom:12px;line-height:1.5;">
            Are you sure you want to cancel the pending removal request for this menu item?
          </p>

          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;font-size:12px;color:#166534;display:flex;align-items:flex-start;gap:10px;">
            <i class="bi bi-check-circle-fill" style="font-size:15px;margin-top:1px;color:#16a34a;flex-shrink:0;"></i>
            <span>The pending removal request will be retracted, and this item will <strong>remain active and available</strong> on the menu.</span>
          </div>

          <div id="cancel-modal-error" style="display:none;color:#dc2626;font-size:12.5px;margin-top:12px;text-align:center;font-weight:600;"></div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--cafe-tan);padding:12px 20px;gap:8px;">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" style="padding:7px 16px;border-radius:8px;">Keep Pending</button>
          <button type="button" class="btn btn-sm" id="cancel-modal-confirm-btn" onclick="submitCancelPendingRemoval()" style="background:linear-gradient(135deg, #d97706, #b45309);color:#fff;border:none;border-radius:8px;padding:7px 18px;font-weight:600;">
            <i class="bi bi-x-circle me-1"></i> Yes, Cancel Request
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- RESEND APPROVAL MODAL -->
  <div class="modal fade" id="resendApprovalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom:none;padding-bottom:0;">
          <h5 class="modal-title" style="color:var(--cafe-brown);font-size:16px;">Resend Approval</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-3 text-center">
          <p style="color:#555;font-size:14px;margin-bottom:20px;">Re-send the approval email to the owner? <br><br><span style="font-size:12px;color:#888;">The owner must click the link in their Gmail to approve this admin.</span></p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <button type="button" class="btn btn-secondary btn-sm" style="border-radius:6px;padding:6px 16px;" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-sm" style="background:var(--cafe-brown);color:#fff;border:none;border-radius:6px;padding:6px 16px;" id="resend-confirm-btn" onclick="submitResendApproval()">Resend</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- REQUEST TOGGLE MODAL -->
  <div class="modal fade" id="requestToggleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom:none;padding-bottom:0;">
          <h5 class="modal-title" style="color:var(--cafe-brown);font-size:16px;" id="rtm-title">Confirm Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 pt-3 text-center">
          <p id="rtm-msg" style="color:#555;font-size:14px;margin-bottom:20px;"></p>
          <div style="display:flex;gap:10px;justify-content:center;">
            <button type="button" class="btn btn-secondary btn-sm" style="border-radius:6px;padding:6px 16px;" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-sm" style="background:var(--cafe-brown);color:#fff;border:none;border-radius:6px;padding:6px 16px;" id="rtm-confirm-btn" onclick="submitUserToggleRequest()">Submit Request</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- REGISTER USER MODAL (from admin panel) -->
  <div class="modal fade" id="registerUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom:1px solid var(--cafe-tan);">
          <h5 class="modal-title" style="color:var(--cafe-brown);font-size:16px;font-weight:700;">
            <i class="bi bi-person-plus-fill me-2"></i>Register New User
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Name Row -->
          <div class="row g-2 mb-2">
            <div class="col-md-4 col-7">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">First Name *</label>
              <input type="text" class="form-control" id="modal-reg-first-name" placeholder="Juan" style="padding:7px 10px;font-size:13px;">
            </div>
            <div class="col-md-2 col-5">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">M.I.</label>
              <input type="text" class="form-control" id="modal-reg-middle-initial" placeholder="D" maxlength="1"
                style="padding:7px 10px;font-size:13px;text-transform:uppercase;"
                oninput="this.value=this.value.replace(/[^a-zA-Z]/g,'').toUpperCase()">
            </div>
            <div class="col-md-4 col-8">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Last Name *</label>
              <input type="text" class="form-control" id="modal-reg-last-name" placeholder="Dela Cruz" style="padding:7px 10px;font-size:13px;">
            </div>
            <div class="col-md-2 col-4">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Suffix</label>
              <select class="form-select" id="modal-reg-suffix" style="padding:7px 10px;font-size:13px;">
                <option value="">None</option>
                <option value="Jr.">Jr.</option>
                <option value="Sr.">Sr.</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
                <option value="V">V</option>
              </select>
            </div>
          </div>

          <!-- Contact & Username Row -->
          <div class="row g-2 mb-2">
            <div class="col-md-6 col-12">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Email *</label>
              <input type="email" class="form-control" id="modal-reg-email" placeholder="juan@example.com"
                style="padding:7px 10px;font-size:13px;"
                oninput="suggestUsernameFromEmail('modal-reg-email','modal-reg-user')">
            </div>
            <div class="col-md-6 col-12">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Username *</label>
              <div style="position:relative;">
                <input type="text" class="form-control" id="modal-reg-user" placeholder="juandc" style="padding:7px 10px;font-size:13px;">
                <span id="modal-reg-user-hint" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:10px;color:#888;pointer-events:none;">auto-filled</span>
              </div>
            </div>
          </div>

          <!-- Password Row -->
          <div class="row g-2 mb-2">
            <div class="col-md-6 col-12">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Password *</label>
              <input type="password" class="form-control" id="modal-reg-pass" minlength="8" placeholder="••••••••" style="padding:7px 10px;font-size:13px;">
            </div>
            <div class="col-md-6 col-12">
              <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Confirm Password *</label>
              <input type="password" class="form-control" id="modal-reg-pass-confirm" minlength="8" placeholder="••••••••" style="padding:7px 10px;font-size:13px;">
            </div>
            <div class="col-12">
              <div style="font-size:11px;color:#777;margin-top:1px;line-height:1.3;">
                <i class="bi bi-shield-lock me-1"></i>Min. 8 characters with uppercase, lowercase & number.
              </div>
            </div>
          </div>

          <!-- Role Row -->
          <div class="mb-2">
            <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Role *</label>
            <select class="form-select" id="modal-reg-role" style="padding:7px 10px;font-size:13px;">
              <option value="cashier">Cashier</option>
              <option value="admin">Admin (requires owner approval)</option>
            </select>
          </div>
          <div id="modal-reg-result" style="display:none;margin-top:8px;"></div>
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
          <h5 class="modal-title" style="color: var(--cafe-brown); font-size:16px;" id="addItemModalTitle">Add Menu Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">

          <!-- STEP 1: Item Details -->
          <div id="add-item-step1">
            <div class="mb-3">
              <label class="form-label">Item Name</label>
              <input type="text" class="form-control" id="new-name" placeholder="e.g. Caramel Macchiato">
            </div>
            <div class="mb-3">
              <label class="form-label">Item Image</label>
              <input type="file" class="form-control" id="new-image" accept="image/*">
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
              <select class="form-select" id="new-category">
                <!-- Dynamically populated when modal opens via populateAddItemCategorySelect() -->
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Price (₱)</label>
              <input type="number" class="form-control" id="new-price" min="1" placeholder="0.00">
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

          <!-- STEP 2a: Cashier — Pending Approval Confirmation -->
          <div id="add-item-step2-cashier" style="display:none;">
            <div style="text-align:center; padding: 14px 6px 16px;">
              <div style="width:64px; height:64px; background:rgba(34,197,94,0.12); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; border:2px solid #22c55e;">
                <i class="bi bi-clock-history" style="font-size:32px; color:#16a34a;"></i>
              </div>
              <h5 style="color:var(--cafe-brown); font-weight:700; margin-bottom:8px;">Add Request Submitted</h5>
              <p style="font-size:14px; color:#475569; margin-bottom:16px;">
                Your request to add <strong id="add-item-cashier-item-name" style="color:var(--cafe-brown);"></strong> has been submitted for approval.
              </p>
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 18px; margin-bottom:12px; text-align:left; font-size:13px; color:#64748b;">
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                  <span>Requested By:</span>
                  <strong id="add-item-cashier-requester" style="color:#1e293b;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                  <span>Status:</span>
                  <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Approval</span>
                </div>
                <div style="font-size:12px; color:#94a3b8; border-top:1px dashed #cbd5e1; padding-top:8px;">
                  ℹ The Admin &amp; Super Admin have received the notification. Once approved, this item will immediately appear in the menu.
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 2b: Admin / Super Admin — Authorization Code Entry -->
          <div id="add-item-step2-admin" style="display:none;">
            <div style="text-align:center; padding: 10px 0 18px;">
              <div style="font-size:42px; margin-bottom:10px;">📧</div>
              <p style="font-size:14px; color:#64748b; margin-bottom:4px;">Request submitted! An authorization code has been sent to the admin email.</p>
              <p style="font-size:13px; color:#94a3b8; margin-bottom:18px;">The Super Admin can also approve directly from their email.</p>
              <div id="add-item-dev-token-box" style="display:none; background:#1e293b; border:1px solid #334155; border-radius:10px; padding:14px; margin-bottom:16px;">
                <p style="font-size:11px; color:#94a3b8; margin-bottom:6px;">⚙ DEV MODE — Authorization Code:</p>
                <span id="add-item-dev-token" style="font-size:26px; font-weight:700; letter-spacing:6px; font-family:monospace; color:#fbbf24;"></span>
              </div>
              <p style="font-size:13px; color:#475569; margin-bottom:8px;">Enter the 6-digit authorization code from the email:</p>
              <input type="text" class="form-control text-center" id="add-item-auth-code"
                maxlength="6" inputmode="numeric" placeholder="_ _ _ _ _ _"
                style="font-size:22px; font-weight:700; letter-spacing:6px; font-family:monospace; max-width:200px; margin:0 auto 14px;"
                oninput="this.value=this.value.replace(/[^0-9]/g,'').substring(0,6)">
              <div id="add-item-step2-msg" style="font-size:12px; color:#ef4444; min-height:18px;"></div>
            </div>
          </div>

        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--cafe-tan); gap: 8px;">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" id="add-item-cancel-btn">Cancel</button>
          <button class="btn btn-sm" id="add-item-step1-btn" style="background: var(--cafe-brown); color:#fff;" onclick="addMenuItemSubmit()"><i class="bi bi-send me-1"></i>Send Request</button>
          <button class="btn btn-sm" id="add-item-step2-btn" style="background: #16a34a; color:#fff; display:none;" onclick="addMenuItemConfirmCode()"><i class="bi bi-check-circle me-1"></i>Confirm &amp; Add</button>
          <button class="btn btn-sm btn-secondary" id="add-item-done-btn" style="display:none;" data-bs-dismiss="modal"><i class="bi bi-check-lg me-1"></i>Done</button>
        </div>
      </div>
    </div>
  </div>

  <!-- PHOTO UPLOAD MODAL (POS Admin) -->
  <div class="modal fade" id="posPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom: 1px solid var(--cafe-tan);">
          <h5 class="modal-title" style="color: var(--cafe-brown); font-size:16px;">
            <i class="bi bi-camera-fill me-2"></i>Upload Item Photo
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <p id="pos-photo-item-label" style="font-size:13px; color:#64748b; margin-bottom:12px;"></p>
          <div id="pos-photo-preview-wrap" style="text-align:center; margin-bottom:14px; display:none;">
            <img id="pos-photo-preview" src="" style="max-width:100%; max-height:180px; border-radius:10px; border:1px solid var(--cafe-tan); object-fit:cover;">
          </div>
          <div class="mb-3">
            <label class="form-label">Choose Photo</label>
            <input type="file" class="form-control" id="pos-photo-file" accept="image/*" onchange="posPreviewPhoto(this)">
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid var(--cafe-tan); gap: 8px;">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-sm" id="pos-photo-submit-btn" style="background: var(--cafe-brown); color:#fff;" onclick="posSubmitPhoto()">
            <i class="bi bi-upload me-1"></i>Upload Photo
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- SIZE SELECTION MODAL -->
  <div class="size-modal-overlay" id="size-modal">
    <div class="size-modal-card">
      <div class="sm-title" id="sm-item-name"></div>
      <div class="sm-subtitle">Select a size</div>
      <div class="size-btn-grid" id="sm-sizes"></div>
      <button class="size-modal-cancel" onclick="closeSizeModal()">Cancel</button>
    </div>
  </div>

  <!-- PAYMENT MODAL -->
  <div class="pay-modal-overlay" id="pay-modal">
    <div class="pay-modal-card">
      <div class="pay-modal-header">
        <h5><i class="bi bi-credit-card-2-front me-2"></i>Payment</h5>
        <button class="pm-close" onclick="closePayModal()" title="Cancel">&#x2715;</button>
      </div>
      <div class="pay-modal-body">

        <!-- Amount Due -->
        <div class="pay-amount-due">
          <div class="pad-label">Amount Due</div>
          <div class="pad-value" id="pay-amount-display">₱0.00</div>
        </div>

        <!-- Method tabs -->
        <div class="pay-method-tabs">
          <button class="pay-method-btn active" id="pm-cash-btn" onclick="selectPayMethod('Cash')">
            <i class="bi bi-cash-stack"></i>Cash
          </button>
          <button class="pay-method-btn" id="pm-gcash-btn" onclick="selectPayMethod('GCash')">
            <i class="bi bi-phone-fill" style="color:#2196f3;"></i>GCash
          </button>
        </div>

        <!-- Cash Panel -->
        <div id="pay-cash-panel">
          <div class="pay-section">
            <label for="pay-tendered">Cash Tendered (₱)</label>
            <input type="text" inputmode="numeric" pattern="[0-9]*" id="pay-tendered" class="pay-input" placeholder="0" autocomplete="off"
              onkeydown="handleTenderedKeydown(event, this);"
              oninput="handleTenderedInput(this);"
              onblur="if(this.value==='0'){this.value='';updateChange();}">
          </div>
          <div class="pay-change-row" id="pay-change-row">
            <span class="pcr-label">Change</span>
            <span class="pcr-value" id="pay-change-display">₱0.00</span>
          </div>
          <button class="pay-confirm-btn" id="pay-cash-confirm-btn" onclick="confirmPayment()" disabled>
            <i class="bi bi-check-circle-fill"></i> Confirm Cash Payment
          </button>
        </div>

        <!-- GCash Panel — PayMongo Payment Links -->
        <div id="pay-gcash-panel" style="display:none;">

          <!-- State: needs key configured -->
          <div id="gcash-no-key" style="display:none; text-align:center; padding:18px 0 10px;">
            <div style="font-size:46px; color:#e0e0e0; margin-bottom:10px;"><i class="bi bi-key"></i></div>
            <div style="font-size:13px; color:#888; margin-bottom:14px; line-height:1.6;">PayMongo API key not configured.<br>Ask an admin to add it in Settings.</div>
          </div>

          <!-- State: ready to generate -->
          <div id="gcash-ready-state" style="text-align:center; padding:8px 0;">
            <div style="background:linear-gradient(135deg,#0070f3,#0099ff); color:#fff; border-radius:10px; padding:8px 16px; display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:800; letter-spacing:0.04em; margin-bottom:14px;">
              <svg width="18" height="18" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="22" stroke="#fff" stroke-width="3"/><path d="M24 14c-5.52 0-10 4.48-10 10s4.48 10 10 10c2.4 0 4.6-.85 6.32-2.26l-3.07-3.07A5.95 5.95 0 0 1 24 30c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.16.67 4.24 1.76L25.17 22.83l8.83 1.18-1.18-8.83-2.34 2.34A9.92 9.92 0 0 0 24 14z" fill="#fff"/></svg>
              Pay with GCash via PayMongo
            </div>
            <div style="background:linear-gradient(135deg,#e3f2fd,#ede7f6); border:2.5px solid #0070f3; border-radius:12px; padding:14px 18px; text-align:center; margin-bottom:14px;">
              <div style="font-size:11px; color:#0070f3; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:6px;">Total to Collect</div>
              <div style="font-size:32px; font-weight:900; color:#0d47a1; line-height:1;" id="gcash-pm-amount">&#8369;0.00</div>
            </div>
            <button id="gcash-generate-btn" onclick="generateGCashLink()"
              style="background:linear-gradient(135deg,#0070f3,#0099ff); color:#fff; border:none; border-radius:12px; padding:13px 28px; font-size:14px; font-weight:700; cursor:pointer; width:100%; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 4px 16px rgba(0,112,243,0.3); transition:opacity 0.2s;">
              <i class="bi bi-qr-code" style="font-size:18px;"></i> Generate GCash QR PH
            </button>
            <div id="gcash-gen-error" style="display:none; margin-top:10px; font-size:12px; color:#dc2626; background:#fee2e2; border-radius:8px; padding:8px 12px;"></div>
          </div>

          <!-- State: link generated — show QR + poll -->
          <div id="gcash-link-state" style="display:none; text-align:center;">
            <div style="font-size:12px; font-weight:700; color:#0070f3; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.06em;">
              <i class="bi bi-qr-code-scan me-1"></i>Scan QR PH &#8212; Amount Pre-set
            </div>
            <div style="padding:10px; background:#fff; border:2px solid #e8eaf6; border-radius:16px; box-shadow:0 6px 24px rgba(0,112,243,0.12); display:inline-block; margin-bottom:10px;">
              <img id="gcash-qr-canvas" src="" alt="QR Code" style="width:200px; height:200px; display:block; border-radius:8px;">
            </div>
            <div style="margin-bottom:10px;">
              <a id="gcash-link-btn" href="#" target="_blank"
                style="display:inline-flex; align-items:center; gap:6px; background:#e8f0fe; border:1.5px solid #adc2fb; color:#1a73e8; border-radius:20px; padding:6px 16px; font-size:12px; font-weight:700; text-decoration:none;">
                <i class="bi bi-box-arrow-up-right"></i> Open Payment Page
              </a>
            </div>
            <div id="gcash-poll-status" style="display:flex; align-items:center; justify-content:center; gap:8px; font-size:13px; color:#555; margin-bottom:12px;">
              <span class="gcash-spinner"></span>
              <span id="gcash-poll-text">Waiting for GCash payment&#8230;</span>
            </div>
            <div id="gcash-paid-badge" style="display:none; background:#d1fae5; border:2px solid #059669; border-radius:12px; padding:12px; margin-bottom:12px;">
              <div style="font-size:24px; margin-bottom:4px;">&#10003;&#10003;</div>
              <div style="font-weight:800; color:#059669; font-size:14px;">Payment Received!</div>
              <div style="font-size:12px; color:#6b7280;">Completing your order&#8230;</div>
            </div>
            <div style="display:flex; gap:8px;">
              <button onclick="resetGCashState()" style="flex:1; background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:10px; padding:9px; font-size:12px; font-weight:600; color:#555; cursor:pointer;">
                <i class="bi bi-arrow-left me-1"></i>Cancel
              </button>
              <button id="pay-gcash-confirm-btn" onclick="confirmPayment()" disabled
                style="flex:2; background:linear-gradient(135deg,#0070f3,#0099ff); color:#fff; border:none; border-radius:10px; padding:9px 14px; font-size:13px; font-weight:700; cursor:pointer; opacity:0.5; display:flex; align-items:center; justify-content:center; gap:6px;">
                <i class="bi bi-check-circle-fill"></i> Confirm Manually
              </button>
            </div>
            <div style="font-size:10.5px; color:#aaa; margin-top:6px;">Auto-confirms when payment is received &#183; GCash only</div>
          </div>

        <span class="pay-cancel-link" onclick="closePayModal()">Cancel</span>
      </div>
    </div>
  </div>
  </div>

  <!-- 58MM THERMAL RECEIPT MODAL -->
  <div class="receipt-modal-overlay" id="receiptModal" onclick="if(event.target===this)closeReceiptModal();">
    <div class="receipt-modal-card">
      <div class="receipt-modal-header no-print">
        <div class="receipt-modal-title">
          <i class="bi bi-receipt-cutoff" style="color:var(--cafe-amber);font-size:18px;"></i>
          <span>Official Sales Receipt</span>
          <span style="background:rgba(16,185,129,0.15);color:#10b981;border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:2px 8px;font-size:11px;font-weight:700;">58mm Ready</span>
        </div>
        <button type="button" class="receipt-modal-close" onclick="closeReceiptModal()" title="Close">&#x2715;</button>
      </div>

      <div class="receipt-modal-body">
        <!-- THERMAL RECEIPT PAPER (58mm Paper / 43mm Printable Head) -->
        <div class="thermal-receipt-paper" id="pos-receipt-printable">

          <!-- STORE HEADER -->
          <div style="text-align:center;margin-bottom:3px;color:#000;">
            <div style="font-size:14px;font-weight:900;letter-spacing:0.5px;line-height:1.2;">CHIPIPIE CAFE</div>
            <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-top:1px;">Coffee &amp; Pastries</div>
            <div style="font-size:9px;margin-top:1px;">Poblacion, Philippines</div>
            <div style="font-size:8.5px;margin-top:1px;">Tel: (045) 123-4567</div>
          </div>

          <div style="border-top:1px dashed #000;margin:4px 0;"></div>

          <!-- ORDER META -->
          <div style="font-size:9.5px;line-height:1.45;color:#000;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-weight:800;">ORDER: <span id="rcpt-order-num" style="font-size:11px;font-weight:900;">#000</span></span>
              <span id="rcpt-status-tag" style="font-weight:900;font-size:9.5px;letter-spacing:0.5px;">[PAID]</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:9px;">
              <span>DATE:</span>
              <span id="rcpt-date" style="font-weight:700;text-align:right;">--</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span>CASHIER:</span>
              <span id="rcpt-cashier" style="font-weight:700;text-align:right;">Staff</span>
            </div>
            <div style="display:flex;justify-content:space-between;">
              <span>PAYMENT:</span>
              <strong id="rcpt-payment-method" style="font-weight:900;">CASH</strong>
            </div>
          </div>

          <div style="border-top:1px dashed #000;margin:4px 0;"></div>

          <!-- ITEMS COLUMN HEADER -->
          <div style="display:flex;justify-content:space-between;font-size:9.5px;font-weight:900;text-transform:uppercase;margin-bottom:2px;color:#000;">
            <span>QTY &nbsp;ITEM</span>
            <span>AMT</span>
          </div>
          <div style="border-top:1.5px solid #000;margin-bottom:3px;"></div>

          <!-- ITEMS LIST (filled by JS) -->
          <div id="rcpt-items-list" style="font-size:10px;line-height:1.35;color:#000;"></div>

          <div style="border-top:1px dashed #000;margin:4px 0 3px 0;"></div>

          <!-- TOTALS -->
          <div style="font-size:10px;line-height:1.45;color:#000;">
            <div style="display:flex;justify-content:space-between;">
              <span id="rcpt-item-count">Items (0):</span>
              <span id="rcpt-subtotal" style="font-weight:700;">&#x20b1;0.00</span>
            </div>
            <div style="border-top:1.5px solid #000;margin:2px 0;"></div>
            <div style="display:flex;justify-content:space-between;font-size:12.5px;font-weight:900;">
              <span>TOTAL:</span>
              <span id="rcpt-total">&#x20b1;0.00</span>
            </div>
            <div style="border-top:1.5px solid #000;margin:2px 0;"></div>
            <div style="display:flex;justify-content:space-between;" id="rcpt-tendered-row">
              <span id="rcpt-tendered-label">Cash Tendered:</span>
              <span id="rcpt-tendered" style="font-weight:700;">&#x20b1;0.00</span>
            </div>
            <div style="display:flex;justify-content:space-between;" id="rcpt-change-row">
              <span>Change:</span>
              <span id="rcpt-change" style="font-weight:800;">&#x20b1;0.00</span>
            </div>
            <div id="rcpt-gcash-ref-row" style="display:none;justify-content:space-between;font-size:9px;color:#000;margin-top:1px;">
              <span>Method:</span>
              <span id="rcpt-gcash-ref" style="font-weight:700;">GCash</span>
            </div>
          </div>

          <div style="border-top:1px dashed #000;margin:4px 0 3px 0;"></div>

          <!-- BARCODE -->
          <div style="text-align:center;margin:2px 0;">
            <svg id="rcpt-barcode" viewBox="0 0 160 26" style="width:100%;max-width:130px;height:22px;display:block;margin:0 auto;shape-rendering:crispEdges;"></svg>
            <div id="rcpt-barcode-text" style="font-size:9px;letter-spacing:1.5px;font-weight:800;margin-top:1px;color:#000;">#000</div>
          </div>

          <div style="border-top:1px dashed #000;margin:4px 0 3px 0;"></div>

          <!-- FOOTER -->
          <div style="text-align:center;font-size:9px;line-height:1.35;color:#000;">
            <div style="font-weight:900;text-transform:uppercase;letter-spacing:0.5px;">THANK YOU!</div>
            <div>Please keep this receipt</div>
            <div style="margin-top:1px;font-size:8px;color:#000;">Powered by ChipiPie POS</div>
          </div>

        </div>
      </div>

      <div class="receipt-modal-footer no-print">
        <button type="button" class="btn btn-outline-secondary" onclick="closeReceiptModal()" style="border-radius:10px;padding:9px 12px;font-size:12.5px;font-weight:600;flex:1;">
          <i class="bi bi-x-circle me-1"></i>Close
        </button>
        <button type="button" class="btn" onclick="reprintKitchenFromModal()" style="background:#ea580c;color:#fff;border:none;border-radius:10px;padding:9px 14px;font-size:12.5px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 4px 12px rgba(234,88,12,0.3);" title="Print kitchen ticket">
          <i class="bi bi-fire"></i>Kitchen
        </button>
        <button type="button" class="btn" onclick="printThermalReceipt()" style="background:linear-gradient(135deg, #10b981, #059669);color:#fff;border:none;border-radius:10px;padding:9px 16px;font-size:12.5px;font-weight:700;flex:2;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 4px 14px rgba(16,185,129,0.35);">
          <i class="bi bi-printer-fill" style="font-size:15px;"></i>Print Receipt (58mm)
        </button>
      </div>
    </div>
  </div>

  <!-- PRINT OPTIONS MODAL (shown after payment) -->
  <div class="print-opts-overlay" id="printOptsModal">
    <div class="print-opts-card">
      <div class="print-opts-header">
        <div class="poi-icon"><i class="bi bi-printer-fill"></i></div>
        <h5>Payment Confirmed! ✓</h5>
        <p>Order <strong id="poi-order-num">#001</strong> &mdash; Select print option</p>
      </div>
      <div class="print-opts-body">
        <!-- Option 1: Kitchen Only -->
        <button class="print-opt-btn kitchen-btn" onclick="printKitchenOnly()">
          <div class="pob-num">1</div>
          <div class="pob-text">
            <span class="pob-title">Kitchen Only</span>
            <span class="pob-desc">Print kitchen order ticket. No customer receipt.</span>
          </div>
          <i class="bi bi-fire pob-icon"></i>
        </button>
        <!-- Option 2: Customer Receipt Only -->
        <button class="print-opt-btn receipt-btn" onclick="printReceiptOnly()">
          <div class="pob-num">2</div>
          <div class="pob-text">
            <span class="pob-title">Customer Receipt Only</span>
            <span class="pob-desc">Show &amp; print 58mm customer receipt. No kitchen ticket.</span>
          </div>
          <i class="bi bi-receipt-cutoff pob-icon"></i>
        </button>
        <!-- Option 3: Kitchen + Customer Receipt -->
        <button class="print-opt-btn full-btn" onclick="printWithReceipt()">
          <div class="pob-num">3</div>
          <div class="pob-text">
            <span class="pob-title">Kitchen + Customer Receipt</span>
            <span class="pob-desc">Print kitchen ticket &amp; open 58mm customer receipt.</span>
          </div>
          <i class="bi bi-check2-circle pob-icon"></i>
        </button>
      </div>
      <div class="print-opts-footer">
        <button class="print-opts-skip" onclick="skipPrintOptions()">Skip &mdash; No printing needed</button>
      </div>
    </div>
  </div>

  <!-- KITCHEN TICKET (hidden, for printing only) -->
  <div id="pos-kitchen-printable" style="display:none; position:absolute; left:-9999px; top:0;">
    <!-- Kitchen ticket content is populated dynamically by JS -->
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
      opts.headers = {};
      const sid = localStorage.getItem('chipipie_session_id');
      if (sid) {
        opts.headers['X-Session-Id'] = sid;
      }
      if (data) {
        opts.method = method || 'POST';
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
      } else {
        opts.method = method || 'GET';
      }
      const res = await fetch(API + endpoint, opts);
      return await res.json();
    }

    /* ========== DATA ========== */
    let menuItems = [];
    let posMenuCategories = [];   // [{id, name}] — loaded from API
    let pendingDeleteIds = new Set();

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
      const sNav = document.querySelector('.sidebar-nav');
      if (sNav) sNav.scrollLeft = 0;

      const titles = { dashboard: 'Dashboard', pos: 'New Order', orders: 'Orders', menu: 'Menu Items', reports: 'Analytics & Reports', users: 'User Management' };
      document.getElementById('page-title').textContent = titles[name] || '';

      if (name === 'pos') { renderPOS(); initShortcutScrollSpy(); }
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
    async function updateDashboard() {
      try {
        const stats = await api('orders.php?action=stats');
        if (!stats.success) return;

        document.getElementById('kpi-sales').textContent = '₱' + stats.today.revenue.toLocaleString();
        document.getElementById('kpi-orders').textContent = stats.today.orders;
        document.getElementById('kpi-avg').textContent = stats.today.orders ? '₱' + Math.round(stats.avg_order) : '₱0';

        const cupsEl = document.getElementById('kpi-cups');
        if (cupsEl) cupsEl.textContent = stats.cups_sold || 0;
        const cupsTrendEl = document.getElementById('kpi-cups-trend');
        if (cupsTrendEl) cupsTrendEl.textContent = `${stats.food_sold || 0} food items sold`;

        const bestSellerEl = document.getElementById('kpi-top');
        if (bestSellerEl) bestSellerEl.textContent = stats.top_seller ? stats.top_seller.name : '—';
        const topTrendEl = document.getElementById('kpi-top-trend');
        if (topTrendEl) topTrendEl.textContent = stats.top_seller ? `${stats.top_seller.sold} units sold (All time)` : 'No sales yet';

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

        // Top items from API
        let topItems = stats.top_items || [];
        if (topItems.length === 0) topItems = [{ name: 'No sales yet', sold: 0, revenue: 0 }];
        const maxSold = Math.max(topItems[0].sold || 0, 1);

        document.getElementById('top-items-list').innerHTML = topItems.map(i => `
    <div class="mb-3">
      <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:3px;">
        <span style="font-weight:500; color:#333;">${i.name}</span>
        <span style="color:var(--cafe-amber); font-weight:600;">₱${parseFloat(i.revenue || 0).toLocaleString()}</span>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <div class="progress-bar-bg" style="flex:1;">
          <div class="progress-bar-fill" style="width:${Math.round((i.sold || 0) / maxSold * 100)}%;"></div>
        </div>
        <span style="font-size:11px; color:#888; min-width:42px;">${i.sold || 0} sold</span>
      </div>
    </div>
  `).join('');

        // Update weekly chart if it exists
        if (window.salesChartIns) {
          window.salesChartIns.data.datasets[0].data = stats.weekly_chart;
          window.salesChartIns.update();
        }

        // Update dashboard hourly chart
        if (window.dashHourlyChartIns && stats.hourly_chart) {
          window.dashHourlyChartIns.data.datasets[0].data = stats.hourly_chart;
          window.dashHourlyChartIns.update();
        }

        // Update doughnut chart
        if (window.categoryChartIns && stats.cat_breakdown && stats.cat_breakdown.length > 0) {
          const catLabels = stats.cat_breakdown.map(c => c.name);
          const catData = stats.cat_breakdown.map(c => parseInt(c.qty));
          const catColorPalette = [V_BLUE,V_PURPLE,V_GREEN,V_ORANGE,V_RED,V_TEAL,V_PINK,V_LIME,V_INDIGO];
          window.categoryChartIns.data.labels = catLabels;
          window.categoryChartIns.data.datasets[0].data = catData;
          window.categoryChartIns.data.datasets[0].backgroundColor = catLabels.map((_, i) => catColorPalette[i % catColorPalette.length]);
          window.categoryChartIns.update();
        }

      } catch(e) { console.warn('Dashboard update failed:', e); }
    }

    /* ========== POS ========== */
    let _activeBranch = 'coffee'; // 'coffee' | 'pizza'

    function switchBranch(branch) {
      if (branch === _activeBranch) return;
      _activeBranch = branch;
      const coffeeBar = document.getElementById('coffee-shortcuts');
      const pizzaBar  = document.getElementById('pizza-shortcuts');

      // Show the right shortcut bar immediately
      if (branch === 'coffee') {
        if (coffeeBar) coffeeBar.style.display = '';
        if (pizzaBar)  pizzaBar.style.display  = 'none';
      } else {
        if (coffeeBar) coffeeBar.style.display = 'none';
        if (pizzaBar)  pizzaBar.style.display  = '';
      }
      document.getElementById('menu-search').value = '';

      // Render content FIRST while old theme class is still on body.
      // This ensures all new DOM elements exist before the theme class changes,
      // so CSS transitions fire on them in both directions.
      renderPOS();

      // THEN switch the theme class — all elements already exist, transitions fire cleanly.
      requestAnimationFrame(() => requestAnimationFrame(() => {
        if (branch === 'pizza') {
          document.body.classList.add('pizza-theme');
        } else {
          document.body.classList.remove('pizza-theme');
        }
      }));
    }

    function renderPOS() {
      const q = (document.getElementById('menu-search')?.value || '').toLowerCase();

      // Super-group definitions per branch
      const coffeeGroups = [
        { label: 'Coffee',     sgId: 'coffee',     icon: 'bi-cup-hot-fill', cats: ['Hot Coffee', 'Iced Coffee'] },
        { label: 'Non-Coffee', sgId: 'non-coffee', icon: 'bi-cup-straw',    cats: ['Classic Milktea', 'Virgin Mojito', 'Citrus Popping Boba', 'Soda Float', 'Matcha Series', 'Frappé'] },
        { label: 'Snack',      sgId: 'snack',      icon: 'bi-egg-fried',    cats: ['Fork and Fun'] },
      ];
      const pizzaGroups = [
        { label: 'Pizza',       sgId: 'pizza',       icon: 'bi-circle',         cats: ['Pizza'],                        noSubcat: true },
        { label: 'Quesadilla',  sgId: 'quesadilla',  icon: 'bi-grid-3x3-gap',   cats: ['Quesadilla'],                   noSubcat: true },
        { label: 'Patatas',     sgId: 'patatas',     icon: 'bi-bag-heart',       cats: ['Patatas'],                      noSubcat: true },
        { label: 'Pasta',       sgId: 'pasta',       icon: 'bi-stars',           cats: ['Pasta'],                        noSubcat: true },
        { label: 'Chickenpops', sgId: 'chickenpops', icon: 'bi-lightning',       cats: ['Chickenpops'],                  noSubcat: true },
        { label: 'Combo',       sgId: 'combo',       icon: 'bi-collection',      cats: ['Combo', 'Gipitmeal'] },
      ];

      const superGroups = _activeBranch === 'pizza' ? pizzaGroups : coffeeGroups;

      // Helper: build card HTML
      function buildCard(item) {
        const isUnavailable = !item.available;
        const isOutOfStock = item.stock <= 0;
        const isLowStock = !isUnavailable && item.stock > 0 && item.stock <= item.minStock;
        const isDisabled = isUnavailable || isOutOfStock;

        let stockClass = 'stock-ok';
        let stockIcon = '';
        let stockLabel = '';

        if (isUnavailable) {
          stockClass = 'stock-out';
          stockIcon = '<i class="bi bi-x-circle-fill me-1"></i>';
          stockLabel = stockIcon + 'Unavailable';
        } else if (isOutOfStock) {
          stockClass = 'stock-out';
          stockIcon = '<i class="bi bi-x-circle-fill me-1"></i>';
          stockLabel = stockIcon + 'Out of Stock';
        } else if (isLowStock) {
          stockClass = 'stock-low';
          stockIcon = '<i class="bi bi-exclamation-triangle-fill me-1"></i>';
          stockLabel = stockIcon + `Low: ${item.stock}`;
        } else {
          stockClass = 'stock-ok';
          stockLabel = `${item.stock} in stock`;
        }

        const cardClass = isDisabled ? 'product-card out-of-stock' : 'product-card';

        let priceDisplay = `₱${item.price}`;
        if (item.sizePrices) {
          const prices = Object.values(item.sizePrices);
          const minP = Math.min(...prices);
          const maxP = Math.max(...prices);
          if (minP !== maxP) priceDisplay = `₱${minP}–₱${maxP}`;
        }

        const escapedName = item.name.replace(/'/g, "\\'");
        const clickHandler = isUnavailable
          ? `showToast('⚠ ${escapedName} is currently unavailable!')`
          : (isOutOfStock
              ? `showToast('⚠ ${escapedName} is out of stock!')`
              : `addToCart(${item.id})`);

        return `
  <div class="col-6 col-md-4 col-xl-3" style="margin-bottom:8px;">
    <div class="${cardClass}" onclick="${clickHandler}" style="min-height:110px;">
      ${item.image
        ? `<img src="${item.image}" style="width:36px;height:36px;object-fit:cover;border-radius:50%;margin:0 auto 6px auto;display:block;">`
        : `<span class="prod-emoji"><i class="bi bi-cup-hot-fill" style="font-size:24px;color:var(--cafe-brown);"></i></span>`}
      <div class="prod-name">${item.name}</div>
      <div class="prod-price">${priceDisplay}</div>
      <div class="stock-badge ${stockClass}">${stockLabel}</div>
    </div>
  </div>`;
      }

      const grid = document.getElementById('menu-grid');
      let html = '';
      let anyVisible = false;

      for (const sg of superGroups) {
        // Collect all items for this super-group that match search
        const sgItems = menuItems
          .filter(m => sg.cats.includes(m.category))
          .filter(m => !q || m.name.toLowerCase().includes(q));

        if (sgItems.length === 0) continue;
        anyVisible = true;

        // Build sub-category button bar + items
        const sgId = sg.sgId || sg.label.replace(/[^a-z0-9]/gi, '-').toLowerCase();
        let btnBarHtml = '';
        let itemsHtml  = '';
        let isFirst = true;
        for (const cat of sg.cats) {
          const catItems = sgItems.filter(m => m.category === cat);
          if (catItems.length === 0) continue;
          const catId = (sgId + '-' + cat.replace(/[^a-z0-9]/gi, '-').toLowerCase());
          const isActive = isFirst;
          btnBarHtml += `<button class="pos-subcat-btn${isActive ? ' active' : ''}" id="scbtn-${catId}" onclick="selectSubcat('${sgId}','${catId}')">${cat}</button>`;
          itemsHtml  += `
    <div class="pos-subcat-items${isActive ? '' : ' collapsed'}" id="items-${catId}">
      <div class="row g-2 pb-2">${catItems.map(buildCard).join('')}</div>
    </div>`;
          isFirst = false;
        }

        html += `
  <div class="pos-supergroup mb-4">
    <div class="pos-supergroup-header" id="sghdr-${sgId}" onclick="toggleSupergroup('${sgId}')">
      <span><i class="bi ${sg.icon} me-2"></i>${sg.label}</span>
      <i class="bi bi-chevron-down sg-chevron"></i>
    </div>
    <div class="pos-supergroup-body" id="sgbody-${sgId}">
      ${sg.noSubcat ? '' : `<div class="pos-subcat-btn-bar">${btnBarHtml}</div>`}
      ${itemsHtml}
    </div>
  </div>`;
      }

      grid.innerHTML = anyVisible ? html : '<div class="text-center text-muted py-5">No items found.</div>';
      renderCart();

      // Initialize the slider — snap without animation so it doesn't tweak on load
      setTimeout(() => {
        const activeGroup = document.getElementById(_activeBranch + '-shortcuts');
        if (activeGroup) {
          const activeBtn = activeGroup.querySelector('.pos-cat-shortcut-btn.active');
          const targetBtn = activeBtn || activeGroup.querySelector('.pos-cat-shortcut-btn');
          if (targetBtn) {
            const sgId = targetBtn.id.replace('scbtn-', '');
            setActiveShortcutBtn(sgId, true); // true = snap, no animation
          }
        }
      }, 50);
    }

    /* ========== CATEGORY SHORTCUT BAR ========== */
    function scrollToCategory(sgId) {
      // Lock scroll spy so it doesn't fight the slider during smooth scroll
      _scrollSpyLocked = true;
      clearTimeout(_scrollSpyLockTimer);
      _scrollSpyLockTimer = setTimeout(() => { _scrollSpyLocked = false; }, 900);

      // Update active highlight immediately
      setActiveShortcutBtn(sgId);

      const target = document.getElementById('sghdr-' + sgId);
      const scrollArea = document.getElementById('pos-scroll-area');
      if (!target || !scrollArea) return;

      // Expand supergroup if collapsed
      const body = document.getElementById('sgbody-' + sgId);
      const hdr  = document.getElementById('sghdr-' + sgId);
      if (body && body.classList.contains('collapsed')) {
        body.classList.remove('collapsed');
        if (hdr) hdr.classList.remove('collapsed');
      }

      const areaTop  = scrollArea.getBoundingClientRect().top;
      const targetTop = target.getBoundingClientRect().top;
      const offset = targetTop - areaTop - 8;
      scrollArea.scrollBy({ top: offset, behavior: 'smooth' });
    }

    function setActiveShortcutBtn(sgId, noAnimation) {
      document.querySelectorAll('.pos-cat-shortcut-btn').forEach(b => b.classList.remove('active'));
      const btn = document.getElementById('scbtn-' + sgId);
      if (btn) {
        btn.classList.add('active');
        const parent = btn.parentElement;
        const slider = parent.querySelector('.shortcut-slider');
        if (slider) {
          // Use getBoundingClientRect for pixel-perfect positioning (ignores borders/padding)
          const parentRect = parent.getBoundingClientRect();
          const btnRect   = btn.getBoundingClientRect();
          const left  = btnRect.left - parentRect.left;
          const width = btnRect.width;
          if (noAnimation) {
            slider.style.transition = 'none';
            slider.style.width     = width + 'px';
            slider.style.transform = `translateX(${left}px)`;
            requestAnimationFrame(() => requestAnimationFrame(() => {
              slider.style.transition = '';
            }));
          } else {
            slider.style.width     = width + 'px';
            slider.style.transform = `translateX(${left}px)`;
          }
        }
      }
    }

    // Highlight the shortcut button as user scrolls (attach only once)
    let _scrollSpyAttached = false;
    let _scrollSpyLocked   = false;   // true while a tab-click is animating
    let _scrollSpyLockTimer = null;
    function initShortcutScrollSpy() {
      if (_scrollSpyAttached) return;
      const scrollArea = document.getElementById('pos-scroll-area');
      if (!scrollArea) return;
      _scrollSpyAttached = true;
      const sgIds = ['coffee', 'non-coffee', 'snack'];
      scrollArea.addEventListener('scroll', function() {
        const areaTop = scrollArea.getBoundingClientRect().top;
        let current = sgIds[0];
        for (const id of sgIds) {
          const el = document.getElementById('sghdr-' + id);
          if (!el) continue;
          const elTop = el.getBoundingClientRect().top - areaTop;
          if (elTop <= 40) current = id;
        }
        if (_scrollSpyLocked) return; // tab-click in progress — don't fight it
        setActiveShortcutBtn(current);
      }, { passive: true });
    }

    /* ========== TAB SELECT ========== */
    function selectSubcat(sgId, catId) {
      // Find all subcat buttons and items in this supergroup
      const body = document.getElementById('sgbody-' + sgId);
      if (!body) return;

      // Hide all items and deactivate all buttons in this group
      body.querySelectorAll('.pos-subcat-items').forEach(el => el.classList.add('collapsed'));
      body.querySelectorAll('.pos-subcat-btn').forEach(btn => btn.classList.remove('active'));

      // Show selected tab's items and mark its button active
      const items = document.getElementById('items-' + catId);
      const btn   = document.getElementById('scbtn-' + catId);
      if (items) items.classList.remove('collapsed');
      if (btn)   btn.classList.add('active');
    }

    function toggleSupergroup(sgId) {
      const hdr = document.getElementById('sghdr-' + sgId);
      const body = document.getElementById('sgbody-' + sgId);
      if (!hdr || !body) return;
      const isCollapsed = body.classList.toggle('collapsed');
      hdr.classList.toggle('collapsed', isCollapsed);
    }

    /* ========== SIZE MODAL ========== */
    let pendingSizeItemId = null;

    function addToCart(id) {
      const item = menuItems.find(m => m.id == id);
      if (!item) return;

      if (!item.available) {
        showToast(`⚠ ${item.name} is currently unavailable!`);
        return;
      }
      if (item.stock <= 0) {
        showToast(`⚠ ${item.name} is out of stock!`);
        return;
      }

      // If item has size variants, show size selection modal
      if (item.sizePrices && Object.keys(item.sizePrices).length > 1) {
        openSizeModal(id);
        return;
      }

      // Single price item — add directly
      const cartKey = String(id) + '_default';
      const currentInCart = cart[cartKey]?.qty || 0;
      if (currentInCart + 1 > item.stock) {
        showToast(`⚠ Only ${item.stock} ${item.name} left in stock!`);
        return;
      }

      cart[cartKey] = { itemId: id, size: null, price: item.price, qty: currentInCart + 1 };
      renderCart();
      showToast(`✓ ${item.name} added`);
    }

    function openSizeModal(id) {
      const item = menuItems.find(m => m.id == id);
      if (!item || !item.sizePrices) return;
      pendingSizeItemId = id;

      document.getElementById('sm-item-name').textContent = item.name;
      const sizesEl = document.getElementById('sm-sizes');
      sizesEl.innerHTML = Object.entries(item.sizePrices).map(([size, price]) =>
        `<button class="size-btn" onclick="selectSize('${size}', ${price})">
          <span class="size-label">${size}</span>
          <span class="size-price">₱${price}</span>
        </button>`
      ).join('');

      document.getElementById('size-modal').classList.add('show');
    }

    function closeSizeModal() {
      document.getElementById('size-modal').classList.remove('show');
      pendingSizeItemId = null;
    }

    function selectSize(size, price) {
      const id = pendingSizeItemId;
      if (!id) return;
      const item = menuItems.find(m => m.id == id);
      if (!item) return;

      if (!item.available) {
        showToast(`⚠ ${item.name} is currently unavailable!`);
        closeSizeModal();
        return;
      }
      if (item.stock <= 0) {
        showToast(`⚠ ${item.name} is out of stock!`);
        closeSizeModal();
        return;
      }

      const cartKey = id + '_' + size;
      const totalItemQty = getTotalItemQty(id);
      if (totalItemQty + 1 > item.stock) {
        showToast(`⚠ Only ${item.stock} ${item.name} left in stock!`);
        closeSizeModal();
        return;
      }

      const currentInCart = cart[cartKey]?.qty || 0;
      cart[cartKey] = { itemId: id, size: size, price: price, qty: currentInCart + 1 };
      closeSizeModal();
      renderCart();
      showToast(`✓ ${item.name} (${size}) added`);
    }

    // Get total quantity of an item across all sizes in cart
    function getTotalItemQty(itemId) {
      let total = 0;
      for (let key in cart) {
        if (cart[key].itemId == itemId) {
          total += cart[key].qty;
        }
      }
      return total;
    }

    // Close size modal on overlay click
    document.addEventListener('click', function(e) {
      if (e.target && e.target.id === 'size-modal') {
        closeSizeModal();
      }
    });

    let _activeMobilePosTab = 'menu';

    function switchMobilePosTab(tab) {
      _activeMobilePosTab = tab;
      const menuCol = document.getElementById('pos-menu-col');
      const cartCol = document.getElementById('pos-cart-col');
      const tabMenuBtn = document.getElementById('pos-mtab-menu');
      const tabCartBtn = document.getElementById('pos-mtab-cart');
      const floatBar = document.getElementById('pos-mobile-float-bar');

      if (!menuCol || !cartCol) return;

      if (tab === 'cart') {
        menuCol.classList.remove('mobile-active');
        cartCol.classList.add('mobile-active');
        if (tabMenuBtn) tabMenuBtn.classList.remove('active');
        if (tabCartBtn) tabCartBtn.classList.add('active');
        if (floatBar) floatBar.classList.remove('visible');
      } else {
        cartCol.classList.remove('mobile-active');
        menuCol.classList.add('mobile-active');
        if (tabMenuBtn) tabMenuBtn.classList.add('active');
        if (tabCartBtn) tabCartBtn.classList.remove('active');
        const keys = Object.keys(cart);
        if (floatBar) {
          if (keys.length > 0) floatBar.classList.add('visible');
          else floatBar.classList.remove('visible');
        }
      }
    }

    function renderCart() {
      const keys = Object.keys(cart);
      const totalQty = keys.reduce((s, key) => s + cart[key].qty, 0);
      const countEl = document.getElementById('cart-count');
      if (countEl) countEl.textContent = totalQty + ' item' + (totalQty !== 1 ? 's' : '');

      const mCount = document.getElementById('pos-mtab-count');
      if (mCount) mCount.textContent = totalQty;
      const mFloatQty = document.getElementById('pmf-badge-qty');
      if (mFloatQty) mFloatQty.textContent = totalQty;

      const floatBar = document.getElementById('pos-mobile-float-bar');

      if (!keys.length) {
        document.getElementById('cart-items').innerHTML = `
      <div class="empty-cart">
        <i class="bi bi-bag"></i>
        No items yet.<br>Tap a menu item to add.
      </div>`;
        document.getElementById('cart-total').textContent = '₱0.00';
        const mTotal = document.getElementById('pos-mtab-total');
        if (mTotal) mTotal.textContent = '₱0.00';
        const mFloatTot = document.getElementById('pmf-badge-total');
        if (mFloatTot) mFloatTot.textContent = '₱0.00';
        if (floatBar) floatBar.classList.remove('visible');
        return;
      }

      let total = 0;
      document.getElementById('cart-items').innerHTML = keys.map(key => {
        const entry = cart[key];
        const item = menuItems.find(m => m.id == entry.itemId);
        if (!item) return '';
        const sub = entry.price * entry.qty;
        total += sub;
        const sizeLabel = entry.size ? ` <span style="font-size:11px; color:#888; font-weight:400;">(${entry.size})</span>` : '';
        return `
      <div class="cart-item-row">
        ${item.image ? `<img src="${item.image}" style="width:24px; height:24px; object-fit:cover; border-radius:50%;">` : `<span style="font-size:16px; display:inline-block; width:24px; text-align:center; color:var(--cafe-brown);"><i class="bi bi-cup-hot-fill"></i></span>`}
        <div style="flex:1; font-size:13px; font-weight:500; color:#333;">${item.name}${sizeLabel}</div>
        <div style="display:flex; align-items:center; gap:4px;">
          <button class="qty-btn" onclick="changeQty('${key}', -1)">−</button>
          <span style="font-size:13px; min-width:18px; text-align:center; font-weight:600;">${entry.qty}</span>
          <button class="qty-btn" onclick="changeQty('${key}', 1)">+</button>
        </div>
        <div style="font-size:13px; min-width:52px; text-align:right; font-weight:600; color:var(--cafe-amber);">₱${sub}</div>
      </div>`;
      }).join('');

      const totalFmt = '₱' + total.toFixed(2);
      document.getElementById('cart-total').textContent = totalFmt;
      const mTotal = document.getElementById('pos-mtab-total');
      if (mTotal) mTotal.textContent = totalFmt;
      const mFloatTot = document.getElementById('pmf-badge-total');
      if (mFloatTot) mFloatTot.textContent = totalFmt;

      if (floatBar) {
        if (totalQty > 0 && _activeMobilePosTab === 'menu') {
          floatBar.classList.add('visible');
        } else {
          floatBar.classList.remove('visible');
        }
      }
    }

    function changeQty(key, delta) {
      const entry = cart[key];
      if (!entry) return;
      const item = menuItems.find(m => m.id == entry.itemId);
      const newQty = entry.qty + delta;

      if (delta > 0 && item) {
        const totalItemQty = getTotalItemQty(entry.itemId);
        if (totalItemQty + 1 > item.stock) {
          showToast(`⚠ Only ${item.stock} ${item.name} left in stock!`);
          return;
        }
      }

      if (newQty <= 0) {
        delete cart[key];
      } else {
        cart[key].qty = newQty;
      }
      renderCart();
    }

    function clearCart() {
      cart = {};
      renderCart();
      if (window.innerWidth <= 768 && _activeMobilePosTab === 'cart') {
        setTimeout(() => switchMobilePosTab('menu'), 250);
      }
    }

    /* ========== PAYMENT MODAL ========== */
    let _paymentMethod = 'Cash';
    let _pendingCartTotal = 0;

    function checkout() {
      const keys = Object.keys(cart);
      if (!keys.length) { showToast('⚠ Cart is empty!'); return; }

      // Check if any cart item became unavailable or out of stock
      for (const key of keys) {
        const item = menuItems.find(m => m.id == cart[key].itemId);
        if (item && !item.available) {
          showToast(`⚠ Cannot checkout: "${item.name}" is marked unavailable.`);
          return;
        }
        if (item && item.stock <= 0) {
          showToast(`⚠ Cannot checkout: "${item.name}" is out of stock.`);
          return;
        }
      }

      // Calculate total
      _pendingCartTotal = keys.reduce((sum, key) => sum + cart[key].price * cart[key].qty, 0);

      // Reset modal state
      document.getElementById('pay-amount-display').textContent = '₱' + _pendingCartTotal.toFixed(2);
      document.getElementById('pay-tendered').value = '';
      document.getElementById('pay-change-display').textContent = '₱0.00';
      const changeRow = document.getElementById('pay-change-row');
      if (changeRow) changeRow.classList.remove('insufficient');
      const cashConfirmBtn = document.getElementById('pay-cash-confirm-btn');
      if (cashConfirmBtn) {
        cashConfirmBtn.disabled = true;
        cashConfirmBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Cash Payment';
      }
      const gcashConfirmBtn = document.getElementById('pay-gcash-confirm-btn');
      if (gcashConfirmBtn) {
        gcashConfirmBtn.disabled = true;
        gcashConfirmBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Manually';
      }
      selectPayMethod('Cash');

      document.getElementById('pay-modal').classList.add('show');
      setTimeout(() => document.getElementById('pay-tendered').focus(), 300);
    }

    function closePayModal() {
      stopGCashPoll();
      _gcashLinkId = null;
      _gcashPollAction = 'check_qrph';
      document.getElementById('pay-modal').classList.remove('show');
      const cashConfirmBtn = document.getElementById('pay-cash-confirm-btn');
      if (cashConfirmBtn) {
        cashConfirmBtn.disabled = true;
        cashConfirmBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Cash Payment';
      }
    }

    function selectPayMethod(method) {
      _paymentMethod = method;
      document.getElementById('pm-cash-btn').classList.toggle('active', method === 'Cash');
      document.getElementById('pm-gcash-btn').classList.toggle('active', method === 'GCash');
      document.getElementById('pay-cash-panel').style.display  = method === 'Cash'  ? '' : 'none';
      document.getElementById('pay-gcash-panel').style.display = method === 'GCash' ? '' : 'none';
      if (method === 'Cash') {
        setTimeout(() => document.getElementById('pay-tendered').focus(), 50);
      }
      if (method === 'GCash') {
        renderGCashPanel();
      }
    }

    /* ========== GCASH QR CONFIG ========== */
    let _gcashConfig = null;
    try { _gcashConfig = JSON.parse(localStorage.getItem('chipipie_gcash_config') || 'null'); } catch(e) {}

    // Temp base64 holder while user selects a file
    let _gcashPendingBase64 = null;

    function openGCashConfig() {
      const cfg = _gcashConfig || {};
      document.getElementById('gcash-config-name').value = cfg.name || '';
      // Reset upload UI
      _gcashPendingBase64 = cfg.qrImage || null;
      const placeholder = document.getElementById('gcash-upload-placeholder');
      const preview     = document.getElementById('gcash-upload-preview');
      if (_gcashPendingBase64) {
        preview.src = _gcashPendingBase64;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
      } else {
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = '';
      }
      document.getElementById('gcash-config-modal').classList.add('show');
    }

    function closeGCashConfig() {
      document.getElementById('gcash-config-modal').classList.remove('show');
    }

    function onGCashFileSelected(input) {
      const file = input.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function(e) {
        _gcashPendingBase64 = e.target.result;
        const preview     = document.getElementById('gcash-upload-preview');
        const placeholder = document.getElementById('gcash-upload-placeholder');
        preview.src = _gcashPendingBase64;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
      };
      reader.readAsDataURL(file);
    }

    function saveGCashConfig() {
      const name = document.getElementById('gcash-config-name').value.trim();
      if (!_gcashPendingBase64) {
        showToast('⚠ Please upload your GCash QR image first.');
        return;
      }
      _gcashConfig = { name: name || 'GCash', qrImage: _gcashPendingBase64 };
      localStorage.setItem('chipipie_gcash_config', JSON.stringify(_gcashConfig));
      closeGCashConfig();
      showToast('✅ GCash QR saved!');
      if (document.getElementById('pay-gcash-panel').style.display !== 'none') {
        renderGCashQR();
      }
    }

    /* ========== GCASH PAYMONGO PAYMENT LINKS ========== */
    let _gcashLinkId     = null;
    let _gcashPollTimer  = null;
    let _gcashKeyOk      = false;

    async function initGCashKey() {
      try {
        const res = await api('paymongo.php?action=get_key');
        _gcashKeyOk = !!(res.success && res.configured);
      } catch(e) { _gcashKeyOk = false; }
      updatePmBadge(_gcashKeyOk);
    }

    function renderGCashPanel() {
      document.getElementById('gcash-pm-amount').textContent = '\u20b1' + _pendingCartTotal.toFixed(2);
      document.getElementById('gcash-no-key').style.display     = _gcashKeyOk ? 'none' : '';
      document.getElementById('gcash-ready-state').style.display = _gcashKeyOk ? ''    : 'none';
      document.getElementById('gcash-link-state').style.display  = 'none';
      document.getElementById('gcash-gen-error').style.display   = 'none';
    }

    function resetGCashState() {
      stopGCashPoll();
      _gcashLinkId = null;
      _gcashPollAction = 'check_qrph';
      renderGCashPanel();
    }

    let _gcashPollAction = 'check_qrph';

    async function generateGCashLink() {
      const btn = document.getElementById('gcash-generate-btn');
      const errEl = document.getElementById('gcash-gen-error');
      btn.disabled = true;
      btn.style.opacity = '0.6';
      btn.innerHTML = '<span class="gcash-spinner" style="border-color:#fff3;border-top-color:#fff;"></span> Generating\u2026';
      errEl.style.display = 'none';

      try {
        // Always call create_qrph to get a real QR PH code (scannable by GCash/bank apps)
        // This uses the PayMongo /v1/qrph endpoint which produces a valid InstaPay/PESONet QR
        const res = await api('paymongo.php?action=create_qrph', {
          amount: _pendingCartTotal,
          description: 'ChipiPie Order'
        });

        if (!res.success) {
          errEl.textContent = res.message || 'Failed to generate QR PH.';
          errEl.style.display = 'block';
          return;
        }

        // QR PH: use qrph_id for polling
        _gcashLinkId = res.qrph_id;
        _gcashPollAction = 'check_qrph';

        // Determine QR image to display
        // Server returns base64 qr_image directly, or qr_string to encode client-side
        let qrImageSrc = res.qr_image || '';
        if (!qrImageSrc && res.qr_string) {
          // Fallback: generate QR from qr_string using free API
          qrImageSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='
            + encodeURIComponent(res.qr_string) + '&format=png&margin=4';
        }

        // Show link state
        document.getElementById('gcash-ready-state').style.display = 'none';
        document.getElementById('gcash-link-state').style.display  = '';
        document.getElementById('gcash-paid-badge').style.display  = 'none';
        document.getElementById('gcash-poll-status').style.display = 'flex';
        document.getElementById('gcash-poll-text').textContent = 'Waiting for GCash payment\u2026';

        // Display QR PH code (real InstaPay/PESONet scannable QR)
        const qrImg = document.getElementById('gcash-qr-canvas');
        qrImg.src = qrImageSrc;

        // QR PH is scan-only — hide the "Open Payment Page" button
        const linkBtn = document.getElementById('gcash-link-btn');
        linkBtn.style.display = 'none';

        // Enable manual confirm
        const confirmBtn = document.getElementById('pay-gcash-confirm-btn');
        confirmBtn.disabled = false;
        confirmBtn.style.opacity = '1';

        startGCashPoll();

      } catch(e) {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
      } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.innerHTML = '<i class="bi bi-qr-code" style="font-size:18px;"></i> Generate GCash QR PH';
      }
    }

    function startGCashPoll() {
      stopGCashPoll();
      _gcashPollTimer = setInterval(async () => {
        if (!_gcashLinkId) return;
        try {
          const res = await api('paymongo.php?action=' + _gcashPollAction + '&id=' + _gcashLinkId);
          if (res.success && res.paid) {
            stopGCashPoll();
            document.getElementById('gcash-poll-status').style.display = 'none';
            document.getElementById('gcash-paid-badge').style.display  = '';
            setTimeout(() => confirmPayment(), 1500);
          } else if (res.status === 'expired') {
            stopGCashPoll();
            document.getElementById('gcash-poll-text').textContent = 'Link expired. Please generate a new one.';
          }
        } catch(e) { /* ignore */ }
      }, 3000);
    }

    function stopGCashPoll() {
      if (_gcashPollTimer) { clearInterval(_gcashPollTimer); _gcashPollTimer = null; }
    }


    async function savePaymongoKey() {
      const key = document.getElementById('pm-secret-key-input').value.trim();
      const statusEl = document.getElementById('pm-key-status');
      if (!key) {
        statusEl.style.display = 'block';
        statusEl.style.background = '#fee2e2';
        statusEl.style.color = '#dc2626';
        statusEl.textContent = 'Please enter your PayMongo secret key.';
        return;
      }
      try {
        const res = await api('paymongo.php?action=save_key', { secret_key: key });
        statusEl.style.display = 'block';
        if (res.success) {
          statusEl.style.background = '#d1fae5';
          statusEl.style.color = '#059669';
          statusEl.textContent = '✓ API key saved successfully.';
          document.getElementById('pm-secret-key-input').value = '';
          _gcashKeyOk = true;
          updatePmBadge(true);
        } else {
          statusEl.style.background = '#fee2e2';
          statusEl.style.color = '#dc2626';
          statusEl.textContent = res.message || 'Failed to save key.';
        }
      } catch(e) {
        statusEl.style.display = 'block';
        statusEl.style.background = '#fee2e2';
        statusEl.style.color = '#dc2626';
        statusEl.textContent = 'Network error. Please try again.';
      }
    }

    function updatePmBadge(ok) {
      const badge = document.getElementById('pm-key-badge');
      if (!badge) return;
      if (ok) {
        badge.style.background = '#d1fae5'; badge.style.color = '#059669';
        badge.textContent = '● Configured';
      } else {
        badge.style.background = '#fee2e2'; badge.style.color = '#dc2626';
        badge.textContent = '● Not Configured';
      }
    }


    function sanitizeTendered(val) {
      if (!val) return '';
      let digits = String(val).replace(/[^0-9]/g, '');
      if (!digits) return '';
      // If all zeros (e.g. 0, 00, 00000000), collapse to a single '0'
      if (/^0+$/.test(digits)) return '0';
      // Strip leading zeros for numbers (e.g. 050 -> 50)
      return digits.replace(/^0+/, '');
    }

    function handleTenderedKeydown(e, el) {
      // Disallow non-integer characters: ., e, +, -, E, commas, spaces
      if (['.', 'e', '+', '-', 'E', ',', ' '].includes(e.key)) {
        e.preventDefault();
        return;
      }
      if (e.key === 'Enter') {
        confirmPayment();
        return;
      }
      // Prevent zeros from accumulating / adding up
      if (e.key === '0') {
        // If already '0', don't allow typing another zero (prevents 00, 000, 00000000)
        if (el.value === '0') {
          e.preventDefault();
          return;
        }
        // If cursor is at start of an existing number, don't allow typing leading zero (e.g. 0 before 50)
        if (el.selectionStart === 0 && el.selectionEnd === 0 && el.value.length > 0) {
          e.preventDefault();
          return;
        }
      }
      // If current value is '0' and user types 1-9, replace the '0' with the new digit (e.g. '5' instead of '05')
      if (e.key >= '1' && e.key <= '9') {
        if (el.value === '0' && (el.selectionStart === null || el.selectionStart === 0 || el.selectionStart === 1)) {
          el.value = e.key;
          e.preventDefault();
          updateChange();
          return;
        }
      }
    }

    function handleTenderedInput(el) {
      const orig = el.value;
      const clean = sanitizeTendered(orig);
      if (orig !== clean) {
        el.value = clean;
      }
      updateChange();
    }

    function updateChange() {
      const inputEl = document.getElementById('pay-tendered');
      if (!inputEl) return;
      const clean = sanitizeTendered(inputEl.value);
      if (inputEl.value !== clean) {
        inputEl.value = clean;
      }
      const changeRow  = document.getElementById('pay-change-row');
      const changeDisp = document.getElementById('pay-change-display');
      const confirmBtn = document.getElementById('pay-cash-confirm-btn');

      if (!clean) {
        // Empty state: neutral ₱0.00, button disabled
        if (changeRow) changeRow.classList.remove('insufficient');
        if (changeDisp) changeDisp.textContent = '₱0.00';
        if (confirmBtn) confirmBtn.disabled = true;
        return;
      }

      const tendered = parseInt(clean, 10) || 0;
      const due = _pendingCartTotal;
      const change = tendered - due;

      if (change < 0) {
        if (changeRow) changeRow.classList.add('insufficient');
        if (changeDisp) changeDisp.textContent = '-₱' + Math.abs(change).toFixed(2);
        if (confirmBtn) confirmBtn.disabled = true;
      } else {
        if (changeRow) changeRow.classList.remove('insufficient');
        if (changeDisp) changeDisp.textContent = '₱' + change.toFixed(2);
        if (confirmBtn) {
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Cash Payment';
        }
      }
    }

    // Close on backdrop click
    document.addEventListener('click', function(e) {
      if (e.target && e.target.id === 'pay-modal') closePayModal();
    });

    /* =============================================
       58MM THERMAL RECEIPT SYSTEM
    ============================================= */
    let _lastReceiptData = null;

    function escapeHtml(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function generateReceiptBarcode(text) {
      const svg = document.getElementById('rcpt-barcode');
      if (!svg) return;
      const clean = String(text || '').replace(/[^A-Za-z0-9#]/g, '');
      const bars = [2, 1, 1, 2, 1, 1, 2, 1, 1]; // start guard
      for (let i = 0; i < clean.length; i++) {
        const code = clean.charCodeAt(i);
        const p1 = (code % 3) + 1;
        const p2 = ((code >> 1) % 3) + 1;
        const p3 = ((code >> 2) % 3) + 1;
        bars.push(p1, 1, p2, 1, p3, 2);
      }
      bars.push(2, 1, 1, 2, 1, 1, 2); // stop guard

      let x = 6;
      const h = 32;
      let rects = '';
      let isBar = true;
      for (let b = 0; b < bars.length; b++) {
        const w = bars[b];
        if (isBar) {
          rects += `<rect x="${x}" y="0" width="${w}" height="${h}" fill="#000" />`;
        }
        x += w;
        isBar = !isBar;
      }
      svg.setAttribute('viewBox', `0 0 ${x + 6} ${h}`);
      svg.innerHTML = rects;
    }

    /* ========== PRINT OPTIONS MODAL ========== */
    let _pendingPrintData = null;

    function showPrintOptionsModal(data) {
      _pendingPrintData = data;
      const numEl = document.getElementById('poi-order-num');
      if (numEl) numEl.textContent = data.orderNumber || ('#' + String(data.id || 1).padStart(3, '0'));
      const modal = document.getElementById('printOptsModal');
      if (modal) {
        modal.classList.add('open');
        modal.style.display = 'flex';
      }
    }

    function closePrintOptionsModal() {
      const modal = document.getElementById('printOptsModal');
      if (modal) {
        modal.classList.remove('open');
        modal.style.display = 'none';
      }
    }

    function skipPrintOptions() {
      closePrintOptionsModal();
      _pendingPrintData = null;
      const searchInput = document.getElementById('pos-search');
      if (searchInput) searchInput.focus();
    }

    function buildKitchenTicketHTML(data) {
      const orderNum = data.orderNumber || ('#' + String(data.id || 1).padStart(3, '0'));
      let timeStr = data.date || '';
      if (!timeStr) {
        timeStr = new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
      } else {
        // Shorten e.g. "Oct 22, 2026 07:30 PM" to "Oct 22 07:30 PM" so it fits in 43mm without truncation
        timeStr = String(timeStr).replace(/,\s*\d{4}/, '');
      }
      const items = data.items || [];
      const itemRows = items.map(it => {
        const qty  = it.qty || it.quantity || 1;
        const name = escapeHtml(it.name || 'Item');
        const size = it.size ? ` (${escapeHtml(it.size)})` : '';
        const notes = it.notes ? `<div style="font-size:8.5px;font-style:italic;color:#000;padding-left:20px;margin-top:1px;">Note: ${escapeHtml(it.notes)}</div>` : '';
        return `<div style="margin-bottom:4px;border-bottom:1px dashed #000;padding-bottom:3px;">
          <div style="display:flex;align-items:baseline;gap:4px;">
            <span style="font-weight:900;font-size:13px;min-width:20px;flex-shrink:0;color:#000;">${qty}x</span>
            <span style="flex:1;font-weight:800;font-size:10.5px;word-break:break-word;line-height:1.2;color:#000;">${name}${size}</span>
          </div>
          ${notes}
        </div>`;
      }).join('');

      return `<div style="font-family:'Courier New',Courier,Monaco,monospace;font-size:10px;color:#000;width:100%;box-sizing:border-box;">
        <div style="text-align:center;margin-bottom:3px;">
          <div style="font-size:12px;font-weight:900;letter-spacing:0.5px;color:#000;">** KITCHEN ORDER **</div>
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-top:1px;color:#000;">CHIPIPIE CAFE</div>
        </div>
        <div style="border-top:2px solid #000;margin:3px 0;"></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin:2px 0;">
          <span style="font-size:10.5px;font-weight:900;color:#000;">ORDER:</span>
          <strong style="font-size:15px;letter-spacing:0.5px;font-weight:900;color:#000;">${orderNum}</strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:9px;margin-bottom:3px;color:#000;">
          <span>TIME:</span><span style="font-weight:700;">${timeStr}</span>
        </div>
        <div style="border-top:1.5px dashed #000;margin:3px 0 4px 0;"></div>
        <div style="font-size:9px;font-weight:900;text-transform:uppercase;margin-bottom:3px;letter-spacing:0.5px;color:#000;">ITEMS TO PREPARE:</div>
        ${itemRows}
        <div style="border-top:2px solid #000;margin:3px 0;"></div>
        <div style="text-align:center;font-size:9.5px;font-weight:900;letter-spacing:0.5px;color:#000;">*** PREPARE NOW ***</div>
      </div>`;
    }

    function printKitchenTicket(data) {
      const el = document.getElementById('pos-kitchen-printable');
      if (!el) return;
      el.innerHTML = buildKitchenTicketHTML(data);
      el.style.display = 'block';
      el.style.position = 'absolute';
      el.style.left = '-9999px';

      document.body.setAttribute('data-print', 'kitchen');
      window.print();

      // Cleanup after print dialog closes
      setTimeout(() => {
        document.body.removeAttribute('data-print');
        el.style.display = 'none';
        el.innerHTML = '';
      }, 800);
    }

    function printKitchenOnly() {
      const data = _pendingPrintData;
      closePrintOptionsModal();
      if (data) printKitchenTicket(data);
      _pendingPrintData = null;
      const searchInput = document.getElementById('pos-search');
      if (searchInput) searchInput.focus();
    }

    function printReceiptOnly() {
      const data = _pendingPrintData;
      closePrintOptionsModal();
      if (!data) return;
      showReceiptModal(data);
      _pendingPrintData = null;
    }

    function printWithReceipt() {
      const data = _pendingPrintData;
      closePrintOptionsModal();
      if (!data) return;
      // Print kitchen first, then after a short delay show receipt
      printKitchenTicket(data);
      setTimeout(() => {
        showReceiptModal(data);
      }, 900);
      _pendingPrintData = null;
    }

    /* Print options modal shortcuts: 1=Kitchen, 2=Receipt, 3=Both, Esc=Skip */
    document.addEventListener('keydown', function(ePO) {
      const po = document.getElementById('printOptsModal');
      if (po && (po.classList.contains('open') || po.style.display === 'flex')) {
        if (ePO.key === 'Escape') {
          skipPrintOptions();
        } else if (ePO.key === '1') {
          printKitchenOnly();
        } else if (ePO.key === '2') {
          printReceiptOnly();
        } else if (ePO.key === '3') {
          printWithReceipt();
        }
      }
    });

    /* ========== RECEIPT MODAL ========== */
    function showReceiptModal(data) {
      if (!data) return;
      _lastReceiptData = data;

      const orderNum = data.orderNumber || (data.id ? ('#' + String(data.id).padStart(3, '0')) : '#001');
      let rawDate = data.date || (new Date().toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }));
      // Compact date string e.g. "Oct 22, '26 07:30 PM" to prevent right overflow
      const orderDate = String(rawDate).replace(/(\b20)(\d{2}\b)/, "'$2");

      const cashier = data.cashier || (currentUser && (currentUser.full_name || currentUser.username)) || 'Staff';
      const paymentMethod = (data.paymentMethod || 'Cash').toUpperCase();
      const total = parseFloat(data.total || 0);
      const tendered = (data.tendered !== undefined && data.tendered !== null) ? parseFloat(data.tendered) : total;
      const change = (data.change !== undefined && data.change !== null) ? parseFloat(data.change) : Math.max(0, tendered - total);

      // Populate Meta
      document.getElementById('rcpt-order-num').textContent = orderNum;
      document.getElementById('rcpt-date').textContent = orderDate;
      document.getElementById('rcpt-cashier').textContent = cashier;
      document.getElementById('rcpt-payment-method').textContent = paymentMethod;

      const statusTag = document.getElementById('rcpt-status-tag');
      if (statusTag) {
        statusTag.textContent = (data.status && data.status.toUpperCase() !== 'COMPLETED') ? `[${data.status.toUpperCase()}]` : '[PAID]';
      }

      // Populate Items
      const listEl = document.getElementById('rcpt-items-list');
      let totalQty = 0;
      const items = data.items || [];
      if (items.length) {
        listEl.innerHTML = items.map(it => {
          const qty      = it.qty || it.quantity || 1;
          totalQty      += qty;
          const price    = parseFloat(it.price || it.unit_price || 0);
          const subtotal = parseFloat(it.subtotal || (price * qty));
          const sizeLabel = it.size ? ` (${escapeHtml(it.size)})` : '';
          return `
            <div style="margin-bottom:4px;">
              <div style="display:flex;align-items:baseline;justify-content:space-between;gap:2px;">
                <div style="display:flex;align-items:baseline;gap:3px;flex:1;min-width:0;">
                  <span style="font-weight:900;min-width:18px;flex-shrink:0;color:#000;font-size:10.5px;">${qty}x</span>
                  <span style="flex:1;word-break:break-word;line-height:1.2;font-weight:700;color:#000;font-size:10.5px;">${escapeHtml(it.name)}${sizeLabel}</span>
                </div>
                <span style="flex-shrink:0;font-weight:900;white-space:nowrap;padding-left:3px;color:#000;font-size:10.5px;text-align:right;">\u20b1${subtotal.toFixed(2)}</span>
              </div>
              ${qty > 1 ? `<div style="font-size:8.5px;color:#000;padding-left:21px;margin-top:1px;">@ \u20b1${price.toFixed(2)} ea</div>` : ''}
            </div>`;
        }).join('');
      } else {
        listEl.innerHTML = '<div style="color:#000;font-style:italic;font-size:9px;">No item details available</div>';
      }

      document.getElementById('rcpt-item-count').textContent = `Items (${totalQty}):`;
      document.getElementById('rcpt-subtotal').textContent = '\u20b1' + total.toFixed(2);
      document.getElementById('rcpt-total').textContent = '\u20b1' + total.toFixed(2);

      // Tendered and Change details
      const tenderedRow = document.getElementById('rcpt-tendered-row');
      const changeRow   = document.getElementById('rcpt-change-row');
      const gcashRow    = document.getElementById('rcpt-gcash-ref-row');
      const tenderedLbl = document.getElementById('rcpt-tendered-label');

      if (paymentMethod === 'GCASH') {
        if (tenderedLbl) tenderedLbl.textContent = 'GCash Collected:';
        document.getElementById('rcpt-tendered').textContent = '\u20b1' + total.toFixed(2);
        document.getElementById('rcpt-change').textContent = '\u20b10.00';
        if (gcashRow) {
          gcashRow.style.display = 'flex';
          const refEl = document.getElementById('rcpt-gcash-ref');
          if (refEl) refEl.textContent = data.gcashRef || 'Verified (PayMongo / QR PH)';
        }
      } else {
        if (tenderedLbl) tenderedLbl.textContent = 'Cash Tendered:';
        document.getElementById('rcpt-tendered').textContent = '\u20b1' + tendered.toFixed(2);
        document.getElementById('rcpt-change').textContent = '\u20b1' + change.toFixed(2);
        if (gcashRow) gcashRow.style.display = 'none';
      }

      // Barcode
      generateReceiptBarcode(orderNum);
      const barcodeTxt = document.getElementById('rcpt-barcode-text');
      if (barcodeTxt) barcodeTxt.textContent = orderNum;

      // Open Modal
      const modal = document.getElementById('receiptModal');
      if (modal) modal.classList.add('open');
    }

    function closeReceiptModal() {
      const modal = document.getElementById('receiptModal');
      if (modal) modal.classList.remove('open');
      const searchInput = document.getElementById('pos-search');
      if (searchInput && document.getElementById('pos-view') && document.getElementById('pos-view').style.display !== 'none') {
        searchInput.focus();
      }
    }

    function reprintKitchenFromModal() {
      if (_lastReceiptData) {
        printKitchenTicket(_lastReceiptData);
      } else {
        showToast('⚠ No order details available to print kitchen ticket');
      }
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const rcpt = document.getElementById('receiptModal');
        if (rcpt && rcpt.classList.contains('open')) {
          closeReceiptModal();
        }
      }
    });

    function printThermalReceipt() {
      document.body.setAttribute('data-print', 'receipt');
      window.print();
      setTimeout(() => {
        document.body.removeAttribute('data-print');
      }, 800);
    }

    async function showReceiptForOrder(orderId) {
      try {
        const res = await api('orders.php?action=receipt&id=' + orderId);
        if (res.success && res.order) {
          const o = res.order;
          showReceiptModal({
            id: o.id,
            orderNumber: o.order_number,
            total: o.total,
            paymentMethod: o.payment_method,
            tendered: o.total,
            change: 0,
            cashier: o.cashier,
            date: o.formatted_date,
            items: o.line_items || []
          });
        } else {
          showToast('⚠ Failed to fetch receipt: ' + (res.message || ''));
        }
      } catch (e) {
        showToast('⚠ Connection error while loading receipt');
      }
    }

    async function confirmPayment() {
      // Validate Cash: tendered must be >= total
      let tenderedAmount = _pendingCartTotal;
      let changeAmount = 0;
      if (_paymentMethod === 'Cash') {
        const inputEl = document.getElementById('pay-tendered');
        const tendered = parseInt(sanitizeTendered(inputEl.value), 10) || 0;
        if (tendered < _pendingCartTotal) {
          showToast('⚠ Cash tendered is less than the total amount!');
          return;
        }
        tenderedAmount = tendered;
        changeAmount = Math.max(0, tendered - _pendingCartTotal);
      }

      // Keep pay modal OPEN — show processing spinner on the confirm button
      const cashBtn  = document.getElementById('pay-cash-confirm-btn');
      const gcashBtn = document.getElementById('pay-gcash-confirm-btn');
      const activeBtn = (cashBtn && !cashBtn.hidden) ? cashBtn : gcashBtn;
      const origBtnHTML = activeBtn ? activeBtn.innerHTML : '';
      if (activeBtn) {
        activeBtn.disabled = true;
        activeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';
      }

      const keys = Object.keys(cart);
      const items = keys.map(key => {
        const entry = cart[key];
        return {
          id: entry.itemId,
          quantity: entry.qty,
          size: entry.size || '',
          unit_price: entry.price
        };
      });

      // Snapshot cart items for receipt before clearing cart
      const cartSnapshot = keys.map(key => ({
        name: cart[key].name || 'Item',
        size: cart[key].size || '',
        qty: cart[key].qty,
        price: cart[key].price,
        subtotal: cart[key].price * cart[key].qty
      }));

      try {
        const res = await api('orders.php?action=checkout', { items, payment_method: _paymentMethod });
        if (res.success) {
          const methodLabel = _paymentMethod === 'GCash' ? '📱 GCash' : '💵 Cash';

          const receiptData = {
            id: res.order ? res.order.id : null,
            orderNumber: res.order ? res.order.order_number : '#001',
            total: res.order ? res.order.total : _pendingCartTotal,
            paymentMethod: _paymentMethod,
            tendered: tenderedAmount,
            change: changeAmount,
            cashier: (res.order && res.order.cashier) || (currentUser && (currentUser.full_name || currentUser.username)) || 'Cashier',
            date: (res.order && res.order.formatted_date) || (new Date().toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })),
            items: (res.order && res.order.line_items && res.order.line_items.length) ? res.order.line_items : cartSnapshot
          };

          // ── Close pay modal THEN immediately show print options (no overlap) ──
          closePayModal();
          clearCart();
          showToast('✅ ' + methodLabel + ' payment confirmed! ' + res.message);
          showPrintOptionsModal(receiptData);

          // Background refreshes (fire-and-forget — non-blocking)
          loadMenuFromDB().then(() => renderPOS()).catch(() => {});
          api('orders.php?date=today').then(ordRes => {
            if (ordRes.success) {
              orders = ordRes.orders.map(o => ({
                id: o.order_number, time: o.time, date: new Date(o.created_at).toDateString(),
                ts: new Date(o.created_at).getTime(), items: o.items || '',
                cartData: null, total: parseFloat(o.total),
                payment: o.payment_method, status: o.status
              }));
              updateDashboard();
              if (document.getElementById('orders-tbody') && currentOrderFilter === 'today') renderOrders();
            }
          }).catch(e => console.warn('Could not refresh orders:', e));

          if (res.stock_alerts && res.stock_alerts.length > 0) showStockAlert(res.stock_alerts);

        } else {
          if (activeBtn) { activeBtn.disabled = false; activeBtn.innerHTML = origBtnHTML || '<i class="bi bi-check-circle-fill"></i> Confirm Cash Payment'; }
          showToast('⚠ ' + res.message);
        }
      } catch (err) {
        if (activeBtn) { activeBtn.disabled = false; activeBtn.innerHTML = origBtnHTML || '<i class="bi bi-check-circle-fill"></i> Confirm Cash Payment'; }
        showToast('⚠ Checkout failed. Check server connection.');
        console.error(err);
      } finally {
        const cBtn = document.getElementById('pay-cash-confirm-btn');
        if (cBtn && cBtn.innerHTML.includes('Processing')) {
          cBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Cash Payment';
        }
        const gBtn = document.getElementById('pay-gcash-confirm-btn');
        if (gBtn && gBtn.innerHTML.includes('Processing')) {
          gBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Confirm Manually';
        }
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
    let _orderPickerMode   = '';  // '' | 'date' | 'week' | 'month' | 'year'
    let _orderPickerVal    = '';

    function _formatLocalDate(d) {
      return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }
    function _getISOWeekString(date) {
      const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
      const dn = d.getUTCDay() || 7;
      d.setUTCDate(d.getUTCDate() + 4 - dn);
      const y  = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
      const wn = Math.ceil((((d - y) / 86400000) + 1) / 7);
      return `${d.getUTCFullYear()}-W${String(wn).padStart(2,'0')}`;
    }
    function _getISOWeekRange(weekStr) {
      if (!weekStr) return '';
      const parts = weekStr.split('-W');
      if (parts.length !== 2) return '';
      const year = parseInt(parts[0], 10), week = parseInt(parts[1], 10);
      const simple = new Date(year, 0, 4);
      const dow = simple.getDay() || 7;
      const monday = new Date(simple);
      monday.setDate(simple.getDate() - dow + 1 + (week - 1) * 7);
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);
      const opt = { month: 'short', day: 'numeric' };
      return `Week ${week} (${monday.toLocaleDateString('en-US', opt)} – ${sunday.toLocaleDateString('en-US', opt)}, ${year})`;
    }
    function _initOrderPickerControls() {
      const now = new Date();
      const di = document.getElementById('order-input-date');
      if (di && !di.value) di.value = _formatLocalDate(now);
      const wi = document.getElementById('order-input-week');
      if (wi && !wi.value) wi.value = _getISOWeekString(now);
      const mi = document.getElementById('order-input-month');
      if (mi && !mi.value) mi.value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
      const ys = document.getElementById('order-input-year');
      if (ys && ys.options.length === 0) {
        const cy = now.getFullYear();
        for (let yr = cy + 1; yr >= 2022; yr--) {
          const o = new Option(yr, yr);
          if (yr === cy) o.selected = true;
          ys.add(o);
        }
      }
    }

    function setOrderFilter(filter, btn) {
      // Clear picker mode when a quick tab is clicked
      _orderPickerMode = '';
      _orderPickerVal  = '';
      const card = document.getElementById('order-picker-card');
      if (card) card.style.display = 'none';
      ['pick-date','pick-week','pick-month','pick-year'].forEach(id => {
        const el = document.getElementById('ofilter-' + id);
        if (el) el.classList.remove('active');
      });
      currentOrderFilter = filter;
      document.querySelectorAll('#order-filter-tabs .cat-pill').forEach(b => b.classList.remove('active'));
      if (btn) btn.classList.add('active');
      renderOrders();
    }

    function setOrderPickerMode(mode) {
      _orderPickerMode = mode;
      _initOrderPickerControls();
      // Deactivate quick tabs
      document.querySelectorAll('#order-filter-tabs .cat-pill').forEach(b => b.classList.remove('active'));
      const ab = document.getElementById('ofilter-pick-' + mode);
      if (ab) ab.classList.add('active');
      // Show picker card
      const card = document.getElementById('order-picker-card');
      if (card) card.style.display = 'block';
      ['date','week','month','year'].forEach(m => {
        const el = document.getElementById('order-picker-' + m);
        if (el) el.style.display = m === mode ? 'block' : 'none';
      });
      applyOrderPickerFilter();
    }

    function applyOrderPickerFilter() {
      _initOrderPickerControls();
      const sumEl = document.getElementById('order-picker-summary');
      if (_orderPickerMode === 'date') {
        _orderPickerVal = document.getElementById('order-input-date')?.value || _formatLocalDate(new Date());
        const parts = (_orderPickerVal || '').split('-');
        let txt = _orderPickerVal;
        if (parts.length === 3) {
          const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
          txt = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        }
        const lbl = document.getElementById('order-date-label');
        if (lbl) lbl.textContent = txt;
        if (sumEl) sumEl.textContent = `Showing: ${txt}`;
      } else if (_orderPickerMode === 'week') {
        _orderPickerVal = document.getElementById('order-input-week')?.value || _getISOWeekString(new Date());
        const txt = _getISOWeekRange(_orderPickerVal) || _orderPickerVal;
        const lbl = document.getElementById('order-week-label');
        if (lbl) lbl.textContent = txt;
        if (sumEl) sumEl.textContent = `Showing: ${txt}`;
      } else if (_orderPickerMode === 'month') {
        _orderPickerVal = document.getElementById('order-input-month')?.value || `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}`;
        const parts = (_orderPickerVal || '').split('-');
        let txt = _orderPickerVal;
        if (parts.length === 2) {
          const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, 1);
          txt = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }
        const lbl = document.getElementById('order-month-label');
        if (lbl) lbl.textContent = txt;
        if (sumEl) sumEl.textContent = `Showing: ${txt}`;
      } else if (_orderPickerMode === 'year') {
        _orderPickerVal = document.getElementById('order-input-year')?.value || String(new Date().getFullYear());
        const lbl = document.getElementById('order-year-label');
        if (lbl) lbl.textContent = `Year ${_orderPickerVal}`;
        if (sumEl) sumEl.textContent = `Showing: Year ${_orderPickerVal}`;
      }
      renderOrders();
    }

    function shiftOrderDate(delta) {
      const input = document.getElementById('order-input-date');
      const val = input.value || _formatLocalDate(new Date());
      const parts = val.split('-');
      const d = new Date(parseInt(parts[0],10), parseInt(parts[1],10)-1, parseInt(parts[2],10));
      d.setDate(d.getDate() + delta);
      input.value = _formatLocalDate(d);
      applyOrderPickerFilter();
    }
    function setOrderPickerToday() {
      document.getElementById('order-input-date').value = _formatLocalDate(new Date());
      applyOrderPickerFilter();
    }
    function shiftOrderWeek(delta) {
      const input = document.getElementById('order-input-week');
      const val = input.value || _getISOWeekString(new Date());
      const parts = val.split('-W');
      let y = parseInt(parts[0],10), w = parseInt(parts[1],10) + delta;
      if (w < 1) { y--; w = 52; } else if (w > 52) { y++; w = 1; }
      input.value = `${y}-W${String(w).padStart(2,'0')}`;
      applyOrderPickerFilter();
    }
    function setOrderPickerThisWeek() {
      document.getElementById('order-input-week').value = _getISOWeekString(new Date());
      applyOrderPickerFilter();
    }
    function shiftOrderMonth(delta) {
      const input = document.getElementById('order-input-month');
      const val = input.value || `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}`;
      const parts = val.split('-');
      let y = parseInt(parts[0],10), m = parseInt(parts[1],10) - 1 + delta;
      const d = new Date(y, m, 1);
      input.value = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
      applyOrderPickerFilter();
    }
    function setOrderPickerThisMonth() {
      const now = new Date();
      document.getElementById('order-input-month').value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
      applyOrderPickerFilter();
    }
    function shiftOrderYear(delta) {
      const select = document.getElementById('order-input-year');
      const cur = parseInt(select.value || new Date().getFullYear(), 10);
      const target = cur + delta;
      let found = false;
      for (let opt of select.options) {
        if (parseInt(opt.value,10) === target) { select.value = target; found = true; break; }
      }
      if (!found) { const opt = new Option(target, target); select.add(opt); select.value = target; }
      applyOrderPickerFilter();
    }
    function setOrderPickerThisYear() {
      document.getElementById('order-input-year').value = new Date().getFullYear();
      applyOrderPickerFilter();
    }

    async function renderOrders() {
      const loadingEl  = document.getElementById('orders-loading');
      const listEl     = document.getElementById('orders-list');
      if (loadingEl) loadingEl.style.display = 'block';
      if (listEl)    listEl.style.display = 'none';

      let apiUrl;
      if (_orderPickerMode && _orderPickerVal) {
        apiUrl = `orders.php?date_mode=${_orderPickerMode}&date_val=${encodeURIComponent(_orderPickerVal)}`;
      } else {
        apiUrl = `orders.php?date=${currentOrderFilter}`;
      }

      try {
        const res = await api(apiUrl);
        if (!res.success) { if (loadingEl) loadingEl.style.display = 'none'; return; }

        // Store for void table reference
        _lastFetchedOrders = res.orders || [];
        renderVoidTable(_lastFetchedOrders);

        const filterLabels = { today: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' };
        const ordCount = res.total_orders || 0;
        const ordRev   = res.total_revenue || 0;
        const ordAvg   = ordCount > 0 ? (ordRev / ordCount) : 0;
        // Determine period label for summary bar
        let periodLabel;
        if (_orderPickerMode && _orderPickerVal) {
          periodLabel = document.getElementById('order-picker-summary')?.textContent?.replace('Showing: ','') || _orderPickerVal;
        } else {
          periodLabel = filterLabels[currentOrderFilter] || 'Today';
        }

        document.getElementById('orders-count-badge').textContent = ordCount + ' order' + (ordCount !== 1 ? 's' : '');
        document.getElementById('osum-orders').textContent  = ordCount.toLocaleString();
        document.getElementById('osum-revenue').textContent = '₱' + ordRev.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('osum-avg').textContent     = '₱' + ordAvg.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('osum-period').textContent  = periodLabel;

        if (!res.orders.length) {
          listEl.innerHTML = '<div class="text-center text-muted py-4" style="font-size:13px;">No orders found for this period.</div>';
        } else {
          listEl.innerHTML = res.orders.map((o, idx) => {
            const cardId   = 'ocard-' + idx;
            const rawItems = (o.items || '').split(', ').filter(Boolean);
            const itemLines = rawItems.map(it => `
              <div class="order-item-line">
                <span><i class="bi bi-dot me-1" style="color:var(--cafe-amber);"></i>${it}</span>
              </div>`).join('');
            const isVoided    = o.status === 'Voided';
            const isRefunded  = o.status === 'Refunded';
            const isCompleted = o.status === 'Completed';
            const pmColor     = o.payment_method === 'GCash' ? '#0070f3' : '#059669';
            const statusBadge = isVoided
              ? `<span style="background:#fee2e2;color:#b91c1c;border-radius:20px;padding:2px 10px;font-size:10px;font-weight:700;">Voided</span>`
              : isRefunded
              ? `<span style="background:#ffedd5;color:#ea580c;border-radius:20px;padding:2px 10px;font-size:10px;font-weight:700;">Refunded</span>`
              : `<span class="badge-success" style="font-size:10px;">Completed</span>`;
            const actionBtns = isCompleted
              ? `<div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                  <button type="button" onclick="showReceiptForOrder(${o.id})" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;"><i class="bi bi-printer-fill me-1"></i>Receipt</button>
                  ${currentUser ? `
                  <button type="button" onclick="requestSingleVoid('${o.id}','${o.order_number}',${o.total})" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;"><i class="bi bi-slash-circle me-1"></i>Void Order</button>
                  <button type="button" onclick="requestSingleRefund('${o.id}','${o.order_number}',${o.total})" style="background:#ffedd5;color:#ea580c;border:1px solid #fed7aa;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;"><i class="bi bi-arrow-counterclockwise me-1"></i>Refund Order</button>` : ''}
                 </div>`
              : `<div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
                  <button type="button" onclick="showReceiptForOrder(${o.id})" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;"><i class="bi bi-printer-fill me-1"></i>Receipt</button>
                 </div>`;
            return `
            <div class="order-card${(isVoided || isRefunded) ? '" style="opacity:0.65;' : ''}" id="${cardId}">
              <div class="order-card-header" onclick="toggleOrderCard('${cardId}')">
                <div class="order-card-num">${o.order_number}</div>
                <div class="order-card-meta">
                  <div>${o.date}</div>
                  <div>${o.time} &nbsp;·&nbsp; ${o.cashier || 'Cashier'} &nbsp;·&nbsp; <span style="background:${pmColor}15;color:${pmColor};border-radius:20px;padding:2px 8px;font-size:10px;font-weight:700;">${o.payment_method}</span></div>
                </div>
                <div class="order-card-total">₱${parseFloat(o.total).toLocaleString(undefined,{minimumFractionDigits:2})}</div>
                ${statusBadge}
                <i class="bi bi-chevron-down order-card-chevron ms-2"></i>
              </div>
              <div class="order-card-body">${itemLines || '<span style="color:#aaa;font-size:12px;">No items</span>'}${actionBtns}</div>
            </div>`;
          }).join('');
        }

        // Sync local orders for dashboard
        if (currentOrderFilter === 'today') {
          orders = res.orders.map(o => ({
            id: o.order_number, time: o.time, date: new Date(o.created_at).toDateString(),
            ts: new Date(o.created_at).getTime(), items: o.items || '',
            total: parseFloat(o.total), payment: o.payment_method, status: o.status
          }));
        }

      } catch (err) { console.error('Orders load error:', err); }

      if (loadingEl) loadingEl.style.display = 'none';
      if (listEl)    listEl.style.display = '';
    }

    function toggleOrderCard(cardId) {
      const card = document.getElementById(cardId);
      if (card) card.classList.toggle('open');
    }

    async function requestSingleVoid(orderId, orderNum, total) {
      const reason = prompt(`Submit request to VOID Order ${orderNum} (₱${parseFloat(total).toFixed(2)})?\n\nEnter reason:`, 'Order cancelled before fulfillment');
      if (reason === null) return;
      try {
        const staffName = document.getElementById('adj-manager-name')?.value || currentUser?.full_name || '';
        const fullReason = staffName ? `${reason.trim() || 'Order cancelled'} — Requested by: ${staffName}` : (reason.trim() || 'Order cancelled');
        const res = await api('orders.php?action=void_order', { order_id: orderId, reason: fullReason });
        if (res.success) {
          _adjSelections = {};
          _adjSelections[orderId] = { type: 'void', orderNum, total: parseFloat(total), reason: fullReason };
          _batchApprovalToken = res.batch_token || null;
          const devBox = document.getElementById('void-dev-token-box');
          const devVal = document.getElementById('void-dev-token-val');
          if (res.dev_token) { devBox.style.display = ''; devVal.textContent = res.dev_token; }
          else               { devBox.style.display = 'none'; }
          openVoidCodeModal();
          showToast('✅ ' + orderNum + ' void requested! Waiting for manager/admin approval…');
        } else {
          showToast('⚠ ' + res.message);
        }
      } catch(e) { showToast('⚠ Failed to void order.'); }
    }

    async function requestSingleRefund(orderId, orderNum, total) {
      const reason = prompt(`Submit request to REFUND Order ${orderNum} (₱${parseFloat(total).toFixed(2)})?\n\nEnter reason:`, 'Customer refund request');
      if (reason === null) return;
      try {
        const staffName = document.getElementById('adj-manager-name')?.value || currentUser?.full_name || '';
        const fullReason = staffName ? `${reason.trim() || 'Customer refund'} — Requested by: ${staffName}` : (reason.trim() || 'Customer refund');
        const res = await api('orders.php?action=refund_order', { order_id: orderId, reason: fullReason });
        if (res.success) {
          _adjSelections = {};
          _adjSelections[orderId] = { type: 'refund', orderNum, total: parseFloat(total), reason: fullReason };
          _batchApprovalToken = res.batch_token || null;
          const devBox = document.getElementById('void-dev-token-box');
          const devVal = document.getElementById('void-dev-token-val');
          if (res.dev_token) { devBox.style.display = ''; devVal.textContent = res.dev_token; }
          else               { devBox.style.display = 'none'; }
          openVoidCodeModal();
          showToast('✅ ' + orderNum + ' refund requested! Waiting for manager/admin approval…');
        } else {
          showToast('⚠ ' + res.message);
        }
      } catch(e) { showToast('⚠ Failed to refund order.'); }
    }

    async function voidOrder(orderId, orderNum, total) {
      return requestSingleVoid(orderId, orderNum, total || 0);
    }

    async function refundOrder(orderId, orderNum, total) {
      return requestSingleRefund(orderId, orderNum, total || 0);
    }

    /* ========== VOID / REFUND TABLE ========== */
    // Map: orderId -> { type:'void'|'refund', orderNum, total }
    let _adjSelections = {};

    function renderVoidTable(orders) {
      const section    = document.getElementById('void-refund-section');
      const adjSection = document.getElementById('adj-history-section');
      if (!section) return;
      if (!currentUser) {
        section.style.display = 'none';
        if (adjSection) adjSection.style.display = 'none';
        return;
      }
      section.style.display = '';
      if (adjSection) adjSection.style.display = '';
      // Soft reset: clear visuals only, preserve localStorage so selections survive navigation
      _adjSelections = {};
      document.querySelectorAll('.adj-radio').forEach(r => r.classList.remove('adj-void-sel','adj-ref-sel'));
      const _topPanel = document.getElementById('adj-top-panel');
      if (_topPanel) _topPanel.style.display = 'none';

      const tbody = document.getElementById('void-orders-tbody');
      if (!tbody) return;

      const eligible = (orders || []).filter(o => o.status === 'Completed');
      if (!eligible.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#aaa;padding:24px;font-size:13px;">No completed orders to adjust in this period.</td></tr>';
        renderAdjustmentHistory();
        return;
      }

      tbody.innerHTML = eligible.map(o => {
        const pmColor    = o.payment_method === 'GCash' ? '#0070f3' : '#059669';
        const shortItems = (o.items||'').split(', ').slice(0,2).join(', ') + ((o.items||'').split(', ').length > 2 ? '\u2026' : '');
        const totalFmt   = '\u20b1' + parseFloat(o.total).toLocaleString(undefined,{minimumFractionDigits:2});
        const itemTitle  = (o.items||'').replace(/"/g,'&quot;');
        return `<tr id="vrow-${o.id}">
          <td style="font-weight:700;white-space:nowrap;">${o.order_number}</td>
          <td style="color:#888;font-size:12px;white-space:nowrap;">${o.date}<br>${o.time}</td>
          <td style="font-size:12px;color:#555;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${itemTitle}">${shortItems||'\u2014'}</td>
          <td style="text-align:right;font-weight:700;white-space:nowrap;">${totalFmt}</td>
          <td style="white-space:nowrap;"><span style="background:${pmColor}15;color:${pmColor};border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;display:inline-block;">${o.payment_method}</span></td>
          <td style="white-space:nowrap;"><span class="badge-success" style="font-size:10px;display:inline-block;">Completed</span></td>
          <td style="text-align:center;white-space:nowrap;">
            <div class="adj-radio adj-void-radio" id="vr-void-${o.id}"
              onclick="selectAdjRadio('${o.id}','void','${o.order_number}',${o.total})" title="Mark for Void"></div>
          </td>
          <td style="text-align:center;white-space:nowrap;">
            <div class="adj-radio adj-ref-radio" id="vr-ref-${o.id}"
              onclick="selectAdjRadio('${o.id}','refund','${o.order_number}',${o.total})" title="Mark for Refund"></div>
          </td>
        </tr>`;
      }).join('');

      renderAdjustmentHistory();
      restoreSelectionsFromStorage();
    }

    /* Toggle a radio circle — stackable across rows, exclusive within a row */
    function selectAdjRadio(orderId, type, orderNum, total) {
      const vEl = document.getElementById(`vr-void-${orderId}`);
      const rEl = document.getElementById(`vr-ref-${orderId}`);
      const existing = _adjSelections[orderId];

      if (existing && existing.type === type) {
        // Toggle off
        delete _adjSelections[orderId];
        if (vEl) vEl.classList.remove('adj-void-sel');
        if (rEl) rEl.classList.remove('adj-ref-sel');
      } else {
        const isVoid    = type === 'void';
        const defReason = existing?.reason || (isVoid ? 'Order cancelled before fulfillment' : 'Customer refund request');
        _adjSelections[orderId] = { type, orderNum, total: parseFloat(total), reason: defReason };
        if (type === 'void') {
          if (vEl) vEl.classList.add('adj-void-sel');
          if (rEl) rEl.classList.remove('adj-ref-sel');
        } else {
          if (rEl) rEl.classList.add('adj-ref-sel');
          if (vEl) vEl.classList.remove('adj-void-sel');
        }
      }
      saveSelectionsToStorage();
      updateSelectionPanel();
      updateSidePanel();
    }

    /* Rebuild the left side panel cards from current _adjSelections */
    function updateSidePanel() {
      const emptyEl  = document.getElementById('adj-side-empty');
      const listEl   = document.getElementById('adj-side-list');
      if (!emptyEl || !listEl) return;

      const keys = Object.keys(_adjSelections);
      if (!keys.length) {
        emptyEl.style.display = '';
        listEl.style.display  = 'none';
        listEl.innerHTML = '';
        return;
      }
      emptyEl.style.display = 'none';
      listEl.style.display  = '';

      listEl.innerHTML = keys.map(orderId => {
        const sel    = _adjSelections[orderId];
        const isVoid = sel.type === 'void';
        const c1     = isVoid ? '#b91c1c' : '#1d4ed8';
        const bg     = isVoid ? '#fee2e2' : '#dbeafe';
        const reason = sel.reason || '';
        const pholder= isVoid ? 'e.g. Wrong order, changed mind...' : 'e.g. Item unavailable, customer dissatisfied...';
        return `<div style="border:1.5px solid ${bg};border-radius:10px;padding:12px;margin-bottom:10px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <span style="background:${bg};color:${c1};border-radius:20px;padding:2px 10px;font-size:10px;font-weight:700;">${isVoid?'VOID':'REFUND'}</span>
            <span style="font-weight:800;font-size:13px;">${sel.orderNum}</span>
            <span style="font-weight:700;color:${c1};font-size:13px;">₱${sel.total.toLocaleString(undefined,{minimumFractionDigits:2})}</span>
          </div>
          <label style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;display:block;margin-bottom:3px;">Reason *</label>
          <input type="text" id="side-reason-inp-${orderId}" value="${reason.replace(/"/g,'&quot;')}" maxlength="200"
            placeholder="${pholder}"
            style="width:100%;padding:7px 9px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:12px;font-family:inherit;box-sizing:border-box;"
            oninput="updateSelReason('${orderId}',this.value)"
            onfocus="this.style.borderColor='${c1}'" onblur="this.style.borderColor='#e5e7eb'">
        </div>`;
      }).join('');
    }

    function updateSelectionPanel() {
      const keys = Object.keys(_adjSelections);
      const panel = document.getElementById('adj-top-panel');
      if (!panel) return;

      if (keys.length === 0) {
        panel.style.display = 'none';
        return;
      }
      panel.style.display = '';

      let voidCount = 0, refCount = 0, totalAmt = 0;
      keys.forEach(id => {
        const s = _adjSelections[id];
        if (s.type === 'void') voidCount++; else refCount++;
        totalAmt += s.total;
      });

      const parts = [];
      if (voidCount) parts.push(`<span style="color:#b91c1c;font-weight:700;">${voidCount} Void</span>`);
      if (refCount)  parts.push(`<span style="color:#1d4ed8;font-weight:700;">${refCount} Refund</span>`);
      const summaryEl = document.getElementById('adj-sel-summary');
      const totalEl   = document.getElementById('adj-sel-total');
      if (summaryEl) summaryEl.innerHTML = `${keys.length} order${keys.length>1?'s':''} selected &nbsp;(${parts.join(' &amp; ')})`;
      if (totalEl)   totalEl.textContent = '\u20b1' + totalAmt.toLocaleString(undefined,{minimumFractionDigits:2});

      // Pre-fill manager name if blank
      const mgrEl = document.getElementById('adj-manager-name');
      if (mgrEl && !mgrEl.value) mgrEl.value = currentUser?.full_name || '';

      // Hide errors
      ['adj-manager-error','adj-reason-error','adj-req-status'].forEach(id => {
        const el = document.getElementById(id); if (el) el.style.display = 'none';
      });
    }

    function clearAdjSelection() {
      _adjSelections = {};
      try { localStorage.removeItem(ADJ_STORE_KEY); } catch(e){}
      document.querySelectorAll('.adj-radio').forEach(r => r.classList.remove('adj-void-sel','adj-ref-sel'));
      const panel = document.getElementById('adj-top-panel');
      if (panel) panel.style.display = 'none';
      updateSidePanel();
    }

    async function renderAdjustmentHistory() {
      const tbody = document.getElementById('adj-history-tbody');
      if (!tbody) return;
      try {
        const res = await api('orders.php?action=get_adjustments');
        if (!res.success || !res.adjustments.length) {
          tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#aaa;padding:16px;">No adjustments yet.</td></tr>';
          return;
        }
        tbody.innerHTML = res.adjustments.map(a => {
          const isVoid = a.type === 'void';
          const badge  = isVoid
            ? '<span style="background:#fee2e2;color:#b91c1c;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">Void</span>'
            : '<span style="background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">Refund</span>';
          const dt = new Date(a.created_at).toLocaleString('en-PH',{dateStyle:'medium',timeStyle:'short'});
          return `<tr>
            <td style="white-space:nowrap;">${badge}</td>
            <td style="font-weight:700;white-space:nowrap;">${a.order_number}</td>
            <td style="font-weight:700;color:#b91c1c;white-space:nowrap;">\u20b1${parseFloat(a.amount).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
            <td style="font-size:12px;color:#555;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${(a.reason||'').replace(/"/g,'&quot;')}">${a.reason||'\u2014'}</td>
            <td style="font-size:12px;color:#888;white-space:nowrap;">${dt}</td>
          </tr>`;
        }).join('');
      } catch(e) { /* silent */ }
    }
    /* ---- localStorage persistence ---- */
    const ADJ_STORE_KEY = 'chipipie_adj_sel';
    function saveSelectionsToStorage() {
      try { localStorage.setItem(ADJ_STORE_KEY, JSON.stringify(_adjSelections)); } catch(e){}
    }
    function restoreSelectionsFromStorage() {
      try {
        const saved = localStorage.getItem(ADJ_STORE_KEY);
        if (!saved) return;
        const data = JSON.parse(saved);
        Object.keys(data).forEach(orderId => {
          const sel = data[orderId];
          const vEl = document.getElementById(`vr-void-${orderId}`);
          const rEl = document.getElementById(`vr-ref-${orderId}`);
          if (!vEl && !rEl) return;
          _adjSelections[orderId] = sel;
          if (sel.type === 'void') { if (vEl) vEl.classList.add('adj-void-sel'); }
          else                     { if (rEl) rEl.classList.add('adj-ref-sel'); }
        });
        updateSelectionPanel();
        updateSidePanel();
      } catch(e){}
    }
    function updateSelReason(orderId, value) {
      if (_adjSelections[orderId]) {
        _adjSelections[orderId].reason = value;
        saveSelectionsToStorage();
        // Also sync the side-panel input if it differs (for cross-reference)
        const inp = document.getElementById(`side-reason-inp-${orderId}`);
        if (inp && inp.value !== value) inp.value = value;
      }
    }

    let _batchApprovalToken = null;
    let _batchPollInterval  = null;

    function openVoidCodeModal() {
      const keys = Object.keys(_adjSelections);
      let voidCount = 0, refCount = 0;
      keys.forEach(id => { if (_adjSelections[id].type === 'void') voidCount++; else refCount++; });
      const label = voidCount && refCount ? 'Void &amp; Refund' : voidCount ? 'Void' : 'Refund';
      const c1    = (refCount && !voidCount) ? '#1d4ed8' : '#dc2626';
      const c2    = (refCount && !voidCount) ? '#1e40af' : '#b91c1c';
      const hdr = document.getElementById('void-modal-hdr');
      if (hdr) hdr.style.background = `linear-gradient(135deg,${c1},${c2})`;
      const title = document.getElementById('void-modal-title');
      if (title) title.innerHTML = `<i class="bi bi-shield-lock me-2"></i>${label} — Enter Code`;
      const btn = document.getElementById('void-confirm-btn');
      if (btn) {
        btn.style.background = `linear-gradient(135deg,${c1},${c2})`;
        btn.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Confirm ${label}`;
      }
      document.getElementById('void-token-input').value = '';
      document.getElementById('void-token-error').style.display = 'none';
      document.getElementById('void-auth-modal').classList.add('show');

      // Start polling if we have a batch token (submitted via request_void_batch)
      if (_batchApprovalToken) {
        stopBatchPoll(); // clear any previous
        _batchPollInterval = setInterval(pollBatchStatus, 4000);
      }
    }

    function closeVoidAuthModal() {
      document.getElementById('void-auth-modal').classList.remove('show');
      stopBatchPoll();
    }

    function stopBatchPoll() {
      if (_batchPollInterval) { clearInterval(_batchPollInterval); _batchPollInterval = null; }
    }

    async function pollBatchStatus() {
      if (!_batchApprovalToken) return stopBatchPoll();
      try {
        const res = await api(`orders.php?action=check_batch_status&batch_token=${_batchApprovalToken}`, null, 'GET');
        if (!res.success) return;
        if (res.batch_status === 'approved') {
          stopBatchPoll();
          _batchApprovalToken = null;
          closeVoidAuthModal();
          clearAdjSelection();
          renderOrders();
          updateDashboard();
          const via = res.approved_via || 'Super Admin';
          showToast(`✅ Request approved by ${via}! Orders processed.`);
        } else if (res.batch_status === 'rejected') {
          stopBatchPoll();
          _batchApprovalToken = null;
          closeVoidAuthModal();
          showToast('❌ Request was rejected.');
        }
      } catch(e) { /* ignore poll errors silently */ }
    }

    async function requestVoidCode() {
      if (!Object.keys(_adjSelections).length) return;

      // Validate manager name
      const mgrEl  = document.getElementById('adj-manager-name');
      const mgrErr = document.getElementById('adj-manager-error');
      const mgr    = mgrEl ? mgrEl.value.trim() : '';
      if (!mgr) {
        if (mgrErr) mgrErr.style.display = '';
        if (mgrEl)  { mgrEl.focus(); mgrEl.style.borderColor = '#dc2626'; }
        return;
      }
      if (mgrErr) mgrErr.style.display = 'none';

      // Validate that every selected order has a reason in _adjSelections or side-panel input
      let missingReason = false;
      Object.keys(_adjSelections).forEach(orderId => {
        const inp = document.getElementById(`side-reason-inp-${orderId}`);
        const val = (inp ? inp.value : _adjSelections[orderId].reason || '').trim();
        if (!val) {
          missingReason = true;
          if (inp) { inp.focus(); inp.style.borderColor = '#dc2626'; }
        } else {
          _adjSelections[orderId].reason = val;
          if (inp) inp.style.borderColor = '#e5e7eb';
        }
      });
      if (missingReason) {
        const status = document.getElementById('adj-req-status');
        if (status) { status.style.display = ''; status.textContent = '\u26a0 Please enter a reason for each marked order.'; }
        return;
      }
      saveSelectionsToStorage();

      const btn    = document.getElementById('adj-req-btn');
      const status = document.getElementById('adj-req-status');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending…';
      status.style.display = 'none';

      try {
        const payload = Object.keys(_adjSelections).map(id => {
          return { id, type: _adjSelections[id].type, reason: _adjSelections[id].reason, orderNum: _adjSelections[id].orderNum };
        });
        const res = await api('orders.php?action=request_void_batch', { 
            orders: payload, 
            manager: document.getElementById('adj-manager-name')?.value || '' 
        });
        if (res.success) {
          const devBox = document.getElementById('void-dev-token-box');
          const devVal = document.getElementById('void-dev-token-val');
          if (res.dev_token) { devBox.style.display = ''; devVal.textContent = res.dev_token; }
          else               { devBox.style.display = 'none'; }
          // Store the batch approval token for polling
          _batchApprovalToken = res.batch_token || null;
          openVoidCodeModal();
          const countMsg = res.recipients_count ? `sent to ${res.recipients_count} admin/manager email(s)` : 'sent';
          showToast(`✅ Request ${countMsg}! Waiting for approval…`);
        } else {
          status.style.display = '';
          status.textContent   = '⚠ ' + res.message;
        }
      } catch(e) {
        status.style.display = '';
        status.textContent   = '⚠ Request failed. Check connection.';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-envelope-fill me-1"></i>Submit Request for Approval';
      }
    }

    async function confirmVoidWithCode() {
      const token = document.getElementById('void-token-input').value.trim().toUpperCase();
      const errEl = document.getElementById('void-token-error');
      const btn   = document.getElementById('void-confirm-btn');
      errEl.style.display = 'none';

      if (!token || token.length < 6) {
        errEl.textContent = 'Please enter the authorization code.';
        errEl.style.display = ''; return;
      }
      const keys = Object.keys(_adjSelections);
      if (!keys.length) {
        errEl.textContent = 'No orders selected. Please close and try again.';
        errEl.style.display = ''; return;
      }

      const mgr        = (document.getElementById('adj-manager-name')?.value || '').trim();
      const baseReason = (document.getElementById('adj-reason-input')?.value  || '').trim()
                        || 'Adjustment';
      const reason = mgr ? `${baseReason} — Authorized by: ${mgr}` : baseReason;

      const _resetBtn = () => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Confirm';
      };
      btn.disabled = true; btn.textContent = 'Verifying…';

      const tid = setTimeout(() => {
        errEl.textContent = '⚠ Server took too long. Please try again.';
        errEl.style.display = ''; _resetBtn();
      }, 20000);

      try {
        const res = await api('orders.php?action=execute_void_batch_with_code', { token });
        clearTimeout(tid);
        
        if (!res.success) {
          errEl.textContent = '⚠ ' + res.message;
          errEl.style.display = ''; _resetBtn(); return;
        }

        closeVoidAuthModal();
        clearAdjSelection();
        showToast(`✅ Request approved and ${keys.length} order(s) processed!`);
        renderOrders();
        updateDashboard();

      } catch(e) {
        clearTimeout(tid);
        errEl.textContent = '⚠ Verification failed. Try again.';
        errEl.style.display = ''; _resetBtn();
      }
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
        const ordersHtml = res.orders.map(o => {
          let statusStyle = '';
          if(o.status === 'Voided') statusStyle = 'color:#dc3545;font-weight:bold;';
          if(o.status === 'Refunded') statusStyle = 'color:#fd7e14;font-weight:bold;';
          
          return `
          <tr>
            <td>${o.order_number}</td>
            <td>${o.date}</td>
            <td>${o.time}</td>
            <td style="max-width:240px;font-size:11px;">${o.items || '—'}</td>
            <td>${o.cashier || '—'}</td>
            <td>${o.payment_method}</td>
            <td style="${statusStyle}">${o.status}</td>
            <td style="text-align:right;">₱${parseFloat(o.total).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
          </tr>`;
        }).join('');

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
    <div class="section-title">Order Transactions (${res.summary.orders} Completed)</div>
    <table>
      <thead><tr><th>Order #</th><th>Date</th><th>Time</th><th>Items</th><th>Cashier</th><th>Payment</th><th>Status</th><th style="text-align:right;">Total</th></tr></thead>
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

    // Group definitions matching the POS ordering grid
    const MENU_GROUPS = [
      // ── Coffee Branch ─────────────────────────────────────
      { label: 'All',         icon: 'bi-grid',              cats: null },   // null = no filter
      { label: 'Coffee',      icon: 'bi-cup-hot-fill',      cats: ['Hot Coffee', 'Iced Coffee'] },
      { label: 'Non-Coffee',  icon: 'bi-cup-straw',         cats: ['Classic Milktea', 'Virgin Mojito', 'Citrus Popping Boba', 'Soda Float', 'Matcha Series', 'Frappé'] },
      { label: 'Snack',       icon: 'bi-egg-fried',         cats: ['Fork and Fun'] },
      // ── Pizza Branch ──────────────────────────────────────
      { label: 'Pizza',       icon: 'bi-circle',            cats: ['Pizza'] },
      { label: 'Quesadilla',  icon: 'bi-grid-3x3-gap',      cats: ['Quesadilla'] },
      { label: 'Patatas',     icon: 'bi-bag-heart',         cats: ['Patatas'] },
      { label: 'Pasta',       icon: 'bi-stars',             cats: ['Pasta'] },
      { label: 'Chickenpops', icon: 'bi-lightning',         cats: ['Chickenpops'] },
      { label: 'Combo',       icon: 'bi-collection',        cats: ['Combo', 'Gipitmeal'] },
    ];

    let currentMenuMgmtGroup = MENU_GROUPS[0]; // default = All

    function setMenuMgmtFilter(label, btn) {
      document.querySelectorAll('#menu-mgmt-filter-tabs .cat-pill').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentMenuMgmtGroup = MENU_GROUPS.find(g => g.label === label) || MENU_GROUPS[0];
      renderMenuTable();
    }

    /* ========== MENU TABLE ========== */
    let menuMgmtTabsBuilt = false;

    function renderMenuTable() {
      // Build tabs once
      if (!menuMgmtTabsBuilt) {
        const tabsEl = document.getElementById('menu-mgmt-filter-tabs');
        if (tabsEl) {
          tabsEl.innerHTML = MENU_GROUPS
            .filter(g => g.label === 'All' || (menuItems || []).some(m => g.cats && g.cats.includes(m.category)))
            .map((g, i) => {
              const sep = (g.label === 'Pizza') ? '<span style="width:1px;background:var(--cafe-tan);height:22px;display:inline-block;margin:0 4px;vertical-align:middle;"></span>' : '';
              return `${sep}<button class="cat-pill${i === 0 ? ' active' : ''}" onclick="setMenuMgmtFilter('${g.label}', this)"><i class="bi ${g.icon} me-1"></i>${g.label}</button>`;
            })
            .join('');
          menuMgmtTabsBuilt = true;
        }
      }

      const q = (document.getElementById('menu-mgmt-search')?.value || '').toLowerCase();
      const filtered = menuItems.filter(m => {
        const matchQ = !q || m.name.toLowerCase().includes(q);
        const matchCat = !currentMenuMgmtGroup.cats || currentMenuMgmtGroup.cats.includes(m.category);
        return matchQ && matchCat;
      });

      document.getElementById('menu-tbody').innerHTML = filtered.map(item => {
        const isOut = item.stock <= 0;
        const isLow = !isOut && item.stock <= item.minStock;
        const stockBadgeClass = isOut ? 'stock-out' : (isLow ? 'stock-low' : 'stock-ok');
        
        let stockIcon = '';
        if (isOut) stockIcon = '<i class="bi bi-x-circle-fill me-1"></i>';
        else if (isLow) stockIcon = '<i class="bi bi-exclamation-triangle-fill me-1"></i>';

        const stockText = isOut ? 'No Stock' : (isLow ? 'Low Stock (' + item.stock + ')' : item.stock + ' / min ' + item.minStock);
        return `
    <tr>
      <td style="white-space:nowrap;">
        ${item.image ? `<img src="${item.image}" style="width:24px; height:24px; object-fit:cover; border-radius:50%; margin-right:8px; vertical-align:middle;">` : `<span style="font-size:16px; margin-right:8px; vertical-align:middle; color:var(--cafe-brown);"><i class="bi bi-cup-hot-fill"></i></span>`}
        <span style="font-weight:600; vertical-align:middle;">${item.name}</span>
      </td>
      <td style="white-space:nowrap;"><span class="badge-cat">${item.category}</span></td>
      <td style="font-weight:600; color:var(--cafe-amber); white-space:nowrap;">₱${item.price}</td>
      <td style="white-space:nowrap;">
        <span class="stock-badge ${stockBadgeClass}" style="font-size:10.5px; padding:2px 7px;">${stockIcon}${stockText}</span>
        <div style="margin-top:4px; display:flex; align-items:center; gap:3px;">
          <button class="qty-btn" onclick="adjustStock(${item.id}, -1)" title="Decrease stock">−</button>
          <input type="number" min="0" value="${item.stock}" style="width:42px; height:24px; font-size:11.5px; font-weight:700; border:1px solid var(--cafe-tan); border-radius:5px; padding:0 2px; text-align:center;" onchange="setStock(${item.id}, this.value)" title="Direct stock edit">
          <button class="qty-btn" onclick="adjustStock(${item.id}, 1)" title="Increase stock">+</button>
        </div>
      </td>
      <td style="white-space:nowrap;">
        <span class="${item.available ? 'badge-success' : 'badge-pending'}" style="font-size:11px; padding:3px 8px;">${item.available ? 'Available' : 'Unavailable'}</span>
      </td>
      <td style="white-space:nowrap; text-align:right;">
        <button class="btn-menu-action me-1" style="background:var(--cafe-tan); color:var(--cafe-brown);" onclick="toggleAvail(${item.id})" title="${item.available ? 'Mark Unavailable' : 'Mark Available'}">
          ${item.available ? 'Mark Unavail.' : 'Mark Avail.'}
        </button>
        <button class="btn-menu-action me-1" style="background:#dbeafe; color:#1d4ed8;" onclick="posOpenPhotoModal(${item.id}, '${item.name.replace(/'/g, "\\'")}')" title="Upload / change photo">
          <i class="bi bi-camera-fill"></i>Photo
        </button>
        ${pendingDeleteIds.has(item.id)
          ? `<div style="display:inline-flex;align-items:center;gap:3px;">
               <button class="btn-menu-action" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a !important;" onclick="openCancelRemovalModal(${item.id})" title="Click to view or cancel pending removal">
                 <i class="bi bi-hourglass-split"></i>Pending
               </button>
               <button class="btn-menu-action" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5 !important;" onclick="openCancelRemovalModal(${item.id})" title="Cancel this pending removal request">
                 <i class="bi bi-x-circle"></i>Cancel
               </button>
             </div>`
          : `<button class="btn-menu-action" style="background:#f8d7da; color:#842029;" onclick="deleteMenuItem(${item.id})" title="Delete item">
               <i class="bi bi-trash-fill"></i>Delete
             </button>`
        }
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
        renderPOS();
        showToast(res.message);
      }
    }

    // deleteMenuItem submits a removal request for admin/superadmin approval without requiring codes

    async function adjustStock(id, delta) {
      const res = await api('menu.php?action=adjust_stock', { id, delta });
      if (res.success) {
        const item = menuItems.find(m => m.id == id);
        if (item) { item.stock = res.stock; item.available = !!res.is_available; }
        renderMenuTable();
        renderPOS();
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
        renderPOS();
        showToast('Stock updated → ' + res.stock);
      }
    }

    /* --- Populate Add-Item category dropdown dynamically --- */
    const POS_PIZZA_CAT_IDS = new Set([10, 11, 12, 13, 14, 15, 16]);

    // Track the approval token for the pending add request
    let _addItemApprovalToken = null;

    function populateAddItemCategorySelect() {
      // Reset modal to Step 1 whenever it opens
      _addItemApprovalToken = null;
      document.getElementById('add-item-step1').style.display = '';
      const s2c = document.getElementById('add-item-step2-cashier');
      if (s2c) s2c.style.display = 'none';
      const s2a = document.getElementById('add-item-step2-admin');
      if (s2a) s2a.style.display = 'none';
      document.getElementById('add-item-step1-btn').style.display = '';
      document.getElementById('add-item-step2-btn').style.display = 'none';
      const doneBtn = document.getElementById('add-item-done-btn');
      if (doneBtn) doneBtn.style.display = 'none';
      const cancelBtn = document.getElementById('add-item-cancel-btn');
      if (cancelBtn) cancelBtn.style.display = '';
      document.getElementById('addItemModalTitle').textContent = 'Add Menu Item';
      document.getElementById('add-item-auth-code').value = '';
      document.getElementById('add-item-step2-msg').textContent = '';
      document.getElementById('add-item-dev-token-box').style.display = 'none';
      document.getElementById('add-item-dev-token').textContent = '';
      // Clear form fields
      document.getElementById('new-name').value = '';
      document.getElementById('new-price').value = '';
      document.getElementById('new-stock').value = '20';
      document.getElementById('new-min-stock').value = '5';
      try { document.getElementById('new-image').value = ''; } catch(e) {}
      // Populate categories
      const sel = document.getElementById('new-category');
      const cafeCategories  = posMenuCategories.filter(c => !POS_PIZZA_CAT_IDS.has(c.id));
      const pizzaCategories = posMenuCategories.filter(c =>  POS_PIZZA_CAT_IDS.has(c.id));
      let html = '';
      if (cafeCategories.length) {
        html += `<optgroup label="☕ ChipiPie Café">`;
        cafeCategories.forEach(c => { html += `<option value="${c.id}" data-name="${c.name}">${c.name}</option>`; });
        html += `</optgroup>`;
      }
      if (pizzaCategories.length) {
        html += `<optgroup label="🍕 Pizza House">`;
        pizzaCategories.forEach(c => { html += `<option value="${c.id}" data-name="${c.name}">${c.name}</option>`; });
        html += `</optgroup>`;
      }
      if (!html) {
        posMenuCategories.forEach(c => { html += `<option value="${c.id}" data-name="${c.name}">${c.name}</option>`; });
      }
      sel.innerHTML = html;
    }

    // STEP 1: Submit the add-item request (sends email to admin/super admin)
    async function addMenuItemSubmit() {
      const name = document.getElementById('new-name').value.trim();
      const imageInput = document.getElementById('new-image');
      const catSel = document.getElementById('new-category');
      const categoryId   = parseInt(catSel.value) || 1;
      const categoryName = catSel.selectedOptions[0]?.dataset.name || catSel.selectedOptions[0]?.text || '';
      const price = parseFloat(document.getElementById('new-price').value);
      const stock = parseInt(document.getElementById('new-stock').value) || 0;
      const minStock = parseInt(document.getElementById('new-min-stock').value) || 5;

      if (!name) { showToast('⚠ Please enter an item name.'); return; }
      if (!price || price <= 0) { showToast('⚠ Please enter a valid price.'); return; }

      const btn = document.getElementById('add-item-step1-btn');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';

      let imageSrc = null;
      if (imageInput.files && imageInput.files[0]) {
        imageSrc = await new Promise(resolve => {
          const reader = new FileReader();
          reader.onload = e => resolve(e.target.result);
          reader.readAsDataURL(imageInput.files[0]);
        });
      }

      const res = await api('menu.php?action=request_add', {
        name, category_id: categoryId, category_name: categoryName, price, stock, min_stock: minStock,
        image: imageSrc
      });

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send me-1"></i>Send Request';

      if (res.success) {
        _addItemApprovalToken = res.approval_token || null;
        document.getElementById('add-item-step1').style.display = 'none';
        document.getElementById('add-item-step1-btn').style.display = 'none';

        const isCashier = (res.is_cashier !== undefined) ? !!res.is_cashier : (!currentUser || currentUser.role === 'cashier');

        if (isCashier) {
          // Cashier: show pending approval confirmation, no authorization code entry
          const s2c = document.getElementById('add-item-step2-cashier');
          if (s2c) s2c.style.display = '';
          const s2a = document.getElementById('add-item-step2-admin');
          if (s2a) s2a.style.display = 'none';
          document.getElementById('add-item-step2-btn').style.display = 'none';
          const doneBtn = document.getElementById('add-item-done-btn');
          if (doneBtn) doneBtn.style.display = '';
          const cancelBtn = document.getElementById('add-item-cancel-btn');
          if (cancelBtn) cancelBtn.style.display = 'none';
          document.getElementById('addItemModalTitle').textContent = 'Request Submitted';
          const nameEl = document.getElementById('add-item-cashier-item-name');
          if (nameEl) nameEl.textContent = `"${name}"`;
          const reqEl = document.getElementById('add-item-cashier-requester');
          if (reqEl) reqEl.textContent = currentUser?.full_name || 'Cashier';
          showToast('✓ ' + (res.message || 'Add request submitted for approval!'));
        } else {
          // Admin / Super Admin: show PIN authorization code entry
          const s2c = document.getElementById('add-item-step2-cashier');
          if (s2c) s2c.style.display = 'none';
          const s2a = document.getElementById('add-item-step2-admin');
          if (s2a) s2a.style.display = '';
          document.getElementById('add-item-step2-btn').style.display = '';
          document.getElementById('addItemModalTitle').textContent = 'Enter Authorization Code';
          // DEV mode: show the code on screen
          if (res.dev_token) {
            document.getElementById('add-item-dev-token-box').style.display = '';
            document.getElementById('add-item-dev-token').textContent = res.dev_token;
          }
        }
      } else {
        showToast('⚠ ' + res.message);
      }
    }

    // STEP 2: Enter the authorization code to execute the add
    async function addMenuItemConfirmCode() {
      const code = document.getElementById('add-item-auth-code').value.trim();
      const msgEl = document.getElementById('add-item-step2-msg');
      msgEl.textContent = '';

      if (!code || code.length < 6) {
        msgEl.textContent = 'Please enter the full 6-digit code.';
        return;
      }

      const btn = document.getElementById('add-item-step2-btn');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding...';

      const res = await api('menu.php?action=execute_add_with_code', { token: code });

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm & Add';

      if (res.success) {
        bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
        showToast('✅ ' + (res.message || 'Item added to menu successfully!'));
        // Refresh menu data
        await loadMenuFromDB();
        renderMenuTable();
        renderPOS();
      } else {
        msgEl.textContent = '⚠ ' + (res.message || 'Invalid or expired code.');
      }
    }

    // Legacy alias kept for any leftover references
    async function addMenuItem() { addMenuItemSubmit(); }

    /* --- Photo Upload (POS admin) --- */
    let _posPhotoItemId = null;

    function posOpenPhotoModal(id, name) {
      _posPhotoItemId = id;
      document.getElementById('pos-photo-item-label').textContent = 'Item: ' + name;
      document.getElementById('pos-photo-file').value = '';
      document.getElementById('pos-photo-preview').src = '';
      document.getElementById('pos-photo-preview-wrap').style.display = 'none';
      const modal = new bootstrap.Modal(document.getElementById('posPhotoModal'));
      modal.show();
    }

    function posPreviewPhoto(input) {
      const wrap = document.getElementById('pos-photo-preview-wrap');
      const img  = document.getElementById('pos-photo-preview');
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = ev => { img.src = ev.target.result; wrap.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
      } else {
        wrap.style.display = 'none';
      }
    }

    async function posSubmitPhoto() {
      if (!_posPhotoItemId) return;
      const fileInput = document.getElementById('pos-photo-file');
      if (!fileInput.files || !fileInput.files[0]) {
        showToast('⚠ Please select a photo first.');
        return;
      }
      const btn = document.getElementById('pos-photo-submit-btn');
      btn.disabled = true;
      try {
        const imageSrc = await new Promise(resolve => {
          const reader = new FileReader();
          reader.onload = ev => resolve(ev.target.result);
          reader.readAsDataURL(fileInput.files[0]);
        });
        const res = await api('menu.php?action=update', { id: _posPhotoItemId, image: imageSrc });
        if (res.success) {
          bootstrap.Modal.getInstance(document.getElementById('posPhotoModal')).hide();
          // Update local cache so table refreshes with new image
          const item = menuItems.find(m => m.id == _posPhotoItemId);
          if (item) item.image = imageSrc;
          renderMenuTable();
          showToast('✓ Photo updated successfully!');
        } else {
          showToast('⚠ ' + (res.message || 'Failed to update photo.'));
        }
      } catch (err) {
        showToast('⚠ Error uploading photo.');
      } finally {
        btn.disabled = false;
      }
    }

    /* ========== REPORTS ========== */
    async function renderReports() {
      try {
        const stats = await api('orders.php?action=stats');
        if (!stats.success) return;

        // KPI cards — all 4 now live
        const el = id => document.getElementById(id);
        const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

        if (el('rep-kpi-month-rev'))    el('rep-kpi-month-rev').textContent    = '₱' + stats.month.revenue.toLocaleString();
        if (el('rep-kpi-month-orders')) el('rep-kpi-month-orders').textContent = stats.month.orders.toLocaleString();

        // Avg daily = month revenue / 30
        const avgDaily = stats.month.orders > 0 ? Math.round(stats.month.revenue / 30) : 0;
        if (el('rep-kpi-avg-daily'))       el('rep-kpi-avg-daily').textContent       = '₱' + avgDaily.toLocaleString();
        if (el('rep-kpi-avg-daily-trend')) el('rep-kpi-avg-daily-trend').textContent = 'Based on last 30 days';

        // Best day — peak index from weekly chart
        const weekly = stats.weekly_chart || [0,0,0,0,0,0,0];
        const weekDayNames = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const peakIdx = weekly.indexOf(Math.max(...weekly));
        const bestDay = Math.max(...weekly) > 0 ? weekDayNames[peakIdx] : '—';
        const bestRev = Math.max(...weekly);
        if (el('rep-kpi-best-day'))       el('rep-kpi-best-day').textContent       = bestDay;
        if (el('rep-kpi-best-day-trend')) el('rep-kpi-best-day-trend').textContent = bestRev > 0 ? '₱' + bestRev.toLocaleString() + ' this week' : 'No data yet';

        // Volume table
        const tbody = document.getElementById('rep-vol-tbody');
        if (tbody) {
          tbody.innerHTML = `
            <tr><td style="font-weight:500;">Today</td><td style="font-weight:600; color:var(--cafe-brown);">${stats.cups_sold || 0}</td><td style="font-weight:600; color:var(--cafe-amber);">${stats.food_sold || 0}</td></tr>
            <tr><td style="font-weight:500;">This Week</td><td style="font-weight:600; color:var(--cafe-brown);">—</td><td style="font-weight:600; color:var(--cafe-amber);">—</td></tr>
            <tr><td style="font-weight:500;">This Month</td><td style="font-weight:600; color:var(--cafe-brown);">—</td><td style="font-weight:600; color:var(--cafe-amber);">—</td></tr>
            <tr><td style="font-weight:500;">This Year</td><td style="font-weight:600; color:var(--cafe-brown);">—</td><td style="font-weight:600; color:var(--cafe-amber);">—</td></tr>
          `;
        }

        // Category breakdown
        const catColorPalette = [V_BLUE,V_PURPLE,V_GREEN,V_ORANGE,V_RED,V_TEAL,V_PINK,V_LIME,V_INDIGO];
        const catData = stats.cat_breakdown || [];
        const totalItems = catData.reduce((s, c) => s + parseInt(c.qty), 0);
        const cats = catData.map((c, i) => ({
          name: c.name,
          qty: parseInt(c.qty),
          pct: totalItems ? Math.round((parseInt(c.qty) / totalItems) * 100) : 0,
          color: catColorPalette[i % catColorPalette.length]
        }));

        document.getElementById('cat-breakdown').innerHTML = cats.map(c => `
    <div class="report-stat">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="width:12px; height:12px; border-radius:4px; background:${c.color}; display:inline-block; flex-shrink:0;"></span>
        <span style="font-size:13px;">${c.name}</span>
      </div>
      <span style="font-weight:800; color:${c.color}; font-size:14px;">${c.pct}%</span>
    </div>
  `).join('') || '<div class="text-muted p-2 text-center">No data this month</div>';

        // Category chart
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

        // Item performance chart
        if (window.itemChartIns) {
          const itemPerf = stats.item_performance || [];
          const barColors = VBAR;
          if (itemPerf.length > 0) {
            window.itemChartIns.data.labels = itemPerf.map(i => i.name);
            window.itemChartIns.data.datasets[0].data = itemPerf.map(i => parseInt(i.sold));
            window.itemChartIns.data.datasets[0].backgroundColor = barColors.slice(0, itemPerf.length);
          } else {
            window.itemChartIns.data.labels = ['No Data Yet'];
            window.itemChartIns.data.datasets[0].data = [0];
          }
          window.itemChartIns.update();
        }

        // Weekly sales chart
        if (window.salesChartIns) {
          window.salesChartIns.data.datasets[0].data = stats.weekly_chart;
          window.salesChartIns.update();
        }

        // Hourly chart
        if (window.hourlyChartIns) {
          window.hourlyChartIns.data.datasets[0].data = stats.hourly_chart;
          window.hourlyChartIns.update();
        }

        // Weekly chart in reports
        if (window.repWeeklyChartIns && stats.weekly_chart) {
          window.repWeeklyChartIns.data.datasets[0].data = stats.weekly_chart;
          window.repWeeklyChartIns.update();
        }

        // Monthly chart
        if (window.monthlyChartIns) {
          window.monthlyChartIns.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
          window.monthlyChartIns.data.datasets[0].data = stats.monthly_chart;
          window.monthlyChartIns.update();
        }

      } catch(e) { console.warn('Reports render failed:', e); }
    }

    /* ========== CHARTS ========== */
    const BROWN  = '#3a2f2d';
    const AMBER  = '#838582';
    // Vibrant palette
    const V_BLUE   = '#2563eb'; const V_PURPLE = '#7c3aed';
    const V_GREEN  = '#059669'; const V_ORANGE = '#f59e0b';
    const V_RED    = '#dc2626'; const V_TEAL   = '#0891b2';
    const V_PINK   = '#db2777'; const V_LIME   = '#65a30d';
    const V_INDIGO = '#4f46e5';
    const VBAR = [V_BLUE,V_PURPLE,V_GREEN,V_ORANGE,V_RED,V_TEAL,V_PINK,V_LIME,V_INDIGO];

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
            data: [0,0,0,0,0,0,0],
            backgroundColor: [V_BLUE,V_PURPLE,V_GREEN,V_ORANGE,V_RED,V_TEAL,V_PINK],
            borderRadius: 8, borderSkipped: false,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } }
          },
          scales: {
            y: { grid: { color: gridColor }, beginAtZero: true, ticks: { color: tickColor, font: baseFont, callback: v => v >= 1000 ? '₱' + (v/1000) + 'k' : '₱' + v } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont } }
          }
        }
      });

      /* Category Doughnut */
      window.categoryChartIns = new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
          labels: ['No Data Yet'],
          datasets: [{ data: [1], backgroundColor: ['#e9ecef'], borderWidth: 3, borderColor: '#fff' }]
        },
        options: {
          responsive: true, maintainAspectRatio: false, cutout: '62%',
          plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 10 } }
          }
        }
      });

      /* Dashboard Hourly Line */
      window.dashHourlyChartIns = new Chart(document.getElementById('dashHourlyChart'), {
        type: 'line',
        data: {
          labels: ['9am','10am','11am','12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm','11pm','12am','1am','2am'],
          datasets: [{
            data: [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            borderColor: V_TEAL, backgroundColor: 'rgba(8,145,178,0.10)', fill: true, tension: 0.4,
            pointRadius: 4, pointBackgroundColor: V_TEAL, pointBorderColor: '#fff', pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } } },
          scales: {
            y: { grid: { color: gridColor }, beginAtZero: true, ticks: { color: tickColor, font: baseFont } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont } }
          }
        }
      });

      /* Analytics Weekly Bar */
      window.repWeeklyChartIns = new Chart(document.getElementById('repWeeklyChart'), {
        type: 'bar',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
            data: [0,0,0,0,0,0,0],
            backgroundColor: [V_BLUE,V_PURPLE,V_GREEN,V_ORANGE,V_RED,V_TEAL,V_PINK],
            borderRadius: 8, borderSkipped: false,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } } },
          scales: {
            y: { grid: { color: gridColor }, beginAtZero: true, ticks: { color: tickColor, font: baseFont } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont } }
          }
        }
      });

      /* Hourly Line */
      window.hourlyChartIns = new Chart(document.getElementById('hourlyChart'), {
        type: 'line',
        data: {
          labels: ['9am','10am','11am','12pm','1pm','2pm','3pm','4pm','5pm','6pm','7pm','8pm','9pm','10pm','11pm','12am','1am','2am'],
          datasets: [{
            data: [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            borderColor: V_TEAL, backgroundColor: 'rgba(8,145,178,0.10)', fill: true, tension: 0.4,
            pointRadius: 4, pointBackgroundColor: V_TEAL, pointBorderColor: '#fff', pointBorderWidth: 2
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
          labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          datasets: [{
            data: [0,0,0,0,0,0,0,0,0,0,0,0],
            borderColor: V_PURPLE, backgroundColor: 'rgba(124,58,237,0.08)', fill: true, tension: 0.4,
            pointRadius: 4, pointBackgroundColor: V_PURPLE, pointBorderColor: '#fff', pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ₱' + ctx.raw.toLocaleString() } }
          },
          scales: {
            y: { grid: { color: gridColor }, beginAtZero: true, ticks: { color: tickColor, font: baseFont, callback: v => v >= 1000 ? '\u20b1' + (v/1000) + 'k' : '\u20b1' + v } },
            x: { grid: { display: false }, ticks: { color: tickColor, font: baseFont } }
          }
        }
      });

      /* Item Horizontal Bar */
      window.itemChartIns = new Chart(document.getElementById('itemChart'), {
        type: 'bar', indexAxis: 'y',
        data: {
          labels: ['—'],
          datasets: [{
            data: [0],
            backgroundColor: VBAR,
            borderRadius: 6, borderSkipped: false,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { color: gridColor }, ticks: { color: tickColor, font: baseFont } },
            y: { grid: { display: false }, ticks: { color: '#333', font: { size: 12, weight: '600' } } }
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

    function updateUserInfoUI(user) {
      if (!user) return;
      const sName = document.getElementById('sidebar-name');
      if (sName) sName.textContent = user.full_name || 'Admin';
      const sRole = document.getElementById('sidebar-role');
      if (sRole) sRole.textContent = user.role === 'admin' ? 'Administrator' : 'Cashier';
      const sAvatar = document.getElementById('sidebar-avatar');
      if (sAvatar) sAvatar.textContent = (user.full_name || 'U').charAt(0).toUpperCase();

      const topName = document.getElementById('topbar-user-name');
      if (topName) topName.textContent = user.full_name || 'Admin';
      const topAvatar = document.getElementById('topbar-user-avatar');
      if (topAvatar) topAvatar.textContent = (user.full_name || 'U').charAt(0).toUpperCase();
    }

    async function handleLogin(e) {
      e.preventDefault();
      const u = document.getElementById('login-user').value.trim();
      const p = document.getElementById('login-pass').value.trim();
      const errEl = document.getElementById('login-error');
      const succEl = document.getElementById('login-success');
      const btn = document.getElementById('pos-login-btn');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
      }

      try {
        const res = await api('auth.php?action=login', { username: u, password: p });

        if (res.requires_2fa) {
          // If super admin logged in from POS, redirect them to the superadmin panel where their 2FA is ready
          if (res.redirect && res.redirect.includes('superadmin')) {
            window.location.href = res.redirect;
            return;
          }
          // Admin 2FA in POS: show POS 2FA screen
          document.getElementById('pos-step-credentials').style.display = 'none';
          document.getElementById('pos-step-2fa').style.display = 'block';
          document.getElementById('pos-2fa-email').textContent = res.masked_email || 'Manager Email';
          const codeInput = document.getElementById('pos-2fa-code');
          codeInput.value = '';
          if (res.dev_code) {
            document.getElementById('pos-dev-hint').style.display = 'block';
            document.getElementById('pos-dev-code-val').textContent = res.dev_code;
            codeInput.value = res.dev_code;
          } else {
            document.getElementById('pos-dev-hint').style.display = 'none';
          }
          if (succEl) {
            succEl.textContent = res.message || 'Verification code sent to manager email.';
            succEl.style.display = 'block';
          }
          setTimeout(() => codeInput.focus(), 100);
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = 'Sign In';
          }
          return;
        }

        if (res.success) {
          if (res.session_id) {
            localStorage.setItem('chipipie_session_id', res.session_id);
          }
          // Super admin has a dedicated panel — redirect there
          if (res.redirect) {
            window.location.href = res.redirect;
            return;
          }
          currentUser = res.user;
          updateUserInfoUI(res.user);
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

      if (btn) {
        btn.disabled = false;
        btn.innerHTML = 'Sign In';
      }
    }

    async function handlePosVerify2FA(e) {
      e.preventDefault();
      const code = document.getElementById('pos-2fa-code').value.trim();
      const errEl = document.getElementById('login-error');
      const succEl = document.getElementById('login-success');
      const btn = document.getElementById('pos-verify-btn');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      if (!code) return;

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';

      try {
        const res = await api('auth.php?action=verify_2fa', { code: code });
        if (res.success && res.user) {
          if (res.session_id) {
            localStorage.setItem('chipipie_session_id', res.session_id);
          }
          currentUser = res.user;
          updateUserInfoUI(res.user);
          document.getElementById('pos-step-2fa').style.display = 'none';
          document.getElementById('pos-step-credentials').style.display = 'block';
          await loadMenuFromDB();
          activateApp(res.user.role === 'admin');
        } else {
          errEl.textContent = res.message || 'Invalid or expired verification code.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Verification error. Please try again.';
        errEl.style.display = 'block';
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-shield-check"></i> Verify & Sign In';
    }

    async function handlePosResend2FA() {
      const btn = document.getElementById('pos-resend-btn');
      const errEl = document.getElementById('login-error');
      const succEl = document.getElementById('login-success');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Resending...';

      try {
        const res = await api('auth.php?action=resend_2fa', {});
        if (res.success) {
          succEl.textContent = res.message || 'A new code has been sent to the manager email.';
          succEl.style.display = 'block';
          if (res.dev_code) {
            document.getElementById('pos-dev-hint').style.display = 'block';
            document.getElementById('pos-dev-code-val').textContent = res.dev_code;
            document.getElementById('pos-2fa-code').value = res.dev_code;
          }
        } else {
          errEl.textContent = res.message || 'Could not resend verification code.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Server error. Please try again.';
        errEl.style.display = 'block';
      }

      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Resend Code';
      }, 5000);
    }

    async function handlePosCancel2FA() {
      try {
        await api('auth.php?action=cancel_2fa', {});
      } catch (e) {}
      document.getElementById('pos-step-2fa').style.display = 'none';
      document.getElementById('pos-step-credentials').style.display = 'block';
      const errEl = document.getElementById('login-error');
      const succEl = document.getElementById('login-success');
      if (errEl) errEl.style.display = 'none';
      if (succEl) succEl.style.display = 'none';
      document.getElementById('login-pass').value = '';
    }

    /* ========== LOAD MENU FROM DB ========== */
    async function loadMenuFromDB() {
      try {
        const res = await api('menu.php');
        if (res.success) {
          menuMgmtTabsBuilt = false;
          pendingDeleteIds = new Set((res.pending_deletions || []).map(Number));
          posMenuCategories = res.categories || [];
          menuItems = res.items.map(i => {
            let sp = null;
            if (i.size_prices) {
              try { sp = typeof i.size_prices === 'string' ? JSON.parse(i.size_prices) : i.size_prices; } catch(e) {}
            }
            return {
              id: i.id, name: i.name, category: i.category,
              category_id: i.category_id,
              price: parseFloat(i.price), sizePrices: sp,
              emoji: i.emoji, image: i.image,
              available: !!parseInt(i.is_available),
              stock: parseInt(i.stock), minStock: parseInt(i.min_stock)
            };
          });
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
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'none';
        if (usersBtn) usersBtn.style.display = 'none';
        safeBuildCharts(); // Build charts so Dashboard view works for cashiers too
        showView('pos', document.getElementById('nav-pos-btn') || document.querySelectorAll('.nav-item-btn')[1]);
      } else {
        mgmtLabel.style.display = 'block';
        menuBtn.style.display = 'flex';
        reportsBtn.style.display = 'flex';
        if (usersBtn) usersBtn.style.display = 'flex';
        safeBuildCharts();
        showView('dashboard', document.getElementById('nav-dash-btn') || document.querySelectorAll('.nav-item-btn')[0]);
      }
      // Preload PayMongo key status (works for both admin and cashier)
      initGCashKey();
    }

    async function logout() {
      try { await api('auth.php?action=logout', {}); } catch (e) { }
      localStorage.removeItem('chipipie_session_id');
      currentUser = null;
      document.getElementById('login-user').value = '';
      document.getElementById('login-pass').value = '';
      const step2fa = document.getElementById('pos-step-2fa');
      const stepCreds = document.getElementById('pos-step-credentials');
      if (step2fa) step2fa.style.display = 'none';
      if (stepCreds) stepCreds.style.display = 'block';
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

    /* ========== PASSWORD COMPLEXITY HELPER ========== */
    function checkPasswordComplexity(p) {
      if (!p || p.length < 8) return 'Password must be at least 8 characters long.';
      if (!/[A-Z]/.test(p)) return 'Password must contain at least one uppercase letter (A-Z).';
      if (!/[a-z]/.test(p)) return 'Password must contain at least one lowercase letter (a-z).';
      if (!/[0-9]/.test(p)) return 'Password must contain at least one number (0-9).';
      return '';
    }

    /* ========== FORGOT PASSWORD (POS Login) ========== */
    /* ========== FORGOT PASSWORD (POS Login) ========== */
    function showPosForgotStep1() {
      document.getElementById('pos-step-credentials').style.display = 'none';
      document.getElementById('pos-step-2fa').style.display = 'none';
      document.getElementById('pos-step-forgot-reset').style.display = 'none';
      document.getElementById('pos-step-forgot-request').style.display = 'block';

      const formEl = document.getElementById('pos-forgot-form');
      const sentMsg = document.getElementById('pos-forgot-sent-msg');
      const instruction = document.getElementById('pos-forgot-instruction');
      if (formEl) formEl.style.display = 'block';
      if (sentMsg) sentMsg.style.display = 'none';
      if (instruction) instruction.style.display = 'block';

      const btn = document.getElementById('pos-forgot-send-btn');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Send Recovery Link';
      }

      const footer = document.getElementById('pos-login-footer');
      if (footer) footer.style.display = 'none';
      const errEl = document.getElementById('login-error');
      const sucEl = document.getElementById('login-success');
      if (errEl) errEl.style.display = 'none';
      if (sucEl) sucEl.style.display = 'none';
      setTimeout(() => {
        const inp = document.getElementById('pos-forgot-user');
        if (inp) {
          inp.value = '';
          inp.focus();
        }
      }, 100);
    }

    function hidePosForgot() {
      document.getElementById('pos-step-forgot-request').style.display = 'none';
      document.getElementById('pos-step-forgot-reset').style.display = 'none';
      document.getElementById('pos-step-credentials').style.display = 'block';

      const formEl = document.getElementById('pos-forgot-form');
      const sentMsg = document.getElementById('pos-forgot-sent-msg');
      const instruction = document.getElementById('pos-forgot-instruction');
      if (formEl) formEl.style.display = 'block';
      if (sentMsg) sentMsg.style.display = 'none';
      if (instruction) instruction.style.display = 'block';

      const footer = document.getElementById('pos-login-footer');
      if (footer) footer.style.display = 'block';
      const errEl = document.getElementById('login-error');
      const sucEl = document.getElementById('login-success');
      if (errEl) errEl.style.display = 'none';
      if (sucEl) sucEl.style.display = 'none';
    }

    async function handlePosSendForgotLink(e) {
      e.preventDefault();
      const errEl = document.getElementById('login-error');
      const sucEl = document.getElementById('login-success');
      if (errEl) errEl.style.display = 'none';
      if (sucEl) sucEl.style.display = 'none';

      const identifier = document.getElementById('pos-forgot-user').value.trim();
      if (!identifier) {
        errEl.textContent = 'Please enter your username or email.';
        errEl.style.display = 'block';
        return;
      }

      const btn = document.getElementById('pos-forgot-send-btn');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending reset link...';
      }

      try {
        const res = await api('auth.php?action=forgot_password', { identifier });
        // The form/button disappears and "The email has been sent" appears!
        const formEl = document.getElementById('pos-forgot-form');
        const sentMsg = document.getElementById('pos-forgot-sent-msg');
        const instruction = document.getElementById('pos-forgot-instruction');

        if (formEl) formEl.style.display = 'none';
        if (instruction) instruction.style.display = 'none';
        if (sentMsg) sentMsg.style.display = 'block';
      } catch (err) {
        errEl.textContent = 'Connection error. Please try again.';
        errEl.style.display = 'block';
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Send Recovery Link';
        }
      }
    }

    async function handlePosResetPasswordViaLink(e) {
      e.preventDefault();
      const errEl = document.getElementById('login-error');
      const sucEl = document.getElementById('login-success');
      if (errEl) errEl.style.display = 'none';
      if (sucEl) sucEl.style.display = 'none';

      const newPass     = document.getElementById('pos-reset-new-pass').value.trim();
      const confirmPass = document.getElementById('pos-reset-confirm-pass').value.trim();

      if (newPass !== confirmPass) {
        errEl.textContent = 'Passwords do not match.';
        errEl.style.display = 'block';
        return;
      }
      const pwdErr = checkPasswordComplexity(newPass);
      if (pwdErr) {
        errEl.textContent = pwdErr;
        errEl.style.display = 'block';
        return;
      }

      const urlParams = new URLSearchParams(window.location.search);
      const token = urlParams.get('reset_token') || '';
      if (!token) {
        errEl.textContent = 'Invalid or missing reset token. Please request a new link.';
        errEl.style.display = 'block';
        return;
      }

      const btn = document.getElementById('pos-reset-submit-btn');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving password...';
      }

      try {
        const res = await api('auth.php?action=reset_password_link', {
          token,
          new_password: newPass
        });
        if (res && res.success) {
          hidePosForgot();
          sucEl.innerHTML = '<div style="display:flex;align-items:center;gap:8px;"><i class="bi bi-check-circle-fill"></i> <span>Password reset successfully! You can now sign in.</span></div>';
          sucEl.style.display = 'block';
          document.getElementById('login-pass').value = '';
          // Remove token from URL cleanly
          history.replaceState({}, '', window.location.pathname);
        } else {
          errEl.textContent = (res && res.message) || 'Could not reset password.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Server error. Please try again.';
        errEl.style.display = 'block';
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Save New Password';
        }
      }
    }

    /* ========== USERNAME AUTO-SUGGEST FROM EMAIL ========== */
    // Tracks which username fields were auto-filled (so manual edits aren't overwritten)
    const _userAutoFilled = {};

    function suggestUsernameFromEmail(emailId, userId) {
      const emailEl    = document.getElementById(emailId);
      const userEl     = document.getElementById(userId);
      const hintId     = userId + '-hint';
      const hintEl     = document.getElementById(hintId);
      if (!emailEl || !userEl) return;

      const email      = emailEl.value || '';
      const atIdx      = email.indexOf('@');
      const localPart  = atIdx > 0 ? email.substring(0, atIdx) : email;
      // Sanitise: keep only letters, digits, underscores, dots, hyphens; lowercase
      const suggested  = localPart.toLowerCase().replace(/[^a-z0-9._-]/g, '');

      // Only auto-fill if the user hasn't manually edited the field
      if (!_userAutoFilled[userId] || userEl.dataset.autoSuggested === 'true') {
        userEl.value = suggested;
        userEl.dataset.autoSuggested = 'true';
        if (hintEl) hintEl.style.display = suggested ? 'inline' : 'none';
      }

      // Once user manually edits the username field, stop auto-filling
      userEl.addEventListener('input', function onUserInput() {
        userEl.dataset.autoSuggested = 'false';
        _userAutoFilled[userId] = true;
        if (hintEl) hintEl.style.display = 'none';
        userEl.removeEventListener('input', onUserInput);
      }, { once: true });
    }

    /* ========== REGISTER (Login page) ========== */
    function showRegisterForm() {
      document.getElementById('login-wrapper').style.display = 'none';
      document.getElementById('register-wrapper').style.display = 'flex';
    }
    function showLoginForm() {
      document.getElementById('register-wrapper').style.display = 'none';
      document.getElementById('login-wrapper').style.display = 'flex';
      hidePosForgot();
    }

    async function handleRegister(e) {
      e.preventDefault();
      const errEl = document.getElementById('reg-error');
      const sucEl = document.getElementById('reg-success');
      errEl.style.display = 'none'; sucEl.style.display = 'none';

      const firstName = document.getElementById('reg-first-name').value.trim();
      const middleInitial = document.getElementById('reg-middle-initial').value.trim();
      const lastName = document.getElementById('reg-last-name').value.trim();
      const suffix = document.getElementById('reg-suffix').value.trim();

      const parts = [firstName, middleInitial ? middleInitial.replace(/\.*$/, '') + '.' : '', lastName, suffix].filter(Boolean);
      const fullName = parts.join(' ');

      const password = document.getElementById('reg-pass').value.trim();
      const confirmPass = document.getElementById('reg-pass-confirm').value.trim();

      if (!firstName || !lastName) {
        errEl.textContent = 'First name and last name are required.';
        errEl.style.display = 'block'; return;
      }
      if (password !== confirmPass) {
        errEl.textContent = 'Passwords do not match.';
        errEl.style.display = 'block'; return;
      }
      const pwdErr = checkPasswordComplexity(password);
      if (pwdErr) {
        errEl.textContent = pwdErr;
        errEl.style.display = 'block'; return;
      }

      const data = {
        first_name: firstName,
        middle_initial: middleInitial,
        last_name: lastName,
        suffix: suffix,
        full_name: fullName,
        email: document.getElementById('reg-email').value.trim(),
        username: document.getElementById('reg-user').value.trim(),
        password: password,
        role: document.getElementById('reg-role').value
      };

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
        const visibleUsers = res.users.filter(u => u.role !== 'super_admin');
        document.getElementById('users-tbody').innerHTML = visibleUsers.map(u => `
          <tr>
            <td style="font-weight:500;">${u.full_name}</td>
            <td><code>${u.username}</code></td>
            <td style="font-size:12px;color:#666;">${u.email || '—'}</td>
            <td><span class="badge-cat">${u.role}</span></td>
            <td><span class="${u.is_active == 1 ? 'badge-success' : 'badge-pending'}">${u.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
            <td>
              <div style="display:flex;gap:6px;align-items:center;">
                <button class="btn btn-sm" style="background:var(--cafe-tan);color:var(--cafe-brown);border:none;border-radius:6px;padding:4px 10px;font-size:12px;line-height:1.4;min-width:110px;text-align:center;" onclick="requestUserToggle(${u.id}, '${u.full_name}', ${u.is_active})">${u.is_active == 1 ? 'Req Deactivate' : 'Req Activate'}</button>
                <button class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;border:none;border-radius:6px;padding:4px 10px;font-size:12px;line-height:1.4;min-width:95px;text-align:center;" onclick="requestUserDelete(${u.id}, '${u.full_name}')"><i class="bi bi-trash-fill"></i> Req Delete</button>
              </div>
            </td>
          </tr>
        `).join('') || '<tr><td colspan="6" class="text-center text-muted py-3">No users found</td></tr>';

        document.getElementById('pending-tbody').innerHTML = res.pending_admin_requests.map(r => {
          const safeName = (r.full_name || '').replace(/'/g, "\\'");
          return `
          <tr>
            <td style="font-weight:500;">${r.full_name}</td>
            <td><code>${r.username}</code></td>
            <td style="font-size:12px;color:#666;">${r.email}</td>
            <td><span class="badge-${r.status === 'approved' ? 'success' : (r.status === 'pending' ? 'pending' : 'danger')}">${r.status}</span></td>
            <td style="font-size:12px;color:#888;">${r.created_at}</td>
            <td>
              <div style="display:flex;gap:6px;align-items:center;">
                ${r.status === 'pending' ? `
                  <button class="btn btn-sm" style="background:var(--cafe-brown);color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:12px;" onclick="resendApprovalEmail(${r.id})" title="Re-send the approval link to the owner's Gmail">
                    <i class="bi bi-envelope"></i> Resend to Owner
                  </button>
                ` : ''}
                <button class="btn btn-sm" style="background:#fee2e2;color:#b91c1c;border:none;border-radius:6px;padding:4px 10px;font-size:12px;line-height:1.4;" onclick="deleteAdminRequestPos(${r.id}, '${safeName}')" title="Remove request">
                  <i class="bi bi-trash-fill"></i> Remove
                </button>
              </div>
            </td>
          </tr>
        `;
        }).join('') || '<tr><td colspan="6" class="text-center text-muted py-3">No pending requests</td></tr>';
      } catch (e) { console.error(e); }
    }

    async function deleteAdminRequestPos(requestId, name) {
      if (!confirm(`Are you sure you want to remove the admin approval request for "${name}"?`)) return;
      try {
        const res = await api('auth.php?action=delete_admin_request', { request_id: requestId });
        showToast(res.success ? '🗑 ' + res.message : '⚠ ' + res.message);
        if (res.success) renderUsers();
      } catch (e) {
        showToast('⚠ Connection error');
      }
    }

    let currentResendRequestId = null;
    function resendApprovalEmail(requestId) {
      currentResendRequestId = requestId;
      new bootstrap.Modal(document.getElementById('resendApprovalModal')).show();
    }

    async function submitResendApproval() {
      if (!currentResendRequestId) return;
      const btn = document.getElementById('resend-confirm-btn');
      const originalText = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>...';
      btn.disabled = true;

      try {
        const res = await api('auth.php?action=resend_approval', { request_id: currentResendRequestId });
        showToast(res.success ? '📧 ' + res.message : '⚠ ' + res.message);
        bootstrap.Modal.getInstance(document.getElementById('resendApprovalModal')).hide();
      } catch (e) {
        showToast('⚠ Connection error');
      } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        currentResendRequestId = null;
      }
    }

    async function toggleUser(userId) {
      const res = await api('auth.php?action=toggle_user', { user_id: userId });
      showToast(res.message);
      renderUsers();
    }

    let currentToggleRequest = null;

    function requestUserToggle(userId, fullName, isActive) {
      const isDeactivating = isActive == 1;
      const actionName = isDeactivating ? 'Deactivate' : 'Activate';
      document.getElementById('rtm-title').textContent = `Request to ${actionName} Account`;
      document.getElementById('rtm-msg').innerHTML = `Submit a request to <strong>${actionName.toLowerCase()}</strong> the account of <strong>${fullName}</strong>?`;
      document.getElementById('rtm-confirm-btn').style.background = 'var(--cafe-brown)';
      currentToggleRequest = {
        type: isDeactivating ? 'user_deactivate' : 'user_activate',
        title: `Request to ${actionName} User: ${fullName}`,
        description: `An admin has requested to ${actionName.toLowerCase()} the account for ${fullName}.`,
        payload: { user_id: userId, name: fullName }
      };
      new bootstrap.Modal(document.getElementById('requestToggleModal')).show();
    }

    function requestUserDelete(userId, fullName) {
      document.getElementById('rtm-title').textContent = 'Request to Delete Account';
      document.getElementById('rtm-msg').innerHTML = `Submit a request to permanently <strong>delete</strong> the account of <strong>${fullName}</strong>?<br><span style="font-size:11px;color:#b91c1c;">This action cannot be undone once approved by the Super Admin.</span>`;
      document.getElementById('rtm-confirm-btn').style.background = '#b91c1c';
      currentToggleRequest = {
        type: 'user_delete',
        title: `Request to Delete User: ${fullName}`,
        description: `An admin has requested to permanently delete the account for ${fullName}.`,
        payload: { user_id: userId, name: fullName }
      };
      new bootstrap.Modal(document.getElementById('requestToggleModal')).show();
    }

    async function submitUserToggleRequest() {
      if (!currentToggleRequest) return;
      const req = currentToggleRequest;
      const btn = document.getElementById('rtm-confirm-btn');
      const originalText = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
      btn.disabled = true;

      try {
        const res = await fetch('api/superadmin.php?action=submit_request', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(req)
        });
        const data = await res.json();
        showToast(data.success ? '✅ ' + data.message : '⚠ ' + data.message);
        bootstrap.Modal.getInstance(document.getElementById('requestToggleModal')).hide();
      } catch (e) {
        showToast('⚠ Connection error');
      } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
        currentToggleRequest = null;
      }
    }

    function showRegisterUserModal() {
      document.getElementById('modal-reg-result').style.display = 'none';
      new bootstrap.Modal(document.getElementById('registerUserModal')).show();
    }

    async function submitRegisterUser() {
      const resEl = document.getElementById('modal-reg-result');
      resEl.style.display = 'none';

      const firstName = document.getElementById('modal-reg-first-name').value.trim();
      const middleInitial = document.getElementById('modal-reg-middle-initial').value.trim();
      const lastName = document.getElementById('modal-reg-last-name').value.trim();
      const suffix = document.getElementById('modal-reg-suffix').value.trim();

      const parts = [firstName, middleInitial ? middleInitial.replace(/\.*$/, '') + '.' : '', lastName, suffix].filter(Boolean);
      const fullName = parts.join(' ');

      const email = document.getElementById('modal-reg-email').value.trim();
      const username = document.getElementById('modal-reg-user').value.trim();
      const password = document.getElementById('modal-reg-pass').value.trim();
      const confirmPass = document.getElementById('modal-reg-pass-confirm').value.trim();
      const role = document.getElementById('modal-reg-role').value;

      if (!firstName || !lastName || !email || !username || !password) {
        resEl.style.display = 'block'; resEl.style.color = '#842029'; resEl.style.background = '#f8d7da';
        resEl.textContent = 'First name, last name, email, username and password are required.'; return;
      }
      if (password !== confirmPass) {
        resEl.style.display = 'block'; resEl.style.color = '#842029'; resEl.style.background = '#f8d7da';
        resEl.textContent = 'Passwords do not match.'; return;
      }
      const pwdErr = checkPasswordComplexity(password);
      if (pwdErr) {
        resEl.style.display = 'block'; resEl.style.color = '#842029'; resEl.style.background = '#f8d7da';
        resEl.textContent = pwdErr; return;
      }

      const data = {
        first_name: firstName,
        middle_initial: middleInitial,
        last_name: lastName,
        suffix: suffix,
        full_name: fullName,
        email, username, password, role
      };

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

    /* ========== MENU ITEM REMOVAL REQUEST ========== */
    let pendingDeleteItemId = null;
    let pendingCancelItemId = null;

    function openCancelRemovalModal(id) {
      const item = menuItems.find(m => m.id == id);
      if (!item) {
        showToast('⚠ Item not found.');
        return;
      }

      pendingCancelItemId = id;

      document.getElementById('cancel-modal-item-name').textContent = item.name;
      document.getElementById('cancel-modal-item-cat').textContent = item.category || 'Category';
      document.getElementById('cancel-modal-item-price').textContent = '₱' + parseFloat(item.price || 0).toFixed(2);
      document.getElementById('cancel-modal-error').style.display = 'none';

      const imgWrap = document.getElementById('cancel-modal-img-wrap');
      if (item.image) {
        imgWrap.innerHTML = `<img src="${item.image}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
      } else {
        imgWrap.innerHTML = item.emoji || '☕';
      }

      const modalEl = document.getElementById('cancelRemovalModal');
      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
      bsModal.show();
    }

    function deleteMenuItem(id) {
      const item = menuItems.find(m => m.id == id);
      if (!item) {
        showToast('⚠ Item not found.');
        return;
      }

      if (pendingDeleteIds.has(id)) {
        openCancelRemovalModal(id);
        return;
      }

      pendingDeleteItemId = id;

      document.getElementById('del-modal-item-name').textContent = item.name;
      document.getElementById('del-modal-item-cat').textContent = item.category || 'Category';
      document.getElementById('del-modal-item-price').textContent = '₱' + parseFloat(item.price || 0).toFixed(2);
      document.getElementById('del-modal-item-stock').textContent = (item.stock || 0) + ' in stock';
      document.getElementById('del-modal-reason').value = '';
      document.getElementById('del-modal-error').style.display = 'none';

      const reqName = (currentUser && (currentUser.full_name || currentUser.username)) ? (currentUser.full_name || currentUser.username) : 'Staff';
      const reqEl = document.getElementById('del-modal-requester');
      if (reqEl) reqEl.textContent = reqName;

      const imgWrap = document.getElementById('del-modal-img-wrap');
      if (item.image) {
        imgWrap.innerHTML = `<img src="${item.image}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
      } else {
        imgWrap.innerHTML = item.emoji || '☕';
      }

      const modalEl = document.getElementById('deleteItemModal');
      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
      bsModal.show();
    }

    async function submitRemoveMenuItem() {
      if (!pendingDeleteItemId) return;
      const item = menuItems.find(m => m.id == pendingDeleteItemId);
      const name = item ? item.name : 'this item';
      const reason = document.getElementById('del-modal-reason').value.trim() || 'Requested from POS';
      const errEl = document.getElementById('del-modal-error');
      const btn = document.getElementById('del-modal-confirm-btn');

      errEl.style.display = 'none';
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';

      try {
        const res = await api('menu.php?action=request_delete', { id: pendingDeleteItemId, reason });
        if (res.success) {
          pendingDeleteIds.add(pendingDeleteItemId);
          renderMenuTable();
          bootstrap.Modal.getInstance(document.getElementById('deleteItemModal')).hide();
          showToast(`✓ Removal request for "${name}" sent for admin approval!`);
        } else {
          errEl.textContent = res.message || 'Failed to submit removal request.';
          errEl.style.display = 'block';
          if (res.already_pending) {
            pendingDeleteIds.add(pendingDeleteItemId);
            renderMenuTable();
          }
        }
      } catch (err) {
        errEl.textContent = 'Connection error. Please try again.';
        errEl.style.display = 'block';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Submit Removal Request';
      }
    }

    async function submitCancelPendingRemoval() {
      if (!pendingCancelItemId) return;
      const item = menuItems.find(m => m.id == pendingCancelItemId);
      const name = item ? item.name : 'this item';
      const errEl = document.getElementById('cancel-modal-error');
      const btn = document.getElementById('cancel-modal-confirm-btn');

      errEl.style.display = 'none';
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Cancelling...';

      try {
        const res = await api('menu.php?action=cancel_delete', { id: pendingCancelItemId });
        if (res.success) {
          pendingDeleteIds.delete(Number(pendingCancelItemId));
          renderMenuTable();
          const modalInst = bootstrap.Modal.getInstance(document.getElementById('cancelRemovalModal'));
          if (modalInst) modalInst.hide();
          showToast(`✓ Pending removal for "${name}" has been cancelled.`);
          pendingCancelItemId = null;
        } else {
          errEl.textContent = res.message || 'Failed to cancel removal request.';
          errEl.style.display = 'block';
        }
      } catch (err) {
        errEl.textContent = 'Connection error while cancelling removal request.';
        errEl.style.display = 'block';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Yes, Cancel Request';
      }
    }

    /* ========== PREVENT DOUBLE-TAP & GESTURE ZOOM ON MOBILE/TABLET ========== */
    (function initZoomPrevention() {
      // 1. Prevent native double-click / double-tap zooming
      document.addEventListener('dblclick', function(e) {
        e.preventDefault();
      }, { passive: false });

      // 2. Prevent double-tap zoom on non-interactive areas in iOS Safari / Android Chrome
      let lastTouchEnd = 0;
      document.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
          // Allow normal rapid clicks on buttons, inputs, tabs, and interactive cards
          const isInteractive = e.target.closest('button, input, select, textarea, a, .product-card, .qty-btn, .cat-pill, .nav-item-btn, [onclick], [role="button"]');
          if (!isInteractive) {
            e.preventDefault();
          }
        }
        lastTouchEnd = now;
      }, { passive: false });

      // 3. Prevent pinch-to-zoom / multi-finger gesture zoom on iOS Safari
      document.addEventListener('gesturestart', function(e) { e.preventDefault(); }, { passive: false });
      document.addEventListener('gesturechange', function(e) { e.preventDefault(); }, { passive: false });
      document.addEventListener('gestureend', function(e) { e.preventDefault(); }, { passive: false });

      // 4. Prevent pinch-to-zoom on multi-touch Android Chrome
      document.addEventListener('touchmove', function(e) {
        if (e.touches && e.touches.length > 1) {
          e.preventDefault();
        }
      }, { passive: false });
    })();

    /* ========== INIT ========== */
    window.addEventListener('DOMContentLoaded', () => {
      const sNav = document.querySelector('.sidebar-nav');
      if (sNav) {
        sNav.scrollLeft = 0;
        sNav.addEventListener('scroll', () => { sNav.scrollLeft = 0; }, { passive: true });
      }

      // Check for password reset token in URL (?reset_token=...)
      const urlParams = new URLSearchParams(window.location.search);
      const resetToken = urlParams.get('reset_token');
      if (resetToken) {
        document.getElementById('pos-step-credentials').style.display = 'none';
        document.getElementById('pos-step-2fa').style.display = 'none';
        document.getElementById('pos-step-forgot-request').style.display = 'none';
        document.getElementById('pos-step-forgot-reset').style.display = 'block';
        const footer = document.getElementById('pos-login-footer');
        if (footer) footer.style.display = 'none';
        const pInput = document.getElementById('pos-reset-new-pass');
        if (pInput) pInput.focus();
        return;
      }

      // Check for existing session
      api('auth.php?action=session').then(res => {
        if (res.success && res.loggedIn) {
          if (res.session_id) {
            localStorage.setItem('chipipie_session_id', res.session_id);
          }
          // Super admin should use their dedicated panel
          if (res.user.role === 'super_admin') {
            window.location.href = 'superadmin-panel.php';
            return;
          }
          currentUser = res.user;
          updateUserInfoUI(res.user);
          loadMenuFromDB().then(() => activateApp(res.user.role === 'admin'));
        } else if (res.pending_2fa && res.role === 'admin') {
          // Resume pending 2FA step if page was reloaded
          document.getElementById('pos-step-credentials').style.display = 'none';
          document.getElementById('pos-step-2fa').style.display = 'block';
          document.getElementById('pos-2fa-email').textContent = res.masked_email || 'Manager Email';
          const codeInput = document.getElementById('pos-2fa-code');
          if (res.dev_code) {
            document.getElementById('pos-dev-hint').style.display = 'block';
            document.getElementById('pos-dev-code-val').textContent = res.dev_code;
            codeInput.value = res.dev_code;
          }
        }
      }).catch(() => { });
    });
  </script>
</body>

</html>
