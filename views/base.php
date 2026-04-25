<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= htmlspecialchars(baseUrl(), ENT_QUOTES) ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>PhòngTrọ</title>
    <link rel="manifest" href="<?= htmlspecialchars(assetUrl('manifest.webmanifest?v=1'), ENT_QUOTES) ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= htmlspecialchars(assetUrl('favicon.png?v=2'), ENT_QUOTES) ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars(assetUrl('favicon.png?v=2'), ENT_QUOTES) ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars(assetUrl('favicon.png?v=2'), ENT_QUOTES) ?>">
    <meta name="theme-color" content="#d97706">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <?php
      $bgPattern = themeBackground();
      $bgOpacityValue = themeBackgroundOpacity();
      $bgOpacity = number_format($bgOpacityValue, 3, '.', '');
      $mobileBgOpacity = number_format(min($bgOpacityValue * 0.78, 0.110), 3, '.', '');
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap&subset=latin,latin-ext,vietnamese');
        html { overflow-x: hidden; color-scheme: light; }
        :root {
            --bg: #fefaf0;
            --card: #ffffff;
            --primary: #d97706; /* vàng cam Thanh Hóa */
            --primary-dark: #b45309;
            --accent: #f59e0b;
            --text: #0f172a;
            --muted: #6b7280;
            --radius: 16px;
            --gap: 16px;
            --shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
            --chat-bubble-width: 320px;
            --chat-bubble-min-height: 68px;
            --chat-scroll-thumb: rgba(217, 119, 6, 0.42);
            --chat-scroll-thumb-hover: rgba(180, 83, 9, 0.68);
            --chat-scroll-track: rgba(255, 247, 237, 0.92);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: "Manrope", "Segoe UI", system-ui, -apple-system, sans-serif;
            font-weight: 500;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
            background:
              radial-gradient(120% 140% at 10% 15%, rgba(217,119,6,0.06), transparent 42%),
              radial-gradient(110% 140% at 90% 20%, rgba(245,158,11,0.06), transparent 50%),
              #fefaf0;
            color: var(--text);
            display: flex;
            flex-direction: column;
            position: relative;
            isolation: isolate;
            overflow-x: hidden;
        }
        p, a, li, button, input, textarea, select { font-family: inherit; }
        button, input, textarea, select { font-weight: 600; }
        .chip, .nav-link, .btn, .badge { font-weight: 700; }
        body::before, body::after {
            content: "";
            position: fixed;
            pointer-events: none;
            z-index: 0;
            will-change: transform;
        }
        :root {
            --dong-size: clamp(1100px, 80vw, 1400px);
            /* đặt tâm đúng tại góc màn hình (tâm trùng góc), 2 góc cân nhau */
            --dong-offset: calc(var(--dong-size) * -0.5);
            --dong-offset-top: calc(var(--dong-size) * -0.45); /* kéo hoa văn góc trên xuống thêm một chút */
        }
        /* hai góc chỉ 1/4 trống đồng */
        body::before {
            width: var(--dong-size);
            height: var(--dong-size);
            left: var(--dong-offset);
            top: var(--dong-offset-top);
            background: url('<?= htmlspecialchars($bgPattern) ?>') center/contain no-repeat;
            opacity: <?= $bgOpacity ?>;
            mix-blend-mode: normal;
            animation: dong-spin-reverse 110s linear infinite;
            animation-delay: var(--dong-delay-before, 0s);
            transform-origin: center center;
        }
        body::after  {
            width: var(--dong-size);
            height: var(--dong-size);
            right: var(--dong-offset);
            bottom: var(--dong-offset);
            background: url('<?= htmlspecialchars($bgPattern) ?>') center/contain no-repeat;
            opacity: <?= $bgOpacity ?>;
            mix-blend-mode: normal;
            animation: dong-spin-reverse 130s linear infinite;
            animation-delay: var(--dong-delay-after, 0s);
            transform-origin: center center;
        }
        @keyframes dong-spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes dong-spin-reverse {
            from { transform: rotate(0deg); }
            to   { transform: rotate(-360deg); }
        }
        h1, h2, h3, h4 { margin: 0 0 10px 0; }
        p { margin-top: 0; }
        a { color: inherit; text-decoration: none; }
        .container {
            width: min(1240px, 92vw);
            max-width: 100%;
            margin: 0 auto;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            color: #0f172a;
            color-scheme: only light;
            forced-color-adjust: none;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            padding: 14px 0;
        }
        .navbar :is(a, span, small, input, button) {
            -webkit-text-fill-color: currentColor;
        }
        .navbar-inner {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 19px;
            letter-spacing: -0.2px;
        }
        .brand img { height: 38px; width: auto; display: block; image-rendering: -webkit-optimize-contrast; border-radius: 10px; }
        .brand small { display:block; color: var(--muted); font-size: 12px; line-height: 1.2; }
        .navbar .brand,
        .navbar .brand .brand-text > div {
            color: #431407;
            -webkit-text-fill-color: #431407;
            font-weight: 900;
            opacity: 1;
        }
        .navbar .brand small {
            color: #7c2d12;
            -webkit-text-fill-color: #7c2d12;
            font-weight: 800;
            opacity: 1;
        }
        .nav-links {
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:nowrap;
            min-width: 0;
            overflow: visible;
        }
        .nav-link {
            padding: 10px 12px;
            border-radius: 12px;
            font-weight: 800;
            color: #431407;
            -webkit-text-fill-color: #431407;
            border: 1px solid transparent;
            transition: all .15s ease;
            white-space: nowrap;
        }
        .nav-link:hover { color: #431407; -webkit-text-fill-color: #431407; background: #fff7ed; border-color: #fcd34d; }
        .nav-link.active { color: #fff7ed; -webkit-text-fill-color: #fff7ed; background: #7c2d12; border-color: #92400e; }
        .desktop-nav-menu {
            position: relative;
            flex: 0 0 auto;
        }
        .desktop-nav-toggle::after {
            content: "▾";
            display: inline-flex;
            margin-left: 6px;
            font-size: 11px;
            line-height: 1;
        }
        .desktop-nav-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 220px;
            max-width: min(300px, 60vw);
            display: grid;
            gap: 6px;
            padding: 8px;
            border-radius: 14px;
            border: 1px solid #fde68a;
            background: #fff;
            box-shadow: 0 20px 42px rgba(15, 23, 42, 0.18);
            z-index: 1100;
        }
        .desktop-nav-panel[hidden] { display: none !important; }
        .desktop-nav-item {
            display: flex;
            align-items: center;
            min-height: 38px;
            padding: 8px 10px;
            border-radius: 10px;
            color: #431407;
            -webkit-text-fill-color: #431407;
            border: 1px solid transparent;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease;
        }
        .desktop-nav-item:hover {
            background: #fff7ed;
            border-color: #fcd34d;
        }
        .desktop-nav-item.is-active {
            background: #7c2d12;
            color: #fff7ed;
            -webkit-text-fill-color: #fff7ed;
            border-color: #92400e;
        }
        .nav-search-form {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff7ed;
            color: #431407;
            -webkit-text-fill-color: #431407;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 12px;
            border: 1px solid #fcd34d;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7), 0 6px 14px rgba(217,119,6,0.10);
            text-decoration: none;
            min-height: 32px;
        }
        .nav-search-form .icon {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .nav-search-form input[type="text"] {
            border: none;
            background: transparent;
            font: inherit;
            color: inherit;
            width: 160px;
            outline: none;
            padding: 0;
        }
        .nav-search-form input::placeholder { color: #7c2d12; -webkit-text-fill-color: #7c2d12; opacity: 1; }
        .nav-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        .mode-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 40px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(124, 45, 18, 0.38);
            background: rgba(255, 247, 237, 0.2);
            color: #431407;
            -webkit-text-fill-color: #431407;
            font-weight: 800;
            font-size: 12px;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .mode-toggle:hover {
            background: rgba(255, 247, 237, 0.34);
            border-color: rgba(124, 45, 18, 0.58);
        }
        .mode-toggle-label {
            letter-spacing: 0.01em;
        }
        .mode-toggle-track {
            width: 34px;
            height: 20px;
            border-radius: 999px;
            position: relative;
            background: #cbd5e1;
            transition: background .18s ease;
            flex: 0 0 auto;
        }
        .mode-toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 1px 4px rgba(15,23,42,0.28);
            transition: transform .18s ease;
        }
        .mode-toggle.is-on .mode-toggle-track {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }
        .mode-toggle.is-on .mode-toggle-thumb {
            transform: translateX(14px);
        }
        .mode-toggle-state {
            min-width: 24px;
            text-align: right;
        }
        .mobile-mode-toggle {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          min-width: 44px;
          min-height: 40px;
          padding: 0 10px;
          border-radius: 12px;
          border: 1px solid #f3cc79;
          background: rgba(255, 247, 237, 0.9);
          color: #7c2d12;
          font-size: 11px;
          font-weight: 900;
          text-transform: uppercase;
          letter-spacing: 0.02em;
          box-shadow: 0 8px 18px rgba(217,119,6,0.14);
          text-decoration: none;
          flex: 0 0 auto;
        }
        .mobile-mode-toggle.is-on {
          border-color: #84cc16;
          background: linear-gradient(135deg, #dcfce7, #bbf7d0);
          color: #166534;
        }
        .navbar .text-muted {
            color: #431407;
            -webkit-text-fill-color: #431407;
            font-weight: 800;
            opacity: 1;
        }
        .navbar .nav-actions .btn-outline {
            color: #431407;
            -webkit-text-fill-color: #431407;
            border-color: rgba(124, 45, 18, 0.46);
            background: rgba(255, 247, 237, 0.12);
        }
        .navbar .nav-actions .btn-outline:hover {
            color: #431407;
            -webkit-text-fill-color: #431407;
            background: rgba(255, 247, 237, 0.28);
            border-color: rgba(124, 45, 18, 0.68);
        }
        .notify-desktop {
            position: relative;
            flex: 0 0 auto;
        }
        .notify-desktop-btn {
            position: relative;
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 999px;
        }
        .notify-desktop-btn svg {
            width: 18px;
            height: 18px;
        }
        .notify-desktop-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            min-width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #ef4444;
            border: 2px solid #fff7ed;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.16);
        }
        .notify-desktop-dot.hidden {
            display: none;
        }
        .notify-desktop-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: min(420px, 88vw);
            background: #fff;
            border: 1px solid #fde68a;
            border-radius: 16px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            z-index: 1200;
        }
        .notify-desktop-menu[hidden] { display: none !important; }
        .notify-desktop-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 14px 12px;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(180deg, #fffaf0, #ffffff);
        }
        .notify-desktop-head h3 {
            margin: 0;
            font-size: 16px;
            color: #7c2d12;
        }
        .notify-desktop-head p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #6b7280;
        }
        .notify-desktop-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .notify-desktop-list {
            max-height: 420px;
            overflow: auto;
            padding: 8px;
        }
        .notify-desktop-empty {
            padding: 22px 16px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
        }
        .notify-desktop-item {
            display: flex;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid #f1f5f9;
            background: #fff;
        }
        .notify-desktop-item + .notify-desktop-item {
            margin-top: 8px;
        }
        .notify-desktop-item.is-unread {
            background: linear-gradient(90deg, #fff7ed, #ffffff);
            border-color: #fdba74;
        }
        .notify-desktop-item-main {
            min-width: 0;
            flex: 1 1 auto;
            color: inherit;
            text-decoration: none;
        }
        .notify-desktop-item-main:hover .notify-desktop-item-title {
            color: #b45309;
        }
        .notify-desktop-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }
        .notify-desktop-item-title-wrap {
            display: flex;
            gap: 8px;
            align-items: center;
            min-width: 0;
        }
        .notify-desktop-item-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.14);
            flex: 0 0 auto;
        }
        .notify-desktop-item-title {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: #7c2d12;
            line-height: 1.35;
        }
        .notify-desktop-read-state {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }
        .notify-desktop-item.is-unread .notify-desktop-read-state {
            border-color: #fdba74;
            background: #fff7ed;
            color: #c2410c;
        }
        .notify-desktop-meta {
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
            color: #64748b;
            font-size: 12px;
        }
        .notify-desktop-item-actions {
            display: flex;
            align-items: center;
        }
        .notify-desktop-item-actions .btn {
            min-height: 34px;
            white-space: nowrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            font-weight: 600;
            gap: 6px;
            transition: all .15s ease;
        }
        .btn-primary { background: linear-gradient(135deg, #fbbf24, #d97706); color: #fff; border-color: #d97706; box-shadow: 0 12px 26px rgba(217,119,6,0.3); }
        .btn-primary:hover { background: linear-gradient(135deg, #d97706, #b45309); }
        .btn-success { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 10px 24px rgba(217,119,6,0.25); }
        .btn-success:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .btn-danger { background: #e11d48; color: #fff; border-color: #e11d48; }
        .btn-danger:hover { background: #c8103d; }
        .btn-ghost { background: #f0f4ff; color: var(--primary); border-color: #d7e2ff; }
        .btn-outline { background: transparent; color: var(--text); border-color: #d0d4dc; }
        .btn-outline-secondary { background: transparent; color: #374151; border-color: #cbd0da; }
        .btn-outline-secondary:hover { background: #f1f3f9; }
        .btn-sm { padding: 8px 12px; font-size: 13px; }
        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #fff;
            padding: 28px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.2), transparent 45%), radial-gradient(circle at 80% 0%, rgba(255,255,255,0.18), transparent 40%);
            pointer-events: none;
        }
        .d-flex { display: flex; }
        .flex-column { flex-direction: column; }
        .flex-grow-1 { flex: 1 1 auto; }
        .d-grid { display: grid; }
        .align-items-center { align-items: center; }
        .align-items-end { align-items: flex-end; }
        .justify-content-center { justify-content: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .g-3 { gap: 12px; }
        .g-4 { gap: 18px; }
        .g-2 { gap: 8px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 6px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-3 { margin-bottom: 14px; }
        .mb-4 { margin-bottom: 18px; }
        .mt-3 { margin-top: 14px; }
        .w-100 { width: 100%; }
        .small { font-size: 13px; }
        .h-100 { height: 100%; }
        .d-inline { display: inline; }
        .justify-between { justify-content: space-between; }
        .row { display: flex; flex-wrap: wrap; gap: var(--gap); align-items: flex-end; }
        .col-md-4 { flex: 1 1 calc(33.333% - var(--gap)); min-width: 240px; }
        .col-md-5 { flex: 1 1 calc(45% - var(--gap)); min-width: 260px; }
        .col-md-6 { flex: 1 1 calc(50% - var(--gap)); min-width: 280px; }
        .col-md-7 { flex: 1 1 calc(55% - var(--gap)); min-width: 320px; }
        .col-md-3 { flex: 1 1 calc(25% - var(--gap)); min-width: 200px; }
        .col-md-12 { flex: 1 1 100%; }
        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.7);
            min-width: 0;
            max-width: 100%;
        }
        .shadow-sm { box-shadow: var(--shadow); }
        .card-body { padding: 16px; min-width: 0; }
        .card img { width: 100%; display: block; }
        .card-room img { height: 180px; object-fit: cover; }
        .card-link { display: block; color: inherit; text-decoration: none; height: 100%; }
        .card-link:hover { transform: translateY(-2px); transition: transform .15s ease; }
        .text-muted { color: var(--muted); }
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin: 12px 0;
            font-weight: 600;
        }
        .alert-success { background: #e9f7ef; color: #1e7e34; border: 1px solid #c9e9d3; }
        .alert-danger { background: #fdeeee; color: #c53030; border: 1px solid #f5c2c7; }
        .alert-info { background: #fff7ed; color: #7c2d12; border: 1px solid #fdba74; box-shadow: 0 10px 24px rgba(217,119,6,0.10); }
        .chat-thread,
        .user-chat-thread,
        .chat-messages,
        #chatListItems {
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: var(--chat-scroll-thumb) var(--chat-scroll-track);
        }
        .chat-thread::-webkit-scrollbar,
        .user-chat-thread::-webkit-scrollbar,
        .chat-messages::-webkit-scrollbar,
        #chatListItems::-webkit-scrollbar {
            width: 10px;
        }
        .chat-thread::-webkit-scrollbar-track,
        .user-chat-thread::-webkit-scrollbar-track,
        .chat-messages::-webkit-scrollbar-track,
        #chatListItems::-webkit-scrollbar-track {
            background: var(--chat-scroll-track);
            border-radius: 999px;
        }
        .chat-thread::-webkit-scrollbar-thumb,
        .user-chat-thread::-webkit-scrollbar-thumb,
        .chat-messages::-webkit-scrollbar-thumb,
        #chatListItems::-webkit-scrollbar-thumb {
            background: var(--chat-scroll-thumb);
            border-radius: 999px;
            border: 2px solid var(--chat-scroll-track);
        }
        .chat-thread::-webkit-scrollbar-thumb:hover,
        .user-chat-thread::-webkit-scrollbar-thumb:hover,
        .chat-messages::-webkit-scrollbar-thumb:hover,
        #chatListItems::-webkit-scrollbar-thumb:hover {
            background: var(--chat-scroll-thumb-hover);
        }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; }
        .form-control, select, input[type=number], input[type=tel], input[type=text], input[type=password] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d8dce6;
            background: #fff;
            outline: none;
            transition: border .15s ease, box-shadow .15s ease;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(78,107,255,0.18); }
        .form-check { display: flex; align-items: center; gap: 8px; }
        .table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .table th, .table td { padding: 10px 8px; border-bottom: 1px solid #eaedf3; text-align: left; }
        .align-middle td, .align-middle th { vertical-align: middle; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-success { background: #e9f7ef; color: #1e7e34; }
        .badge-warning { background: #fff4db; color: #8a5d00; }
        .table-responsive { overflow-x: auto; width: 100%; max-width: 100%; }
        .file-picker-input-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        .file-picker {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }
        .file-picker-main {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            cursor: pointer;
        }
        .file-picker-trigger {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            border: 1px dashed #d97706;
            background: #fff7ed;
            color: #b45309;
            font-size: 26px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
            flex: 0 0 46px;
            transition: all .15s ease;
            overflow: hidden;
        }
        .file-picker-trigger:hover {
            background: #ffedd5;
            border-color: #b45309;
            transform: translateY(-1px);
        }
        .file-picker-trigger.has-preview {
            padding: 0;
            border-style: solid;
            background: #f8fafc;
        }
        .file-picker-trigger.has-preview:hover {
            background: #f8fafc;
        }
        .file-picker-trigger img,
        .file-picker-trigger video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .file-picker-file-label {
            max-width: 100%;
            padding: 4px;
            font-size: 10px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }
        .file-picker-meta {
            min-width: 0;
            font-size: 13px;
            color: #4b5563;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-picker-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 0;
        }
        .file-picker-preview:empty { display: none; }
        .file-picker-thumb {
            width: 76px;
            height: 76px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 11px;
            text-align: center;
            padding: 4px;
        }
        .file-picker-thumb img,
        .file-picker-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .single-file-row {
            width: 100%;
            align-items: flex-start !important;
        }
        .single-file-row .file-picker {
            flex: 1 1 auto;
            min-width: 0;
        }
        .single-file-row .remove-file {
            flex: 0 0 auto;
            align-self: flex-start;
            margin-top: 2px;
        }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--gap); }
        main { margin: 0 0 40px; padding-top: 24px; }
        @media (max-width: 768px) {
            .navbar-inner { flex-direction: column; align-items: flex-start; }
            .row { gap: 12px; }
            .btn { width: auto; }
        }
        /* admin layout */
        .admin-shell { display: grid; grid-template-columns: 204px 1fr; gap: 12px; align-items: start; margin-top: 12px; min-width: 0; }
        .admin-shell > div { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
        .admin-menu {
            position: sticky;
            top: 86px; /* giữ dưới navbar */
            background: var(--card);
            border: 1px solid #e6e8f0;
            border-radius: 14px;
            padding: 10px;
            box-shadow: var(--shadow);
            align-self: start;
            max-height: calc(100vh - 102px);
            overflow: auto;
            min-width: 0;
        }
        .admin-menu a {
            display: flex;
            align-items: center;
            min-height: 40px;
            padding: 8px 10px;
            border-radius: 10px;
            margin-bottom: 6px;
            color: var(--text);
            border: 1px solid #e6e8f0;
            font-weight: 600;
            line-height: 1.2;
            font-size: 15px;
            transition: color .15s ease, border-color .15s ease, background .15s ease;
        }
        .admin-menu a::before {
            content: "";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 7px;
            background: #fff7ed;
            color: #b45309;
            font-size: 12px;
            flex: 0 0 auto;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }
        .admin-menu a[href*="route=admin"]::before { content: "📊"; }
        .admin-menu a[href*="route=admin-rooms"]::before { content: "🏠"; }
        .admin-menu a[href*="route=admin-users"]::before { content: "👥"; }
        .admin-menu a[href*="route=admin-leads"]::before { content: "🎯"; }
        .admin-menu a[href*="route=admin-payments"]::before { content: "💳"; }
        .admin-menu a[href*="route=admin-seek-posts"]::before { content: "📝"; }
        .admin-menu a[href*="route=admin-messages"]::before { content: "💬"; }
        .admin-menu a[href*="route=admin-cta"]::before { content: "📨"; }
        .admin-menu a[href*="route=admin-theme"]::before { content: "🎨"; }
        .admin-menu a[href*="route=admin-reports"]::before { content: "📈"; }
        .admin-menu a[href*="route=admin-settings"]::before { content: "⚙"; }
        .admin-menu a[href*="route=admin-audit-logs"]::before { content: "📜"; }
        .admin-menu a[href*="route=dashboard"]::before { content: "📊"; }
        .admin-menu a[href*="route=dashboard"][href*="tab=lead"]::before { content: "🎯"; }
        .admin-menu a[href*="route=my-rooms"]::before { content: "🏘"; }
        .admin-menu a[href*="route=my-rooms"][href*="focus=tenants"]::before { content: "👥"; }
        .admin-menu a[href*="route=my-rooms"][href*="focus=contracts"]::before { content: "📝"; }
        .admin-menu a[href*="route=my-rooms"][href*="focus=invoices"]::before { content: "🧾"; }
        .admin-menu a[href*="route=my-rooms"][href*="focus=issues"]::before { content: "🛠"; }
        .admin-menu a[href*="route=payment-history"]::before { content: "💳"; }
        .admin-menu a[href*="route=room-create"]::before { content: "➕"; }
        .admin-menu a[href*="route=room-edit"]::before { content: "✏"; }
        .admin-menu a[href*="route=my-stay"]::before { content: "🏠"; }
        .admin-menu a.active { background: #eef1ff; color: var(--primary); border-color: #d6dcff; box-shadow: inset 0 0 0 1px #d6dcff; }
        .admin-menu a:hover { border-color: var(--primary); color: var(--primary); }
        .admin-shell > div > *:first-child { margin-top: 0; }
        @media (max-width: 992px) {
            .admin-shell { grid-template-columns: 1fr; }
            .admin-menu { position: relative; top: 0; display: flex; flex-wrap: wrap; gap: 8px; max-height: none; overflow: visible; }
            .admin-menu a { margin: 0; }
        }
        /* slider */
        .slider { position: relative; overflow: hidden; border-radius: var(--radius); }
        .slides { display: flex; transition: transform .4s ease; }
        .slide { min-width: 100%; height: 280px; position: relative; }
        .slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .slider-dots { position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; }
        .slider-dots button { width: 10px; height: 10px; border-radius: 50%; border: none; background: rgba(255,255,255,0.6); cursor: pointer; }
        .slider-dots button.active { background: #fff; box-shadow: 0 0 0 2px rgba(0,0,0,0.15); }
        /* footer */
        .site-footer {
            margin-top: 32px;
            background: #0f172a;
            color: #e2e8f0;
            padding: 18px 0;
        }
        .site-footer a { color: #cbd5e1; }
        .site-footer small { color: #94a3b8; }
        @media (max-width: 768px) {
            .site-footer { display: none; }
        }
        /* Floating tư vấn modal */
        .cta-float-btn {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 1100;
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 12px 16px;
            font-weight: 700;
            box-shadow: 0 14px 30px rgba(217,119,6,0.3);
            cursor: pointer;
        }
        .cta-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.52);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1099;
            padding: 12px;
        }
        .cta-overlay.open { display: flex; }
        .cta-modal {
            width: min(420px, 96vw);
            background: linear-gradient(180deg, #fbbf24 0%, #d97706 100%);
            color: #0f172a;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(217,119,6,0.35);
        }
        .cta-modal-header {
            padding: 18px 18px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .cta-modal-body { padding: 0 18px 18px; display: flex; flex-direction: column; gap: 12px; }
        .cta-title { margin: 0; font-size: 20px; font-weight: 800; color: #7c2d12; }
        .cta-sub { margin: 0; color: #111827; font-size: 13px; }
        .cta-close { background: transparent; border: none; color: #7c2d12; font-size: 20px; cursor: pointer; }
        .cta-field { display: flex; flex-direction: column; gap: 6px; }
        .cta-field input, .cta-field select, .cta-field textarea {
            width: 100%;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid rgba(124,45,18,0.35);
            background: #fffaf0;
            color: #7c2d12;
            font-weight: 700;
            caret-color: #7c2d12;
        }
        .cta-field select {
            background: #fff7e6;
            border: 1px solid rgba(124,45,18,0.35);
        }
        .cta-field select:focus,
        .cta-field input:focus,
        .cta-field textarea:focus {
            outline: none;
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.25);
        }
        .cta-field select option {
            background: #fffaf0;
            color: #7c2d12;
        }
        .cta-field input::placeholder,
        .cta-field textarea::placeholder {
            color: #a16207;
            opacity: 1;
        }
        .cta-field input::placeholder,
        .cta-field textarea::placeholder { color: rgba(177, 36, 36, 0.6); }
        .cta-submit {
            margin-top: 4px;
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #e6961f;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(217,119,6,0.35);
        }
    </style>
    <style>
      /* Shopee-like mobile experience */
      .mobile-search-shell { display: none; }
      .mobile-tabbar { display: none; }
      @media (max-width: 768px) {
        :root {
          --bg: #fffaf0;
          --card: #ffffff;
          --primary: #d97706;
          --primary-dark: #b45309;
          --accent: #fbbf24;
          --radius: 14px;
          --gap: 12px;
          --shadow: 0 14px 30px rgba(217, 119, 6, 0.16);
          --dong-size: clamp(520px, 135vw, 680px);
          --dong-offset: calc(var(--dong-size) * -0.54);
          --dong-offset-top: calc(var(--dong-size) * -0.48);
        }
        body::before,
        body::after {
          display: block;
          opacity: <?= $mobileBgOpacity ?>;
          filter: sepia(0.12) saturate(1.03);
        }
        body::before {
          left: calc(var(--dong-size) * -0.58);
          top: calc(var(--dong-size) * -0.48);
        }
        body::after {
          right: calc(var(--dong-size) * -0.56);
          bottom: calc(var(--dong-size) * -0.44);
        }
        body {
          padding: max(8px, env(safe-area-inset-top, 0px)) 10px calc(76px + env(safe-area-inset-bottom, 0px));
          background:
            radial-gradient(120% 120% at 0% 0%, rgba(240,180,60,0.08), transparent 45%),
            radial-gradient(120% 120% at 100% 0%, rgba(255,213,102,0.08), transparent 50%),
            #fffaf0;
        }
        html,
        body {
          overflow-y: auto;
          scrollbar-width: none;
          -ms-overflow-style: none;
        }
        html::-webkit-scrollbar,
        body::-webkit-scrollbar { display: none; width: 0; height: 0; }
        .container { width: 100%; max-width: 100%; padding: 0; }
        .navbar {
          top: max(8px, env(safe-area-inset-top, 0px));
          padding: 4px 10px;
          margin: 0;
          border-radius: 14px;
          padding-top: 6px;
          padding-bottom: 6px;
          box-shadow: 0 10px 22px rgba(217,119,6,0.16);
          background: linear-gradient(135deg, #fbbf24, #d97706);
          width: 100%;
          max-width: 100%;
        }
        .navbar-inner {
          display: flex;
          grid-template-columns: none;
          flex-direction: row;
          align-items: center;
          gap: 8px;
          justify-content: flex-start;
          width: 100%;
          min-width: 0;
        }
        .brand { display:none; }
        .brand-text { display: none; }
        .mobile-brand-only {
          display: inline-flex !important;
          align-items: center;
          justify-content: flex-start;
          margin-right: 6px;
        }
        .mobile-brand-only img { height: 36px; width: auto; }
        .nav-links { display: none; }
        .nav-actions { display: none; }
        .mobile-search-shell {
          display: flex;
          align-items: center;
          gap: 10px;
          width: 100%;
          flex: 1 1 auto;
          justify-content: flex-start;
          min-height: 46px;
          min-width: 0;
        }
        .mobile-search-input {
          flex: 1 1 0;
          display: flex;
          align-items: center;
          justify-content: flex-start;
          gap: 6px;
          background: linear-gradient(135deg, #ffffff, #fffaf0 70%);
          color: #7c2d12;
          padding: 8px 12px;
          border-radius: 12px;
          border: 1px solid #fce1a7;
          box-shadow: 0 10px 24px rgba(217,119,6,0.18), inset 0 1px 0 rgba(255,255,255,0.7);
          font-weight: 800;
          font-size: 14px;
          text-decoration: none;
          min-height: 40px;
          min-width: 0;
        }
        .mobile-search-input .icon {
          font-size: 16px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 22px;
          height: 22px;
          border-radius: 7px;
          background: #fef3c7;
          color: #d97706;
          box-shadow: inset 0 1px 0 rgba(255,255,255,0.8), 0 6px 14px rgba(217,119,6,0.18);
          flex-shrink: 0;
        }
        .mobile-search-input span:last-child {
          color: #9a3412;
          min-width: 0;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .mobile-mini-btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 8px 10px;
          background: #fff7ed;
          color: #b45309;
          border-radius: 11px;
          border: 1px solid #fbbf24;
          font-weight: 800;
          min-width: 86px;
          font-size: 13px;
          text-decoration: none;
        }
        .mobile-chat-btn {
          width: 42px;
          height: 42px;
          flex: 0 0 42px;
          border-radius: 12px;
          border: 1px solid #fcd34d;
          background: linear-gradient(135deg, #fbbf24, #d97706);
          color: #7c2d12;
          box-shadow: 0 10px 18px rgba(217,119,6,0.18);
          font-size: 18px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
        }
        .hero { padding: 12px 12px; border-radius: 12px; margin: 6px 0 12px; }
        .row { flex-direction: column; gap: 8px; }
        .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-3, .col-md-12 { flex: 1 1 100%; min-width: 100%; }
        .grid-2 { grid-template-columns: 1fr; }
        .card { border-radius: 12px; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.10); }
        .card-body { padding: 12px; }
        .card-room img { height: 140px; }
        .card-room.room-card { border: 1px solid #ffe0d9; box-shadow: 0 12px 22px rgba(217,119,6,0.12); }
        .rooms-grid { display: grid !important; grid-template-columns: 1fr; gap: 12px; }
        .rooms-grid > [class*='col-'] { width: 100%; min-width: 0; }
        .room-thumb { height: 128px; }
        .room-price { font-size: 14px; color: #b45309; }
        .room-desc { display: none; }
        .room-meta { gap: 4px; }
        .cta { padding: 9px 9px; border-radius: 10px; font-weight: 800; font-size: 13px; }
        .page-stack { gap: 16px; }
        .slider .slide { height: 176px; }
        .admin-shell { grid-template-columns: 1fr; }
        .admin-menu {
          position: relative;
          top: 0;
          display: grid;
          grid-template-columns: repeat(2, minmax(0, 1fr));
          gap: 10px;
          max-height: none;
          overflow: visible;
          width: 100%;
          padding: 10px;
          border-radius: 18px;
          background: linear-gradient(180deg, rgba(255,255,255,0.94), rgba(255,249,237,0.96));
          box-shadow: 0 16px 30px rgba(217,119,6,0.08);
        }
        .admin-menu a {
          flex: none;
          width: 100%;
          margin: 0;
          min-height: 78px;
          padding: 12px 10px;
          flex-direction: column;
          justify-content: center;
          text-align: center;
          gap: 8px;
          font-size: 13px;
          line-height: 1.3;
          border-radius: 16px;
          min-width: 0;
          box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }
        .admin-menu a::before {
          width: 34px;
          height: 34px;
          margin-right: 0;
          border-radius: 11px;
          font-size: 16px;
          background: linear-gradient(135deg, #fff4d8, #ffe8b2);
          box-shadow: inset 0 1px 0 rgba(255,255,255,0.86), 0 8px 16px rgba(217,119,6,0.12);
        }
        .admin-menu a.active {
          background: linear-gradient(180deg, #eef2ff, #fff7ed);
          box-shadow: 0 14px 24px rgba(217,119,6,0.14), inset 0 0 0 1px #d6dcff;
        }
        .admin-menu a.active::before {
          background: linear-gradient(135deg, #fbbf24, #f59e0b);
          color: #7c2d12;
        }
        @media (max-width: 420px) {
          .admin-menu a {
            min-height: 72px;
            font-size: 12.5px;
            padding: 10px 8px;
          }
        }
        .kpi-value { font-size: 20px; }
        .chip { font-size: 11.5px; padding: 6px 8px; }
        .chip-lite { font-size: 11px; padding: 5px 8px; }
        .badge { font-size: 11px; padding: 4px 8px; }
        .form-control, select, input[type=number], input[type=tel], input[type=text], input[type=password] { font-size: 15px; padding: 10px 11px; }
        main { padding-top: 16px; overflow-x: clip; }
        .table { font-size: 12.5px; }
        .table th, .table td { white-space: normal; word-break: normal; overflow-wrap: break-word; }
        .cta-modal { width: 100%; border-radius: 14px; }
        .cta-field input, .cta-field select, .cta-field textarea { font-size: 14px; }
        h1, h2, h3, h4 { letter-spacing: -0.2px; }
        p, li { line-height: 1.6; }
        .site-footer { padding-bottom: 82px; }
        .cta-float-btn { bottom: 82px; right: 12px; }
        .chat-bubble-btn { bottom: 82px; right: 12px; width: 50px; height: 50px; display: none !important; }
        .chat-panel { display: none !important; }
        .mobile-tabbar {
          display: flex;
          position: fixed;
          left: 0; right: 0; bottom: 0;
          height: 62px;
          background: #fff;
          border-top: 1px solid #f3f4f6;
          box-shadow: 0 -10px 26px rgba(0,0,0,0.08);
          z-index: 1400;
          padding: 6px 4px calc(6px + env(safe-area-inset-bottom, 0px));
        }
        .mobile-tabbar .tab-item {
          flex: 1 1 0;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 2px;
          text-decoration: none;
          color: #6b7280;
          font-size: 11px;
          font-weight: 700;
        }
        .mobile-tabbar .tab-icon { font-size: 18px; line-height: 1; }
        .mobile-tabbar .tab-item.active { color: #b45309; }
        .mobile-tabbar .tab-item.active .tab-icon { transform: translateY(-1px); }
        #leadNotifyPrompt {
          padding: 10px 12px;
          border-radius: 14px;
          margin: 8px 0 10px;
          gap: 10px !important;
        }
        #leadNotifyPrompt strong { display:block; margin-bottom: 2px; }
        #leadNotifyPrompt small { display:block; line-height:1.45; }
        #leadNotifyPrompt .btn {
          min-height: 40px;
          padding: 8px 12px;
        }
      }
      @media (max-width: 540px) {
        .hero { padding: 13px 12px; }
        .room-thumb { height: 120px; }
        .mobile-search-input { font-size: 14px; padding: 8px 11px; }
        .rooms-grid { grid-template-columns: 1fr; gap: 10px; }
        h1, h2, h3 { line-height: 1.25; }
      }
    </style>
</head>
<body>
<?php
  $currentRoute = $_GET['route'] ?? 'rooms';
  $currentSection = trim((string)($_GET['section'] ?? ''));
  $currentDashboardTab = trim((string)($_GET['tab'] ?? 'home'));
  $currentFocus = trim((string)($_GET['focus'] ?? ''));
  $hideFloatingChatCta = in_array($currentRoute, ['login', 'register'], true);
  $user = currentUser();
  $userRole = (string)($user['role'] ?? '');
  $isLandlordUser = !empty($user) && (($user['role'] ?? '') === 'landlord');
  $desktopNotifications = [];
  $desktopUnreadCount = 0;
  $leadDashboardUrl = routeUrl('dashboard', ['tab' => 'lead']) . '#lead';
  $isPortalLandlord = $currentRoute === 'portal-landlord';
  $isPortalTenant = $currentRoute === 'portal-tenant';
  $isPortalAdmin = $currentRoute === 'portal-admin';
  if ($isLandlordUser) {
      try {
          $desktopNotifications = recentLeadNotificationsByLandlord((int)$user['id'], 8);
          $desktopUnreadCount = countUnreadLeadNotificationsByLandlord((int)$user['id']);
      } catch (Throwable $e) {
          error_log('Lead notification preload failed: ' . $e->getMessage());
      }
  }
  $managementRoles = ['tenant', 'landlord', 'staff', 'admin'];
  $isManagementCapable = !empty($user) && in_array($userRole, $managementRoles, true);
  $canStaffRoomManage = ($userRole !== 'staff') || staffHasPermission('room_manage');
  $managementModeEnabled = false;
  if ($isManagementCapable) {
      $managementModeEnabled = (($_SESSION['ui_management_mode_' . $userRole] ?? '1') !== '0');
  }
  $landlordSidebarRoutes = ['dashboard', 'my-rooms', 'payment-history', 'room-create', 'room-edit', 'room-ops'];
  $hideLandlordSidebar = $managementModeEnabled
      && in_array($userRole, ['landlord', 'staff'], true)
      && in_array($currentRoute, $landlordSidebarRoutes, true);
  $managementToggleHref = $isManagementCapable
      ? routeUrl('mode-management', ['state' => $managementModeEnabled ? 'off' : 'on'])
      : '';
  $managementToggleState = $managementModeEnabled ? 'On' : 'Off';
  $managementToggleAria = $managementModeEnabled ? 'Tắt chế độ quản lý' : 'Bật chế độ quản lý';
  $brandHref = routeUrl('rooms');
  if ($user) {
      if ($isManagementCapable && !$managementModeEnabled) {
          if ($userRole === 'landlord' || $userRole === 'staff') {
              $brandHref = $canStaffRoomManage ? routeUrl('room-create') : routeUrl('rooms');
          } else {
              $brandHref = routeUrl('rooms');
          }
      } else {
          $brandHref = routeUrl(defaultRouteForUser($user));
      }
  }
  $desktopSearchEnabled = false;
  $desktopNavLinks = [];
  $mobileUtilityHref = $user ? routeUrl($userRole === 'admin' ? 'admin-messages' : 'messages') : routeUrl('login');
  $mobileUtilityLabel = $user ? 'Tin nhắn hỗ trợ' : 'Đăng nhập';
  $mobileUtilityIcon = $user ? '💬' : '👤';
  $brandSubLabel = 'Nền tảng kết nối thuê & cho thuê';

  switch ($userRole) {
      case 'tenant':
          if ($managementModeEnabled) {
              $brandSubLabel = 'Cổng người thuê';
              $desktopNavLinks = [
                  [
                      'href' => routeUrl('portal-tenant', ['section' => 'dashboard']),
                      'label' => 'Bảng điều khiển',
                      'active' => ($isPortalTenant && in_array($currentSection, ['', 'dashboard'], true)) || ($currentRoute === 'my-stay' && ($currentSection === '' || $currentSection === 'room')),
                  ],
                  [
                      'href' => routeUrl('my-stay', ['section' => 'room']) . '#my-room',
                      'label' => 'Phòng của tôi',
                      'active' => $currentRoute === 'my-stay' && ($currentSection === '' || $currentSection === 'room'),
                  ],
                  [
                      'href' => routeUrl('portal-tenant', ['section' => 'contract']),
                      'label' => 'Hợp đồng của tôi',
                      'active' => ($isPortalTenant && $currentSection === 'contract'),
                  ],
                  [
                      'href' => routeUrl('my-stay', ['section' => 'invoices']) . '#my-invoices',
                      'label' => 'Hóa đơn của tôi',
                      'active' => $currentRoute === 'my-stay' && $currentSection === 'invoices',
                  ],
                  [
                      'href' => routeUrl('portal-tenant', ['section' => 'payments']),
                      'label' => 'Thanh toán',
                      'active' => ($isPortalTenant && $currentSection === 'payments'),
                  ],
                  [
                      'href' => routeUrl('my-stay', ['section' => 'issues']) . '#my-issues',
                      'label' => 'Yêu cầu sửa chữa',
                      'active' => $currentRoute === 'my-stay' && $currentSection === 'issues',
                  ],
                  [
                      'href' => routeUrl('notifications'),
                      'label' => 'Thông báo',
                      'active' => $currentRoute === 'notifications' || ($isPortalTenant && $currentSection === 'notifications'),
                  ],
                  [
                      'href' => routeUrl('profile'),
                      'label' => 'Hồ sơ',
                      'active' => $currentRoute === 'profile' || ($isPortalTenant && $currentSection === 'profile'),
                  ],
              ];
          } else {
              $brandSubLabel = 'Chế độ tìm phòng';
              $desktopSearchEnabled = true;
              $desktopNavLinks = [
                  [
                      'href' => routeUrl('rooms'),
                      'label' => 'Trang chủ',
                      'active' => $currentRoute === 'rooms',
                  ],
                  [
                      'href' => routeUrl('search'),
                      'label' => 'Tìm phòng',
                      'active' => $currentRoute === 'search',
                  ],
                  [
                      'href' => routeUrl('seek-posts'),
                      'label' => 'Đăng tìm phòng',
                      'active' => $currentRoute === 'seek-posts',
                  ],
                  [
                      'href' => routeUrl('profile'),
                      'label' => 'Hồ sơ',
                      'active' => $currentRoute === 'profile',
                  ],
              ];
          }
          $mobileUtilityHref = routeUrl('notifications');
          $mobileUtilityLabel = 'Thông báo';
          $mobileUtilityIcon = '🔔';
          break;
      case 'landlord':
      case 'staff':
          if ($managementModeEnabled) {
              $brandSubLabel = 'Cổng chủ trọ';
              $desktopNavLinks = [
                  [
                      'href' => routeUrl('portal-landlord', ['section' => 'dashboard']),
                      'label' => 'Bảng điều khiển',
                      'active' => ($isPortalLandlord && in_array($currentSection, ['', 'dashboard'], true)) || ($currentRoute === 'dashboard' && !in_array($currentDashboardTab, ['lead', 'payments'], true)),
                  ],
                  [
                      'href' => routeUrl('dashboard', ['tab' => 'lead']) . '#lead',
                      'label' => 'Nhu cầu',
                      'active' => ($isPortalLandlord && $currentSection === 'leads') || ($currentRoute === 'dashboard' && $currentDashboardTab === 'lead'),
                  ],
                  [
                      'href' => routeUrl('my-rooms'),
                      'label' => 'Phòng trọ',
                      'active' => (($isPortalLandlord && $currentSection === 'rooms') || in_array($currentRoute, ['my-rooms', 'room-ops', 'room-create', 'room-edit'], true)) && !in_array($currentFocus, ['tenants', 'contracts', 'invoices', 'issues'], true),
                  ],
                  [
                      'href' => routeUrl('my-rooms', ['focus' => 'tenants']),
                      'label' => 'Khách thuê',
                      'active' => ($isPortalLandlord && $currentSection === 'tenants') || ($currentRoute === 'my-rooms' && $currentFocus === 'tenants'),
                  ],
                  [
                      'href' => routeUrl('my-rooms', ['focus' => 'contracts']),
                      'label' => 'Hợp đồng',
                      'active' => ($isPortalLandlord && $currentSection === 'contracts') || ($currentRoute === 'my-rooms' && $currentFocus === 'contracts'),
                  ],
                  [
                      'href' => routeUrl('my-rooms', ['focus' => 'invoices']),
                      'label' => 'Hóa đơn',
                      'active' => ($isPortalLandlord && $currentSection === 'invoices') || ($currentRoute === 'my-rooms' && $currentFocus === 'invoices'),
                  ],
                  [
                      'href' => routeUrl('payment-history'),
                      'label' => 'Thanh toán',
                      'active' => ($isPortalLandlord && $currentSection === 'payments') || $currentRoute === 'payment-history' || ($currentRoute === 'dashboard' && $currentDashboardTab === 'payments'),
                  ],
                  [
                      'href' => routeUrl('my-rooms', ['focus' => 'issues']),
                      'label' => 'Sự cố',
                      'active' => ($isPortalLandlord && $currentSection === 'incidents') || ($currentRoute === 'my-rooms' && $currentFocus === 'issues'),
                  ],
                  [
                      'href' => routeUrl('dashboard'),
                      'label' => 'Báo cáo',
                      'active' => ($isPortalLandlord && $currentSection === 'reports') || ($currentRoute === 'dashboard' && !in_array($currentDashboardTab, ['lead', 'payments'], true)),
                  ],
              ];
          } else {
              $brandSubLabel = 'Chế độ đăng phòng';
              if ($canStaffRoomManage) {
                  $desktopSearchEnabled = false;
                  $desktopNavLinks = [
                      [
                          'href' => routeUrl('room-create'),
                          'label' => 'Đăng phòng',
                          'active' => $currentRoute === 'room-create',
                      ],
                      [
                          'href' => routeUrl('my-rooms'),
                          'label' => 'Tin đã đăng',
                          'active' => in_array($currentRoute, ['my-rooms', 'room-edit'], true),
                      ],
                  ];
              } else {
                  $desktopSearchEnabled = true;
                  $desktopNavLinks = [
                      [
                          'href' => routeUrl('rooms'),
                          'label' => 'Trang chủ',
                          'active' => $currentRoute === 'rooms',
                      ],
                      [
                          'href' => routeUrl('search'),
                          'label' => 'Tìm phòng',
                          'active' => $currentRoute === 'search',
                      ],
                      [
                          'href' => routeUrl('seek-posts'),
                          'label' => 'Bài tìm phòng',
                          'active' => $currentRoute === 'seek-posts',
                      ],
                      [
                          'href' => routeUrl('profile'),
                          'label' => 'Hồ sơ',
                          'active' => $currentRoute === 'profile',
                      ],
                  ];
              }
          }
          $mobileUtilityHref = routeUrl('notifications');
          $mobileUtilityLabel = 'Thông báo vận hành';
          $mobileUtilityIcon = '🔔';
          break;
      case 'admin':
          if ($managementModeEnabled) {
              $brandSubLabel = 'Cổng quản trị';
              $desktopNavLinks = [
                  [
                      'href' => routeUrl('admin'),
                      'label' => 'Bảng điều khiển',
                      'active' => ($isPortalAdmin && in_array($currentSection, ['', 'dashboard'], true)) || $currentRoute === 'admin',
                  ],
                  [
                      'href' => routeUrl('admin-users'),
                      'label' => 'Người dùng',
                      'active' => ($isPortalAdmin && $currentSection === 'users') || $currentRoute === 'admin-users',
                  ],
                  [
                      'href' => routeUrl('admin-leads'),
                      'label' => 'Nhu cầu',
                      'active' => ($isPortalAdmin && $currentSection === 'leads') || $currentRoute === 'admin-leads',
                  ],
                  [
                      'href' => routeUrl('admin-payments'),
                      'label' => 'Giao dịch',
                      'active' => ($isPortalAdmin && $currentSection === 'transactions') || $currentRoute === 'admin-payments',
                  ],
                  [
                      'href' => routeUrl('admin-reports'),
                      'label' => 'Báo cáo',
                      'active' => ($isPortalAdmin && $currentSection === 'reports') || $currentRoute === 'admin-reports',
                  ],
                  [
                      'href' => routeUrl('admin-settings'),
                      'label' => 'Cài đặt hệ thống',
                      'active' => ($isPortalAdmin && $currentSection === 'settings') || in_array($currentRoute, ['admin-theme', 'admin-settings'], true),
                  ],
                  [
                      'href' => routeUrl('admin-audit-logs'),
                      'label' => 'Nhật ký kiểm tra',
                      'active' => ($isPortalAdmin && $currentSection === 'audit') || $currentRoute === 'admin-audit-logs',
                  ],
              ];
          } else {
              $brandSubLabel = 'Chế độ công khai';
              $desktopSearchEnabled = true;
              $desktopNavLinks = [
                  [
                      'href' => routeUrl('rooms'),
                      'label' => 'Trang chủ',
                      'active' => $currentRoute === 'rooms',
                  ],
                  [
                      'href' => routeUrl('search'),
                      'label' => 'Tìm phòng',
                      'active' => $currentRoute === 'search',
                  ],
                  [
                      'href' => routeUrl('seek-posts'),
                      'label' => 'Bài tìm phòng',
                      'active' => $currentRoute === 'seek-posts',
                  ],
              ];
          }
          $mobileUtilityHref = routeUrl('admin-messages');
          $mobileUtilityLabel = 'Tin nhắn';
          $mobileUtilityIcon = '💬';
          break;
      default:
          $desktopSearchEnabled = true;
          $desktopNavLinks = [
              [
                  'href' => routeUrl('seek-posts'),
                  'label' => 'Đăng tìm phòng',
                  'active' => $currentRoute === 'seek-posts',
              ],
              [
                  'href' => routeUrl('register', ['is_landlord' => 1]),
                  'label' => 'Dành cho chủ trọ',
                  'active' => $currentRoute === 'register' && !empty($_GET['is_landlord']),
              ],
          ];
          break;
  }
  $profileHref = routeUrl('profile');
  $desktopNavLinks = array_values(array_filter($desktopNavLinks, static function ($link) use ($profileHref) {
      $href = (string)($link['href'] ?? '');
      $label = (string)($link['label'] ?? '');
      return $href !== $profileHref
          && $label !== 'Hồ sơ'
          && $label !== 'Trang chủ';
  }));
  $desktopPrimaryNavLimit = empty($user) ? 2 : 3;
  $desktopInlineOverflowThreshold = 1;
  if ($isManagementCapable && $managementModeEnabled) {
      $desktopSearchEnabled = false;
      $desktopPrimaryNavLinks = [];
      $desktopOverflowNavLinks = [];
  } else {
      $desktopPrimaryNavLinks = array_slice($desktopNavLinks, 0, $desktopPrimaryNavLimit);
      $desktopOverflowNavLinks = array_slice($desktopNavLinks, $desktopPrimaryNavLimit);
      if (count($desktopOverflowNavLinks) <= $desktopInlineOverflowThreshold) {
          $desktopPrimaryNavLinks = array_merge($desktopPrimaryNavLinks, $desktopOverflowNavLinks);
          $desktopOverflowNavLinks = [];
      }
  }
  $desktopOverflowHasActive = false;
  foreach ($desktopOverflowNavLinks as $overflowLink) {
      if (!empty($overflowLink['active'])) {
          $desktopOverflowHasActive = true;
          break;
      }
  }
?>
<?php if ($hideLandlordSidebar): ?>
<style>
  @media (min-width: 993px) {
    .admin-shell {
      grid-template-columns: 1fr;
    }
    .admin-shell > .admin-menu {
      display: none;
    }
  }
</style>
<?php endif; ?>
<nav class="navbar">
  <div class="container navbar-inner">
    <a class="brand" href="<?= htmlspecialchars($brandHref) ?>" aria-label="PhongTrọ">
        <img src="<?= htmlspecialchars(assetUrl('logo1.png')) ?>" alt="PhongTrọ" onerror="this.style.display='none'">
        <div class="brand-text">
            <div>PhòngTrọ</div>
            <small><?= htmlspecialchars($brandSubLabel) ?></small>
        </div>
    </a>
    <div class="nav-links">
        <?php if ($desktopSearchEnabled): ?>
          <form class="nav-search-form" method="get" action="?" role="search">
            <input type="hidden" name="route" value="search">
            <span class="icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="presentation">
                <circle cx="11" cy="11" r="6.5" stroke="#d97706" stroke-width="2"></circle>
                <path d="M15.5 15.5L20 20" stroke="#FBBC04" stroke-width="2" stroke-linecap="round"></path>
              </svg>
            </span>
            <input type="text" name="keyword" placeholder="Tìm phòng" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
          </form>
        <?php endif; ?>
        <?php foreach ($desktopPrimaryNavLinks as $link): ?>
          <a class="nav-link <?= !empty($link['active']) ? 'active' : '' ?>" href="<?= htmlspecialchars((string)$link['href']) ?>"><?= htmlspecialchars((string)$link['label']) ?></a>
        <?php endforeach; ?>
        <?php if (!empty($desktopOverflowNavLinks)): ?>
          <div class="desktop-nav-menu" id="desktopNavMenuRoot">
            <button class="nav-link desktop-nav-toggle <?= $desktopOverflowHasActive ? 'active' : '' ?>" type="button" id="desktopNavMenuToggle" aria-expanded="false" aria-haspopup="true">
              Thêm
            </button>
            <div class="desktop-nav-panel" id="desktopNavMenuPanel" hidden>
              <?php foreach ($desktopOverflowNavLinks as $link): ?>
                <a class="desktop-nav-item <?= !empty($link['active']) ? 'is-active' : '' ?>" href="<?= htmlspecialchars((string)$link['href']) ?>"><?= htmlspecialchars((string)$link['label']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
    </div>
    <div class="mobile-search-shell">
        <a class="brand mobile-brand-only" href="<?= htmlspecialchars($brandHref) ?>" aria-label="PhongTrọ">
            <img src="<?= htmlspecialchars(assetUrl('logo1.png')) ?>" alt="PhongTrọ" onerror="this.style.display='none'">
        </a>
        <a class="mobile-search-input" href="?route=search">
            <span class="icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="presentation">
                <circle cx="11" cy="11" r="6.5" stroke="#d97706" stroke-width="2"></circle>
                <path d="M15.5 15.5L20 20" stroke="#FBBC04" stroke-width="2" stroke-linecap="round"></path>
              </svg>
            </span>
            <span>Tìm phòng</span>
        </a>
        <?php if ($isManagementCapable): ?>
          <a class="mobile-mode-toggle <?= $managementModeEnabled ? 'is-on' : '' ?>"
             href="<?= htmlspecialchars($managementToggleHref) ?>"
             aria-label="<?= htmlspecialchars($managementToggleAria) ?>">
            <?= htmlspecialchars($managementToggleState) ?>
          </a>
        <?php endif; ?>
        <a class="mobile-chat-btn" href="<?= htmlspecialchars($mobileUtilityHref) ?>" aria-label="<?= htmlspecialchars($mobileUtilityLabel) ?>"><?= htmlspecialchars($mobileUtilityIcon) ?></a>
    </div>
    <div class="d-flex gap-2 align-items-center nav-actions">
        <?php if ($user): ?>
            <?php
                $notifyClass = ($currentRoute === 'notifications') ? 'btn btn-primary btn-sm notify-desktop-btn' : 'btn btn-outline btn-sm notify-desktop-btn';
                $contextAction = null;
                $profileClass = ($currentRoute === 'profile') ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm';
                if ($userRole === 'admin') {
                    $contextAction = [
                        'href' => routeUrl('admin-messages'),
                        'label' => 'Tin nhắn',
                    ];
                }
            ?>
            <?php if ($isLandlordUser): ?>
                <div class="notify-desktop" id="desktopNotifyRoot">
                    <button class="<?= $notifyClass ?>" type="button" id="desktopNotifyToggle" aria-label="Thông báo" aria-expanded="false" aria-haspopup="true">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6.25 9.75a5.75 5.75 0 1 1 11.5 0v2.77c0 .74.22 1.46.62 2.08l.73 1.1c.5.75-.03 1.8-.93 1.8H5.83c-.9 0-1.43-1.05-.93-1.8l.73-1.1c.4-.62.62-1.34.62-2.08V9.75Z" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M9.75 18.5a2.25 2.25 0 0 0 4.5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <span class="notify-desktop-dot <?= $desktopUnreadCount > 0 ? '' : 'hidden' ?>" id="desktopNotifyDot"></span>
                    </button>
                    <div class="notify-desktop-menu" id="desktopNotifyMenu" hidden>
                        <div class="notify-desktop-head">
                            <div>
                                <h3>Thông báo</h3>
                                <p id="desktopNotifySummary"><?= $desktopUnreadCount > 0 ? ('Bạn có ' . $desktopUnreadCount . ' chưa đọc.') : 'Chưa có thông báo chưa đọc.' ?></p>
                            </div>
                            <div class="notify-desktop-actions">
                                <button type="button" class="btn btn-outline btn-sm" id="desktopNotifyMarkAllBtn" <?= $desktopUnreadCount > 0 ? '' : 'disabled' ?>>Đọc hết</button>
                                <a class="btn btn-outline btn-sm" href="<?= htmlspecialchars(routeUrl('notifications')) ?>">Xem tất cả</a>
                            </div>
                        </div>
                        <div class="notify-desktop-list" id="desktopNotifyList">
                            <?php if (empty($desktopNotifications)): ?>
                                <div class="notify-desktop-empty">Chưa có thông báo nào.</div>
                            <?php else: ?>
                                <?php foreach ($desktopNotifications as $notification): ?>
                                    <?php
                                        $leadId = (int)($notification['id'] ?? 0);
                                        $isUnread = empty($notification['notification_read_at']);
                                    ?>
                                    <div class="notify-desktop-item <?= $isUnread ? 'is-unread' : 'is-read' ?>" data-lead-id="<?= $leadId ?>" data-is-read="<?= $isUnread ? '0' : '1' ?>">
                                        <a class="notify-desktop-item-main" href="<?= htmlspecialchars($leadDashboardUrl) ?>">
                                            <div class="notify-desktop-item-top">
                                                <div class="notify-desktop-item-title-wrap">
                                                    <?php if ($isUnread): ?><span class="notify-desktop-item-dot"></span><?php endif; ?>
                                                    <p class="notify-desktop-item-title"><?= htmlspecialchars((string)($notification['room_title'] ?? ('Phòng #' . (int)($notification['room_id'] ?? 0)))) ?></p>
                                                </div>
                                                <span class="notify-desktop-read-state"><?= $isUnread ? 'Chưa đọc' : 'Đã đọc' ?></span>
                                            </div>
                                            <div class="notify-desktop-meta">
                                                <span>#Nhu cầu <?= $leadId ?></span>
                                                <span><?= htmlspecialchars((string)($notification['created_at'] ?? '')) ?></span>
                                            </div>
                                        </a>
                                        <div class="notify-desktop-item-actions">
                                            <button type="button" class="btn btn-outline btn-sm desktopNotifyToggleRead"><?= $isUnread ? 'Đã đọc' : 'Chưa đọc' ?></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($isManagementCapable): ?>
                <a class="mode-toggle <?= $managementModeEnabled ? 'is-on' : '' ?>"
                   href="<?= htmlspecialchars($managementToggleHref) ?>"
                   role="switch"
                   aria-checked="<?= $managementModeEnabled ? 'true' : 'false' ?>"
                   aria-label="<?= htmlspecialchars($managementToggleAria) ?>">
                    <span class="mode-toggle-label">Quản lý</span>
                    <span class="mode-toggle-track"><span class="mode-toggle-thumb"></span></span>
                    <span class="mode-toggle-state"><?= htmlspecialchars($managementToggleState) ?></span>
                </a>
            <?php endif; ?>
            <?php if ($contextAction): ?>
                <a class="btn btn-outline btn-sm" href="<?= htmlspecialchars((string)$contextAction['href']) ?>"><?= htmlspecialchars((string)$contextAction['label']) ?></a>
            <?php endif; ?>
            <a class="<?= $profileClass ?>" href="?route=profile">Tài khoản</a>
            <a class="btn btn-outline btn-sm logout-btn" href="?route=logout">Đăng xuất</a>
        <?php else: ?>
            <a class="btn btn-outline btn-sm" href="?route=login">Đăng nhập</a>
            <a class="btn btn-primary btn-sm" href="?route=register">Đăng ký</a>
        <?php endif; ?>
    </div>
  </div>
</nav>

<?php if (empty($user) && !$hideFloatingChatCta): ?>
  <!-- Floating tư vấn button + modal (chỉ cho khách chưa đăng nhập) -->
  <button class="cta-float-btn" id="ctaOpen" aria-label="Tư vấn miễn phí" style="width:48px;height:48px;padding:0;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#d97706;">
    <span style="font-size:18px;line-height:1;display:inline-flex;align-items:center;justify-content:center;">💬</span>
  </button>
  <div class="cta-overlay" id="ctaOverlay" aria-hidden="true">
    <div class="cta-modal" role="dialog" aria-modal="true" aria-labelledby="ctaTitle">
      <div class="cta-modal-header">
        <div>
          <div style="display:flex; align-items:center; gap:10px;">
            <img src="<?= htmlspecialchars(assetUrl('logo1.png')) ?>" alt="PhòngTrọ" style="height:34px; width:auto; border-radius:8px;" onerror="this.style.display='none'">
            <div>
              <div id="ctaTitle" class="cta-title">PhòngTrọ</div>
              <p class="cta-sub">Đăng ký nhận thông tin miễn phí</p>
            </div>
          </div>
        </div>
        <button class="cta-close" id="ctaClose" aria-label="Đóng">×</button>
      </div>
      <div class="cta-modal-body">
        <div class="cta-field">
          <input type="text" id="ctaName" placeholder="Tên của bạn*" required>
        </div>
        <div class="cta-field">
          <input type="tel" id="ctaPhone" placeholder="Số điện thoại của bạn*" required>
        </div>
        <div class="cta-field">
          <input type="email" id="ctaEmail" placeholder="Email của bạn">
        </div>
        <div class="cta-field">
          <select id="ctaProvince" required>
            <option value="">Chọn khu vực tư vấn*</option>
            <option>Hà Nội</option>
            <option>TP Hồ Chí Minh</option>
            <option>Thanh Hóa</option>
            <option>Đà Nẵng</option>
          </select>
        </div>
        <div class="cta-field">
          <textarea id="ctaMessage" rows="3" placeholder="Tin nhắn của bạn*"></textarea>
        </div>
        <button class="cta-submit" id="ctaSubmit" type="button">Gửi tin nhắn</button>
      </div>
    </div>
  </div>
<?php elseif (($user['role'] ?? '') !== 'admin' && !$hideFloatingChatCta): ?>
  <a class="cta-float-btn" href="<?= htmlspecialchars($chatHref) ?>" aria-label="Mở tin nhắn" style="width:48px;height:48px;padding:0;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#d97706;text-decoration:none;">
    <span style="font-size:18px;line-height:1;display:inline-flex;align-items:center;justify-content:center;">💬</span>
  </a>
<?php endif; ?>

<main class="container" style="flex:1 0 auto;">
    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <?php if (!empty($user) && ($user['role'] ?? '') === 'landlord'): ?>
        <div id="leadNotifyPrompt" class="alert alert-info" style="display:none; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <strong id="leadNotifyPromptTitle">Bật thông báo quan tâm mới</strong>
                <small id="leadNotifyPromptDesc">Cho phép trình duyệt gửi thông báo khi có người quan tâm phòng, kể cả khi đã thu nhỏ hoặc tắt thẻ trình duyệt.</small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-primary btn-sm" id="leadNotifyAllowBtn">Cho phép</button>
                <button type="button" class="btn btn-outline btn-sm" id="leadNotifyHideBtn">Ẩn tạm</button>
            </div>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>
<footer class="site-footer" style="flex-shrink:0;">
    <div class="container">
        <div class="d-flex justify-between align-items-center" style="flex-wrap:wrap; gap:8px;">
            <strong>PhòngTrọ</strong>
            <small>© <?= date('Y') ?> - Kết nối người thuê và chủ trọ.</small>
        </div>
    </div>
</footer>
<?php if (empty($user)): ?>
  <script>
    (() => {
      const overlay = document.getElementById('ctaOverlay');
      const openBtn = document.getElementById('ctaOpen');
      const closeBtn = document.getElementById('ctaClose');
      const submitBtn = document.getElementById('ctaSubmit');
      const formFields = {
        name: document.getElementById('ctaName'),
        phone: document.getElementById('ctaPhone'),
        email: document.getElementById('ctaEmail'),
        province: document.getElementById('ctaProvince'),
        message: document.getElementById('ctaMessage'),
      };
      const toggle = (show) => {
        if (!overlay) return;
        overlay.classList.toggle('open', show);
        overlay.setAttribute('aria-hidden', show ? 'false' : 'true');
      };
      openBtn?.addEventListener('click', () => toggle(true));
      closeBtn?.addEventListener('click', () => toggle(false));
      overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) toggle(false);
      });
      submitBtn?.addEventListener('click', async () => {
        const name = formFields.name?.value.trim();
        const phone = formFields.phone?.value.trim();
        const email = formFields.email?.value.trim();
        const province = formFields.province?.value.trim();
        const message = formFields.message?.value.trim();
        if (!name || !phone || !message) {
          alert('Vui lòng nhập đủ Tên, SĐT và Tin nhắn.');
          return;
        }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang gửi...';
        try {
          const fd = new FormData();
          fd.append('name', name);
          fd.append('phone', phone);
          fd.append('email', email);
          fd.append('province', province);
          fd.append('message', message);
          const res = await fetch('?route=cta-submit', { method: 'POST', body: fd });
          const json = await res.json().catch(() => ({}));
          if (json.ok) {
            alert('Đã ghi nhận thông tin, chúng tôi sẽ liên hệ sớm!');
            toggle(false);
            Object.values(formFields).forEach(f => { if (f) f.value = ''; });
          } else {
            alert(json.error || 'Gửi thất bại, thử lại sau.');
          }
        } catch (e) {
          alert('Lỗi kết nối, thử lại.');
        } finally {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Gửi tin nhắn';
        }
      });
    })();
  </script>
<?php endif; ?>
<?php if (!empty($user) && ($user['role'] ?? '') === 'landlord'): ?>
<script>
  (() => {
    const hasNotificationApi = 'Notification' in window;
    const prompt = document.getElementById('leadNotifyPrompt');
    const promptTitle = document.getElementById('leadNotifyPromptTitle');
    const promptDesc = document.getElementById('leadNotifyPromptDesc');
    const allowBtn = document.getElementById('leadNotifyAllowBtn');
    const hideBtn = document.getElementById('leadNotifyHideBtn');
    const storageKey = 'lead_notify_last_seen_<?= (int)$user['id'] ?>';
    const shownKey = 'lead_notify_last_shown_<?= (int)$user['id'] ?>';
    const notifyRoute = '?route=lead-notifications';
    const streamRoute = '?route=lead-notifications-stream';
    const markRoute = '?route=lead-notifications-mark';
    const subscribeRoute = '?route=push-subscribe';
    const unsubscribeRoute = '?route=push-unsubscribe';
    const vapidPublicKey = <?= json_encode(pushVapidPublicKey(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const hasPushConfig = vapidPublicKey !== '' && 'PushManager' in window;
    const notificationCenterUrl = <?= json_encode($leadDashboardUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const iconUrl = '<?= htmlspecialchars(assetUrl('favicon.png')) ?>';
    const desktopNotifyRoot = document.getElementById('desktopNotifyRoot');
    const desktopNotifyToggle = document.getElementById('desktopNotifyToggle');
    const desktopNotifyMenu = document.getElementById('desktopNotifyMenu');
    const desktopNotifyList = document.getElementById('desktopNotifyList');
    const desktopNotifyDot = document.getElementById('desktopNotifyDot');
    const desktopNotifySummary = document.getElementById('desktopNotifySummary');
    const desktopNotifyMarkAllBtn = document.getElementById('desktopNotifyMarkAllBtn');
    let desktopItems = <?= json_encode(array_values($desktopNotifications), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let polling = null;
    let stream = null;
    let reconnectTimer = null;
    let serviceWorkerReady = null;
    let pushSyncDone = false;
    const notificationPermission = () => hasNotificationApi ? Notification.permission : 'default';

    const getNumber = (key) => Number(localStorage.getItem(key) || '0');
    const setNumber = (key, value) => {
      const next = Math.max(Number(value || 0), getNumber(key));
      localStorage.setItem(key, String(next));
      return next;
    };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    }[char]));
    const notificationLink = () => notificationCenterUrl;
    const normalizeDesktopItem = (item) => ({
      id: Number(item?.id || 0),
      room_id: Number(item?.room_id || 0),
      room_title: String(item?.room_title || ''),
      created_at: String(item?.created_at || ''),
      status: String(item?.status || 'new'),
      notification_read_at: item?.notification_read_at ? String(item.notification_read_at) : '',
    });
    const isItemRead = (item) => !!(item && item.notification_read_at);
    const updateDesktopUnreadState = (count) => {
      if (desktopNotifyDot) {
        desktopNotifyDot.classList.toggle('hidden', count <= 0);
      }
      if (desktopNotifySummary) {
        desktopNotifySummary.textContent = count > 0
          ? `Bạn có ${count} chưa đọc.`
          : 'Chưa có thông báo chưa đọc.';
      }
      if (desktopNotifyMarkAllBtn) {
        desktopNotifyMarkAllBtn.disabled = count <= 0;
      }
    };
    const renderDesktopNotifications = () => {
      if (!desktopNotifyList) return;
      if (!desktopItems.length) {
        desktopNotifyList.innerHTML = '<div class="notify-desktop-empty">Chưa có thông báo nào.</div>';
        return;
      }
      desktopNotifyList.innerHTML = desktopItems.map((rawItem) => {
        const item = normalizeDesktopItem(rawItem);
        const read = isItemRead(item);
        const title = item.room_title || `Phòng #${item.room_id || item.id}`;
        return `
          <div class="notify-desktop-item ${read ? 'is-read' : 'is-unread'}" data-lead-id="${item.id}" data-is-read="${read ? '1' : '0'}">
            <a class="notify-desktop-item-main" href="${escapeHtml(notificationLink(item.id))}">
              <div class="notify-desktop-item-top">
                <div class="notify-desktop-item-title-wrap">
                  ${read ? '' : '<span class="notify-desktop-item-dot"></span>'}
                  <p class="notify-desktop-item-title">${escapeHtml(title)}</p>
                </div>
                <span class="notify-desktop-read-state">${read ? 'Đã đọc' : 'Chưa đọc'}</span>
              </div>
              <div class="notify-desktop-meta">
                <span>#Nhu cầu ${item.id}</span>
                <span>${escapeHtml(item.created_at)}</span>
              </div>
            </a>
            <div class="notify-desktop-item-actions">
              <button type="button" class="btn btn-outline btn-sm desktopNotifyToggleRead">${read ? 'Chưa đọc' : 'Đã đọc'}</button>
            </div>
          </div>
        `;
      }).join('');
    };
    const mergeDesktopItems = (items = []) => {
      const map = new Map(desktopItems.map((item) => [Number(item.id || 0), normalizeDesktopItem(item)]));
      items.forEach((item) => {
        const normalized = normalizeDesktopItem(item);
        if (!normalized.id) return;
        const existing = map.get(normalized.id) || {};
        map.set(normalized.id, {
          ...existing,
          ...normalized,
          notification_read_at: normalized.notification_read_at || existing.notification_read_at || '',
        });
      });
      desktopItems = Array.from(map.values())
        .sort((a, b) => Number(b.id || 0) - Number(a.id || 0))
        .slice(0, 8);
      renderDesktopNotifications();
    };
    const syncDesktopItemReadState = (leadId, isRead) => {
      desktopItems = desktopItems.map((item) => {
        if (Number(item.id || 0) !== Number(leadId || 0)) return item;
        return {
          ...item,
          notification_read_at: isRead ? new Date().toISOString().slice(0, 19).replace('T', ' ') : '',
        };
      });
      renderDesktopNotifications();
    };

    const setPromptContent = (state = 'default') => {
      if (!promptTitle || !promptDesc || !allowBtn) return;
      if (state === 'denied') {
        promptTitle.textContent = 'Thông báo đang bị chặn';
        promptDesc.textContent = 'Trình duyệt đang chặn thông báo trang này. Bấm biểu tượng cạnh thanh địa chỉ để bật lại thông báo.';
        allowBtn.textContent = 'Bật lại';
        return;
      }
      promptTitle.textContent = 'Bật thông báo quan tâm mới';
      if (hasPushConfig) {
        promptDesc.textContent = 'Cho phép trình duyệt gửi thông báo khi có người quan tâm phòng, kể cả khi đã thu nhỏ hoặc tắt thẻ trình duyệt.';
      } else {
        promptDesc.textContent = 'Cho phép thông báo để nhận cảnh báo nhu cầu mới khi đang mở ứng dụng.';
      }
      allowBtn.textContent = 'Cho phép';
    };

    const showPrompt = (state = 'default') => {
      if (!prompt) return;
      setPromptContent(state);
      prompt.style.display = 'flex';
    };

    const hidePrompt = () => {
      if (!prompt) return;
      prompt.style.display = 'none';
    };

    const fetchJson = async (url) => {
      const res = await fetch(url, {
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      return res.json();
    };

    const postJson = async (url, payload) => {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload || {}),
      });
      return res.json().catch(() => ({}));
    };
    const setDesktopMenuOpen = (open) => {
      if (!desktopNotifyMenu || !desktopNotifyToggle) return;
      desktopNotifyMenu.hidden = !open;
      desktopNotifyToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    const markNotificationState = async (leadId, isRead) => {
      const response = await postJson(markRoute, {
        lead_id: leadId,
        is_read: isRead ? 1 : 0,
      }).catch(() => ({}));
      if (!response || !response.ok) return null;
      syncDesktopItemReadState(leadId, !!response.is_read);
      updateDesktopUnreadState(Number(response.unread_count || 0));
      return response;
    };

    const ensureServiceWorker = async () => {
      if (!('serviceWorker' in navigator)) return null;
      if (!serviceWorkerReady) {
        serviceWorkerReady = navigator.serviceWorker.register('sw.js')
          .then(() => navigator.serviceWorker.ready)
          .catch(() => null);
      }
      return serviceWorkerReady;
    };

    const urlBase64ToUint8Array = (base64String) => {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const raw = atob(base64);
      const output = new Uint8Array(raw.length);
      for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
      }
      return output;
    };

    const syncPushSubscription = async (force = false) => {
      if (!hasPushConfig || notificationPermission() !== 'granted') return;
      if (pushSyncDone && !force) return;
      const registration = await ensureServiceWorker();
      if (!registration || !registration.pushManager) return;

      let subscription = await registration.pushManager.getSubscription();
      if (!subscription) {
        try {
          subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
          });
        } catch (error) {
          return;
        }
      }

      if (!subscription) return;
      const payload = typeof subscription.toJSON === 'function'
        ? subscription.toJSON()
        : { endpoint: subscription.endpoint };
      const response = await postJson(subscribeRoute, payload).catch(() => ({}));
      if (response && response.ok) {
        pushSyncDone = true;
      }
    };

    const clearPushSubscription = async () => {
      if (!hasPushConfig) return;
      const registration = await ensureServiceWorker();
      if (!registration || !registration.pushManager) return;
      const subscription = await registration.pushManager.getSubscription();
      if (!subscription) return;
      try {
        await postJson(unsubscribeRoute, { endpoint: subscription.endpoint });
      } catch (error) {
        // Ignore server errors and still try to clean local subscription.
      }
      try {
        await subscription.unsubscribe();
      } catch (error) {
        // Ignore; browser can keep stale subscription until next successful sync.
      }
      pushSyncDone = false;
    };

    const openNotificationCenter = () => {
      window.focus();
      window.location.href = notificationCenterUrl;
    };

    const showBrowserNotification = async (title, options) => {
      if (!hasNotificationApi || notificationPermission() !== 'granted') return false;
      const registration = await ensureServiceWorker();
      if (registration && typeof registration.showNotification === 'function') {
        try {
          await registration.showNotification(title, options);
          return true;
        } catch (error) {
          // Fallback to Notification below.
        }
      }

      try {
        const notification = new Notification(title, options);
        notification.onclick = () => {
          notification.close();
          openNotificationCenter();
        };
        return true;
      } catch (error) {
        return false;
      }
    };

    const seedCursor = async (force = false) => {
      if (!force && getNumber(storageKey) > 0) {
        return getNumber(storageKey);
      }
      const data = await fetchJson(`${notifyRoute}&seed=1`);
      if (data && data.ok) {
        if (typeof data.unread_count !== 'undefined') {
          updateDesktopUnreadState(Number(data.unread_count || 0));
        }
        return setNumber(storageKey, Number(data.latest_id || 0));
      }
      return getNumber(storageKey);
    };

    const notifyLeadItems = async (items = []) => {
      for (const item of items) {
        const leadId = Number(item.id || 0);
        if (!leadId || leadId <= getNumber(shownKey)) continue;

        const shown = await showBrowserNotification('Có người vừa quan tâm phòng', {
          body: `Phòng ${item.room_title || ('#' + item.room_id)} vừa có nhu cầu mới.`,
          icon: iconUrl,
          tag: `lead-${leadId}`,
          renotify: true,
          data: {
            url: notificationCenterUrl,
            leadId,
          },
        });

        if (shown) {
          setNumber(shownKey, leadId);
        }
      }
    };

    const handleLeadPayload = async (data) => {
      if (!data || !data.ok) return;
      const items = Array.isArray(data.items) ? data.items : [];
      const latestFromItems = items.reduce((max, item) => Math.max(max, Number(item.id || 0)), 0);
      const latestId = Math.max(Number(data.latest_id || 0), latestFromItems);

      if (items.length > 0) {
        mergeDesktopItems(items);
      }
      if (typeof data.unread_count !== 'undefined') {
        updateDesktopUnreadState(Number(data.unread_count || 0));
      }

      if (items.length > 0 && notificationPermission() === 'granted') {
        await notifyLeadItems(items);
      }

      if (latestId > 0) {
        setNumber(storageKey, latestId);
      }
    };

    const stopPolling = () => {
      if (!polling) return;
      clearInterval(polling);
      polling = null;
    };

    const pollLeadNotifications = async () => {
      let lastSeen = getNumber(storageKey);
      if (!lastSeen) {
        await seedCursor();
        lastSeen = getNumber(storageKey);
      }

      try {
        const data = await fetchJson(`${notifyRoute}&after_id=${encodeURIComponent(String(lastSeen))}`);
        await handleLeadPayload(data);
      } catch (error) {
        // Silent retry on next poll.
      }
    };

    const startPolling = async () => {
      if (polling || stream) return;
      if (!getNumber(storageKey)) {
        await seedCursor();
      }
      await pollLeadNotifications();
      polling = setInterval(pollLeadNotifications, 10000);
    };

    const stopStream = () => {
      if (stream) {
        stream.close();
        stream = null;
      }
    };

    const scheduleReconnect = () => {
      if (reconnectTimer) return;
      reconnectTimer = window.setTimeout(() => {
        reconnectTimer = null;
        startRealtime();
      }, 2500);
    };

    const startStream = async () => {
      if (!('EventSource' in window) || stream) {
        return false;
      }

      await seedCursor();
      const afterId = getNumber(storageKey);
      const source = new EventSource(`${streamRoute}&after_id=${encodeURIComponent(String(afterId))}`);
      stream = source;

      source.addEventListener('open', () => {
        stopPolling();
      });

      source.addEventListener('lead', async (event) => {
        try {
          const data = JSON.parse(event.data || '{}');
          await handleLeadPayload(data);
        } catch (error) {
          // Ignore malformed payloads and keep listening.
        }
      });

      source.addEventListener('ping', () => {
        // Keep-alive event for idle periods.
      });

      source.onerror = () => {
        if (stream !== source) return;
        stopStream();
        startPolling();
        scheduleReconnect();
      };

      return true;
    };

    const startRealtime = async () => {
      if (notificationPermission() === 'granted') {
        await ensureServiceWorker();
        await syncPushSubscription();
      }
      const streamStarted = await startStream();
      if (!streamStarted) {
        await startPolling();
      }
    };

    renderDesktopNotifications();
    updateDesktopUnreadState(<?= (int)$desktopUnreadCount ?>);

    desktopNotifyToggle?.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      setDesktopMenuOpen(desktopNotifyMenu?.hidden ?? true);
    });

    desktopNotifyList?.addEventListener('click', async (event) => {
      const button = event.target.closest('.desktopNotifyToggleRead');
      if (!button) return;
      const item = button.closest('.notify-desktop-item');
      const leadId = Number(item?.dataset.leadId || '0');
      if (!item || !leadId) return;
      button.disabled = true;
      try {
        const nextIsRead = item.dataset.isRead !== '1';
        await markNotificationState(leadId, nextIsRead);
      } finally {
        button.disabled = false;
      }
    });

    desktopNotifyMarkAllBtn?.addEventListener('click', async () => {
      desktopNotifyMarkAllBtn.disabled = true;
      let shouldRestoreButton = true;
      try {
        const response = await postJson(markRoute, { mark_all: 1 }).catch(() => ({}));
        if (!response || !response.ok) return;
        desktopItems = desktopItems.map((item) => ({
          ...item,
          notification_read_at: item.notification_read_at || new Date().toISOString().slice(0, 19).replace('T', ' '),
        }));
        renderDesktopNotifications();
        updateDesktopUnreadState(Number(response.unread_count || 0));
        shouldRestoreButton = false;
      } finally {
        if (shouldRestoreButton) {
          desktopNotifyMarkAllBtn.disabled = false;
        }
      }
    });

    document.addEventListener('click', (event) => {
      if (!desktopNotifyRoot || !desktopNotifyMenu || desktopNotifyMenu.hidden) return;
      if (desktopNotifyRoot.contains(event.target)) return;
      setDesktopMenuOpen(false);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setDesktopMenuOpen(false);
      }
    });

    allowBtn?.addEventListener('click', async () => {
      try {
        if (!hasNotificationApi) {
          hidePrompt();
          return;
        }
        await ensureServiceWorker();
        if (notificationPermission() === 'denied') {
          showPrompt('denied');
          return;
        }
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
          hidePrompt();
          await seedCursor(true);
          await syncPushSubscription(true);
          await startRealtime();
          await showBrowserNotification('Thông báo đã được bật', {
            body: 'Web sẽ báo khi có người quan tâm đến phòng của bạn.',
            icon: iconUrl,
            tag: 'lead-notify-enabled',
            data: { url: notificationCenterUrl },
          });
        } else if (permission === 'denied') {
          await clearPushSubscription();
          showPrompt('denied');
        } else {
          showPrompt('default');
        }
      } catch (error) {
        showPrompt('default');
      }
    });

    hideBtn?.addEventListener('click', () => hidePrompt());

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event?.data?.type === 'PUSH_SUBSCRIPTION_CHANGED') {
          syncPushSubscription(true);
        }
      });
    }

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState !== 'visible') return;
      if (!hasNotificationApi) {
        hidePrompt();
        startRealtime();
      } else if (notificationPermission() === 'granted') {
        hidePrompt();
        startRealtime();
      } else if (notificationPermission() === 'denied') {
        showPrompt('denied');
      } else {
        showPrompt('default');
      }
    });

    window.addEventListener('beforeunload', () => {
      stopStream();
      stopPolling();
      if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
      }
    });

    if (!hasNotificationApi) {
      hidePrompt();
      startRealtime();
    } else if (notificationPermission() === 'granted') {
      ensureServiceWorker();
      hidePrompt();
      startRealtime();
    } else if (notificationPermission() === 'denied') {
      ensureServiceWorker();
      clearPushSubscription();
      showPrompt('denied');
    } else {
      ensureServiceWorker();
      showPrompt('default');
    }
  })();
</script>
<?php endif; ?>
<script>
  (() => {
    const menus = [
      {
        root: document.getElementById('desktopNavMenuRoot'),
        toggle: document.getElementById('desktopNavMenuToggle'),
        panel: document.getElementById('desktopNavMenuPanel'),
      },
    ].filter((menu) => menu.root && menu.toggle && menu.panel);
    if (!menus.length) return;

    const setMenuOpen = (menu, open) => {
      menu.panel.hidden = !open;
      menu.toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const closeAllMenus = (exceptMenu = null) => {
      menus.forEach((menu) => {
        if (menu === exceptMenu) return;
        setMenuOpen(menu, false);
      });
    };

    menus.forEach((menu) => {
      menu.toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const nextOpen = menu.panel.hidden;
        closeAllMenus(menu);
        setMenuOpen(menu, nextOpen);
      });
    });

    document.addEventListener('click', (event) => {
      const clickedInsideMenu = menus.some((menu) => menu.root.contains(event.target));
      if (clickedInsideMenu) return;
      closeAllMenus();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeAllMenus();
      }
    });
  })();
