from datetime import datetime
import sqlite3
from flask import Flask, jsonify, request
import numpy as np
import pandas as pd
from pathlib import Path
from sklearn.tree import DecisionTreeClassifier

app = Flask(__name__)

# --- DATA MINING: DECISION TREE DARI DATASET CSV ---
# Dataset simulasi hanya untuk uji teknis. Ganti dengan data berlabel
# hasil triase yang dianonimkan dan divalidasi tim klinis sebelum riset/penerapan.
FEATURE_COLUMNS = [
    "q_abc", "q_kesadaran", "q_perdarahan", "q_kejang", "q_nyeri_dada",
    "q_cedera", "q_stroke", "q_alergi_berat", "q_obstetri", "potensi_memburuk"
]
DATASET_PATH = Path(__file__).resolve().with_name("dataset_simulasi_igd_100.csv")


def train_decision_tree_model():
    if not DATASET_PATH.exists():
        raise FileNotFoundError(
            f"Dataset tidak ditemukan: {DATASET_PATH}. Letakkan CSV di folder yang sama dengan API."
        )

    dataset = pd.read_csv(DATASET_PATH)
    required_columns = FEATURE_COLUMNS + ["label_simulasi"]
    missing = [col for col in required_columns if col not in dataset.columns]
    if missing:
        raise ValueError(f"Kolom dataset tidak lengkap: {', '.join(missing)}")
    if dataset.empty:
        raise ValueError("Dataset kosong.")

    X_train = dataset[FEATURE_COLUMNS].apply(pd.to_numeric, errors="raise")
    # Klasifikasi biner untuk mendukung logika red-flag yang sudah ada.
    # Kelas simulasi lain tetap bukan label klinis atau bukti pasien aman.
    y_train = (dataset["label_simulasi"].astype(str) == "SIMULASI_RED_FLAG").astype(int)
    if y_train.nunique() < 2:
        raise ValueError("Dataset harus memiliki contoh untuk kedua kelas target.")

    clf = DecisionTreeClassifier(random_state=42, max_depth=4, class_weight="balanced")
    clf.fit(X_train, y_train)
    return clf


# Inisialisasi model Decision Tree saat server berjalan
ml_model = train_decision_tree_model()


# Inisialisasi Database SQLite
def init_db():
  conn = sqlite3.connect('igd_database.db')
  cursor = conn.cursor()
  cursor.execute("""
        CREATE TABLE IF NOT EXISTS patients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            no_registrasi TEXT,
            nik TEXT,
            nama TEXT,
            tanggal_lahir TEXT,
            jenis_kelamin TEXT,
            no_hp TEXT,
            alamat TEXT,
            keluhan TEXT,
            penjamin TEXT,
            red_flag INTEGER,
            status_penanganan TEXT,
            status_pembayaran TEXT,
            waktu_datang TEXT
        )
    """)
  conn.commit()
  conn.close()


init_db()



@app.route('/api/pasien/by-rm', methods=['GET'])
def cari_pasien_by_rm():
    # Kolom No. RM pada form ini berisi nomor registrasi pasien.
    no_rm = str(request.args.get('no_rm', request.args.get('no_registrasi', ''))).strip()
    if not no_rm:
        return jsonify({'status': 'error', 'message': 'Nomor registrasi wajib diisi.'}), 400

    conn = sqlite3.connect('igd_database.db')
    conn.row_factory = sqlite3.Row
    try:
        cursor = conn.cursor()
        cursor.execute(
            "SELECT no_registrasi AS no_rm, nik, nama, tanggal_lahir, "
            "jenis_kelamin, no_hp, alamat FROM patients "
            "WHERE TRIM(COALESCE(no_registrasi, '')) = ? "
            "ORDER BY id DESC LIMIT 1",
            (no_rm,)
        )
        patient = cursor.fetchone()
        if patient is None:
            return jsonify({'status': 'error', 'message': 'Nomor registrasi tidak ditemukan pada database pasien.'}), 404
        return jsonify({'status': 'success', 'patient': dict(patient)})
    finally:
        conn.close()


