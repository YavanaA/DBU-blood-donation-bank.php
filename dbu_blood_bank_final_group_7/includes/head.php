<?php
if (!isset($page_title)) $page_title = 'DBU Blood Bank';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> — DBU Blood Bank</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<style>
  :root {
    --dbu-blue:    #003087;
    --dbu-blue2:   #1e40af;
    --dbu-red:     #CC0000;
    --dbu-gold:    #F59E0B;
    --dbu-dark:    #0d1b2a;
    --dbu-light:   #f5f7fa;
    --dbu-emerald: #059669;
    --dbu-teal:    #0891b2;
    --dbu-purple:  #7c3aed;
    --dbu-violet:  #6d28d9;
    --dbu-rose:    #e11d48;
    --dbu-pink:    #db2777;
    --dbu-orange:  #ea580c;
    --dbu-amber:   #d97706;
    --dbu-indigo:  #4338ca;
    --dbu-lime:    #65a30d;
    --dbu-cyan:    #0e7490;
  }

  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f5f7fa;
  }

  ::-webkit-scrollbar { width: 8px; }
  ::-webkit-scrollbar-track { background: #e8ecf5; }
  ::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, var(--dbu-blue), var(--dbu-purple));
    border-radius: 10px;
  }

  a { color: var(--dbu-blue); }
  a:hover { color: var(--dbu-purple); }

  .btn-dbu-red     { background: var(--dbu-red);    color:#fff; border:none; }
  .btn-dbu-red:hover   { background:#a80000; color:#fff; }
  .btn-dbu-blue    { background: var(--dbu-blue);   color:#fff; border:none; }
  .btn-dbu-blue:hover  { background:#001d6e; color:#fff; }
  .btn-dbu-emerald { background: var(--dbu-emerald);color:#fff; border:none; }
  .btn-dbu-emerald:hover { background:#047857; color:#fff; }
  .btn-dbu-purple  { background: var(--dbu-purple); color:#fff; border:none; }
  .btn-dbu-purple:hover  { background:#5b21b6; color:#fff; }
  .btn-dbu-teal    { background: var(--dbu-teal);   color:#fff; border:none; }
  .btn-dbu-teal:hover    { background:#0e7490; color:#fff; }
  .btn-dbu-orange  { background: var(--dbu-orange); color:#fff; border:none; }
  .btn-dbu-orange:hover  { background:#c2410c; color:#fff; }

  .btn-grad-primary {
    background: linear-gradient(135deg, var(--dbu-blue), var(--dbu-purple));
    color: #fff; border: none;
    transition: .2s;
  }
  .btn-grad-primary:hover { opacity:.88; color:#fff; }
  .btn-grad-danger {
    background: linear-gradient(135deg, var(--dbu-red), var(--dbu-rose));
    color: #fff; border: none;
  }
  .btn-grad-danger:hover { opacity:.88; color:#fff; }
  .btn-grad-success {
    background: linear-gradient(135deg, var(--dbu-emerald), var(--dbu-teal));
    color: #fff; border: none;
  }
  .btn-grad-success:hover { opacity:.88; color:#fff; }

  .badge-blood  { background: rgba(204,0,0,.13); color: var(--dbu-red); }
  .badge-blue   { background: rgba(0,48,135,.12); color: var(--dbu-blue); }
  .badge-purple { background: rgba(124,58,237,.12); color: var(--dbu-purple); }
  .badge-emerald{ background: rgba(5,150,105,.12); color: var(--dbu-emerald); }
  .badge-teal   { background: rgba(8,145,178,.12); color: var(--dbu-teal); }
  .badge-gold   { background: rgba(217,119,6,.13); color: var(--dbu-amber); }

  .section-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: var(--dbu-blue);
    letter-spacing: -.3px;
  }
  .section-title-light { font-size: 1.65rem; font-weight: 800; color:#fff; }
  .gradient-heading {
    background: linear-gradient(135deg, var(--dbu-blue) 0%, var(--dbu-purple) 55%, var(--dbu-rose) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .card-dbu {
    border: none;
    border-radius: 18px;
    box-shadow: 0 4px 24px rgba(0,48,135,.08);
    transition: box-shadow .25s, transform .25s;
    background: #fff;
  }
  .card-dbu:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0,48,135,.14);
  }

  .card-accent { border-radius: 18px; border: none; background:#fff; }
  .ca-red     { border-top: 5px solid var(--dbu-red); }
  .ca-blue    { border-top: 5px solid var(--dbu-blue); }
  .ca-emerald { border-top: 5px solid var(--dbu-emerald); }
  .ca-purple  { border-top: 5px solid var(--dbu-purple); }
  .ca-gold    { border-top: 5px solid var(--dbu-gold); }
  .ca-teal    { border-top: 5px solid var(--dbu-teal); }
  .ca-orange  { border-top: 5px solid var(--dbu-orange); }
  .ca-rose    { border-top: 5px solid var(--dbu-rose); }
  .ca-indigo  { border-top: 5px solid var(--dbu-indigo); }
  .ca-pink    { border-top: 5px solid var(--dbu-pink); }

  .dbu-hero  { background: linear-gradient(135deg, #003087 0%, #2d1b69 45%, #7c3aed 75%, #CC0000 100%); }
  .dbu-hero-teal  { background: linear-gradient(135deg, #0d1b2a 0%, #003087 50%, #0891b2 100%); }
  .dbu-hero-emerald{ background: linear-gradient(135deg, #064e3b 0%, #059669 60%, #0891b2 100%); }

  .bg-grad-blue-purple {
    background: linear-gradient(135deg, var(--dbu-blue) 0%, var(--dbu-purple) 100%);
  }
  .bg-grad-red-rose {
    background: linear-gradient(135deg, var(--dbu-red) 0%, var(--dbu-rose) 100%);
  }
  .bg-grad-emerald-teal {
    background: linear-gradient(135deg, var(--dbu-emerald) 0%, var(--dbu-teal) 100%);
  }
  .bg-grad-orange-amber {
    background: linear-gradient(135deg, var(--dbu-orange) 0%, var(--dbu-amber) 100%);
  }
  .bg-grad-purple-indigo {
    background: linear-gradient(135deg, var(--dbu-purple) 0%, var(--dbu-indigo) 100%);
  }
  .bg-grad-dark {
    background: linear-gradient(135deg, #0d1b2a 0%, #1a2742 100%);
  }

  .bg-soft-blue   { background: linear-gradient(135deg, #eff6ff, #e0e7ff); }
  .bg-soft-purple { background: linear-gradient(135deg, #f5f3ff, #ede9fe); }
  .bg-soft-rose   { background: linear-gradient(135deg, #fff1f2, #fce7f3); }
  .bg-soft-emerald{ background: linear-gradient(135deg, #ecfdf5, #d1fae5); }
  .bg-soft-gold   { background: linear-gradient(135deg, #fffbeb, #fef3c7); }
  .bg-soft-teal   { background: linear-gradient(135deg, #ecfeff, #cffafe); }
  .bg-soft-mix    { background: linear-gradient(160deg, #eff6ff 0%, #fdf4ff 50%, #ecfdf5 100%); }

  .icon-circle {
    width: 60px; height: 60px;
    border-radius: 50%;
    display: flex; align-items: center;
    justify-content: center; font-size: 24px;
    margin: 0 auto 14px; flex-shrink: 0;
  }
  .ic-red     { background: linear-gradient(135deg,rgba(204,0,0,.15),rgba(225,29,72,.1)); }
  .ic-blue    { background: linear-gradient(135deg,rgba(0,48,135,.12),rgba(67,56,202,.08)); }
  .ic-emerald { background: linear-gradient(135deg,rgba(5,150,105,.14),rgba(8,145,178,.08)); }
  .ic-purple  { background: linear-gradient(135deg,rgba(124,58,237,.14),rgba(109,40,217,.08)); }
  .ic-gold    { background: linear-gradient(135deg,rgba(217,119,6,.14),rgba(245,158,11,.1)); }
  .ic-teal    { background: linear-gradient(135deg,rgba(8,145,178,.14),rgba(14,116,144,.08)); }
  .ic-orange  { background: linear-gradient(135deg,rgba(234,88,12,.14),rgba(217,119,6,.1)); }
  .ic-rose    { background: linear-gradient(135deg,rgba(225,29,72,.13),rgba(219,39,119,.08)); }
  .ic-indigo  { background: linear-gradient(135deg,rgba(67,56,202,.13),rgba(124,58,237,.08)); }
  .ic-lime    { background: linear-gradient(135deg,rgba(101,163,13,.13),rgba(5,150,105,.08)); }

  .glass {
    background: rgba(255,255,255,.14);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.26);
    border-radius: 16px;
  }
  .glass-dark {
    background: rgba(0,0,0,.18);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 16px;
  }

  input:focus, select:focus, textarea:focus {
    border-color: var(--dbu-purple) !important;
    box-shadow: 0 0 0 3px rgba(124,58,237,.18) !important;
  }

  .navbar .nav-link { font-size: .9rem; }
  .navbar .nav-link.active { color: #FFD700 !important; font-weight: 700; }

  .dropdown-item.active, .dropdown-item:active { background: var(--dbu-purple); }

  .stat-pill {
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 16px; padding: 20px 14px; text-align: center;
  }

  @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }
  .anim-pulse { animation: pulse 2.2s ease-in-out infinite; }

  @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
  .fade-up { animation: fadeUp .6s ease both; }
</style>
