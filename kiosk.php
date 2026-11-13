<?php
// Mengatur alur step kiosk berdasarkan interaksi user
session_start();

// Inisialisasi step dari session jika ada, atau default ke 'welcome'
$step = $_SESSION['current_step'] ?? 'home';
$patient_data = $_SESSION['patient_data'] ?? [];
$result_data = $_SESSION['result_data'] ?? null;
$error_message = '';

// Endpoint AJAX lokal untuk mencari data pasien berdasarkan Nomor RM.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['ajax'] ?? '') === 'lookup_rm') {
    header('Content-Type: application/json; charset=utf-8');
    $no_rm_lookup = trim($_GET['no_rm'] ?? '');
    if ($no_rm_lookup === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nomor RM wajib diisi.']);
        exit;
    }
    $lookup_url = 'http://127.0.0.1:5000/api/pasien/by-rm?no_rm=' . rawurlencode($no_rm_lookup);
    $lookup_ch = curl_init($lookup_url);
    curl_setopt($lookup_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($lookup_ch, CURLOPT_TIMEOUT, 8);
    $lookup_response = curl_exec($lookup_ch);
    $lookup_error = curl_error($lookup_ch);
    $lookup_code = (int) curl_getinfo($lookup_ch, CURLINFO_HTTP_CODE);
    curl_close($lookup_ch);
    if ($lookup_error) {
        http_response_code(502);
        echo json_encode(['status' => 'error', 'message' => 'Layanan pencarian pasien tidak dapat dihubungi.']);
        exit;
    }
    http_response_code($lookup_code ?: 502);
    echo $lookup_response !== false ? $lookup_response : json_encode(['status' => 'error', 'message' => 'Respons API kosong.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_step'] ?? '';

    // Deteksi jika user ingin kembali ke menu utama
    if ($action === 'home') {
        // Akhiri sesi pasien dan kembalikan ke HOME BASE utama (../index.php)
        unset(
            $_SESSION['current_step'],
            $_SESSION['patient_data'],
            $_SESSION['result_data']
        );
        session_write_close();
        header('Location: ../index.php');
        exit;
    }
    elseif ($action === 'start_registration') {
        $step = 'welcome';
        $patient_data = [];
        $result_data = null;
        unset($_SESSION['patient_data'], $_SESSION['result_data']);
    }
        elseif ($action === 'to_payer') {
        $step = 'payer';
        $patient_data = [
            'no_rm'         => trim($_POST['no_rm'] ?? ''),
            'nik'           => trim($_POST['nik'] ?? ''),
            'nama'          => trim($_POST['nama'] ?? ''),
            'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
            'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'Laki-laki',
            'no_hp'         => trim($_POST['no_hp'] ?? ''),
            'alamat'        => trim($_POST['alamat'] ?? '')
        ];
    } 
    elseif ($action === 'back_to_welcome') {
        $step = 'welcome';
    }
    elseif ($action === 'back_to_payer') {
        $step = 'payer';
    }
    elseif ($action === 'to_screening') {
        $step = 'screening';
        if (isset($_POST['penjamin'])) {
            $patient_data['penjamin'] = $_POST['penjamin'];
        }
    }
    elseif ($action === 'submit_final') {
        if (isset($_POST['penjamin'])) {
            $patient_data['penjamin'] = $_POST['penjamin'];
        }

        $payload = [
            'no_rm'              => trim($patient_data['no_rm'] ?? ''),
            'nik'                => trim($patient_data['nik'] ?? ''),
            'nama'               => trim($patient_data['nama'] ?? ''),
            'tanggal_lahir'      => $patient_data['tanggal_lahir'] ?? '',
            'jenis_kelamin'      => $patient_data['jenis_kelamin'] ?? '',
            'no_hp'              => trim($patient_data['no_hp'] ?? ''),
            'alamat'             => trim($patient_data['alamat'] ?? ''),
            'penjamin'           => $patient_data['penjamin'] ?? 'UMUM',
            'keluhan_nyeri_dada' => $_POST['keluhan_nyeri_dada'] ?? '0',
            'keluhan_sesak'      => $_POST['keluhan_sesak'] ?? '0',
            'keluhan_pingsan'    => $_POST['keluhan_pingsan'] ?? '0',
            'keluhan_perdarahan' => $_POST['keluhan_perdarahan'] ?? '0',
            'keluhan_kejang'     => $_POST['keluhan_kejang'] ?? '0',
            'keluhan_cedera'     => $_POST['keluhan_cedera'] ?? '0',
            'keluhan_demam'      => $_POST['keluhan_demam'] ?? '0',
            'keluhan_muntah_diare' => $_POST['keluhan_muntah_diare'] ?? '0',
            'keluhan_kehamilan'  => $_POST['keluhan_kehamilan'] ?? '0',
            'keluhan_nyeri_berat'=> $_POST['keluhan_nyeri_berat'] ?? '0',
            'keluhan_bayi_anak'  => $_POST['keluhan_bayi_anak'] ?? '0',
            'keluhan_lainnya'    => trim($_POST['keluhan_lainnya'] ?? ''),
            'q_abc'              => $_POST['q_abc'] ?? '0',
            'q_kesadaran'        => $_POST['q_kesadaran'] ?? '0',
            'q_perdarahan'       => $_POST['q_perdarahan'] ?? '0',
            'q_kejang'           => $_POST['q_kejang'] ?? '0',
            'q_nyeri_dada'       => $_POST['q_nyeri_dada'] ?? '0',
            'q_cedera'           => $_POST['q_cedera'] ?? '0',
            'q_stroke'           => $_POST['q_stroke'] ?? '0',
            'q_alergi_berat'      => $_POST['q_alergi_berat'] ?? '0',
            'q_obstetri'          => $_POST['q_obstetri'] ?? '0',
            'potensi_memburuk'   => $_POST['potensi_memburuk'] ?? '0'
        ];

        $ch = curl_init('http://127.0.0.1:5000/api/screening');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $step = 'screening';
            $error_message = "Gagal terhubung ke server backend screening (Python API): " . $curl_error;
        } else {
            $json = json_decode($response, true);
            if ($json && isset($json['status']) && $json['status'] === 'success') {
                $step = 'result';
                $result_data = $json['patient'];
            } else {
                $step = 'screening';
                $error_message = "Respon API tidak valid atau gagal memproses screening.";
            }
        }
    }

    $_SESSION['current_step'] = $step;
    $_SESSION['patient_data'] = $patient_data;
    $_SESSION['result_data'] = $result_data;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anjungan Mandiri Pasien IGD (Smart Check-In)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --p:#087f8c;
            --p2:#0ea5a4;
            --navy:#103b4a;
            --muted:#6b8189;
            --line:#dcebed;
            --soft:#eef9fa;
            --shadow:0 24px 70px rgba(16,59,74,.12);
        }

        *{box-sizing:border-box}
        body{
            font-family:'Plus Jakarta Sans',sans-serif;
            background:
                radial-gradient(circle at 5% 10%,rgba(20,184,166,.12),transparent 28%),
                radial-gradient(circle at 95% 15%,rgba(14,165,164,.10),transparent 30%),
                linear-gradient(135deg,#f8ffff 0%,#eff8fa 55%,#f7fbfc 100%);
            background-attachment:fixed;
        }
        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            opacity:.35;
            background-image:
                linear-gradient(rgba(8,127,140,.035) 1px,transparent 1px),
                linear-gradient(90deg,rgba(8,127,140,.035) 1px,transparent 1px);
            background-size:38px 38px;
            mask-image:linear-gradient(to bottom,black,transparent 90%);
        }

        header{
            background:linear-gradient(135deg,#056b78 0%,#087f8c 48%,#0ca39d 100%) !important;
            box-shadow:0 15px 40px rgba(8,127,140,.20) !important;
        }
        header .container{padding-top:26px!important;padding-bottom:26px!important}
        header h1{letter-spacing:-.035em!important}
        header p{opacity:.88}

        /* Progress stepper yang diperbesar */
        .kiosk-progress{
            display:flex;
            align-items:center;
            justify-content:center;
            width:min(860px, 100%);
            margin:0 auto 26px;
            padding:14px 24px;
            border:1px solid rgba(220,235,237,.95);
            border-radius:22px;
            background:rgba(255,255,255,.78);
            backdrop-filter:blur(15px);
            box-shadow:0 10px 30px rgba(16,59,74,.07);
        }
        .progress-item{
            display:flex;
            align-items:center;
            gap:12px;
            color:#9aadb2;
            font-size:13px;
            font-weight:800;
            white-space:nowrap;
            transition:.25s ease;
        }
        .progress-dot{
            width:38px;height:38px;
            display:grid;place-items:center;
            border-radius:12px;
            background:#edf3f4;
            color:#8da1a6;
            font-size:13px;
            transition:.25s ease;
        }
        .progress-item.active{color:var(--p)}
        .progress-item.active .progress-dot{
            color:white;
            background:linear-gradient(145deg,var(--p),var(--p2));
            box-shadow:0 7px 16px rgba(8,127,140,.20);
        }
        .progress-line{
            flex:1;
            height:3px;
            min-width:35px;
            max-width:140px;
            margin:0 14px;
            background:#e3ecee;
            border-radius:999px;
            transition:.3s ease;
        }
        .progress-line.active{background:linear-gradient(90deg,var(--p),var(--p2))}

        main > div:not(.kiosk-progress){
            animation:kioskIn .45s ease both;
        }
        main .rounded-3xl{
            border-color:rgba(220,235,237,.95)!important;
            box-shadow:var(--shadow)!important;
        }

        .welcome-heading{
            margin:0;
            color:var(--navy);
            line-height:1.05;
            letter-spacing:-.04em;
        }
        .welcome-heading span{
            display:block;
            margin-bottom:7px;
            color:#527079;
            font-size:clamp(18px,2.1vw,28px);
            font-weight:700;
            letter-spacing:-.025em;
        }
        .welcome-heading strong{
            display:block;
            font-size:clamp(29px,4vw,48px);
            font-weight:800;
            background:linear-gradient(100deg,#056b78,#087f8c,#0ea5a4);
            -webkit-background-clip:text;
            background-clip:text;
            -webkit-text-fill-color:transparent;
        }

        /* ================= KUSTOMISASI FORM UKURAN JUMBO UNTUK KIOSK ================= */
        .kiosk-form-group label {
            font-size: 13px !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            letter-spacing: 0.025em !important;
            margin-bottom: 8px !important;
            display: block;
        }
        
        .kiosk-input, .kiosk-select, .kiosk-textarea {
            height: 60px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            border-radius: 16px !important;
            padding-left: 50px !important;
            padding-right: 20px !important;
            border: 2px solid #cbd5e1 !important;
            background-color: #f8fafc !important;
            transition: all 0.2s ease !important;
        }

        .kiosk-select {
            padding-left: 20px !important;
        }

        .kiosk-textarea {
            height: auto !important;
            min-height: 110px !important;
            padding-top: 14px !important;
            padding-left: 20px !important;
        }

        .kiosk-input:focus, .kiosk-select:focus, .kiosk-textarea:focus {
            border-color: var(--p) !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 5px rgba(8,127,140,0.12), 0 10px 25px rgba(16,59,74,0.08) !important;
            transform: translateY(-1px);
        }

        .kiosk-input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            pointer-events: none;
        }

        /* ================= KUSTOMISASI KARTU PENJAMIN JUMBO ================= */
        .card-penjamin {
            min-height: 95px !important;
            padding: 22px 24px !important;
            border-radius: 20px !important;
            border-width: 2.5px !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .card-penjamin:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(8,127,140,0.12);
        }
        /* ========================================================================= */

        button[type="submit"]{
            min-height:60px;
            font-size:16px;
            font-weight:800;
            transition:transform .2s ease,box-shadow .2s ease,filter .2s ease!important;
        }
        button[type="submit"]:hover{
            transform:translateY(-2px);
            filter:saturate(1.04);
        }
        button[type="submit"]:active{transform:scale(.985)}

        .card-keluhan,.radio-label{
            transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background .2s ease!important;
        }
        .card-keluhan:hover,.radio-label:hover{
            transform:translateY(-2px);
            box-shadow:0 10px 24px rgba(16,59,74,.08);
        }
        .card-keluhan{
            min-height:118px;
        }
        .card-keluhan input{pointer-events:none}

        .kiosk-tip{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:16px;
            padding:14px 16px;
            border-radius:16px;
            background:linear-gradient(90deg,#effafa,#f8ffff);
            border:1px solid #d8edef;
            color:#475569;
            font-size:12px;
            line-height:1.5;
            font-weight:600;
        }
        .kiosk-tip i{color:var(--p);font-size:16px}

        @keyframes kioskIn{
            from{opacity:0;transform:translateY(12px)}
            to{opacity:1;transform:none}
        }

        /* ================= SCREENING JUMBO UNTUK LAYAR ANJUNGAN ================= */
        .screening-kiosk {
            max-width: 1100px !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding: 34px !important;
        }

        .screening-header h2 {
            font-size: 27px !important;
            line-height: 1.25 !important;
        }

        .screening-header p {
            font-size: 15px !important;
            line-height: 1.6 !important;
        }

        .screening-section-title {
            font-size: 18px !important;
            line-height: 1.45 !important;
            margin-bottom: 16px !important;
        }

        .screening-kiosk .card-keluhan {
            min-height: 155px !important;
            padding: 24px 16px !important;
            border-width: 3px !important;
            border-radius: 22px !important;
            font-size: 17px !important;
            line-height: 1.35 !important;
            gap: 6px !important;
            -webkit-tap-highlight-color: transparent;
        }

        .screening-kiosk .card-keluhan > span {
            font-size: 42px !important;
            line-height: 1 !important;
            margin-bottom: 8px !important;
        }

        .screening-kiosk .card-keluhan input[type="checkbox"] {
            width: 27px !important;
            height: 27px !important;
            margin-top: 10px !important;
        }

        .screening-questions {
            padding: 28px !important;
            border-radius: 24px !important;
        }

        .screening-question {
            padding-bottom: 20px !important;
            margin-bottom: 20px !important;
        }

        .screening-question-text {
            font-size: 18px !important;
            line-height: 1.55 !important;
            font-weight: 800 !important;
            margin-bottom: 13px !important;
        }

        .screening-kiosk .radio-label {
            min-height: 64px !important;
            min-width: 145px !important;
            padding: 14px 24px !important;
            border-width: 2px !important;
            border-radius: 16px !important;
            font-size: 17px !important;
            line-height: 1.2 !important;
            justify-content: center !important;
            gap: 10px !important;
        }

        .screening-kiosk .radio-label input[type="radio"] {
            width: 25px !important;
            height: 25px !important;
        }

        .screening-kiosk button[type="submit"] {
            min-height: 72px !important;
            font-size: 18px !important;
            border-radius: 20px !important;
        }

        .screening-note {
            font-size: 15px !important;
            line-height: 1.55 !important;
        }

        @media(max-width:900px){
            .screening-kiosk {
                padding: 24px !important;
            }

            .screening-kiosk .card-keluhan {
                min-height: 135px !important;
                font-size: 16px !important;
            }

            .screening-question-text {
                font-size: 17px !important;
            }
        }

        @media(max-width:640px){
            .screening-kiosk {
                padding: 18px !important;
                border-radius: 24px !important;
            }

            .screening-header h2 {
                font-size: 22px !important;
            }

            .screening-header p {
                font-size: 13px !important;
            }

            .screening-section-title {
                font-size: 16px !important;
            }

            .screening-kiosk .card-keluhan {
                min-height: 125px !important;
                padding: 18px 10px !important;
                font-size: 14px !important;
            }

            .screening-kiosk .card-keluhan > span {
                font-size: 34px !important;
            }

            .screening-questions {
                padding: 18px !important;
            }

            .screening-question-text {
                font-size: 15px !important;
            }

            .screening-kiosk .radio-label {
                min-height: 58px !important;
                min-width: 0 !important;
                flex: 1 !important;
                padding: 12px 10px !important;
                font-size: 15px !important;
            }

            .screening-kiosk button[type="submit"] {
                min-height: 66px !important;
                font-size: 16px !important;
            }
        }

        @media(max-width:640px){
            main{padding-top:22px!important;padding-bottom:24px!important}
            header h1{font-size:20px!important}
            header p{font-size:10px!important}
            .kiosk-progress{padding:10px 14px;margin-bottom:18px}
            .progress-label{font-size:11px;}
            .progress-dot{width:32px;height:32px;font-size:11px;}
            .progress-line{min-width:22px;margin:0 8px}
            .welcome-heading span{font-size:17px}
            .welcome-heading strong{font-size:30px;line-height:1.08}
            .card-keluhan{min-height:105px}
            .kiosk-input, .kiosk-select{height:54px!important;font-size:15px!important;}
            .card-penjamin{min-height:85px !important;padding:16px 18px !important;}
        }

        @media print {
            body * { visibility:hidden; }
            #printable-result, #printable-result * { visibility:visible; }
            #printable-result { position:absolute;left:0;top:0;width:100%;box-shadow:none!important;border:none!important;padding:0!important; }
            .no-print { display:none!important; }
        }
            /* ===== TOUCHSCREEN SCREENING ===== */
        .screening-kiosk{width:100%;}
        .touch-nav-btn{min-height:52px;padding:0 16px;border-radius:14px;background:#f1f5f9;color:#475569;font-weight:800;font-size:14px;display:inline-flex;align-items:center;gap:8px;transition:.2s;border:1px solid #e2e8f0;}
        .touch-nav-btn:hover{background:#e0f2fe;color:#0369a1;border-color:#7dd3fc;}
        .wizard-dot{padding:7px 10px;border-radius:10px;text-align:center;background:#f8fafc;transition:.3s;}
        .wizard-dot.active{background:#e0f2fe;color:#0369a1;}
        .wizard-dot.done{background:#dcfce7;color:#15803d;}
        .wizard-intro{display:flex;align-items:center;gap:18px;background:linear-gradient(135deg,#f8fafc,#eef9fa);border:1px solid #dcebed;border-radius:22px;padding:20px;}
        .wizard-big-icon{width:66px;height:66px;min-width:66px;border-radius:20px;background:white;display:flex;align-items:center;justify-content:center;font-size:34px;box-shadow:0 8px 24px rgba(16,59,74,.08);}
        .wizard-intro h3{font-size:24px;line-height:1.25;font-weight:900;color:#0f172a;margin:0;}
        .wizard-intro p{font-size:15px;line-height:1.6;color:#64748b;margin-top:5px;}
        .interactive-choice{position:relative;min-height:185px;border:3px solid #e2e8f0;border-radius:24px;background:#fff;padding:25px 20px 20px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;cursor:pointer;user-select:none;transition:.2s;box-shadow:0 5px 18px rgba(15,23,42,.05);}
        .interactive-choice:hover{border-color:#7dd3fc;transform:translateY(-2px);box-shadow:0 10px 25px rgba(14,165,233,.12);}
        .interactive-choice.selected{border-color:#0284c7;background:#effaff;box-shadow:0 0 0 4px rgba(14,165,233,.12),0 12px 28px rgba(14,165,233,.12);transform:translateY(-2px);}
        .choice-icon{font-size:46px;line-height:1;margin-bottom:12px;}
        .choice-title{font-size:18px;line-height:1.3;font-weight:900;color:#0f172a;}
        .choice-help{font-size:13px;line-height:1.45;color:#64748b;margin-top:7px;max-width:270px;}
        .choice-check{position:absolute;right:14px;top:14px;width:34px;height:34px;border-radius:50%;background:#e2e8f0;color:transparent;display:flex;align-items:center;justify-content:center;font-size:16px;transition:.2s;}
        .interactive-choice.selected .choice-check{background:#0284c7;color:#fff;}
        .info-hint{display:flex;align-items:center;gap:10px;padding:14px 17px;border-radius:16px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;font-size:14px;}
        .info-hint i{font-size:18px;color:#0ea5e9;}
        .info-hint.selected-hint{background:#ecfdf5;border-color:#a7f3d0;color:#047857;}
        .info-hint.selected-hint i{color:#10b981;}
        .wizard-actions{display:flex;justify-content:flex-end;margin-top:26px;gap:12px;}
        .primary-touch-btn,.secondary-touch-btn{min-height:66px;border-radius:18px;padding:0 26px;font-size:17px;font-weight:900;display:inline-flex;align-items:center;justify-content:center;gap:10px;transition:.2s;border:0;cursor:pointer;}
        .primary-touch-btn{color:#fff;background:linear-gradient(135deg,#0284c7,#0f766e);box-shadow:0 12px 25px rgba(2,132,199,.25);}
        .primary-touch-btn:hover{filter:brightness(.96);transform:translateY(-1px);}
        .primary-touch-btn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none;}
        .secondary-touch-btn{background:#f1f5f9;color:#475569;border:2px solid #e2e8f0;}
        .secondary-touch-btn:hover{background:#e2e8f0;}
        .small-primary{min-width:150px;}
        .question-area{min-height:230px;}
        .screening-question-card{position:relative;display:flex;gap:18px;align-items:flex-start;padding:28px;border:3px solid #dbeafe;border-radius:24px;background:linear-gradient(135deg,#f8fbff,#fff);box-shadow:0 8px 24px rgba(15,23,42,.06);}
        .question-number{width:48px;height:48px;min-width:48px;border-radius:15px;background:#0284c7;color:white;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;}
        .question-icon{font-size:40px;line-height:1;min-width:45px;}
        .question-counter{font-size:11px;letter-spacing:.12em;color:#0284c7;font-weight:900;margin-bottom:7px;}
        .screening-question-card h4{font-size:23px;line-height:1.4;font-weight:900;color:#0f172a;margin:0;}
        .screening-question-card p{font-size:15px;line-height:1.55;color:#64748b;margin-top:9px;}
        .answer-btn{min-height:100px;border-radius:22px;border:3px solid;display:flex;align-items:center;justify-content:center;gap:15px;padding:15px 22px;text-align:left;cursor:pointer;transition:.2s;}
        .answer-btn b{display:block;font-size:21px;line-height:1.1;}
        .answer-btn small{display:block;font-size:13px;margin-top:5px;font-weight:600;}
        .answer-yes{background:#fff7f7;border-color:#fecaca;color:#b91c1c;}
        .answer-no{background:#f3fff8;border-color:#bbf7d0;color:#15803d;}
        .answer-unknown{background:#fffbeb;border-color:#fcd34d;color:#92400e;}
        .answer-btn:hover,.answer-btn.chosen{transform:translateY(-2px);box-shadow:0 10px 25px rgba(15,23,42,.09);}
        .answer-btn.chosen.answer-yes{background:#fee2e2;border-color:#ef4444;}
        .answer-btn.chosen.answer-no{background:#dcfce7;border-color:#22c55e;}
        .answer-btn.chosen.answer-unknown{background:#fef3c7;border-color:#d97706;}
        .answer-icon{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.8);font-size:25px;font-weight:900;flex-shrink:0;}
        .question-navigation{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:14px;margin-top:24px;}
        .summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
        .summary-card{border:2px solid #e2e8f0;border-radius:20px;background:#fff;overflow:hidden;}
        .summary-card-title{padding:15px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:15px;font-weight:900;color:#334155;display:flex;align-items:center;gap:8px;}
        .summary-content{padding:13px 16px;max-height:310px;overflow:auto;}
        .summary-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;line-height:1.4;}
        .summary-row:last-child{border-bottom:0;}
        .summary-row>span:first-child:not(.summary-answer){font-size:21px;min-width:28px;}
        .summary-empty{padding:15px;color:#94a3b8;font-size:13px;}
        .question-summary{align-items:flex-start;}
        .summary-answer{min-width:62px;text-align:center;border-radius:9px;padding:5px 7px;font-size:10px;font-weight:900;}
        .answer-summary.yes{background:#fee2e2;color:#b91c1c;}
        .answer-summary.no{background:#dcfce7;color:#15803d;}
        .confirmation-box{display:flex;align-items:center;gap:13px;padding:17px 19px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:17px;color:#1e40af;}
        .confirmation-box>i{font-size:27px;color:#2563eb;}
        .confirmation-box b{font-size:15px;}
        .confirmation-box p{font-size:13px;color:#64748b;margin-top:2px;}
        .dual-actions{justify-content:space-between;}
        .submit-touch-btn{flex:1;max-width:520px;}
        @media(max-width:900px){
            .interactive-choice{min-height:165px;}
            .screening-question-card h4{font-size:20px;}
            .question-navigation{grid-template-columns:1fr 1fr;}
            #answerStatus{grid-column:1/-1;grid-row:1;}
            .summary-grid{grid-template-columns:1fr;}
            .dual-actions{flex-direction:column-reverse;}
            .dual-actions button{width:100%;max-width:none;}
        }
        @media(max-width:640px){
            .wizard-intro{align-items:flex-start;padding:16px;}
            .wizard-big-icon{width:52px;height:52px;min-width:52px;font-size:27px;border-radius:15px;}
            .wizard-intro h3{font-size:20px;}
            .wizard-intro p{font-size:13px;}
            .interactive-choice{min-height:145px;padding:18px 13px;}
            .choice-icon{font-size:36px;}
            .choice-title{font-size:16px;}
            .choice-help{font-size:12px;}
            .screening-question-card{padding:20px;gap:11px;}
            .question-number{width:38px;height:38px;min-width:38px;font-size:16px;}
            .question-icon{font-size:30px;min-width:32px;}
            .screening-question-card h4{font-size:18px;}
            .screening-question-card p{font-size:13px;}
            .answer-btn{min-height:86px;padding:12px;}
            .answer-btn b{font-size:18px;}
            .answer-btn small{font-size:11px;}
            .question-navigation{grid-template-columns:1fr;}
            .question-navigation #answerStatus{grid-column:auto;grid-row:auto;order:-1;}
            .question-navigation button{width:100%;}
            .wizard-actions{flex-direction:column;}
            .wizard-actions button{width:100%;}
        }



        .home-base button{touch-action:manipulation;}
        .result-kiosk{animation:resultIn .45s ease-out;}
        .result-pulse{animation:resultPulse 1.8s ease-in-out infinite;}
        .result-action{min-height:76px;font-size:1.05rem;font-weight:900;border-radius:1.25rem;touch-action:manipulation;}
        .result-countdown{font-variant-numeric:tabular-nums;}
        @keyframes resultIn{from{opacity:0;transform:translateY(18px) scale(.985)}to{opacity:1;transform:none}}
        @keyframes resultPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.04)}}
        @media (prefers-reduced-motion:reduce){.result-kiosk,.result-pulse{animation:none!important;}}
</style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-sky-50/30 to-slate-100 text-slate-800 min-h-screen flex flex-col justify-between selection:bg-sky-500 selection:text-white">

    <header class="bg-sky-700 text-white shadow-lg relative overflow-hidden no-print">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="container mx-auto px-6 py-8 relative z-10 text-center">
            <div class="inline-flex items-center justify-center bg-white/10 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-semibold tracking-wider uppercase mb-3 border border-white/20">
                <i class="fa-solid fa-hospital-user mr-2"></i> Sistem Penapisan Mandiri Berdasarkan Permenkes No. 47 Tahun 2018
            </div>
            <h1 class="text-2xl md:text-3xl font-black tracking-tight">ANJUNGAN MANDIRI PASIEN IGD (SMART CHECK-IN)</h1>
            <p class="text-xs md:text-sm text-sky-100 mt-1 font-medium">RSUD / RSI Jaya Utama — Triage System</p>
        </div>
    </header>

    <?php if (!empty($error_message)): ?>
    <div class="container mx-auto px-4 mt-6 max-w-3xl no-print">
        <div class="bg-rose-100 border border-rose-400 text-rose-700 px-4 py-3 rounded-2xl relative text-xs font-bold shadow-md flex items-center gap-3" role="alert">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <main class="container mx-auto px-4 py-10 max-w-4xl flex-grow">
        <?php if ($step !== 'result' && $step !== 'home'): ?>
        <div class="kiosk-progress no-print" aria-label="Tahapan pendaftaran">
            <?php
                $progress_steps = [
                    'welcome' => ['1', 'Data Pasien'],
                    'payer' => ['2', 'Penjamin'],
                    'screening' => ['3', 'Screening']
                ];
                $step_order = ['welcome','payer','screening'];
                $active_index = array_search($step, $step_order, true);
            ?>
            <?php foreach ($progress_steps as $key => $item): 
                $idx = array_search($key, $step_order, true);
                $active = ($idx <= $active_index);
            ?>
                <div class="progress-item <?php echo $active ? 'active' : ''; ?>">
                    <span class="progress-dot"><?php echo $item[0]; ?></span>
                    <span class="progress-label"><?php echo $item[1]; ?></span>
                </div>
                <?php if ($key !== 'screening'): ?><span class="progress-line <?php echo $idx < $active_index ? 'active' : ''; ?>"></span><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ================= HOME BASE / BERANDA ================= -->
        <?php if ($step == 'home'): ?>
        <div class="home-base max-w-5xl mx-auto">
            <div class="bg-white/95 rounded-[2rem] shadow-2xl border border-white/80 overflow-hidden">
                <div class="relative bg-gradient-to-br from-sky-700 via-cyan-700 to-teal-600 text-white px-8 md:px-14 py-12 md:py-16 text-center overflow-hidden">
                    <div class="absolute -top-20 -right-16 w-56 h-56 rounded-full bg-white/10"></div>
                    <div class="absolute -bottom-28 -left-16 w-72 h-72 rounded-full bg-white/10"></div>
                    <div class="relative z-10">
                        <div class="w-24 h-24 md:w-28 md:h-28 mx-auto rounded-[2rem] bg-white/15 border border-white/30 flex items-center justify-center text-5xl md:text-6xl shadow-xl backdrop-blur">
                            <i class="fa-solid fa-house-medical"></i>
                        </div>
                        <p class="mt-7 text-sm md:text-base font-bold tracking-[.2em] uppercase text-cyan-100">Anjungan Mandiri IGD</p>
                        <h2 class="mt-2 text-3xl md:text-5xl font-black tracking-tight">Selamat Datang</h2>
                        <p class="mt-4 max-w-2xl mx-auto text-sm md:text-lg text-white/90 leading-relaxed">
                            Silakan pilih menu di bawah. Jika Anda akan berobat, tekan <b>Mulai Pendaftaran</b> untuk mengisi data pasien dan melakukan screening awal.
                        </p>
                    </div>
                </div>

                <div class="p-7 md:p-12">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form method="POST" class="group">
                            <input type="hidden" name="action_step" value="start_registration">
                            <button type="submit" class="w-full min-h-[190px] rounded-3xl border-4 border-sky-100 bg-gradient-to-br from-sky-50 to-white hover:border-sky-400 hover:from-sky-100 hover:shadow-xl transition-all duration-200 p-7 text-left flex items-center gap-6">
                                <span class="shrink-0 w-20 h-20 md:w-24 md:h-24 rounded-3xl bg-sky-600 text-white flex items-center justify-center text-4xl shadow-lg group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-user-plus"></i>
                                </span>
                                <span>
                                    <span class="block text-2xl md:text-3xl font-black text-slate-800">Mulai Pendaftaran</span>
                                    <span class="block mt-2 text-sm md:text-base text-slate-500 leading-relaxed">Pendaftaran pasien baru/lama dan screening awal IGD.</span>
                                </span>
                            </button>
                        </form>

                        <div class="min-h-[190px] rounded-3xl border-4 border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-7 flex items-center gap-6">
                            <span class="shrink-0 w-20 h-20 md:w-24 md:h-24 rounded-3xl bg-emerald-600 text-white flex items-center justify-center text-4xl shadow-lg">
                                <i class="fa-solid fa-circle-info"></i>
                            </span>
                            <span>
                                <span class="block text-2xl md:text-3xl font-black text-slate-800">Petunjuk</span>
                                <span class="block mt-2 text-sm md:text-base text-slate-500 leading-relaxed">Gunakan layar sentuh. Tekan pilihan yang paling sesuai dengan kondisi pasien.</span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-8 rounded-3xl bg-amber-50 border-2 border-amber-200 p-5 md:p-6 flex gap-4 items-start">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <p class="font-black text-amber-900">Jika kondisi pasien tampak sangat gawat</p>
                            <p class="mt-1 text-sm text-amber-800 leading-relaxed">Segera minta bantuan petugas IGD. Jangan menunggu proses anjungan apabila pasien membutuhkan pertolongan segera.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= STEP 1: WELCOME / DATA PASIEN ================= -->
        <?php elseif ($step == 'welcome'): ?>
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden backdrop-blur-xl">
            <div class="px-8 pt-6 flex justify-between items-center">
                <form method="POST">
                    <input type="hidden" name="action_step" value="home">
                    <button type="submit" class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-500 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3.5 py-2.5 rounded-xl transition">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Menu Utama
                    </button>
                </form>
            </div>

            <div class="bg-slate-50/50 px-8 py-5 border-b border-slate-100 text-center space-y-2">
                <div class="w-14 h-14 bg-sky-100 text-sky-600 rounded-2xl flex items-center justify-center text-2xl mx-auto shadow-inner">
                    <i class="fa-solid fa-house-chimney-medical"></i>
                </div>
                <h2 class="welcome-heading"><span>Selamat Datang di</span><strong>Instalasi Gawat Darurat</strong></h2>
                <p class="text-xs md:text-sm text-slate-500 max-w-md mx-auto">Silakan lakukan pendaftaran mandiri dan penapisan awal untuk mempercepat penanganan medis Anda atau keluarga.</p>
                <div class="kiosk-tip max-w-lg mx-auto text-left">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Siapkan NIK, identitas pasien, nomor HP/WhatsApp, dan alamat sebelum melanjutkan.</span>
                </div>
            </div>
            
            <form method="POST" class="p-8 md:p-10 space-y-6">
                <input type="hidden" name="action_step" value="to_payer">
                
                <div class="kiosk-form-group">
                    <label>
                        NOMOR REKAM MEDIS (NO. RM) <span class="text-slate-400 font-normal lowercase">(Opsional jika sudah pernah berobat)</span>
                    </label>
                    <div class="relative">
                        <span class="kiosk-input-icon">
                            <i class="fa-solid fa-id-card"></i>
                        </span>
                        <input type="text" id="no_rm" name="no_rm" value="<?php echo htmlspecialchars($patient_data['no_rm'] ?? ''); ?>" placeholder="Contoh: 12-34-56 (Kosongkan jika pasien baru)" 
                            class="w-full kiosk-input">
                    </div>
                    <button type="button" id="btnCariRM" class="mt-3 px-5 py-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-semibold">
                        <i class="fa-solid fa-magnifying-glass mr-2"></i>Cari Data Pasien
                    </button>
                    <p id="statusCariRM" class="mt-2 text-sm text-slate-600" aria-live="polite"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="kiosk-form-group">
                        <label>NIK PASIEN <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="kiosk-input-icon">
                                <i class="fa-solid fa-address-card"></i>
                            </span>
                            <input type="text" id="nik" name="nik" value="<?php echo htmlspecialchars($patient_data['nik'] ?? ''); ?>" required maxlength="16" placeholder="Masukkan 16 digit NIK" class="w-full kiosk-input">
                        </div>
                    </div>
                    <div class="kiosk-form-group">
                        <label>NAMA LENGKAP PASIEN <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="kiosk-input-icon">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($patient_data['nama'] ?? ''); ?>" required placeholder="Sesuai KTP / Identitas" class="w-full kiosk-input">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="kiosk-form-group">
                        <label>TANGGAL LAHIR <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="kiosk-input-icon">
                                <i class="fa-solid fa-calendar-days"></i>
                            </span>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo htmlspecialchars($patient_data['tanggal_lahir'] ?? ''); ?>" required class="w-full kiosk-input">
                        </div>
                    </div>
                    <div class="kiosk-form-group">
                        <label>JENIS KELAMIN <span class="text-rose-500">*</span></label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="w-full kiosk-select">
                            <option value="Laki-laki" <?php echo (($patient_data['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo (($patient_data['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="kiosk-form-group">
                    <label>NOMOR HP / WHATSAPP <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="kiosk-input-icon">
                            <i class="fa-solid fa-phone"></i>
                        </span>
                        <input type="text" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($patient_data['no_hp'] ?? ''); ?>" required placeholder="Contoh: 08123456789" class="w-full kiosk-input">
                    </div>
                </div>

                <div class="kiosk-form-group">
                    <label>ALAMAT DOMISILI <span class="text-rose-500">*</span></label>
                    <textarea id="alamat" name="alamat" required placeholder="Masukkan alamat lengkap domisili saat ini..." class="w-full kiosk-textarea"><?php echo htmlspecialchars($patient_data['alamat'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-sky-600 to-blue-700 hover:from-sky-700 hover:to-blue-800 text-white rounded-2xl transition shadow-lg shadow-sky-600/30 flex items-center justify-center gap-3">
                    <span>Lanjut ke Pemilihan Penjamin</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- ================= STEP 2: PEMILIHAN PENJAMIN ================= -->
        <?php elseif ($step == 'payer'): ?>
        <div class="bg-white/90 backdrop-blur-2xl rounded-3xl shadow-2xl border border-white/80 p-8 md:p-12 space-y-8 max-w-2xl mx-auto relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-56 h-56 bg-sky-400/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-56 h-56 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex justify-between items-center relative z-10">
                <form method="POST">
                    <input type="hidden" name="action_step" value="back_to_welcome">
                    <button type="submit" class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-500 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-4 py-3 rounded-xl transition">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Sebelumnya
                    </button>
                </form>
            </div>

            <div class="text-center space-y-3 relative z-10">
                <div class="w-20 h-20 bg-gradient-to-tr from-sky-500 to-blue-600 text-white rounded-3xl flex items-center justify-center text-3xl mx-auto shadow-xl shadow-sky-500/30 ring-8 ring-sky-50">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Metode Pembayaran / Penjamin</h2>
                    <p class="text-xs md:text-sm text-slate-500 mt-1">Halo, <b class="text-sky-700 font-bold text-sm"><?php echo htmlspecialchars($patient_data['nama'] ?? ''); ?></b>. Silakan pilih metode penjamin layanan kesehatan Anda:</p>
                </div>
            </div>

            <form method="POST" class="space-y-6 relative z-10">
                <input type="hidden" name="action_step" value="to_screening">

                <div class="space-y-4">
                    <?php $current_penjamin = $patient_data['penjamin'] ?? 'JKN'; ?>
                    
                    <!-- Kartu BPJS / JKN -->
                    <label class="card-penjamin flex items-center justify-between bg-white/80 border-2 border-slate-200/90 rounded-2xl cursor-pointer hover:border-sky-500 hover:bg-sky-50/40 transition-all shadow-sm group">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner flex-shrink-0">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <span class="block font-black text-base md:text-lg text-slate-900 group-hover:text-sky-800 transition-colors">BPJS Kesehatan / JKN</span>
                                <span class="block text-xs md:text-sm text-slate-500 font-medium mt-0.5">Penjaminan melalui jaminan kesehatan nasional</span>
                            </div>
                        </div>
                        <input type="radio" name="penjamin" value="JKN" <?php echo ($current_penjamin === 'JKN') ? 'checked' : ''; ?> required class="w-6 h-6 text-sky-600 accent-sky-600 focus:ring-sky-500 cursor-pointer flex-shrink-0 ml-4">
                    </label>

                    <!-- Kartu Umum / Mandiri -->
                    <label class="card-penjamin flex items-center justify-between bg-white/80 border-2 border-slate-200/90 rounded-2xl cursor-pointer hover:border-sky-500 hover:bg-sky-50/40 transition-all shadow-sm group">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner flex-shrink-0">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div>
                                <span class="block font-black text-base md:text-lg text-slate-900 group-hover:text-sky-800 transition-colors">Umum / Mandiri / Tunai</span>
                                <span class="block text-xs md:text-sm text-slate-500 font-medium mt-0.5">Pembayaran pribadi / tunai secara mandiri</span>
                            </div>
                        </div>
                        <input type="radio" name="penjamin" value="UMUM" <?php echo ($current_penjamin === 'UMUM') ? 'checked' : ''; ?> class="w-6 h-6 text-sky-600 accent-sky-600 focus:ring-sky-500 cursor-pointer flex-shrink-0 ml-4">
                    </label>

                    <!-- Kartu Asuransi / Perusahaan -->
                    <label class="card-penjamin flex items-center justify-between bg-white/80 border-2 border-slate-200/90 rounded-2xl cursor-pointer hover:border-sky-500 hover:bg-sky-50/40 transition-all shadow-sm group">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner flex-shrink-0">
                                <i class="fa-solid fa-building-shield"></i>
                            </div>
                            <div>
                                <span class="block font-black text-base md:text-lg text-slate-900 group-hover:text-sky-800 transition-colors">Asuransi Lain / Perusahaan</span>
                                <span class="block text-xs md:text-sm text-slate-500 font-medium mt-0.5">Penjaminan pihak ketiga / korporasi / asuransi swasta</span>
                            </div>
                        </div>
                        <input type="radio" name="penjamin" value="ASURANSI" <?php echo ($current_penjamin === 'ASURANSI') ? 'checked' : ''; ?> class="w-6 h-6 text-sky-600 accent-sky-600 focus:ring-sky-500 cursor-pointer flex-shrink-0 ml-4">
                    </label>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-sky-600 to-blue-700 hover:from-sky-700 hover:to-blue-800 text-white rounded-2xl transition-all duration-200 shadow-xl shadow-sky-600/30 flex items-center justify-center gap-3 group">
                    <span class="text-base font-bold">Mulai Screening Gawat Darurat</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1.5 transition-transform"></i>
                </button>
            </form>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const payerCards = document.querySelectorAll('.card-penjamin');
            payerCards.forEach(card => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio.checked) {
                    card.classList.remove('bg-white/80', 'border-slate-200/90');
                    card.classList.add('bg-sky-50/90', 'border-sky-500', 'shadow-md', 'scale-[1.01]');
                }
                card.addEventListener('click', function(e) {
                    if (e.target !== radio) {
                        radio.checked = true;
                    }
                    payerCards.forEach(c => {
                        c.classList.remove('bg-sky-50/90', 'border-sky-500', 'shadow-md', 'scale-[1.01]');
                        c.classList.add('bg-white/80', 'border-slate-200/90');
                    });
                    if (radio.checked) {
                        card.classList.remove('bg-white/80', 'border-slate-200/90');
                        card.classList.add('bg-sky-50/90', 'border-sky-500', 'shadow-md', 'scale-[1.01]');
                    }
                });
            });
        });
        </script>

        <!-- ================= STEP 3: SCREENING INTERAKTIF ================= -->
        <?php elseif ($step == 'screening'): ?>
        <div class="screening-kiosk max-w-6xl mx-auto">
            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 md:px-10 pt-7 md:pt-9 pb-5 border-b border-slate-100">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-50 text-sky-700 text-xs font-black uppercase tracking-wide mb-3">
                                <i class="fa-solid fa-hand-pointer"></i> Mode Anjungan Touchscreen
                            </div>
                            <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Screening Awal IGD</h2>
                            <p class="text-sm md:text-base text-slate-500 mt-1.5">Jawab pertanyaan berikut sesuai kondisi pasien saat ini. Tekan pilihan yang paling sesuai.</p>
                            <p class="text-sm text-slate-600 mt-2">Pasien: <b class="text-slate-900"><?php echo htmlspecialchars($patient_data['nama'] ?? ''); ?></b> <?php if(!empty($patient_data['no_rm'])): ?> · No. RM: <?php echo htmlspecialchars($patient_data['no_rm']); ?><?php endif; ?> · Penjamin: <b class="text-sky-700"><?php echo htmlspecialchars($patient_data['penjamin'] ?? 'UMUM'); ?></b></p>
                        </div>
                        <div class="flex gap-2 shrink-0 no-print">
                            <form method="POST">
                                <input type="hidden" name="action_step" value="back_to_payer">
                                <button type="submit" class="touch-nav-btn"><i class="fa-solid fa-arrow-left"></i><span>Kembali ke Halaman Sebelumnya</span></button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-7">
                        <div class="flex items-center justify-between text-sm font-black text-slate-600 mb-2">
                            <span id="wizardStepLabel">Langkah 1 dari 3</span>
                            <span id="wizardProgressText">0%</span>
                        </div>
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div id="wizardProgress" class="h-full rounded-full bg-gradient-to-r from-sky-500 to-teal-500 transition-all duration-500" style="width:0%"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 mt-3 text-[11px] md:text-xs font-bold text-slate-400">
                            <div id="dot1" class="wizard-dot active">1 · Keluhan</div>
                            <div id="dot2" class="wizard-dot">2 · Pertanyaan</div>
                            <div id="dot3" class="wizard-dot">3 · Konfirmasi</div>
                        </div>
                    </div>
                </div>

                <form method="POST" id="screeningForm">
                    <input type="hidden" name="action_step" value="submit_final">
                    <input type="hidden" name="penjamin" value="<?php echo htmlspecialchars($patient_data['penjamin'] ?? 'UMUM'); ?>">

                    <!-- STEP 1 -->
                    <section id="wizardPage1" class="wizard-page px-5 md:px-10 py-7 md:py-9">
                        <div class="wizard-intro">
                            <div class="wizard-big-icon">🩺</div>
                            <div>
                                <h3>Pertama, apa yang sedang dirasakan?</h3>
                                <p>Pilih <b>satu atau lebih</b> keluhan yang sedang dialami pasien. Jika tidak sesuai, Anda boleh melanjutkan tanpa memilih.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-7">
                        <?php
                        $keluhan_list = [
                            ['name'=>'keluhan_nyeri_dada','icon'=>'❤️','label'=>'Nyeri Dada','help'=>'Rasa sakit, tertekan, atau tidak nyaman di dada'],
                            ['name'=>'keluhan_sesak','icon'=>'🫁','label'=>'Sesak / Sulit Bernapas','help'=>'Napas terasa berat atau sulit bernapas'],
                            ['name'=>'keluhan_pingsan','icon'=>'🧠','label'=>'Pingsan / Penurunan Kesadaran','help'=>'Tidak sadar, sulit dibangunkan, atau pingsan'],
                            ['name'=>'keluhan_perdarahan','icon'=>'🩸','label'=>'Perdarahan Banyak','help'=>'Darah keluar banyak atau sulit berhenti'],
                            ['name'=>'keluhan_kejang','icon'=>'⚡','label'=>'Kejang','help'=>'Kejang sedang terjadi atau berulang'],
                            ['name'=>'keluhan_cedera','icon'=>'🤕','label'=>'Cedera / Kecelakaan','help'=>'Cedera akibat kecelakaan atau kejadian lain'],
                            ['name'=>'keluhan_demam','icon'=>'🌡️','label'=>'Demam','help'=>'Suhu tubuh meningkat atau terasa panas'],
                            ['name'=>'keluhan_muntah_diare','icon'=>'🤢','label'=>'Muntah / Diare','help'=>'Mual, muntah, atau buang air besar cair'],
                            ['name'=>'keluhan_kehamilan','icon'=>'🤰','label'=>'Masalah Kehamilan / Persalinan','help'=>'Keluhan terkait kehamilan atau proses persalinan'],
                            ['name'=>'keluhan_nyeri_berat','icon'=>'😣','label'=>'Nyeri Berat','help'=>'Nyeri yang dirasakan sangat kuat atau mengganggu aktivitas'],
                            ['name'=>'keluhan_bayi_anak','icon'=>'👶','label'=>'Keluhan Bayi / Anak','help'=>'Keluhan kesehatan pada bayi atau anak'],
                            ['name'=>'keluhan_lainnya_pilih','icon'=>'☑️','label'=>'Lainnya','help'=>'Keluhan tidak tercantum pada pilihan di atas'],
                        ];
                        foreach($keluhan_list as $k):
                        ?>
                            <label class="interactive-choice complaint-choice">
                                <input type="checkbox" name="<?= $k['name']; ?>" value="1" class="sr-only complaint-input">
                                <div class="choice-check"><i class="fa-solid fa-check"></i></div>
                                <span class="choice-icon"><?= $k['icon']; ?></span>
                                <span class="choice-title"><?= $k['label']; ?></span>
                                <span class="choice-help"><?= $k['help']; ?></span>
                            </label>
                        <?php endforeach; ?>
                        </div>

                        <div id="otherComplaintWrap" class="hidden mt-4">
                            <label for="keluhan_lainnya" class="block font-semibold mb-2">Tuliskan keluhan lainnya</label>
                            <textarea id="keluhan_lainnya" name="keluhan_lainnya" maxlength="500" rows="3" class="w-full rounded-xl border border-slate-300 p-3" placeholder="Jelaskan keluhan yang dirasakan pasien..."></textarea>
                            <small class="text-slate-500">Maksimal 500 karakter.</small>
                        </div>

                        <div id="complaintHint" class="info-hint mt-6">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Belum ada keluhan yang dipilih. Anda tetap dapat melanjutkan.</span>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" id="nextToQuestions" class="primary-touch-btn">
                                Lanjut ke Pertanyaan <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </section>

                    <!-- STEP 2 -->
                    <section id="wizardPage2" class="wizard-page hidden px-5 md:px-10 py-7 md:py-9">
                        <div class="wizard-intro">
                            <div class="wizard-big-icon">❓</div>
                            <div>
                                <h3>Beberapa pertanyaan penting</h3>
                                <p>Pilih <b>YA</b>, <b>TIDAK</b>, atau <b>TIDAK TAHU</b> jika Anda tidak yakin dengan kondisi pasien.</p>
                            </div>
                        </div>

                        <div id="questionArea" class="question-area mt-7">
                            <?php
                            $questions = [
                                ['q'=>'Apakah pasien sangat sulit bernapas, sesak saat diam, bibir tampak kebiruan, atau tidak mampu berbicara normal karena sesak?','name'=>'q_abc','icon'=>'🫁','explain'=>'Pilih YA jika salah satu kondisi tersebut dialami pasien.'],
                                ['q'=>'Apakah pasien tidak sadar, sulit dibangunkan, mengalami penurunan kesadaran, atau baru saja pingsan?','name'=>'q_kesadaran','icon'=>'🧠','explain'=>'Pilih YA jika salah satu kondisi tersebut dialami pasien.'],
                                ['q'=>'Apakah terdapat perdarahan yang banyak atau tidak berhenti?','name'=>'q_perdarahan','icon'=>'🩸','explain'=>'Pilih YA jika perdarahan terlihat banyak atau terus berlangsung dan tidak berhenti.'],
                                ['q'=>'Apakah pasien sedang mengalami kejang, kejang berulang, atau belum sadar seperti biasanya setelah kejang?','name'=>'q_kejang','icon'=>'⚡','explain'=>'Pilih YA jika salah satu kondisi tersebut dialami pasien.'],
                                ['q'=>'Apakah pasien mengalami nyeri dada berat atau terasa tertekan, terutama disertai sesak napas, keringat dingin, atau hampir pingsan?','name'=>'q_nyeri_dada','icon'=>'❤️','explain'=>'Pilih YA jika nyeri dada disertai salah satu tanda tersebut.'],
                                ['q'=>'Apakah pasien mengalami cedera atau kecelakaan berat, misalnya benturan kepala, kecelakaan lalu lintas, atau cedera yang tampak mengancam nyawa?','name'=>'q_cedera','icon'=>'🤕','explain'=>'Pilih YA jika kondisi cedera atau kecelakaan tampak berat.'],
                                ['q'=>'Apakah pasien tiba-tiba mengalami wajah mencong, kelemahan pada satu sisi tubuh, atau kesulitan berbicara?','name'=>'q_stroke','icon'=>'🧠','explain'=>'Perhatikan gejala yang muncul secara tiba-tiba.'],
                                ['q'=>'Apakah pasien mengalami reaksi alergi berat, seperti sesak napas atau pembengkakan lidah/tenggorokan?','name'=>'q_alergi_berat','icon'=>'⚠️','explain'=>'Pilih YA jika muncul sesak atau pembengkakan pada lidah/tenggorokan setelah kemungkinan paparan pemicu.'],
                                ['q'=>'Jika pasien sedang hamil atau baru melahirkan, apakah terjadi perdarahan banyak, kejang, pingsan, atau nyeri perut hebat?','name'=>'q_obstetri','icon'=>'🤰','explain'=>'Jika tidak sedang hamil atau baru melahirkan, pilih TIDAK.'],
                                ['q'=>'Apakah gejala dirasakan berpotensi memburuk meskipun tidak ada tanda gawat darurat ekstrem di atas?','name'=>'potensi_memburuk','icon'=>'⚠️','explain'=>'Pilih YA jika kondisi/gejala terasa dapat memburuk meskipun tidak ada tanda ekstrem yang disebutkan sebelumnya.'],
                            ];
                            foreach($questions as $i=>$question):
                            ?>
                            <div class="screening-question-card hidden" data-question="<?= $i; ?>" data-name="<?= $question['name']; ?>">
                                <div class="question-number"><?= $i+1; ?></div>
                                <div class="question-icon"><?= $question['icon']; ?></div>
                                <div class="flex-1 min-w-0">
                                    <div class="question-counter">PERTANYAAN <?= $i+1; ?> DARI <?= count($questions); ?></div>
                                    <h4><?= $question['q']; ?></h4>
                                    <p><?= $question['explain']; ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="answerArea" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                            <button type="button" class="answer-btn answer-yes" data-answer="1">
                                <span class="answer-icon">✓</span>
                                <span><b>YA</b><small>Ya, saya mengalami kondisi ini</small></span>
                            </button>
                            <button type="button" class="answer-btn answer-no" data-answer="0">
                                <span class="answer-icon">✕</span>
                                <span><b>TIDAK</b><small>Tidak, kondisi tersebut tidak dialami</small></span>
                            </button>
                            <button type="button" class="answer-btn answer-unknown" data-answer="2">
                                <span class="answer-icon">?</span>
                                <span><b>TIDAK TAHU</b><small>Tidak yakin / tidak dapat memastikan</small></span>
                            </button>
                        </div>

                        <div class="question-navigation">
                            <button type="button" id="backToComplaints" class="secondary-touch-btn"><i class="fa-solid fa-arrow-left"></i> Kembali</button>
                            <div class="text-sm text-slate-500 font-semibold text-center" id="answerStatus">Pilih salah satu jawaban untuk melanjutkan</div>
                            <button type="button" id="nextQuestion" class="primary-touch-btn small-primary" disabled>Lanjut <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </section>

                    <!-- STEP 3 -->
                    <section id="wizardPage3" class="wizard-page hidden px-5 md:px-10 py-7 md:py-9">
                        <div class="wizard-intro">
                            <div class="wizard-big-icon">🔎</div>
                            <div>
                                <h3>Periksa kembali jawaban Anda</h3>
                                <p>Pastikan semua jawaban sudah sesuai kondisi pasien sebelum data dikirim.</p>
                            </div>
                        </div>

                        <div class="summary-grid mt-7">
                            <div class="summary-card">
                                <div class="summary-card-title"><span>🩺</span> Keluhan yang dipilih</div>
                                <div id="summaryComplaints" class="summary-content"></div>
                            </div>
                            <div class="summary-card">
                                <div class="summary-card-title"><span>❓</span> Jawaban screening</div>
                                <div id="summaryAnswers" class="summary-content"></div>
                            </div>
                        </div>

                        <div class="confirmation-box mt-6">
                            <i class="fa-solid fa-circle-check"></i>
                            <div>
                                <b>Data sudah sesuai?</b>
                                <p>Jika masih ada yang salah, gunakan tombol kembali untuk mengubah jawaban.</p>
                            </div>
                        </div>

                        <div class="wizard-actions dual-actions">
                            <button type="button" id="backToQuestions" class="secondary-touch-btn"><i class="fa-solid fa-arrow-left"></i> Ubah Jawaban</button>
                            <button type="submit" id="finalSubmit" class="primary-touch-btn submit-touch-btn"><i class="fa-solid fa-paper-plane"></i> Ya, Kirim & Proses Pendaftaran</button>
                        </div>
                    </section>
                </form>
            </div>

            <div class="mt-5 text-center text-xs md:text-sm text-slate-500 flex items-center justify-center gap-2">
                <i class="fa-solid fa-shield-heart text-emerald-500"></i>
                <span>Jawaban digunakan sebagai <b>screening awal</b> dan bukan pengganti pemeriksaan dokter.</span>
            </div>
        </div>

        <script>
        (function(){
            const root = document.querySelector('.screening-kiosk');
            if (!root || root.dataset.initialized === '1') return;
            root.dataset.initialized = '1';

            const pages = [root.querySelector('#wizardPage1'), root.querySelector('#wizardPage2'), root.querySelector('#wizardPage3')];
            const progress = root.querySelector('#wizardProgress');
            const progressText = root.querySelector('#wizardProgressText');
            const stepLabel = root.querySelector('#wizardStepLabel');
            const dots = [root.querySelector('#dot1'), root.querySelector('#dot2'), root.querySelector('#dot3')];
            const complaintInputs = [...root.querySelectorAll('.complaint-input')];
            const complaintHint = root.querySelector('#complaintHint');
            const questions = [...root.querySelectorAll('.screening-question-card')];
            const nextQuestion = root.querySelector('#nextQuestion');
            const answerStatus = root.querySelector('#answerStatus');
            const answers = {};
            let currentQuestion = 0;
            let currentStep = 1;

            function setStep(step){
                currentStep = step;
                pages.forEach((page, i) => page.classList.toggle('hidden', i !== step-1));
                const pct = step === 1 ? 33 : (step === 2 ? 66 : 100);
                progress.style.width = pct + '%';
                progressText.textContent = pct + '%';
                stepLabel.textContent = 'Langkah ' + step + ' dari 3';
                dots.forEach((dot,i)=>{
                    dot.classList.toggle('active', i === step-1);
                    dot.classList.toggle('done', i < step-1);
                });
                window.scrollTo({top: root.offsetTop - 20, behavior:'smooth'});
            }

            function syncComplaintCard(input){
                const card = input.closest('.complaint-choice');
                card.classList.toggle('selected', input.checked);
            }

            const otherChoice = root.querySelector('input[name="keluhan_lainnya_pilih"]');
            const otherWrap = root.querySelector('#otherComplaintWrap');
            const otherText = root.querySelector('#keluhan_lainnya');
            if (otherChoice) otherChoice.addEventListener('change', () => {
                otherWrap.classList.toggle('hidden', !otherChoice.checked);
                if (!otherChoice.checked) otherText.value = '';
            });

            complaintInputs.forEach(input=>{
                input.addEventListener('change', function(){
                    syncComplaintCard(input);
                    const count = complaintInputs.filter(x=>x.checked).length;
                    complaintHint.innerHTML = count
                        ? '<i class="fa-solid fa-circle-check"></i><span><b>'+count+' keluhan dipilih.</b> Tekan tombol lanjut untuk menjawab pertanyaan berikutnya.</span>'
                        : '<i class="fa-solid fa-circle-info"></i><span>Belum ada keluhan yang dipilih. Anda tetap dapat melanjutkan.</span>';
                    complaintHint.classList.toggle('selected-hint', count > 0);
                });
            });

            root.querySelector('#nextToQuestions').addEventListener('click', function(){
                currentQuestion = 0;
                showQuestion();
                setStep(2);
            });

            function showQuestion(){
                questions.forEach((q,i)=>q.classList.toggle('hidden', i !== currentQuestion));
                const name = questions[currentQuestion].dataset.name;
                const hasAnswer = Object.prototype.hasOwnProperty.call(answers,name);
                root.querySelectorAll('.answer-btn').forEach(btn=>{
                    btn.classList.toggle('chosen', hasAnswer && btn.dataset.answer === answers[name]);
                });
                nextQuestion.disabled = !hasAnswer;
                answerStatus.textContent = hasAnswer ? 'Jawaban tersimpan. Tekan Lanjut.' : 'Pilih salah satu jawaban untuk melanjutkan';
                answerStatus.className = hasAnswer ? 'text-sm text-emerald-600 font-bold text-center' : 'text-sm text-slate-500 font-semibold text-center';
            }

            root.querySelectorAll('.answer-btn').forEach(btn=>{
                btn.addEventListener('click', function(){
                    const name = questions[currentQuestion].dataset.name;
                    answers[name] = btn.dataset.answer;
                    let hidden = root.querySelector('input[name="'+name+'"]');
                    if (!hidden){
                        hidden = document.createElement('input');
                        hidden.type='hidden';
                        hidden.name=name;
                        root.querySelector('#screeningForm').appendChild(hidden);
                    }
                    hidden.value = answers[name];
                    showQuestion();
                });
            });

            nextQuestion.addEventListener('click', function(){
                if (!Object.prototype.hasOwnProperty.call(answers, questions[currentQuestion].dataset.name)) return;
                if (currentQuestion < questions.length - 1){
                    currentQuestion++;
                    showQuestion();
                } else {
                    buildSummary();
                    setStep(3);
                }
            });

            root.querySelector('#backToComplaints').addEventListener('click', ()=>setStep(1));
            root.querySelector('#backToQuestions').addEventListener('click', ()=>{ showQuestion(); setStep(2); });

            function escapeHtml(value){ return String(value).replace(/[&<>\"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[ch])); }

            function buildSummary(){
                const selected = complaintInputs.filter(x=>x.checked);
                const complaintBox = root.querySelector('#summaryComplaints');
                complaintBox.innerHTML = selected.length
                    ? selected.map(x=>'<div class="summary-row"><span>'+x.closest('.complaint-choice').querySelector('.choice-icon').textContent+'</span><b>'+x.closest('.complaint-choice').querySelector('.choice-title').textContent+'</b></div>').join('') + (otherChoice && otherChoice.checked && otherText.value.trim() ? '<div class="summary-row"><span>✍️</span><b>'+escapeHtml(otherText.value.trim())+'</b></div>' : '')
                    : '<div class="summary-empty">Tidak ada keluhan khusus yang dipilih.</div>';

                const labels = {};
                questions.forEach(q=>{ labels[q.dataset.name] = q.querySelector('h4').textContent; });
                root.querySelector('#summaryAnswers').innerHTML = questions.map(q=>{
                    const raw = answers[q.dataset.name];
                    const val = raw === '1' ? 'YA' : (raw === '2' ? 'TIDAK TAHU' : 'TIDAK');
                    const cls = raw === '1' ? 'answer-summary yes' : (raw === '2' ? 'answer-summary unknown' : 'answer-summary no');
                    return '<div class="summary-row question-summary"><span class="summary-answer '+cls+'">'+val+'</span><span>'+labels[q.dataset.name]+'</span></div>';
                }).join('');
            }

            setStep(1);
        })();
        </script>

        <!-- ================= STEP 4: LAYAR HASIL ================= -->
        <?php elseif ($step == 'result' && $result_data): ?>
        <div id="printable-result" class="result-kiosk bg-white rounded-[2rem] shadow-2xl border border-slate-200/80 p-6 md:p-10 max-w-3xl mx-auto space-y-6 text-center">
            <div class="flex justify-end no-print">
                <form method="POST">
                    <input type="hidden" name="action_step" value="home">
                    <button type="submit" class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-500 hover:text-sky-600 bg-slate-100 hover:bg-sky-50 px-3.5 py-2.5 rounded-xl transition">
                        <i class="fa-solid fa-house"></i> Kembali ke Menu Utama
                    </button>
                </form>
            </div>

            <?php if (isset($result_data['red_flag']) && $result_data['red_flag'] == 1): ?>
            <div class="bg-rose-50 border-4 border-rose-500 rounded-3xl p-6 space-y-4 shadow-sm">
                <div class="text-5xl">🚨</div>
                <h2 class="text-xl font-black text-rose-700">INDIKASI KEGAWATDARURATAN</h2>
                <div class="bg-rose-600 text-white font-bold py-2 px-5 rounded-xl text-xs uppercase tracking-wider animate-pulse inline-block shadow-md">
                    JANGAN MENUNGGU
                </div>
                <p class="text-slate-700 text-xs font-medium">Berdasarkan jawaban Anda, terdapat tanda/gejala yang memerlukan penilaian segera oleh petugas IGD.<br><b>Pendaftaran mandiri dihentikan sementara.</b></p>
                <div class="bg-white p-4 rounded-2xl border border-rose-200 text-left space-y-1.5 shadow-sm">
                    <p class="text-xs text-slate-500">No. Registrasi: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['no_registrasi'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Nama: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['nama'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Keluhan: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['keluhan'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Status Penanganan: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['status_penanganan'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Status Pembayaran: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['status_pembayaran'] ?? '-'); ?></b></p>
                </div>
                <div class="bg-rose-100 p-4 rounded-2xl text-rose-900 font-bold text-xs uppercase tracking-wider border border-rose-200">
                    Silakan Menuju:<br><span class="text-sm font-black">🔴 AREA TRIASE IGD</span>
                </div>
                <button type="button" onclick="alert('PETUGAS IGD DIPERLUKAN. Silakan segera panggil atau minta bantuan petugas IGD yang berada di sekitar Anda. Jangan menunggu proses pendaftaran mandiri.')" class="w-full bg-rose-700 hover:bg-rose-800 active:scale-[0.99] text-white font-black text-base md:text-lg py-5 px-6 rounded-2xl shadow-xl shadow-rose-700/30 transition flex items-center justify-center gap-3 no-print">
                    <i class="fa-solid fa-bell-concierge text-2xl"></i>
                    <span>PANGGIL PETUGAS IGD</span>
                </button>
                <p class="text-xs text-rose-800 font-semibold">Tekan tombol ini, lalu segera minta bantuan petugas IGD secara langsung.</p>
            </div>
            <?php elseif (isset($result_data['red_flag']) && $result_data['red_flag'] == 2): ?>
            <div class="bg-amber-50 border-4 border-amber-500 rounded-3xl p-6 space-y-4 shadow-sm">
                <div class="text-5xl">⚠️</div>
                <h2 class="text-xl font-black text-amber-800">PERLU PENILAIAN PETUGAS</h2>
                <div class="bg-amber-100 text-amber-900 font-bold py-2 px-5 rounded-xl text-xs uppercase tracking-wider inline-block">JAWABAN BELUM DAPAT DIPASTIKAN</div>
                <p class="text-slate-700 text-xs font-medium">Ada jawaban “Tidak Tahu”. Screening mandiri belum dapat memastikan kondisi pasien. Mohon segera sampaikan hal ini kepada petugas IGD untuk penilaian langsung.</p>
                <div class="bg-white p-4 rounded-2xl border border-amber-200 text-left space-y-1.5 shadow-sm">
                    <p class="text-xs text-slate-500">No. Registrasi: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['no_registrasi'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Nama: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['nama'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Keluhan: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['keluhan'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Status Penanganan: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['status_penanganan'] ?? '-'); ?></b></p>
                </div>
                <div class="bg-amber-100 p-4 rounded-2xl text-amber-900 font-bold text-xs border border-amber-200">Silakan segera konfirmasi ke PETUGAS IGD untuk penilaian dan penentuan prioritas pelayanan.</div>
                <button type="button" onclick="alert('Mohon segera sampaikan kepada petugas IGD bahwa ada jawaban screening yang tidak dapat dipastikan.')" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-black text-base py-5 px-6 rounded-2xl shadow-xl transition no-print"><i class="fa-solid fa-bell-concierge"></i> PANGGIL PETUGAS IGD</button>
            </div>
            <?php else: ?>
            <div class="bg-emerald-50 border-4 border-emerald-500 rounded-3xl p-6 space-y-4 shadow-sm">
                <div class="text-5xl">🟢</div>
                <h2 class="text-xl font-black text-emerald-700">SCREENING AWAL SELESAI</h2>
                <p class="text-slate-700 text-xs font-medium">Dari jawaban yang Anda berikan, tidak ditemukan tanda bahaya mutlak pada screening mandiri.<br><span class="text-[10px] text-slate-400">(Namun, hasil ini bukan diagnosis medis).</span></p>
                <div class="bg-white p-4 rounded-2xl border border-emerald-200 text-left space-y-1.5 shadow-sm">
                    <p class="text-xs text-slate-500">No. Registrasi: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['no_registrasi'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Nama: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['nama'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Penjamin: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['penjamin'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Status Pembayaran: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['status_pembayaran'] ?? '-'); ?></b></p>
                    <p class="text-xs text-slate-500">Status: <b class="text-slate-800"><?php echo htmlspecialchars($result_data['status_penanganan'] ?? '-'); ?></b></p>
                </div>
                <div class="bg-emerald-100 p-4 rounded-2xl text-emerald-900 font-bold text-xs border border-emerald-200">
                    Silakan lanjut ke petugas IGD untuk pemeriksaan dan penentuan prioritas pelayanan.
                </div>
            </div>
            <?php endif; ?>

            <div class="no-print rounded-2xl bg-slate-50 border border-slate-200 px-5 py-4 flex items-center justify-center gap-3 text-sm font-bold text-slate-600">
                <i class="fa-solid fa-house text-sky-600"></i>
                <span>Kembali ke beranda otomatis dalam <b class="result-countdown text-sky-700">20</b> detik</span>
            </div>

            <div class="flex gap-4 pt-2 no-print">
                <button onclick="window.print()" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-bold py-3.5 rounded-2xl transition text-xs text-center flex items-center justify-center gap-2 shadow-md">
                    <i class="fa-solid fa-print"></i> Cetak Bukti Screening
                </button>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action_step" value="home">
                    <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-3.5 rounded-2xl transition text-xs text-center flex items-center justify-center shadow-md">
                        Selesai & Kembali ke Beranda
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 mt-12 no-print">
        SIMRS IGD Engine &copy; 2026 - Berdasarkan Permenkes No. 47 Tahun 2018
    </footer>

<script>
document.addEventListener("DOMContentLoaded", function(){
    const nik = document.querySelector('input[name="nik"]');
    if(nik){
        nik.addEventListener("input", function(){
            this.value = this.value.replace(/\D/g,"").slice(0,16);
            if(this.value.length===16){
                this.style.borderColor="#16a66a";
            }else{
                this.style.borderColor="";
            }
        });
    }

    const hp = document.querySelector('input[name="no_hp"]');
    if(hp){
        hp.addEventListener("input", function(){
            this.value=this.value.replace(/\D/g,"").slice(0,15);
        });
    }

    const progress=document.querySelector(".kiosk-progress");
    if(progress){
        setTimeout(function(){
            progress.scrollIntoView({behavior:"smooth",block:"start"});
        },120);
    }

    document.querySelectorAll("form").forEach(function(form){
        form.addEventListener("submit", function(){
            const btn=form.querySelector('button[type="submit"]');
            if(btn && !btn.dataset.locked){
                btn.dataset.locked="1";
                btn.style.opacity=".82";
                const original=btn.innerHTML;
                if(form.querySelector('input[name="action_step"][value="submit_final"]')){
                    btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses Screening...';
                }else if(form.querySelector('input[name="action_step"][value="to_payer"]')){
                    btn.innerHTML='Memproses Data... <i class="fa-solid fa-circle-notch fa-spin"></i>';
                }
                setTimeout(function(){
                    if(document.body.contains(btn) && btn.dataset.locked==="1"){
                        btn.innerHTML=original;
                        btn.dataset.locked="";
                        btn.style.opacity="";
                    }
                },7000);
            }
        });
    });

    document.addEventListener("keydown",function(e){
        if(e.key==="Escape"){
            const home=document.querySelector('input[name="action_step"][value="home"]');
            if(home && document.activeElement.tagName!=="INPUT" && document.activeElement.tagName!=="TEXTAREA"){
                home.closest("form")?.submit();
            }
        }
    });
    const resultPage=document.querySelector('.result-kiosk');
    if(resultPage){
        const homeForm=resultPage.querySelector('form input[name="action_step"][value="home"]')?.closest('form');
        const finishBtn=resultPage.querySelector('button[type="submit"]');
        const countdown=resultPage.querySelector('.result-countdown');
        let seconds=20;
        let timer=setInterval(function(){
            if(!countdown) return;
            seconds--;
            countdown.textContent=seconds;
            if(seconds<=0){ clearInterval(timer); if(homeForm) homeForm.submit(); }
        },1000);
        if(finishBtn){
            finishBtn.addEventListener('click',function(){ clearInterval(timer); });
        }
    }

});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const rmInput = document.getElementById('no_rm');
    const searchButton = document.getElementById('btnCariRM');
    const status = document.getElementById('statusCariRM');
    if (!rmInput || !searchButton || !status) return;

    async function cariDataPasien() {
        const noRm = rmInput.value.trim();
        if (!noRm) {
            status.textContent = 'Masukkan Nomor RM terlebih dahulu.';
            status.className = 'mt-2 text-sm text-rose-600';
            rmInput.focus();
            return;
        }
        searchButton.disabled = true;
        searchButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Mencari...';
        status.textContent = '';
        try {
            const response = await fetch('?ajax=lookup_rm&no_rm=' + encodeURIComponent(noRm), {
                headers: {'Accept': 'application/json'}
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success' || !result.patient) {
                status.textContent = response.status === 404
                    ? 'Data No. RM tidak ditemukan. Silakan isi data pasien secara manual.'
                    : (result.message || 'Pencarian gagal. Silakan coba kembali.');
                status.className = 'mt-2 text-sm text-amber-700';
                return;
            }
            const patient = result.patient;
            ['nik', 'nama', 'tanggal_lahir', 'jenis_kelamin', 'no_hp', 'alamat'].forEach(function (field) {
                const el = document.getElementById(field);
                if (el && patient[field] !== null && patient[field] !== undefined) {
                    el.value = patient[field];
                    el.dispatchEvent(new Event('change', {bubbles: true}));
                }
            });
            status.textContent = 'Data pasien ditemukan dan terisi otomatis. Silakan periksa kembali.';
            status.className = 'mt-2 text-sm text-emerald-700';
        } catch (e) {
            status.textContent = 'Layanan pencarian tidak dapat dihubungi. Silakan isi data secara manual.';
            status.className = 'mt-2 text-sm text-rose-600';
        } finally {
            searchButton.disabled = false;
            searchButton.innerHTML = '<i class="fa-solid fa-magnifying-glass mr-2"></i>Cari Data Pasien';
        }
    }
    searchButton.addEventListener('click', cariDataPasien);
    rmInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            cariDataPasien();
        }
    });
});
</script>

</body>
</html>