@app.route('/')
def home():
  return '<h3>Server Flask IGD Aktif (Dilengkapi Data Mining Decision Tree) & Terhubung ke Database!</h3>'


@app.route('/api/screening', methods=['POST'])
def proses_screening():
  try:
    data = request.json

    nik = data.get('nik', '-')
    nama = data.get('nama', 'Tanpa Nama')
    tanggal_lahir = data.get('tanggal_lahir', '-')
    jenis_kelamin = data.get('jenis_kelamin', 'L')
    no_hp = data.get('no_hp', '-')
    alamat = data.get('alamat', '-')
    penjamin = str(data.get('penjamin', 'NONE')).strip().upper()
    membership_status = str(data.get('membership_status', 'INACTIVE')).strip().upper()

    # Parameter Screening Red Flag
    q_abc = int(data.get('q_abc', 0))
    q_kesadaran = int(data.get('q_kesadaran', 0))
    q_perdarahan = int(data.get('q_perdarahan', 0))
    q_kejang = int(data.get('q_kejang', 0))
    q_nyeri_dada = int(data.get('q_nyeri_dada', 0))
    q_cedera = int(data.get('q_cedera', 0))
    q_stroke = int(data.get('q_stroke', 0))
    q_alergi_berat = int(data.get('q_alergi_berat', 0))
    q_obstetri = int(data.get('q_obstetri', 0))
    potensi_memburuk = int(data.get('potensi_memburuk', 0))
    
    jawaban_screening = [q_abc, q_kesadaran, q_perdarahan, q_kejang, q_nyeri_dada, q_cedera, q_stroke, q_alergi_berat, q_obstetri, potensi_memburuk]
    ada_tidak_tahu = any(jawaban == 2 for jawaban in jawaban_screening)

    # Prediksi menggunakan Model Data Mining (Decision Tree)
    input_features = np.array([jawaban_screening])
    prediksi_ml = ml_model.predict(input_features)[0]

    # Daftar Keluhan
    list_keluhan = []
    if data.get('keluhan_nyeri_dada') == '1':
      list_keluhan.append('Nyeri Dada')
    if data.get('keluhan_sesak') == '1':
      list_keluhan.append('Sesak Napas')
    if data.get('keluhan_pingsan') == '1':
      list_keluhan.append('Penurunan Kesadaran')
    if data.get('keluhan_perdarahan') == '1':
      list_keluhan.append('Perdarahan Banyak')
    if data.get('keluhan_kejang') == '1':
      list_keluhan.append('Kejang')
    if data.get('keluhan_cedera') == '1':
      list_keluhan.append('Cedera / Kecelakaan')
    if data.get('keluhan_demam') == '1':
      list_keluhan.append('Demam')
    if data.get('keluhan_muntah_diare') == '1':
      list_keluhan.append('Muntah / Diare')
    if data.get('keluhan_kehamilan') == '1':
      list_keluhan.append('Masalah Kehamilan / Persalinan')
    if data.get('keluhan_nyeri_berat') == '1':
      list_keluhan.append('Nyeri Berat')
    if data.get('keluhan_bayi_anak') == '1':
      list_keluhan.append('Keluhan Bayi / Anak')

    keluhan_lainnya = str(data.get('keluhan_lainnya', '')).strip()[:500]
    if keluhan_lainnya:
      list_keluhan.append('Lainnya: ' + keluhan_lainnya)

    keluhan_str = (
        ', '.join(list_keluhan)
        if list_keluhan
        else 'Keluhan Umum / Tidak Spesifik'
    )

    # Decision Engine memadukan Model Decision Tree Machine Learning & Aturan Permenkes
    if prediksi_ml == 1 or (
        q_abc == 1
        or q_kesadaran == 1
        or q_perdarahan == 1
        or q_kejang == 1
        or q_nyeri_dada == 1
        or q_cedera == 1
        or q_stroke == 1
        or q_alergi_berat == 1
        or q_obstetri == 1
    ):
      red_flag = 1
      status_penanganan = 'Segera Menuju Area Triase IGD (Level A) [ML Decision Tree Validated]'
    elif ada_tidak_tahu:
      red_flag = 2
      status_penanganan = 'Perlu Penilaian Petugas - Ada Jawaban Tidak Tahu'
    elif potensi_memburuk == 1:
      red_flag = 0
      status_penanganan = (
          'Waspada Potensi Memburuk - Prioritas Pengamatan (Level B)'
      )
    else:
      red_flag = 0
      status_penanganan = 'Non-Gawat / Rawat Jalan / Poliklinik (Level C/D)'

    if red_flag in (1, 2):
      send_to = 'TRIASE IGD'
      if penjamin == 'JKN' and membership_status == 'ACTIVE':
        status_pembayaran = 'BERPOTENSI DIJAMIN - VERIFIKASI PETUGAS'
      elif penjamin == 'ASURANSI':
        status_pembayaran = 'VERIFIKASI PENJAMIN'
      else:
        status_pembayaran = 'PEMBIAYAAN PERLU VERIFIKASI'
      hasil_screening = {
          'kode': 'RED_FLAG',
          'judul': 'INDIKASI KEGAWATDARURATAN',
          'peringatan': 'JANGAN MENUNGGU',
          'pesan': ('Berdasarkan analisis model keputusan & jawaban Anda, '
                    'terdapat tanda/gejala yang memerlukan penilaian segera.'),
          'pendaftaran_mandiri': 'DIHENTIKAN_SEMENTARA',
          'arahan': 'Silakan menuju AREA TRIASE IGD.',
          'keterangan': 'Petugas akan melakukan pemeriksaan prioritas.',
          'tombol': 'PANGGIL PETUGAS IGD'
      }
    else:
      send_to = 'TRIASE'
      if penjamin == 'JKN' and membership_status == 'ACTIVE':
        status_pembayaran = 'VERIFIKASI KESESUAIAN MANFAAT/JENIS PELAYANAN'
      elif penjamin in ('NONE', 'UMUM'):
        status_pembayaran = 'UMUM/TUNAI'
      else:
        status_pembayaran = 'VERIFIKASI PENJAMIN'
      hasil_screening = {
          'kode': 'PERLU_KONFIRMASI' if red_flag == 2 else 'TANPA_RED_FLAG',
          'judul': 'PERLU PENILAIAN PETUGAS' if red_flag == 2 else 'SCREENING AWAL SELESAI',
          'peringatan': 'JANGAN MENUNGGU PENILAIAN PETUGAS' if red_flag == 2 else None,
          'pesan': ('Ada jawaban yang belum pasti. Petugas perlu menilai langsung.' if red_flag == 2 else ('Model keputusan tidak mendeteksi indikasi red flag.')), 
          'pendaftaran_mandiri': 'SELESAI',
          'arahan': ('Silakan segera konfirmasi kepada petugas IGD.' if red_flag == 2 else ('Silakan lanjut ke petugas IGD untuk pemeriksaan lanjutan.')),
          'keterangan': 'Hasil ini merupakan analisis sistem pendukung keputusan.',
          'tombol': 'KONFIRMASI KE PETUGAS IGD' if red_flag == 2 else 'LANJUT KE TRIASE'
      }

    pembiayaan_sementara = {
        'judul': 'INFORMASI PEMBIAYAAN SEMENTARA',
        'penjamin': penjamin,
        'status_kepesertaan': ('Aktif' if membership_status == 'ACTIVE' else 'Belum terverifikasi'),
        'status_kegawatdaruratan': 'Menunggu penilaian klinis petugas',
        'status_akhir': 'Status akhir pembiayaan ditentukan setelah verifikasi.'
    }

    no_registrasi = f"IGD-{datetime.now().strftime('%d%m%y%H%M%S')}"
    waktu_datang = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    # Simpan ke Database SQLite
    conn = sqlite3.connect('igd_database.db')
    cursor = conn.cursor()
    cursor.execute(
        """
            INSERT INTO patients (no_registrasi, nik, nama, tanggal_lahir, jenis_kelamin, no_hp, alamat, keluhan, penjamin, red_flag, status_penanganan, status_pembayaran, waktu_datang)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """,
        (
            no_registrasi,
            nik,
            nama,
            tanggal_lahir,
            jenis_kelamin,
            no_hp,
            alamat,
            keluhan_str,
            penjamin,
            red_flag,
            status_penanganan,
            status_pembayaran,
            waktu_datang,
        ),
    )
    conn.commit()
    conn.close()

    return jsonify({
        'status': 'success',
        'patient': {
            'no_registrasi': no_registrasi,
            'nik': nik,
            'nama': nama,
            'keluhan': keluhan_str,
            'penjamin': penjamin,
            'membership_status': membership_status,
            'send_to': send_to,
            'red_flag': red_flag,
            'prediksi_model_simulasi': int(prediksi_ml),
            'catatan_model': 'Prediksi berbasis dataset simulasi untuk uji teknis, bukan keputusan klinis.',
            'status_penanganan': status_penanganan,
            'status_pembayaran': status_pembayaran,
            'hasil_screening': hasil_screening,
            'informasi_pembiayaan': pembiayaan_sementara,
        },
    })

  except Exception as e:
    return jsonify({'status': 'error', 'message': str(e)}), 400