</script>
<script>
  // Giữ trống đồng quay liên tục, không reset khi reload
  (() => {
    const root = document.documentElement;
    const now = Date.now();
    const durBefore = 110000; // ms
    const durAfter = 130000;  // ms
    root.style.setProperty('--dong-delay-before', `${- (now % durBefore) / 1000}s`);
    root.style.setProperty('--dong-delay-after', `${- (now % durAfter) / 1000}s`);
  })();
</script>
<?php if (!empty($user) && $user['role'] !== 'admin'): ?>
  <style>
  .chat-bubble-btn {
    position: fixed;
    right: 18px;
    bottom: 18px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d97706, #b45309);
    color: #fff;
    border: none;
    box-shadow: 0 18px 36px rgba(217,119,6,0.35);
    cursor: pointer;
    z-index: 1600;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: visible;
  }
  .chat-bubble-btn.show-close::after {
    content: "×";
    position: absolute;
    top: -6px;
    right: -6px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #ef4444;
    color: #fff;
    font-size: 12px;
    display: grid;
    place-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
  }
  .chat-panel {
    position: fixed;
    right: 18px;
    bottom: 78px;
    width: 320px;
    height: clamp(420px, 72vh, 520px);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 22px 44px rgba(15, 23, 42, 0.18);
    border: 1px solid #e5e7eb;
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 1550;
  }
  .chat-panel.open { display:flex; }
  .chat-panel header {
    padding: 12px;
    background: linear-gradient(135deg, #d97706, #b45309);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .chat-panel header .title { font-weight: 700; }
  .chat-messages { padding: 12px; flex:1 1 auto; overflow:auto; display:flex; flex-direction:column; gap:8px; background:#f8fafc; min-height:0; }
  .chat-input { padding: 10px; border-top:1px solid #e5e7eb; display:flex; gap:8px; background:#fff; }
  .chat-input input { flex:1 1 auto; }
  .bubble {
    width: min(var(--chat-bubble-width), calc(100% - 8px));
    min-height: var(--chat-bubble-min-height);
    padding:10px 12px;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    gap:6px;
    flex:0 0 auto;
    overflow-wrap:anywhere;
  }
  .bubble.mine { margin-left: auto; background:#d97706; color:#fff; }
  .bubble.theirs { margin-right: auto; background:#fff; border:1px solid #e5e7eb; }
  @media (max-width: 768px) {
    .chat-bubble-btn { display:none !important; }
    .chat-panel { width: calc(100% - 24px); right: 12px; bottom: 92px; height: 72vh; }
  }
</style>
<button class="chat-bubble-btn" id="chatToggle" aria-label="Trò chuyện với quản trị viên">💬</button>
<div class="chat-panel" id="chatPanel" aria-hidden="true">
  <header>
    <div style="display:flex;align-items:center;gap:8px;">
      <div style="width:32px;height:32px;border-radius:50%;overflow:hidden;background:#fff;color:#b45309;display:flex;align-items:center;justify-content:center;font-weight:800;">
        <img src="<?= htmlspecialchars(assetUrl($user['avatar'] ?? 'avt.jpg')) ?>" alt="avt" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.textContent='A'; this.remove();">
      </div>
      <div>
        <div class="title">Trò chuyện với quản trị viên</div>
        <small style="opacity:0.85;">Thường phản hồi trong vài phút</small>
      </div>
    </div>
    <button id="chatClose" style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;">×</button>
  </header>
  <div class="chat-messages" id="chatMessagesMini">
    <div class="text-muted" id="chatEmpty">Đang tải...</div>
  </div>
  <div id="chatSuggestBox" style="padding:10px;border-top:1px solid #e5e7eb;display:flex;gap:6px;flex-wrap:wrap;background:#f8fafc;flex:0 0 auto;">
    <button type="button" class="btn btn-outline btn-sm chat-suggest" data-text="Cho mình hỏi phòng còn trống không?">Phòng còn trống?</button>
    <button type="button" class="btn btn-outline btn-sm chat-suggest" data-text="Giờ xem phòng gần nhất là khi nào?">Lịch xem phòng</button>
    <button type="button" class="btn btn-outline btn-sm chat-suggest" data-text="Cho mình xin báo giá và địa chỉ chi tiết nhé.">Báo giá & địa chỉ</button>
  </div>
  <form id="chatFormMini" class="chat-input">
    <input type="text" id="chatInputMini" class="form-control" placeholder="Nhập tin nhắn..." required>
    <button class="btn btn-primary" type="submit">Gửi</button>
  </form>
</div>
<script>
  (() => {
    const toggleBtn = document.getElementById('chatToggle');
    const panel = document.getElementById('chatPanel');
    const closeBtn = document.getElementById('chatClose');
    const msgBox = document.getElementById('chatMessagesMini');
    const empty = document.getElementById('chatEmpty');
    const form = document.getElementById('chatFormMini');
    const input = document.getElementById('chatInputMini');
    const suggestBox = document.getElementById('chatSuggestBox');
    let loading = false;
    const relTime = (iso) => {
      const t = new Date(iso.replace(' ','T'));
      const diff = (Date.now() - t.getTime())/1000;
      if (diff < 60) return 'vừa xong';
      if (diff < 3600) return `Đã gửi ${Math.floor(diff/60)} phút trước`;
      if (diff < 86400) return `Đã gửi ${Math.floor(diff/3600)} giờ trước`;
      return iso;
    };
    const renderMessages = (items) => {
      msgBox.innerHTML = '';
      if (!items || items.length === 0) {
        if (suggestBox) suggestBox.style.display = 'flex';
        msgBox.innerHTML = `
          <div class="bubble theirs">
            <div>Xin chào! Bạn cần hỗ trợ phòng trọ khu vực nào?</div>
            <div style="font-size:11px;opacity:0.7;margin-top:4px;">Vừa xong</div>
          </div>
          <div class="bubble theirs">
            <div>Bạn có thể để lại giá tối đa và thời gian xem phòng nhé.</div>
            <div style="font-size:11px;opacity:0.7;margin-top:4px;">Vừa xong</div>
          </div>
        `;
        return;
      }
      if (suggestBox) suggestBox.style.display = 'none';
      items.slice().reverse().forEach(m => {
        const mine = Number(m.sender_id) === Number(<?= (int)$user['id'] ?>);
        const div = document.createElement('div');
        div.className = `bubble ${mine ? 'mine' : 'theirs'}`;
        const text = m.content_effective || m.content || m.message || '';
        div.innerHTML = `<div>${escapeHtml(text)}</div><div class="msg-time" data-time="${m.created_at}" style="font-size:11px;opacity:0.7;margin-top:4px;">✓ ${m.created_at}</div>`;
        msgBox.appendChild(div);
      });
      msgBox.scrollTop = msgBox.scrollHeight;
      updateTimes();
    };
    const fetchMessages = async () => {
      if (loading) return;
      loading = true;
      try {
        const res = await fetch('?route=messages&ajax=1', { cache: 'no-store' });
        const json = await res.json();
        if (json.ok) renderMessages(json.messages || []);
      } catch(e) {
        console.error(e);
      } finally { loading = false; }
    };
    const escapeHtml = (str) => str.replace(/[&<>\"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;','\'':'&#39;'}[c]));
    const updateTimes = () => {
      document.querySelectorAll('.msg-time').forEach(el => {
        const t = el.getAttribute('data-time');
        if (t) {
          el.textContent = '✓ ' + relTime(t);
          el.title = t;
        }
      });
    };
    document.querySelectorAll('.chat-suggest').forEach(btn => {
      btn.addEventListener('click', () => {
        input.value = btn.getAttribute('data-text') || '';
        input.focus();
      });
    });
    toggleBtn?.addEventListener('click', () => {
      if (!panel) return;
      const willOpen = !panel.classList.contains('open');
      panel.classList.toggle('open', willOpen);
      panel.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
      toggleBtn.classList.toggle('show-close', willOpen);
      if (willOpen) fetchMessages();
    });
    closeBtn?.addEventListener('click', () => {
      panel.classList.remove('open');
      panel.setAttribute('aria-hidden','true');
      toggleBtn.classList.remove('show-close');
    });
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const text = input.value.trim();
      if (!text) return;
      const fd = new FormData();
      fd.append('content', text);
      fd.append('ajax', '1');
      try {
        await fetch('?route=messages', { method: 'POST', body: fd });
        input.value = '';
        if (suggestBox) suggestBox.style.display = 'none';
        fetchMessages();
      } catch (err) {
        alert('Gửi thất bại, thử lại.');
      }
    });
    setInterval(() => {
      if (panel.classList.contains('open')) fetchMessages();
    }, 4000);
  })();
</script>
<?php endif; ?>

<?php
  $mobileTabs = [];
  if (!empty($user)) {
    if ($userRole === 'landlord' || $userRole === 'staff') {
      if ($managementModeEnabled) {
        $mobileTabs = [
          ['href' => routeUrl('portal-landlord', ['section' => 'dashboard']), 'label' => 'Bảng điều khiển', 'icon' => '📊', 'active' => ($isPortalLandlord && in_array($currentSection, ['', 'dashboard'], true)) || ($currentRoute === 'dashboard' && !in_array($currentDashboardTab, ['lead', 'payments'], true))],
          ['href' => routeUrl('dashboard', ['tab' => 'lead']) . '#lead', 'label' => 'Nhu cầu', 'icon' => '🎯', 'active' => ($isPortalLandlord && $currentSection === 'leads') || ($currentRoute === 'dashboard' && $currentDashboardTab === 'lead')],
          ['href' => routeUrl('my-rooms'), 'label' => 'Phòng trọ', 'icon' => '🏘', 'active' => ($isPortalLandlord && $currentSection === 'rooms') || in_array($currentRoute, ['my-rooms', 'room-ops', 'room-create', 'room-edit'], true)],
          ['href' => routeUrl('payment-history'), 'label' => 'Thanh toán', 'icon' => '💳', 'active' => ($isPortalLandlord && $currentSection === 'payments') || $currentRoute === 'payment-history'],
        ];
      } else {
        if ($canStaffRoomManage) {
          $mobileTabs = [
            ['href' => routeUrl('rooms'), 'label' => 'Trang chủ', 'icon' => '🏠', 'active' => $currentRoute === 'rooms'],
            ['href' => routeUrl('room-create'), 'label' => 'Đăng phòng', 'icon' => '➕', 'active' => $currentRoute === 'room-create'],
            ['href' => routeUrl('my-rooms'), 'label' => 'Tin đã đăng', 'icon' => '🏘', 'active' => in_array($currentRoute, ['my-rooms', 'room-edit'], true)],
            ['href' => routeUrl('profile'), 'label' => 'Hồ sơ', 'icon' => '👤', 'active' => $currentRoute === 'profile'],
          ];
        } else {
          $mobileTabs = [
            ['href' => routeUrl('rooms'), 'label' => 'Trang chủ', 'icon' => '🏠', 'active' => $currentRoute === 'rooms'],
            ['href' => routeUrl('search'), 'label' => 'Tìm kiếm', 'icon' => '🔍', 'active' => $currentRoute === 'search'],
            ['href' => routeUrl('seek-posts'), 'label' => 'Bài tìm phòng', 'icon' => '📝', 'active' => $currentRoute === 'seek-posts'],
            ['href' => routeUrl('profile'), 'label' => 'Hồ sơ', 'icon' => '👤', 'active' => $currentRoute === 'profile'],
          ];
        }
      }
    } elseif ($userRole === 'tenant') {
      if ($managementModeEnabled) {
        $mobileTabs = [
          ['href' => routeUrl('portal-tenant', ['section' => 'dashboard']), 'label' => 'Bảng điều khiển', 'icon' => '🏠', 'active' => ($isPortalTenant && in_array($currentSection, ['', 'dashboard'], true)) || ($currentRoute === 'my-stay' && ($currentSection === '' || $currentSection === 'room'))],
          ['href' => routeUrl('my-stay', ['section' => 'invoices']) . '#my-invoices', 'label' => 'Hóa đơn', 'icon' => '🧾', 'active' => $currentRoute === 'my-stay' && $currentSection === 'invoices'],
          ['href' => routeUrl('my-stay', ['section' => 'issues']) . '#my-issues', 'label' => 'Sửa chữa', 'icon' => '🛠', 'active' => $currentRoute === 'my-stay' && $currentSection === 'issues'],
          ['href' => routeUrl('profile'), 'label' => 'Hồ sơ', 'icon' => '👤', 'active' => $currentRoute === 'profile'],
        ];
      } else {
        $mobileTabs = [
          ['href' => routeUrl('rooms'), 'label' => 'Trang chủ', 'icon' => '🏠', 'active' => $currentRoute === 'rooms'],
          ['href' => routeUrl('search'), 'label' => 'Tìm kiếm', 'icon' => '🔍', 'active' => $currentRoute === 'search'],
          ['href' => routeUrl('seek-posts'), 'label' => 'Đăng tìm', 'icon' => '📝', 'active' => $currentRoute === 'seek-posts'],
          ['href' => routeUrl('profile'), 'label' => 'Hồ sơ', 'icon' => '👤', 'active' => $currentRoute === 'profile'],
        ];
      }
    } elseif ($userRole === 'admin') {
      if ($managementModeEnabled) {
        $mobileTabs = [
          ['href' => routeUrl('admin'), 'label' => 'Bảng điều khiển', 'icon' => '🛠', 'active' => ($isPortalAdmin && in_array($currentSection, ['', 'dashboard'], true)) || $currentRoute === 'admin'],
          ['href' => routeUrl('admin-users'), 'label' => 'Người dùng', 'icon' => '👥', 'active' => ($isPortalAdmin && $currentSection === 'users') || $currentRoute === 'admin-users'],
          ['href' => routeUrl('admin-payments'), 'label' => 'Giao dịch', 'icon' => '💳', 'active' => ($isPortalAdmin && $currentSection === 'transactions') || $currentRoute === 'admin-payments'],
          ['href' => routeUrl('admin-audit-logs'), 'label' => 'Nhật ký', 'icon' => '📜', 'active' => ($isPortalAdmin && $currentSection === 'audit') || $currentRoute === 'admin-audit-logs'],
        ];
      } else {
        $mobileTabs = [
          ['href' => routeUrl('rooms'), 'label' => 'Trang chủ', 'icon' => '🏠', 'active' => $currentRoute === 'rooms'],
          ['href' => routeUrl('search'), 'label' => 'Tìm kiếm', 'icon' => '🔍', 'active' => $currentRoute === 'search'],
          ['href' => routeUrl('seek-posts'), 'label' => 'Đăng tìm', 'icon' => '📝', 'active' => $currentRoute === 'seek-posts'],
          ['href' => routeUrl('profile'), 'label' => 'Hồ sơ', 'icon' => '👤', 'active' => $currentRoute === 'profile'],
        ];
      }
    } else {
      $mobileTabs = [
        ['href' => routeUrl('rooms'), 'label' => 'Trang chủ', 'icon' => '🏠', 'active' => $currentRoute === 'rooms'],
        ['href' => routeUrl('search'), 'label' => 'Tìm kiếm', 'icon' => '🔍', 'active' => $currentRoute === 'search'],
        ['href' => routeUrl('notifications'), 'label' => 'Thông báo', 'icon' => '🔔', 'active' => $currentRoute === 'notifications'],
        ['href' => routeUrl('profile'), 'label' => 'Cá nhân', 'icon' => '👤', 'active' => $currentRoute === 'profile'],
      ];
    }
  } else {
    $mobileTabs = [
      ['href' => routeUrl('rooms'), 'label' => 'Trang chủ', 'icon' => '🏠', 'active' => $currentRoute === 'rooms'],
      ['href' => routeUrl('search'), 'label' => 'Tìm kiếm', 'icon' => '🔍', 'active' => $currentRoute === 'search'],
      ['href' => routeUrl('seek-posts'), 'label' => 'Đăng tìm', 'icon' => '📝', 'active' => $currentRoute === 'seek-posts'],
      ['href' => routeUrl('login'), 'label' => 'Tài khoản', 'icon' => '👤', 'active' => in_array($currentRoute, ['login', 'register', 'profile'], true)],
    ];
  }
?>
<nav class="mobile-tabbar" aria-label="Điều hướng nhanh trên di động">
  <?php foreach ($mobileTabs as $tab): ?>
    <a class="tab-item <?= !empty($tab['active']) ? 'active' : '' ?>" href="<?= htmlspecialchars((string)$tab['href']) ?>">
      <span class="tab-icon"><?= htmlspecialchars($tab['icon']) ?></span>
      <span class="tab-label"><?= htmlspecialchars($tab['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<script>
  (() => {
    const token = <?= json_encode(csrfToken(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    if (!token) return;

    const ensureFormToken = (form) => {
      if (!(form instanceof HTMLFormElement)) return;
      const method = String(form.method || 'GET').toUpperCase();
      if (method === 'GET') return;
      if (form.querySelector('input[name="_csrf"]')) return;
      const field = document.createElement('input');
      field.type = 'hidden';
      field.name = '_csrf';
      field.value = token;
      form.appendChild(field);
    };

    const syncForms = (root = document) => {
      if (!(root instanceof Element || root instanceof Document)) return;
      root.querySelectorAll('form').forEach(ensureFormToken);
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => syncForms(document), { once: true });
    } else {
      syncForms(document);
    }

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof Element)) return;
          if (node.matches('form')) {
            ensureFormToken(node);
          } else {
            syncForms(node);
          }
        });
      });
    });
    if (document.body) {
      observer.observe(document.body, { childList: true, subtree: true });
    }

    if (typeof window.fetch === 'function') {
      const originalFetch = window.fetch.bind(window);
      window.fetch = (input, init = {}) => {
        const requestInit = Object.assign({}, init || {});
        const method = String(requestInit.method || 'GET').toUpperCase();
        const unsafe = !['GET', 'HEAD', 'OPTIONS'].includes(method);
        let sameOrigin = true;
        try {
          const rawUrl = typeof input === 'string' ? input : (input && input.url ? input.url : '');
          const parsed = new URL(rawUrl || window.location.href, window.location.href);
          sameOrigin = parsed.origin === window.location.origin;
        } catch (e) {
          sameOrigin = true;
        }

        if (unsafe && sameOrigin) {
          const headers = new Headers(requestInit.headers || {});
          if (!headers.has('X-CSRF-Token')) {
            headers.set('X-CSRF-Token', token);
          }
          requestInit.headers = headers;
          if (requestInit.body instanceof FormData && !requestInit.body.has('_csrf')) {
            requestInit.body.append('_csrf', token);
          }
        }

        return originalFetch(input, requestInit);
      };
    }
  })();
</script>

<script>
  (() => {
    if (!('addEventListener' in window)) return;
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js').catch(() => {});
    }
    window.__deferredInstallPrompt = window.__deferredInstallPrompt || null;
    window.__pwaInstalled = window.__pwaInstalled || false;

    window.addEventListener('beforeinstallprompt', (event) => {
      event.preventDefault();
      window.__deferredInstallPrompt = event;
      window.dispatchEvent(new CustomEvent('phongtro-install-available'));
    });

    window.addEventListener('appinstalled', () => {
      window.__deferredInstallPrompt = null;
      window.__pwaInstalled = true;
      window.dispatchEvent(new CustomEvent('phongtro-app-installed'));
    });
  })();
