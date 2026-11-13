<?php
// main/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#087f8c">
    <title>Anjungan Mandiri IGD - RSUD / RSI Jaya Utama</title>

    <!-- Font & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root{
            --primary:#087f8c;
            --primary-dark:#05616c;
            --primary-soft:#e8f8fa;
            --cyan:#14b8a6;
            --navy:#103b4a;
            --text:#183642;
            --muted:#6b8189;
            --white:#ffffff;
            --line:#dcebed;
            --bg:#f2f9fa;
            --success:#16a66a;
            --shadow:0 24px 70px rgba(16,59,74,.12);
            --shadow-soft:0 12px 35px rgba(16,59,74,.09);
        }

        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{
            margin:0;
            min-height:100vh;
            font-family:'Plus Jakarta Sans',sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at 8% 12%, rgba(20,184,166,.13), transparent 28%),
                radial-gradient(circle at 92% 18%, rgba(8,127,140,.12), transparent 30%),
                linear-gradient(135deg,#f8ffff 0%,#eef8fa 52%,#f7fbfc 100%);
            overflow-x:hidden;
        }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            opacity:.32;
            background-image:
                linear-gradient(rgba(8,127,140,.035) 1px,transparent 1px),
                linear-gradient(90deg,rgba(8,127,140,.035) 1px,transparent 1px);
            background-size:42px 42px;
            mask-image:linear-gradient(to bottom,black,transparent 88%);
        }

        a{text-decoration:none;color:inherit}
        button{font:inherit}

        /* HEADER */
        .topbar{
            position:sticky;
            top:0;
            z-index:100;
            background:rgba(255,255,255,.88);
            backdrop-filter:blur(18px);
            -webkit-backdrop-filter:blur(18px);
            border-bottom:1px solid rgba(220,235,237,.85);
        }

        .topbar-inner{
            width:min(1240px,calc(100% - 40px));
            margin:auto;
            min-height:84px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:24px;
        }

        .brand{display:flex;align-items:center;gap:14px}
        .brand-logo{
            width:52px;height:52px;
            border-radius:17px;
            display:grid;place-items:center;
            color:white;
            font-size:22px;
            background:linear-gradient(145deg,var(--primary),var(--cyan));
            box-shadow:0 10px 25px rgba(8,127,140,.25);
            position:relative;
            overflow:hidden;
        }

        .brand-logo::after{
            content:"";
            position:absolute;
            width:70px;height:18px;
            background:rgba(255,255,255,.18);
            transform:rotate(-35deg);
            top:-10px;left:-20px;
        }

        .brand-name{
            margin:0;
            font-size:16px;
            font-weight:800;
            letter-spacing:.05em;
            color:var(--navy);
        }

        .brand-sub{
            margin:4px 0 0;
            font-size:11px;
            color:var(--muted);
            font-weight:600;
        }

        .header-tools{display:flex;align-items:center;gap:10px}
        .status,.clock{
            display:flex;align-items:center;gap:9px;
            min-height:40px;
            padding:0 14px;
            border:1px solid var(--line);
            border-radius:13px;
            background:rgba(255,255,255,.72);
            font-size:11px;
            font-weight:700;
        }

        .status{color:var(--success)}
        .pulse{
            width:9px;height:9px;
            border-radius:50%;
            background:#20c47b;
            box-shadow:0 0 0 0 rgba(32,196,123,.5);
            animation:pulse 1.8s infinite;
        }

        .clock{color:var(--navy);font-variant-numeric:tabular-nums}

        /* TICKER */
        .ticker{
            background:linear-gradient(90deg,var(--primary-dark),var(--primary),#0c9c9a);
            color:white;
            min-height:43px;
            display:flex;
            align-items:center;
            overflow:hidden;
            box-shadow:0 5px 20px rgba(8,127,140,.12);
        }

        .ticker-inner{
            width:min(1240px,calc(100% - 40px));
            margin:auto;
            display:flex;
            align-items:center;
            gap:13px;
            white-space:nowrap;
        }

        .ticker-label{
            flex:0 0 auto;
            display:flex;align-items:center;gap:7px;
            padding:6px 10px;
            border-radius:9px;
            background:rgba(255,255,255,.16);
            font-size:9px;
            font-weight:800;
            letter-spacing:.12em;
            text-transform:uppercase;
        }

        .ticker-window{overflow:hidden;flex:1}
        .ticker-text{
            display:inline-block;
            padding-left:100%;
            animation:marquee 24s linear infinite;
            font-size:11px;
            font-weight:600;
        }

        /* HERO */
        .page{
            width:min(1240px,calc(100% - 40px));
            margin:auto;
        }

        .hero{
            min-height:calc(100vh - 127px);
            display:grid;
            grid-template-columns:1fr 1.05fr;
            align-items:center;
            gap:58px;
            padding:55px 0 48px;
        }

        .eyebrow{
            display:inline-flex;
            align-items:center;
            gap:8px;
            color:var(--primary-dark);
            background:var(--primary-soft);
            border:1px solid #ccecef;
            border-radius:999px;
            padding:8px 13px;
            font-size:10px;
            font-weight:800;
            letter-spacing:.12em;
            text-transform:uppercase;
            animation:fadeUp .7s ease both;
        }

        .eyebrow i{font-size:11px}

        .hero h2.welcome-title{
            margin:20px 0 16px;
            max-width:760px;
            color:var(--navy);
            animation:fadeUp .7s .08s ease both;
            letter-spacing:-.035em;
        }

        .welcome-small{
            display:block;
            margin-bottom:7px;
            font-size:clamp(23px,2.4vw,34px);
            line-height:1.2;
            font-weight:700;
            letter-spacing:-.025em;
            color:#42636d;
        }

        .welcome-main{
            display:block;
            font-size:clamp(39px,4.7vw,64px);
            line-height:1.04;
            font-weight:800;
            letter-spacing:-.045em;
            white-space:nowrap;
            background:linear-gradient(100deg,var(--primary-dark),var(--primary),var(--cyan));
            -webkit-background-clip:text;
            background-clip:text;
            -webkit-text-fill-color:transparent;
            color:transparent;
        }

        .hero-description{
            max-width:590px;
            margin:0;
            color:var(--muted);
            font-size:15px;
            line-height:1.85;
            animation:fadeUp .7s .16s ease both;
        }

        /* QUICK INFO */
        .quick-info{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:18px;
            margin:32px 0;
            animation:fadeUp .7s .24s ease both;
        }

        .quick-card{
            padding:22px 20px;
            border:2px solid rgba(8,127,140,.2);
            border-radius:24px;
            background:rgba(255,255,255,.95);
            box-shadow:0 15px 40px rgba(16,59,74,.1);
            transition:.25s ease;
        }

        .quick-card:hover{
            transform:translateY(-5px);
            border-color:var(--primary);
            box-shadow:0 20px 50px rgba(8,127,140,.18);
        }

        .quick-icon{
            width:52px;height:52px;
            display:grid;place-items:center;
            border-radius:16px;
            color:white;
            background:linear-gradient(145deg,var(--primary),var(--cyan));
            font-size:22px;
            margin-bottom:14px;
            box-shadow:0 8px 20px rgba(8,127,140,.25);
        }

        .quick-card strong{
            display:block;
            font-size:16px;
            font-weight:800;
            color:var(--navy);
            letter-spacing:-.01em;
        }

        .quick-card small{
            display:block;
            margin-top:6px;
            font-size:12px;
            line-height:1.5;
            color:var(--muted);
            font-weight:600;
        }

        /* MAIN ACTION CARD */
        .action-card{
            position:relative;
            padding:42px 40px;
            border:2px solid rgba(8,127,140,.25);
            border-radius:36px;
            background:rgba(255,255,255,.98);
            box-shadow:0 30px 90px rgba(16,59,74,.18);
            overflow:hidden;
            animation:fadeUp .7s .3s ease both;
        }

        .action-card::before{
            content:"";
            position:absolute;
            width:300px;height:300px;
            border-radius:50%;
            background:rgba(20,184,166,.12);
            right:-120px;top:-130px;
        }

        .action-inner{
            position:relative;
            z-index:1;
            display:flex;
            align-items:center;
            gap:28px;
        }

        .action-icon{
            width:110px;height:110px;
            flex:0 0 110px;
            border-radius:30px;
            display:grid;place-items:center;
            color:white;
            font-size:46px;
            background:linear-gradient(145deg,var(--primary),var(--cyan));
            box-shadow:0 20px 45px rgba(8,127,140,.32);
        }

        .action-title{
            margin:0 0 10px;
            font-size:28px;
            font-weight:800;
            color:var(--navy);
            letter-spacing:-.02em;
        }

        .action-text{
            margin:0;
            color:var(--muted);
            font-size:16px;
            line-height:1.75;
        }

        .start-button{
            width:100%;
            margin-top:30px;
            border:0;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:18px;
            min-height:86px;
            border-radius:24px;
            color:white;
            background:linear-gradient(100deg,var(--primary-dark),var(--primary),#0c9c9a);
            font-size:22px;
            font-weight:800;
            box-shadow:0 20px 45px rgba(8,127,140,.38);
            transition:.25s ease;
            position:relative;
            overflow:hidden;
        }

        .start-button::before{
            content:"";
            position:absolute;
            top:0;left:-110%;
            width:60%;
            height:100%;
            transform:skewX(-20deg);
            background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);
            transition:.7s ease;
        }

        .start-button:hover{
            transform:translateY(-3px);
            box-shadow:0 24px 50px rgba(8,127,140,.44);
        }

        .start-button:hover::before{left:150%}
        .start-button i{transition:.25s ease;font-size:24px}
        .start-button:hover i{transform:translateX(6px)}

        .privacy-note{
            margin:18px 0 0;
            text-align:center;
            color:#8aa0a7;
            font-size:11px;
        }

        .privacy-note i{color:var(--success);margin-right:5px}

        /* MEDICAL ILLUSTRATION (DIPERBESAR & DIPROPORSIKAN) */
        .visual{
            position:relative;
            min-height:600px;
            display:grid;
            place-items:center;
            animation:fadeIn 1s .25s ease both;
        }

        .visual-orb{
            position:absolute;
            width:min(550px,90vw);
            aspect-ratio:1;
            border-radius:50%;
            background:
                radial-gradient(circle at 50% 50%,rgba(255,255,255,.9) 0 38%,transparent 39%),
                linear-gradient(145deg,#dff8f7,#eaf6fb);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.9),0 40px 80px rgba(8,127,140,.14);
        }

        .visual-ring{
            position:absolute;
            width:480px;
            height:480px;
            border:1px dashed rgba(8,127,140,.24);
            border-radius:50%;
            animation:spin 32s linear infinite;
        }

        .medical-scene{
            position:relative;
            z-index:2;
            width:min(490px,88vw);
            aspect-ratio:1/1;
            border-radius:48px;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            background:linear-gradient(145deg,rgba(255,255,255,.9),rgba(233,249,250,.8));
            border:1px solid rgba(255,255,255,.98);
            box-shadow:0 32px 70px rgba(16,59,74,.16);
        }

        .scene-glow{
            position:absolute;
            width:210px;height:210px;
            border-radius:50%;
            background:rgba(20,184,166,.16);
            filter:blur(8px);
        }

        .scene-content{
            position:relative;
            z-index:2;
            text-align:center;
            padding:0 24px;
        }

        .scene-cross{
            width:130px;height:130px;
            margin:auto;
            border-radius:38px;
            display:grid;place-items:center;
            color:white;
            font-size:64px;
            background:linear-gradient(145deg,var(--primary),var(--cyan));
            box-shadow:0 24px 50px rgba(8,127,140,.3);
            animation:float 4s ease-in-out infinite;
        }

        .scene-title{
            margin:26px 0 8px;
            color:var(--navy);
            font-size:24px;
            font-weight:800;
            letter-spacing:-.02em;
        }

        .scene-sub{
            margin:0;
            color:var(--muted);
            font-size:13px;
            font-weight:600;
        }

        .float-badge{
            position:absolute;
            z-index:5;
            display:flex;
            align-items:center;
            gap:11px;
            padding:14px 18px;
            border-radius:18px;
            background:rgba(255,255,255,.96);
            border:1px solid var(--line);
            box-shadow:0 15px 40px rgba(16,59,74,.12);
            font-size:12px;
            font-weight:800;
            color:var(--navy);
            animation:float 4.5s ease-in-out infinite;
        }

        .float-badge i{
            width:34px;height:34px;
            display:grid;place-items:center;
            border-radius:11px;
            background:var(--primary-soft);
            color:var(--primary);
            font-size:14px;
        }

        .badge-one{top:6%;left:2%}
        .badge-two{right:0;top:22%;animation-delay:.8s}
        .badge-three{left:2%;bottom:10%;animation-delay:1.5s}

        /* FOOTER */
        footer{
            position:relative;
            z-index:5;
            border-top:1px solid var(--line);
            background:rgba(255,255,255,.75);
            backdrop-filter:blur(14px);
        }

        .footer-inner{
            width:min(1240px,calc(100% - 40px));
            min-height:76px;
            margin:auto;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
        }

        .copyright{
            font-size:10px;
            color:#7b9097;
            line-height:1.6;
        }

        .copyright strong{color:var(--navy)}

        .staff-login{
            display:inline-flex;
            align-items:center;
            gap:7px;
            color:#7b9097;
            font-size:10px;
            font-weight:700;
            padding:9px 11px;
            border-radius:10px;
            transition:.2s ease;
        }

        .staff-login:hover{
            color:var(--primary);
            background:var(--primary-soft);
        }

        /* ANIMATIONS */
        @keyframes fadeUp{
            from{opacity:0;transform:translateY(18px)}
            to{opacity:1;transform:none}
        }
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes pulse{
            70%{box-shadow:0 0 0 9px rgba(32,196,123,0)}
            100%{box-shadow:0 0 0 0 rgba(32,196,123,0)}
        }
        @keyframes marquee{
            from{transform:translateX(0)}
            to{transform:translateX(-100%)}
        }
        @keyframes float{
            0%,100%{transform:translateY(0)}
            50%{transform:translateY(-9px)}
        }
        @keyframes spin{to{transform:rotate(360deg)}}

        /* TABLET */
        @media(max-width:980px){
            .hero{
                grid-template-columns:1fr;
                gap:40px;
                padding-top:45px;
            }

            .welcome-main{
                font-size:clamp(38px,6vw,54px);
            }
            .visual{min-height:500px;order:-1}
            .hero h2{font-size:50px}
            .hero-copy{text-align:center}
            .hero-description{margin:auto}
            .quick-info{max-width:650px;margin-left:auto;margin-right:auto}
            .action-card{max-width:650px;margin:auto;text-align:left}
            .eyebrow{justify-content:center}
        }

        /* MOBILE */
        @media(max-width:640px){
            .topbar-inner,.page,.footer-inner,.ticker-inner{
                width:min(100% - 24px,1240px);
            }
            .topbar-inner{min-height:72px}
            .brand-sub{display:none}
            .brand-name{font-size:12px}
            .brand-logo{width:44px;height:44px;border-radius:14px}
            .header-tools .status{display:none}
            .clock{font-size:9px;padding:0 10px;min-height:36px}
            .ticker-inner{gap:7px}
            .ticker-label{font-size:8px}
            .ticker-text{font-size:9px}
            .hero{
                min-height:auto;
                padding:28px 0 35px;
                gap:25px;
            }
            .visual{min-height:380px}
            .visual-orb{width:350px}
            .visual-ring{width:310px;height:310px}
            .medical-scene{
                width:300px;
                border-radius:36px;
            }
            .scene-cross{width:90px;height:90px;border-radius:28px;font-size:44px}
            .scene-title{font-size:16px;margin-top:18px}
            .scene-sub{font-size:10px}
            .float-badge{padding:10px 12px;font-size:9px}
            .float-badge i{width:26px;height:26px;font-size:11px}
            .badge-one{left:0}
            .badge-two{right:0}
            .badge-three{left:0;bottom:6%}
            .hero h2.welcome-title{
                margin-top:14px;
                margin-bottom:14px;
            }

            .welcome-small{
                font-size:20px;
                margin-bottom:5px;
            }

            .welcome-main{
                font-size:34px;
                line-height:1.08;
                white-space:normal;
            }
            .hero-description{font-size:12px;line-height:1.7}
            .quick-info{gap:12px;margin:24px 0}
            .quick-card{padding:16px 14px}
            .quick-card strong{font-size:13px}
            .quick-card small{font-size:10px}
            .action-card{padding:26px;border-radius:24px}
            .action-inner{gap:16px}
            .action-icon{width:76px;height:76px;flex-basis:76px;border-radius:20px;font-size:30px}
            .action-title{font-size:20px}
            .action-text{font-size:12px}
            .start-button{min-height:70px;font-size:17px;border-radius:18px}
            .privacy-note{font-size:9px}
            .footer-inner{padding:15px 0;min-height:auto}
            .copyright{font-size:8px}
            .staff-login{font-size:9px}
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="#" class="brand" aria-label="Beranda">
            <div class="brand-logo">
                <i class="fa-solid fa-hospital"></i>
            </div>
            <div>
                <h1 class="brand-name">RSUD / RSI JAYA UTAMA</h1>
                <p class="brand-sub">Anjungan Mandiri • Pendaftaran & Screening Pasien IGD</p>
            </div>
        </a>

        <div class="header-tools">
            <div class="status" title="Status sistem">
                <span class="pulse"></span>
                <span>SERVER ONLINE</span>
            </div>
            <div id="live-clock" class="clock" aria-live="polite">
                <i class="fa-regular fa-clock"></i>
                Memuat waktu...
            </div>
        </div>
    </div>
</header>

<div class="ticker" aria-label="Informasi pelayanan">
    <div class="ticker-inner">
        <div class="ticker-label">
            <i class="fa-solid fa-bullhorn"></i>
            Informasi
        </div>
        <div class="ticker-window">
            <div class="ticker-text">
                Selamat datang di IGD. Silakan gunakan Anjungan Mandiri untuk memulai pendaftaran dan screening awal. Pastikan data yang diisi sesuai dengan identitas pasien.
            </div>
        </div>
    </div>
</div>

<main class="page">
    <section class="hero">

        <div class="hero-copy">
            <div class="eyebrow">
                <i class="fa-solid fa-fingerprint"></i>
                Self-Service Check-In
            </div>

            <h2 class="welcome-title">
                <span class="welcome-small">Selamat Datang di</span>
                <span class="welcome-main">Instalasi Gawat Darurat</span>
            </h2>

            <p class="hero-description">
                Gunakan anjungan ini untuk melakukan pendaftaran mandiri,
                mengisi data pasien, serta melakukan screening awal sebelum
                mendapatkan pelayanan di IGD.
            </p>

            <div class="quick-info">
                <div class="quick-card">
                    <div class="quick-icon"><i class="fa-solid fa-id-card"></i></div>
                    <strong>Data Pasien</strong>
                    <small>Identitas & penjamin</small>
                </div>
                <div class="quick-card">
                    <div class="quick-icon"><i class="fa-solid fa-stethoscope"></i></div>
                    <strong>Screening</strong>
                    <small>Keluhan awal pasien</small>
                </div>
                <div class="quick-card">
                    <div class="quick-icon"><i class="fa-solid fa-ticket"></i></div>
                    <strong>Antrean</strong>
                    <small>Proses pelayanan IGD</small>
                </div>
            </div>

            <div class="action-card">
                <div class="action-inner">
                    <div class="action-icon">
                        <i class="fa-solid fa-laptop-medical"></i>
                    </div>
                    <div>
                        <h3 class="action-title">Mulai Pendaftaran Pasien</h3>
                        <p class="action-text">
                            Tekan tombol di bawah untuk memulai proses
                            pendaftaran dan pengisian screening awal.
                        </p>
                    </div>
                </div>

                <a href="screening/kiosk.php" class="start-button" id="startButton">
                    <span>Mulai Screening / Pendaftaran</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <p class="privacy-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    Data pasien diproses untuk kebutuhan pelayanan IGD.
                </p>
            </div>
        </div>

        <!-- Area Ilustrasi Kanan yang Telah Diperbesar & Diproporsikan -->
        <div class="visual" aria-label="Ilustrasi pelayanan IGD">
            <div class="visual-orb"></div>
            <div class="visual-ring"></div>

            <div class="float-badge badge-one">
                <i class="fa-solid fa-user-check"></i>
                <span>Registrasi Mandiri</span>
            </div>

            <div class="float-badge badge-two">
                <i class="fa-solid fa-heart-pulse"></i>
                <span>Pelayanan IGD</span>
            </div>

            <div class="float-badge badge-three">
                <i class="fa-solid fa-clock"></i>
                <span>Proses Cepat</span>
            </div>

            <div class="medical-scene">
                <div class="scene-glow"></div>
                <div class="scene-content">
                    <div class="scene-cross">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <h3 class="scene-title">Pelayanan Gawat Darurat</h3>
                    <p class="scene-sub">Cepat • Terarah • Terintegrasi</p>
                </div>
            </div>
        </div>

    </section>
</main>

<footer>
    <div class="footer-inner">
        <div class="copyright">
            <strong>SIMRS IGD © 2026 — RSUD / RSI Jaya Utama</strong><br>
            Standar Pelayanan Kegawatdaruratan Medis Berbasis Permenkes No. 47 Tahun 2018
        </div>

        <a href="login.php" class="staff-login" title="Login Khusus Petugas IGD">
            <i class="fa-solid fa-user-shield"></i>
            Login Petugas
        </a>
    </div>
</footer>

<script>
(function(){
    "use strict";

    const clock = document.getElementById("live-clock");
    const startButton = document.getElementById("startButton");

    function updateClock(){
        const now = new Date();

        const dateText = now.toLocaleDateString("id-ID", {
            weekday:"long",
            day:"2-digit",
            month:"short",
            year:"numeric"
        });

        const timeText = now.toLocaleTimeString("id-ID", {
            hour:"2-digit",
            minute:"2-digit",
            second:"2-digit",
            hour12:false
        });

        clock.innerHTML =
            '<i class="fa-regular fa-clock"></i> ' +
            dateText + " • " + timeText;
    }

    updateClock();
    setInterval(updateClock, 1000);

    if(startButton){
        startButton.addEventListener("pointerdown", function(){
            this.style.transform = "scale(.985)";
        });

        startButton.addEventListener("pointerup", function(){
            this.style.transform = "";
        });

        startButton.addEventListener("pointerleave", function(){
            this.style.transform = "";
        });
    }
})();
</script>

</body>
</html>