@app.route('/api/data-mining/ringkasan', methods=['GET'])
def ringkasan_data_mining():
    """Analisis deskriptif pola data screening yang tersimpan."""
    conn = sqlite3.connect('igd_database.db')
    conn.row_factory = sqlite3.Row
    try:
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) AS total FROM patients")
        total = cursor.fetchone()['total']

        cursor.execute("""
            SELECT keluhan, COUNT(*) AS jumlah
            FROM patients
            GROUP BY keluhan
            ORDER BY jumlah DESC
            LIMIT 10
        """)
        pola_keluhan = [dict(row) for row in cursor.fetchall()]

        cursor.execute("""
            SELECT red_flag, COUNT(*) AS jumlah
            FROM patients
            GROUP BY red_flag
            ORDER BY red_flag
        """)
        distribusi_screening = [
            {
                'kategori': ('Indikasi kegawatdaruratan' if row['red_flag'] == 1
                             else 'Perlu konfirmasi petugas' if row['red_flag'] == 2
                             else 'Tidak terdeteksi red flag'),
                'jumlah': row['jumlah']
            }
            for row in cursor.fetchall()
        ]

        cursor.execute("""
            SELECT status_penanganan, COUNT(*) AS jumlah
            FROM patients
            GROUP BY status_penanganan
            ORDER BY jumlah DESC
        """)
        pola_penanganan = [dict(row) for row in cursor.fetchall()]

        return jsonify({
            'status': 'success',
            'jenis_analisis': 'data mining decision tree & deskriptif',
            'total_data': total,
            'pola_keluhan_terbanyak': pola_keluhan,
            'distribusi_hasil_screening': distribusi_screening,
            'pola_status_penanganan': pola_penanganan,
            'catatan': 'Analisis memadukan model klasifikasi Decision Tree dan statistik rekapitulasi data kunjungan.'
        })
    finally:
        conn.close()


@app.route('/dashboard')
def dashboard_ig():
  conn = sqlite3.connect('igd_database.db')
  conn.row_factory = sqlite3.Row
  cursor = conn.cursor()
  cursor.execute("""
        SELECT no_registrasi, nama, keluhan, penjamin, status_penanganan, status_pembayaran, waktu_datang, red_flag 
        FROM patients ORDER BY id DESC
    """)
  patients = cursor.fetchall()
  conn.close()

  html_content = '<h2>Dashboard Petugas IGD</h2><table border="1" cellpadding="5"><tr><th>No Reg</th><th>Nama</th><th>Keluhan</th><th>Penjamin</th><th>Status Penanganan</th><th>Waktu Datang</th></tr>'
  for p in patients:
    html_content += f"<tr><td>{p['no_registrasi']}</td><td>{p['nama']}</td><td>{p['keluhan']}</td><td>{p['penjamin']}</td><td>{p['status_penanganan']}</td><td>{p['waktu_datang']}</td></tr>"
  html_content += '</table>'
  return html_content


if __name__ == '__main__':
  app.run(host='127.0.0.1', port=5000, debug=True)