</script>
<script>
  (() => {
    const MAX_PREVIEW_ITEMS = 8;

    const revokePreviewUrls = (input) => {
      const urls = Array.isArray(input._pickerPreviewUrls) ? input._pickerPreviewUrls : [];
      urls.forEach((url) => {
        try { URL.revokeObjectURL(url); } catch (e) {}
      });
      input._pickerPreviewUrls = [];
    };

    const rememberPreviewUrl = (input, file) => {
      const url = URL.createObjectURL(file);
      input._pickerPreviewUrls = input._pickerPreviewUrls || [];
      input._pickerPreviewUrls.push(url);
      return url;
    };

    const appendFilePreview = (target, input, file) => {
      if (file.type && file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = rememberPreviewUrl(input, file);
        img.alt = file.name;
        target.appendChild(img);
        return;
      }

      if (file.type && file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = rememberPreviewUrl(input, file);
        video.muted = true;
        video.playsInline = true;
        video.preload = 'metadata';
        target.appendChild(video);
        return;
      }

      const label = document.createElement('span');
      label.className = 'file-picker-file-label';
      label.textContent = file.name.length > 18 ? `${file.name.slice(0, 15)}...` : file.name;
      target.appendChild(label);
    };

    const renderPreview = (input, metaEl, previewEl, triggerEl) => {
      if (!metaEl || !previewEl || !triggerEl) return;
      revokePreviewUrls(input);
      previewEl.innerHTML = '';
      triggerEl.classList.remove('has-preview');
      triggerEl.setAttribute('aria-label', 'Chọn tệp');
      triggerEl.textContent = '+';

      const files = Array.from(input.files || []);
      if (!files.length) {
        metaEl.textContent = 'Chưa chọn tệp';
        return;
      }

      metaEl.textContent = files.length === 1
        ? files[0].name
        : `Đã chọn ${files.length} tệp`;

      triggerEl.innerHTML = '';
      triggerEl.classList.add('has-preview');
      triggerEl.setAttribute('aria-label', `Đổi tệp ${files[0].name}`);
      appendFilePreview(triggerEl, input, files[0]);

      files.slice(1, MAX_PREVIEW_ITEMS).forEach((file) => {
        const item = document.createElement('div');
        item.className = 'file-picker-thumb';
        appendFilePreview(item, input, file);
        previewEl.appendChild(item);
      });
    };

    const enhanceFileInput = (input) => {
      if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;
      if (input.dataset.filePickerEnhanced === '1') return;

      input.dataset.filePickerEnhanced = '1';
      input.classList.add('file-picker-input-hidden');

      const picker = document.createElement('div');
      picker.className = 'file-picker';

      const main = document.createElement('div');
      main.className = 'file-picker-main';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'file-picker-trigger';
      trigger.setAttribute('aria-label', 'Chọn tệp');
      trigger.textContent = '+';

      const meta = document.createElement('div');
      meta.className = 'file-picker-meta';
      meta.textContent = 'Chưa chọn tệp';

      const preview = document.createElement('div');
      preview.className = 'file-picker-preview';

      main.appendChild(trigger);
      main.appendChild(meta);
      picker.appendChild(main);
      picker.appendChild(preview);

      input.insertAdjacentElement('afterend', picker);

      const openPicker = () => input.click();
      trigger.addEventListener('click', openPicker);
      main.addEventListener('click', (event) => {
        if (trigger.contains(event.target)) return;
        openPicker();
      });
      input.addEventListener('change', () => renderPreview(input, meta, preview, trigger));

      renderPreview(input, meta, preview, trigger);
    };

    const enhanceAll = (root = document) => {
      root.querySelectorAll('input[type="file"]').forEach((input) => {
        enhanceFileInput(input);
      });
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => enhanceAll(document));
    } else {
      enhanceAll(document);
    }

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) return;
          if (node.matches && node.matches('input[type="file"]')) {
            enhanceFileInput(node);
            return;
          }
          enhanceAll(node);
        });
      });
    });

    if (document.body) {
      observer.observe(document.body, { childList: true, subtree: true });
    }
  })();
