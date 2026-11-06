// Teks UI Rahina (bahasa Indonesia). Tanpa emoji; sebut fungsi, bukan teknologi (DESIGN_SYSTEM.md §1.1, §9).
import type { Verdict } from '@/types/rahina';

export const id = {
    appName: 'Rahina',
    today: {
        title: 'Rahina Ini',
        summaryHeading: 'Ringkasan hari ini',
        prevDay: 'Hari sebelumnya',
        nextDay: 'Hari berikutnya',
        backToToday: 'Kembali ke hari ini',
        sourceLine: (count: number) =>
            count > 0
                ? `Disusun dari perhitungan wariga · ${count} sumber`
                : 'Disusun dari perhitungan wariga',
        templateNotice:
            'Ringkasan otomatis belum bisa disusun. Poin di atas disusun langsung dari data perhitungan.',
        rawHeading: 'Data perhitungan',
        dewasaHeading: 'Dewasa hari ini',
        noDewasa:
            'Belum ada dewasa tervalidasi untuk tanggal ini. Aturan dewasa ditampilkan setelah diperiksa narasumber.',
        notAvailable:
            'Tanggal ini belum tersedia di kalender Rahina (2020-2035).',
        sheetTitle: 'Sumber & data mentah',
        sheetDescription:
            'Daftar dewasa beserta sumber tertulisnya, dan hasil perhitungan wariga untuk tanggal ini.',
        source: 'Sumber',
        page: 'hlm.',
        alahing: 'Alahing',
        unreviewed: 'Belum ditinjau narasumber Rahina',
        rahinan: 'Rahinan',
        wuku: 'Wuku',
        sasih: 'Sasih',
        ingkel: 'Ingkel',
        uripTotal: 'Urip (saptawara + pancawara)',
        saka: 'Tahun Saka',
        ngunaratri: 'ngunaratri',
        nampih: 'nampih',
        penanggal: 'Penanggal',
        pangelong: 'Pangelong',
        purnama: 'Purnama',
        tilem: 'Tilem',
        engine: 'Mesin hitung',
    },
    calendar: {
        title: 'Kalender',
        today: 'Hari ini',
        prevMonth: 'Bulan sebelumnya',
        nextMonth: 'Bulan berikutnya',
        upcoming: 'Akan datang',
        daysUntil: (n: number) => (n === 0 ? 'Hari ini' : `H-${n}`),
        legendPurnama: 'Purnama',
        legendTilem: 'Tilem',
        legendToday: 'Hari ini',
        legendPenanggal: 'Penanggal',
        legendPangelong: 'Pangelong',
        legendNgunaratri: 'Ngunaratri',
        hint: 'Pilih tanggal untuk melihat ringkasan dan detail',
        sasihLabel: 'Sasih',
        notAvailable: 'Belum tersedia',
        dewasaStrip: (baik: number, buruk: number) =>
            `${baik} dewasa baik, ${buruk} dewasa kurang baik`,
    },
    detail: {
        loading: 'Menyusun ringkasan hari ini…',
        loadError:
            'Detail tanggal belum bisa dimuat. Periksa koneksi lalu coba lagi.',
        retry: 'Coba lagi',
        close: 'Tutup',
        mainInfo: 'Informasi utama',
        wewaran: 'Wewaran',
        paringkelan: 'Paringkelan',
        urip: 'Urip',
        sasihDay: 'Hari sasih',
        paringkelanNote:
            'Nama hasil perhitungan. Penjelasan arti ditampilkan setelah ada sumber tervalidasi.',
    },
    paringkelan: {
        jejepan: 'Jejepan',
        lintang: 'Lintang',
        panca_suda: 'Pancasuda',
        rakam: 'Rakam',
        eka_jala_rsi: 'Eka Jala Rsi',
        watek_alit: 'Watek Alit',
        watek_madya: 'Watek Madya',
        pararasan: 'Pararasan',
        pratithi_samut_pada: 'Pratithi Samut Pada',
    } as Record<string, string>,
    nav: {
        label: 'Menu utama',
        home: 'Beranda',
        calendar: 'Kalender',
        ask: 'Tanya Wariga',
        profile: 'Profil',
        login: 'Masuk',
        register: 'Daftar',
    },
    auth: {
        loginTitle: 'Masuk',
        loginHeading: 'Masuk ke Rahina',
        loginDescription: 'Masuk dengan nomor HP dan kata sandi.',
        registerTitle: 'Daftar',
        registerHeading: 'Buat akun Rahina',
        registerDescription:
            'Cukup nama dan nomor HP. Nomor yang sama dipakai untuk ringkasan pagi via WhatsApp.',
        name: 'Nama',
        namePlaceholder: 'Nama panggilan atau lengkap',
        phone: 'Nomor HP (WhatsApp)',
        phonePlaceholder: '0812 3456 7890',
        phoneHint:
            'Gunakan nomor yang aktif di WhatsApp. Nomor tidak dikirim ke layanan AI.',
        password: 'Kata sandi',
        passwordConfirm: 'Ulangi kata sandi',
        remember: 'Ingat saya',
        loginButton: 'Masuk',
        registerButton: 'Buat akun',
        noAccount: 'Belum punya akun?',
        registerLink: 'Daftar',
        haveAccount: 'Sudah punya akun?',
        loginLink: 'Masuk',
        forgotHint:
            'Lupa kata sandi? Hubungi tim Rahina lewat halaman Kontak untuk bantuan atur ulang.',
    },
    profile: {
        title: 'Profil',
        account: 'Akun',
        accountSettings: 'Pengaturan akun',
        logout: 'Keluar',
        waTitle: 'Rahina Pagi via WhatsApp',
        waIntro: (time: string) =>
            `Terima ringkasan hari menurut wariga setiap pukul ${time} WITA, dan balas pesannya untuk bertanya hari baik.`,
        consentDaily: (time: string) =>
            `Saya setuju menerima ringkasan harian Rahina via WhatsApp pukul ${time} WITA.`,
        birthDate: 'Tanggal lahir (opsional, untuk pengingat otonan)',
        consentBirthdate:
            'Saya setuju tanggal lahir dipakai untuk menghitung otonan. Tanggal lahir tidak dikirim ke layanan AI.',
        enable: 'Aktifkan Rahina Pagi',
        pendingTitle: 'Satu langkah lagi: hubungkan nomor WhatsApp',
        pendingText: (phone: string | null) =>
            `Tekan tombol di bawah, lalu kirim pesan "DAFTAR" dari WhatsApp nomor ${phone ?? 'akun Anda'}. Langsung tersambung, tanpa kode.`,
        connect: 'Hubungkan WhatsApp',
        codeLabel: 'Kode pendaftaran',
        otherNumber: (code: string, minutes: number) =>
            `Memakai WhatsApp dengan nomor lain? Kirim "DAFTAR ${code}" dari nomor itu (kode berlaku ${minutes} menit).`,
        otherNumberButton: 'Pakai nomor lain',
        newCode: 'Buat kode baru',
        refresh: 'Saya sudah mengirim, periksa status',
        deviceMissing: 'Nomor WhatsApp Rahina belum tersedia. Coba lagi nanti.',
        status: 'Status',
        statuses: {
            pending: 'Menunggu nomor dihubungkan',
            active: 'Aktif',
            paused: 'Dijeda',
            opted_out: 'Dihentikan lewat WhatsApp',
            failed: 'Gagal terkirim berulang kali',
        } as Record<string, string>,
        phone: 'Nomor',
        lastSent: 'Terakhir dikirim',
        never: 'Belum pernah',
        pause: 'Jeda pengiriman',
        resume: 'Lanjutkan pengiriman',
        saveBirthDate: 'Simpan tanggal lahir',
        removeBirthDate: 'Hapus tanggal lahir',
        changePhone: 'Ganti nomor WhatsApp',
        changePhoneTitle: 'Ubah Nomor WhatsApp Rahina Pagi',
        newPhoneLabel: 'Nomor WhatsApp baru',
        newPhonePlaceholder: '0812 3456 7890',
        useAccountPhone: (phone: string | null) =>
            `Gunakan nomor akun (${phone ?? ''})`,
        savePhone: 'Simpan nomor WhatsApp',
        connectDirectlyButton: 'Hubungkan Langsung Sekarang',
        connectDirectlyHint:
            'Aktifkan langsung nomor ini tanpa perlu menunggu pesan WhatsApp masuk (cocok untuk pengujian lokal).',
        connectViaWa: 'Kirim Pesan via WhatsApp',
        phoneChanged: 'Nomor WhatsApp berhasil diperbarui.',
        connectedDirectly:
            'Nomor WhatsApp berhasil dihubungkan dan Rahina Pagi aktif!',
        otonan: (days: number, label: string | null) =>
            (days === 0
                ? 'Hari ini otonan Anda'
                : `Otonan berikutnya ${days} hari lagi`) +
            (label ? ` (${label})` : ''),
        deleteData: 'Hapus nomor & data WhatsApp',
        deleteConfirm:
            'Nomor, tanggal lahir, dan riwayat pesan WhatsApp Anda akan dihapus. Lanjutkan?',
        deleteYes: 'Ya, hapus',
        cancel: 'Batal',
        privacy:
            'Pesan dikirim lewat layanan pihak ketiga (Fonnte). Nomor dan tanggal lahir disimpan terenkripsi dan tidak dikirim ke layanan AI. Ketik BERHENTI di WhatsApp kapan saja untuk berhenti.',
        saved: 'Pengaturan tersimpan.',
        deleted: 'Data WhatsApp Anda sudah dihapus.',
    },
    ask: {
        title: 'Tanya Wariga',
        intro: 'Tanyakan hari baik untuk beragam kegiatan Anda-seperti membuka usaha, pertanian, mendirikan bangunan, hingga upacara-serta telaah kearifan wariga (zodiak bintang Bali, garis hidup, dan perjodohan). Jawaban disusun dari data perhitungan wariga, lengkap dengan dewasa dan sumbernya.',
        placeholder: 'Contoh: hari baik buka warung atau karakter kelahiran 15 Mei?',
        inputLabel: 'Pertanyaan',
        send: 'Tanya',
        working:
            'Mencocokkan aktivitas dan mencari data perhitungan wariga…',
        suggestionsLabel: 'Contoh pertanyaan',
        suggestions: [
            {
                icon: 'dagang',
                text: 'Kapan hari baik buka warung bulan depan?',
            },
            { icon: 'pertanian', text: 'Hari baik menanam padi bulan ini' },
            {
                icon: 'pembangunan',
                text: 'Hari baik mulai bangun rumah bulan Desember',
            },
            { icon: 'jodoh', text: 'Kecocokan 11 Mei 2006 dan 20 Agustus 2006' },
            { icon: 'bintang', text: 'Karakter bintang kelahiran 15 Mei' },
            { icon: 'upacara', text: 'Hari baik pawiwahan awal tahun depan' },
        ],
        you: 'Anda',
        clarifyLabel: 'Pilih kategori kegiatan',
        support: 'Mendukung',
        block: 'Penghalang',
        none: '-',
        seeDetail: 'Lihat detail hari',
        stepsLabel: 'Langkah pencarian',
        templateNotice:
            'Disusun langsung dari data perhitungan, tanpa ringkasan otomatis.',
        dropped: (n: number) =>
            `${n} tanggal dari ringkasan otomatis dibuang karena tidak ada di data perhitungan.`,
        error: 'Pertanyaan belum bisa dijawab. Periksa koneksi lalu coba lagi.',
        retry: 'Coba lagi',
        newChat: 'Mulai pertanyaan baru',
        privacy:
            'Pertanyaan hanya diproses untuk mencari jawaban dan tidak disimpan sebagai teks.',
    },
    culturalNotice:
        'Untuk upacara besar, mohon konsultasikan dewasa dengan sulinggih atau pemangku setempat.',
    disclaimer: 'Menurut perhitungan wariga. Bersifat informatif.',
    verdict: {
        ayu: 'Baik',
        ala: 'Hindari',
        campur: 'Pertimbangkan',
        info: 'Info',
    } satisfies Record<Verdict, string>,
    wewaran: {
        ekawara: 'Ekawara',
        dwiwara: 'Dwiwara',
        triwara: 'Triwara',
        caturwara: 'Caturwara',
        pancawara: 'Pancawara',
        sadwara: 'Sadwara',
        saptawara: 'Saptawara',
        astawara: 'Astawara',
        sangawara: 'Sangawara',
        dasawara: 'Dasawara',
    } as Record<string, string>,
} as const;