</script>
<?php if (($currentRoute ?? '') !== 'profile'): ?>
<style>
  .install-reminder-popup {
    position: fixed;
    left: 12px;
    right: 12px;
    bottom: calc(74px + env(safe-area-inset-bottom, 0px));
    z-index: 1700;
    max-width: 420px;
    margin: 0 auto;
    border: 1px solid #f59e0b;
    border-radius: 14px;
    background: #fffdf5;
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.22);
    padding: 12px;
  }
  .install-reminder-popup[hidden] { display: none !important; }
  .install-reminder-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #92400e;
  }
  .install-reminder-desc {
    margin: 6px 0 0;
    font-size: 13px;
    color: #4b5563;
    line-height: 1.45;
  }
  .install-reminder-actions {
    margin-top: 10px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .install-reminder-actions .btn {
    min-height: 34px;
    font-size: 12px;
    padding: 8px 11px;
  }
</style>
<div class="install-reminder-popup" id="installReminderPopup" hidden>
  <p class="install-reminder-title">Cài ứng dụng PhòngTrọ</p>
  <p class="install-reminder-desc">Nhấn Cài ngay để thêm ứng dụng ra màn hình chính.</p>
  <div class="install-reminder-actions">
    <button type="button" class="btn btn-primary btn-sm" id="installReminderBtn">Cài ngay</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="installReminderLaterBtn">Để sau</button>
  </div>
</div>
<script>
  (() => {
    const popup = document.getElementById('installReminderPopup');
    const installBtn = document.getElementById('installReminderBtn');
    const laterBtn = document.getElementById('installReminderLaterBtn');
    if (!popup || !installBtn || !laterBtn) return;

    const dismissKey = 'pwa_install_prompt_dismissed_at_v2';
    const installedKey = 'pwa_install_profile_installed_v1';
    const dismissCooldownMs = 5 * 60 * 1000;
    const isMobile = window.matchMedia('(max-width: 992px)').matches;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    const readNum = (key) => {
      try { return Number(localStorage.getItem(key) || '0'); } catch (e) { return 0; }
    };
    const setVal = (key, value) => {
      try { localStorage.setItem(key, value); } catch (e) {}
    };
    const isDismissedRecently = () => {
      const ts = readNum(dismissKey);
      return ts > 0 && (Date.now() - ts) < dismissCooldownMs;
    };
    const isInstalled = () => {
      if (isStandalone || window.__pwaInstalled === true) return true;
      try { return localStorage.getItem(installedKey) === '1'; } catch (e) { return false; }
    };

    const refresh = () => {
      const canInstall = !!window.__deferredInstallPrompt;
      popup.hidden = !(isMobile && !isInstalled() && !isDismissedRecently() && canInstall);
    };

    installBtn.addEventListener('click', async () => {
      const deferredPrompt = window.__deferredInstallPrompt;
      if (!deferredPrompt) return;
      try {
        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        if (choice?.outcome === 'accepted') {
          setVal(installedKey, '1');
          window.__deferredInstallPrompt = null;
          popup.hidden = true;
        }
      } catch (e) {
        // ignore
      }
    });

    laterBtn.addEventListener('click', () => {
      setVal(dismissKey, String(Date.now()));
      popup.hidden = true;
    });

    window.addEventListener('phongtro-install-available', refresh);
    window.addEventListener('phongtro-app-installed', () => {
      setVal(installedKey, '1');
      popup.hidden = true;
    });

    refresh();
    window.setTimeout(refresh, 1200);
    window.setInterval(refresh, 30 * 1000);
  })();
</script>
<?php endif; ?>
<style>
  html.browser-dark {
    color-scheme: dark;
    --bd-bg: #100d0a;
    --bd-bg-2: #17110c;
    --bd-surface: #1d1813;
    --bd-surface-2: #251d15;
    --bd-border: #473625;
    --bd-text: #fff6e7;
    --bd-muted: #cdbb9f;
    --bd-accent: #f59e0b;
    --bd-accent-deep: #b45309;
    --bd-accent-soft: #352414;
    --bd-success: #2f9e68;
  }
  html.browser-dark body {
    background:
      radial-gradient(90% 110% at 7% 8%, rgba(245,158,11,0.12), transparent 38%),
      radial-gradient(90% 120% at 94% 16%, rgba(180,83,9,0.11), transparent 44%),
      linear-gradient(180deg, #140f0a 0%, #0f0d0b 48%, #100d0a 100%),
      var(--bd-bg) !important;
    color: var(--bd-text) !important;
  }
  html.browser-dark body::before,
  html.browser-dark body::after {
    opacity: 0.105 !important;
    filter: sepia(0.45) saturate(1.05) brightness(1.15) contrast(0.92);
  }
  html.browser-dark .navbar {
    background: linear-gradient(90deg, #fbbf24, #f59e0b) !important;
    color: #431407 !important;
    color-scheme: only light;
    forced-color-adjust: none;
    box-shadow: 0 12px 28px rgba(67, 20, 7, 0.20) !important;
    border-bottom: 1px solid rgba(124,45,18,0.22) !important;
  }
  html.browser-dark .brand small,
  html.browser-dark .text-muted,
  html.browser-dark small {
    color: var(--bd-muted) !important;
  }
  html.browser-dark .navbar .brand,
  html.browser-dark .navbar .brand .brand-text > div {
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
    font-weight: 900 !important;
    opacity: 1 !important;
    text-shadow: none !important;
  }
  html.browser-dark .navbar .brand small {
    color: #7c2d12 !important;
    -webkit-text-fill-color: #7c2d12 !important;
    font-weight: 800 !important;
    opacity: 1 !important;
    text-shadow: none !important;
  }
  html.browser-dark .navbar .nav-link {
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
  }
  html.browser-dark .navbar .nav-link:hover {
    background: #fff7ed !important;
    border-color: rgba(124,45,18,0.42) !important;
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
  }
  html.browser-dark .navbar .nav-link.active {
    background: #7c2d12 !important;
    border-color: rgba(67,20,7,0.52) !important;
    color: #fff7ed !important;
    -webkit-text-fill-color: #fff7ed !important;
  }
  html.browser-dark .nav-search-form {
    background: #fff7ed !important;
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
    border-color: rgba(124,45,18,0.34) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.72), 0 8px 16px rgba(67,20,7,0.14) !important;
  }
  html.browser-dark .nav-search-form input::placeholder {
    color: #7c2d12 !important;
    -webkit-text-fill-color: #7c2d12 !important;
  }
  html.browser-dark .navbar .btn-outline,
  html.browser-dark .navbar .btn-outline-secondary {
    border-color: rgba(124,45,18,0.48) !important;
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
    background: rgba(255,247,237,0.16) !important;
  }
  html.browser-dark .navbar .btn-outline:hover,
  html.browser-dark .navbar .btn-outline-secondary:hover {
    background: rgba(255,247,237,0.32) !important;
  }
  html.browser-dark .desktop-nav-panel {
    background: #fff !important;
    border-color: rgba(124,45,18,0.26) !important;
    box-shadow: 0 20px 42px rgba(67,20,7,0.20) !important;
  }
  html.browser-dark .desktop-nav-item {
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
  }
  html.browser-dark .desktop-nav-item:hover {
    background: #fff7ed !important;
    border-color: rgba(124,45,18,0.3) !important;
  }
  html.browser-dark .desktop-nav-item.is-active {
    background: #7c2d12 !important;
    border-color: rgba(67,20,7,0.52) !important;
    color: #fff7ed !important;
    -webkit-text-fill-color: #fff7ed !important;
  }
  html.browser-dark .mode-toggle {
    border-color: rgba(124,45,18,0.48) !important;
    background: rgba(255,247,237,0.2) !important;
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
  }
  html.browser-dark .mode-toggle:hover {
    background: rgba(255,247,237,0.35) !important;
  }
  html.browser-dark .mode-toggle-track {
    background: #cbd5e1 !important;
  }
  html.browser-dark .mode-toggle.is-on .mode-toggle-track {
    background: linear-gradient(135deg, #22c55e, #16a34a) !important;
  }
  html.browser-dark .mobile-mode-toggle {
    border-color: rgba(124,45,18,0.4) !important;
    background: rgba(255,247,237,0.88) !important;
    color: #7c2d12 !important;
  }
  html.browser-dark .mobile-mode-toggle.is-on {
    border-color: #84cc16 !important;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0) !important;
    color: #166534 !important;
  }
  html.browser-dark .navbar .btn-primary,
  html.browser-dark .navbar .btn-success {
    background: linear-gradient(135deg, var(--bd-accent), var(--bd-accent-deep)) !important;
    border-color: #be6b0a !important;
    box-shadow: 0 10px 22px rgba(217, 119, 6, 0.28) !important;
  }
  html.browser-dark .navbar .text-muted,
  html.browser-dark .navbar .nav-link,
  html.browser-dark .navbar .nav-actions .btn {
    opacity: 1 !important;
    font-weight: 800 !important;
    text-shadow: none !important;
  }
  html.browser-dark .navbar .text-muted {
    color: #431407 !important;
    -webkit-text-fill-color: #431407 !important;
  }
  html.browser-dark .card,
  html.browser-dark .chat-pane,
  html.browser-dark .chat-panel,
  html.browser-dark .admin-menu,
  html.browser-dark .install-reminder-popup,
  html.browser-dark .profile-install-card,
  html.browser-dark .notify-card,
  html.browser-dark .cta-modal {
    background: var(--bd-surface) !important;
    color: var(--bd-text) !important;
    border-color: var(--bd-border) !important;
    box-shadow: 0 18px 42px rgba(0,0,0,0.46) !important;
  }
  html.browser-dark .admin-menu a {
    background: var(--bd-surface-2) !important;
    color: #f3f4f6 !important;
    border-color: var(--bd-border) !important;
  }
  html.browser-dark .admin-menu a::before {
    background: linear-gradient(135deg, #3a2918, #4d3219) !important;
    color: #ffd99a !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 8px 16px rgba(0,0,0,0.18) !important;
  }
  html.browser-dark .admin-menu a.active {
    background: linear-gradient(135deg, var(--bd-accent), var(--bd-accent-deep)) !important;
    color: #fff !important;
    border-color: #f1b45a !important;
    box-shadow: 0 10px 24px rgba(217,119,6,0.35) !important;
  }
  html.browser-dark .admin-menu a.active::before {
    background: rgba(255,244,223,0.18) !important;
    color: #fff7ed !important;
  }
  html.browser-dark .admin-menu a:hover {
    background: #2c2117 !important;
    border-color: rgba(245,158,11,0.32) !important;
    color: #fff !important;
  }
  html.browser-dark .table th,
  html.browser-dark .table td {
    border-bottom-color: #2d3240 !important;
  }
  html.browser-dark .table th {
    color: #dce1ea !important;
  }
  html.browser-dark .form-control,
  html.browser-dark select,
  html.browser-dark textarea,
  html.browser-dark input[type=text],
  html.browser-dark input[type=tel],
  html.browser-dark input[type=number],
  html.browser-dark input[type=password],
  html.browser-dark input[type=email] {
    background: #15110d !important;
    color: #f8fafc !important;
    border-color: var(--bd-border) !important;
  }
  html.browser-dark .form-control::placeholder,
  html.browser-dark textarea::placeholder,
  html.browser-dark input::placeholder {
    color: var(--bd-muted) !important;
  }
  html.browser-dark .btn-primary,
  html.browser-dark .btn-success {
    background: linear-gradient(135deg, var(--bd-accent), var(--bd-accent-deep)) !important;
    border-color: #be6b0a !important;
    color: #fff !important;
    box-shadow: 0 12px 26px rgba(217,119,6,0.36) !important;
  }
  html.browser-dark .btn-primary:hover,
  html.browser-dark .btn-success:hover {
    background: linear-gradient(135deg, #cf740c, #92400e) !important;
  }
  html.browser-dark .btn-outline,
  html.browser-dark .btn-outline-secondary {
    color: #e8ecf3 !important;
    border-color: #596173 !important;
    background: transparent !important;
  }
  html.browser-dark .btn-outline:hover,
  html.browser-dark .btn-outline-secondary:hover {
    background: #2b2118 !important;
  }
  html.browser-dark .alert-success {
    background: #122b20 !important;
    color: #9ee6bd !important;
    border-color: var(--bd-success) !important;
  }
  html.browser-dark .alert-danger {
    background: #432027 !important;
    color: #fecaca !important;
    border-color: #7f1d1d !important;
  }
  html.browser-dark .alert-info {
    background: var(--bd-accent-soft) !important;
    color: #f7d9ad !important;
    border-color: #7f5e36 !important;
  }
  html.browser-dark .hero {
    background:
      radial-gradient(circle at 18% 20%, rgba(255,214,148,0.18), transparent 32%),
      linear-gradient(135deg, #b8650d 0%, #8a4107 52%, #5b2b08 100%) !important;
    border: 1px solid rgba(245,158,11,0.24) !important;
    box-shadow: 0 20px 44px rgba(0,0,0,0.38), inset 0 1px 0 rgba(255,255,255,0.08) !important;
  }
  html.browser-dark .hero::after {
    background:
      radial-gradient(circle at 24% 18%, rgba(255,235,190,0.13), transparent 34%),
      radial-gradient(circle at 86% 0%, rgba(245,158,11,0.12), transparent 36%) !important;
    opacity: 0.72;
  }
  html.browser-dark .chat-list {
    background: linear-gradient(180deg, #332111, #1c1510) !important;
    color: #f9ecd8 !important;
  }
  html.browser-dark .chat-list .head {
    color: #ffe4bf !important;
  }
  html.browser-dark .chat-search input {
    background: #17110c !important;
    color: #f8e2c1 !important;
    border-color: #61462a !important;
  }
  html.browser-dark .chat-item:hover {
    background: rgba(245,158,11,0.12) !important;
  }
  html.browser-dark .chat-item.active {
    background: #8a4f0f !important;
    color: #fff !important;
  }
  html.browser-dark .chat-item .preview,
  html.browser-dark .chat-item .time {
    color: #e8d0ad !important;
  }
  html.browser-dark .chat-item .avatar {
    background: #21160d !important;
  }
  html.browser-dark .chat-thread,
  html.browser-dark .chat-messages {
    background: #12100d !important;
  }
  html.browser-dark .chat-input {
    background: #18130f !important;
    border-top-color: var(--bd-border) !important;
  }
  html.browser-dark .bubble.mine {
    background: var(--bd-accent-deep) !important;
    color: #fff !important;
  }
  html.browser-dark .bubble.theirs {
    background: #221b15 !important;
    color: #eef1f7 !important;
    border-color: #444036 !important;
  }
  html.browser-dark .mobile-search-input {
    background: #2b1d10 !important;
    color: #f3e6d3 !important;
    border-color: #6e4b2b !important;
    box-shadow: none !important;
  }
  html.browser-dark .mobile-search-input .icon {
    background: #332415 !important;
    color: var(--bd-accent) !important;
    box-shadow: none !important;
  }
  html.browser-dark .mobile-mini-btn,
  html.browser-dark .mobile-chat-btn {
    background: #22170f !important;
    color: #f3e8d8 !important;
    border-color: #6e4b2b !important;
    box-shadow: none !important;
  }
  html.browser-dark .mobile-tabbar {
    background: #15110d !important;
    border-top-color: rgba(245,158,11,0.22) !important;
    box-shadow: 0 -10px 26px rgba(0,0,0,0.45) !important;
  }
  html.browser-dark .mobile-tabbar .tab-item {
    color: #b4bcc9 !important;
  }
  html.browser-dark .mobile-tabbar .tab-item.active {
    color: var(--bd-accent) !important;
  }
  html.browser-dark .site-footer {
    background: var(--bd-bg-2) !important;
    color: #cfd5df !important;
    border-top: 1px solid #312c25 !important;
  }
  html.browser-dark .room-detail-shell .room-card,
  html.browser-dark .room-detail-shell > .card {
    background: rgba(18, 15, 11, 0.92) !important;
    color: var(--bd-text) !important;
    border: 1px solid rgba(245,158,11,0.16) !important;
  }
  html.browser-dark .room-detail-shell .card-body,
  html.browser-dark .room-detail-shell p,
  html.browser-dark .room-detail-shell h1,
  html.browser-dark .room-detail-shell h2,
  html.browser-dark .room-detail-shell h3,
  html.browser-dark .room-detail-shell strong,
  html.browser-dark .room-detail-shell label {
    color: var(--bd-text) !important;
  }
  html.browser-dark .room-detail-shell .text-muted,
  html.browser-dark .room-detail-shell .subtle,
  html.browser-dark .room-detail-shell .trust-list,
  html.browser-dark .room-detail-shell .trust-list li,
  html.browser-dark .room-detail-shell .room-address {
    color: var(--bd-muted) !important;
  }
  html.browser-dark .room-detail-shell .chip,
  html.browser-dark .room-detail-shell .chip-lite {
    background: rgba(245,158,11,0.13) !important;
    color: #ffe4b8 !important;
    border-color: rgba(245,158,11,0.34) !important;
  }
  html.browser-dark .room-detail-shell .meta-item,
  html.browser-dark .room-detail-shell .social-proof {
    background: rgba(255,244,223,0.08) !important;
    color: #f9ead0 !important;
    border: 1px solid rgba(245,158,11,0.22) !important;
  }
  html.browser-dark .room-detail-shell .lead-pane {
    background:
      radial-gradient(circle at 18% 12%, rgba(245,158,11,0.18), transparent 34%),
      linear-gradient(145deg, #23180f 0%, #1b1510 54%, #120f0b 100%) !important;
    color: var(--bd-text) !important;
    border: 1px solid rgba(245,158,11,0.28) !important;
    box-shadow: 0 22px 44px rgba(0,0,0,0.42), inset 0 1px 0 rgba(255,255,255,0.05) !important;
  }
  html.browser-dark .room-detail-shell .lead-pane::after {
    background:
      radial-gradient(circle at 20% 18%, rgba(245,158,11,0.14), transparent 40%),
      radial-gradient(circle at 85% 10%, rgba(180,83,9,0.12), transparent 34%) !important;
    opacity: 0.8;
  }
  html.browser-dark .room-detail-shell .lead-chip,
  html.browser-dark .room-detail-shell .badge-fomo {
    background: rgba(245,158,11,0.14) !important;
    color: #ffd99a !important;
    border-color: rgba(245,158,11,0.36) !important;
  }
  html.browser-dark .room-detail-shell .lead-pane [style*="background:#f8fafc"],
  html.browser-dark .room-detail-shell .lead-pane [style*="background: #f8fafc"] {
    background: rgba(255,244,223,0.08) !important;
    color: #ffe8c2 !important;
    border-color: rgba(245,158,11,0.32) !important;
  }
  html.browser-dark .room-detail-shell .lead-pane .badge {
    background: rgba(47,158,104,0.16) !important;
    color: #a7f3c6 !important;
    border-color: rgba(47,158,104,0.34) !important;
  }
  html.browser-dark .room-detail-shell .thumb-strip-wrap {
    background: rgba(18,15,11,0.84) !important;
  }
  html.browser-dark .room-detail-shell .thumb-strip img {
    border-color: rgba(255,244,223,0.10) !important;
    background: #120f0b !important;
  }
  html.browser-dark .room-detail-shell .thumb-strip img.active {
    border-color: var(--bd-accent) !important;
  }
  html.browser-dark .room-detail-shell .card[style*="dashed"] {
    background: rgba(255,244,223,0.06) !important;
    border-color: rgba(245,158,11,0.28) !important;
  }
</style>
<script>
  (() => {
    const root = document.documentElement;
    const getBody = () => document.body;

    const hasProcessedMarker = (el) => {
      if (!el || !el.attributes) return false;
      return Array.from(el.attributes).some((attr) => String(attr.name || '').startsWith('_processed_'));
    };

    const hasKnownForcedDarkAttrs = () => {
      const body = getBody();
      return root.hasAttribute('data-darkreader-mode')
        || root.hasAttribute('data-darkreader-scheme')
        || hasProcessedMarker(root)
        || hasProcessedMarker(body);
    };

    const withBrowserDarkTemporarilyDisabled = (fn) => {
      const hadClass = root.classList.contains('browser-dark');
      if (hadClass) {
        root.classList.remove('browser-dark');
      }
      try {
        return fn();
      } finally {
        if (hadClass) {
          root.classList.add('browser-dark');
        }
      }
    };

    const looksDarkByComputedStyle = () => {
      return withBrowserDarkTemporarilyDisabled(() => {
        const body = getBody();
        if (!body || typeof window.getComputedStyle !== 'function') return false;
        const bg = String(window.getComputedStyle(body).backgroundColor || '');
        const m = bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
        if (!m) return false;
        const r = Number(m[1] || 0);
        const g = Number(m[2] || 0);
        const b = Number(m[3] || 0);
        const luminance = (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
        return luminance < 70;
      });
    };

    const syncBrowserDarkClass = () => {
      const enabled = hasKnownForcedDarkAttrs() || looksDarkByComputedStyle();
      root.classList.toggle('browser-dark', enabled);
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', syncBrowserDarkClass, { once: true });
    }
    syncBrowserDarkClass();

    const observer = new MutationObserver(syncBrowserDarkClass);
    observer.observe(root, { attributes: true, subtree: true });
    if (getBody()) {
      observer.observe(getBody(), { attributes: true, subtree: true, childList: true });
    }

    window.setInterval(syncBrowserDarkClass, 1200);
  })();
</script>

<?php if (!empty($user) && $user['role'] === 'admin'): ?>
<style>
  .admin-chat-btn {
    position: fixed;
    right: 18px;
    bottom: 18px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d97706, #b45309);
    color: #fff;
    border: none;
    box-shadow: 0 18px 36px rgba(217,119,6,0.35);
    cursor: pointer;
    z-index: 1500;
    font-size: 22px;
    display:flex;align-items:center;justify-content:center;
  }
  @media (max-width: 768px) {
    .admin-chat-btn {
      display: none;
    }
  }
</style>
<button class="admin-chat-btn" id="adminChatBtn" aria-label="Tin nhắn với người dùng">💬</button>
<script>
  document.getElementById('adminChatBtn')?.addEventListener('click', () => {
    window.location = '?route=admin-messages';
  });
</script>
<?php endif; ?>
</body>
</html>
  
