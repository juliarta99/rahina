import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    BookBookmark,
    CheckCircle,
    FileText,
    Gavel,
    Handshake,
    Prohibit,
    Scales,
    WarningCircle,
} from '@phosphor-icons/react';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { SiteFooter } from '@/components/rahina/site-footer';

export default function TermsPage() {
    return (
        <div className="min-h-screen bg-kertas font-sans text-mangsi antialiased selection:bg-nila-100 selection:text-nila">
            <Head>
                <title>Syarat & Ketentuan Layanan - Rahina Kalender Bali</title>
                <meta
                    name="description"
                    content="Syarat & Ketentuan Penggunaan Platform Rahina Kalender Bali, etika konsultasi wariga, dan batasan tanggung jawab."
                />
            </Head>

            {/* HEADER FIXED */}
            <RahinaHeader current="terms" />

            <main className="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16">
                {/* TOMBOL KEMBALI */}
                <div className="mb-6">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-abu-600 transition-colors hover:text-nila"
                    >
                        <ArrowLeft size={16} weight="bold" />
                        <span>Kembali ke Beranda</span>
                    </Link>
                </div>

                {/* JUDUL */}
                <div className="border-b border-abu-200 pb-8">
                    <div className="inline-flex items-center gap-2 rounded-full border border-nila/20 bg-nila-100/70 px-3.5 py-1 text-xs font-semibold text-nila">
                        <FileText size={14} weight="bold" />
                        <span>Panduan & Aturan Penggunaan</span>
                    </div>
                    <h1 className="mt-4 font-serif text-3xl font-bold tracking-tight text-mangsi sm:text-4xl">
                        Syarat & Ketentuan Layanan
                    </h1>
                    <p className="mt-2 text-xs text-abu-600 sm:text-sm">
                        Terakhir diperbarui: 6 November 2026.
                    </p>
                </div>

                {/* KONTEN SYARAT & KETENTUAN */}
                <div className="mt-8 space-y-10 text-sm leading-relaxed text-abu-700 sm:text-base">
                    {/* BAGIAN 1: KETENTUAN UMUM */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            1. Ketentuan Umum & Penerimaan
                        </h2>
                        <p className="mt-3">
                            Dengan mengakses, mendaftar, atau menggunakan platform <strong>Rahina</strong>
                            (termasuk situs web, kalender digital, asisten Tanya Wariga, dan layanan notifikasi
                            WhatsApp), Anda menyatakan telah membaca, memahami, dan menyetujui untuk terikat
                            oleh Syarat dan Ketentuan ini. Jika Anda tidak menyetujui salah satu poin di dalamnya,
                            kami menghormati keputusan Anda untuk tidak melanjutkan penggunaan layanan kami.
                        </p>
                    </section>

                    {/* BAGIAN 2: HAKIKAT & BATASAN REKOMENDASI WARIGA */}
                    <section className="space-y-4">
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            2. Hakikat Kearifan Wariga & Batasan Tanggung Jawab Budaya
                        </h2>

                        <div className="rounded-2xl border border-amber-300 bg-amber-50/70 p-5 shadow-xs sm:p-6">
                            <div className="flex items-start gap-3.5">
                                <WarningCircle size={24} weight="bold" className="text-amber-700 shrink-0 mt-0.5" />
                                <div>
                                    <h3 className="font-bold text-amber-900">
                                        Sarana Mawas Diri, Bukan Kepastian Ilmiah Mutlak
                                    </h3>
                                    <p className="mt-1.5 text-xs text-amber-900/90 sm:text-sm leading-relaxed">
                                        Seluruh data perhitungan kalender, ala ayuning dewasa, karakter kelahiran,
                                        dan ramalan perjodohan Lontar Tri Pramana yang disajikan oleh Rahina merupakan
                                        alih digital dari sastra tradisional Bali. Perhitungan ini dimaksudkan sebagai
                                        sarana mawas diri (<em>mulat sarira</em>), pelestarian budaya, dan panduan
                                        spiritual, bukan sebagai vonis masa depan yang pasti atau takdir mutlak.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-abu-200 bg-white p-5 shadow-xs sm:p-6">
                            <div className="flex items-start gap-3.5">
                                <Scales size={22} weight="bold" className="text-nila shrink-0 mt-0.5" />
                                <div>
                                    <h3 className="font-bold text-mangsi">
                                        Bukan Pengganti Sulinggih dan Pemangku Adat
                                    </h3>
                                    <p className="mt-1 text-xs text-abu-600 sm:text-sm leading-relaxed">
                                        Untuk upacara-upacara keagamaan berskala madya atau utama (seperti Pawiwahan,
                                        Ngaben/Pitra Yadnya, Melaspas, dan Odalan Pura), umat senantiasa dianjurkan
                                        berkonsultasi langsung dengan Sulinggih, Ida Pedanda, atau Pemangku desa adat
                                        setempat guna mendapatkan penetapan hari (*dudonan karya*) yang selaras dengan
                                        desa, kala, dan patra setempat.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-abu-200 bg-white p-5 shadow-xs sm:p-6">
                            <div className="flex items-start gap-3.5">
                                <BookBookmark size={22} weight="bold" className="text-nila shrink-0 mt-0.5" />
                                <div>
                                    <h3 className="font-bold text-mangsi">
                                        Bukan Nasihat Medis, Hukum, atau Keuangan
                                    </h3>
                                    <p className="mt-1 text-xs text-abu-600 sm:text-sm leading-relaxed">
                                        Informasi yang dihasilkan platform Rahina tidak dapat diperlakukan sebagai
                                        rekomendasi medis darurat, nasihat hukum formal, atau analisis spekulasi finansial.
                                        Keputusan praktis dalam kehidupan sehari-hari tetap berada di bawah pertimbangan
                                        akal budi dan tanggung jawab pribadi masing-masing pengguna.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* BAGIAN 3: ATURAN PENGGUNAAN */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            3. Etika & Tanggung Jawab Pengguna
                        </h2>
                        <p className="mt-3">
                            Demi menjaga ekosistem platform yang aman, bersih, dan bermanfaat bagi sesama umat,
                            pengguna dilarang keras untuk:
                        </p>
                        <ul className="mt-3 list-disc space-y-2 pl-5 text-xs sm:text-sm">
                            <li>Mengirimkan pesan bernada kebencian, pencemaran nama baik, atau konten tidak pantas ke sistem AI Tanya Wariga.</li>
                            <li>Melakukan upaya peretasan, penetrasi keamanan, atau bypass terhadap sistem autentikasi dan batasan laju (rate limiting).</li>
                            <li>Melakukan web scraping agresif atau penggunaan bot yang mengganggu performa server dan ketersediaan bagi pengguna lain.</li>
                            <li>Menyalahgunakan nomor WhatsApp milik orang lain tanpa izin untuk mendaftarkan layanan notifikasi otomatis.</li>
                        </ul>
                    </section>

                    {/* BAGIAN 4: HAK KEKAYAAN INTELEKTUAL */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            4. Hak Kekayaan Intelektual
                        </h2>
                        <p className="mt-3">
                            Sastra lontar dan ajaran wariga adalah warisan adiluhung peradaban para leluhur Bali
                            yang menjadi milik bersama. Namun demikian, kode sumber aplikasi, basis data terstruktur,
                            desain antarmuka, aset grafis, logo Rahina, dan karakter visual maskot <strong>Gek Ina</strong>
                            adalah kekayaan intelektual milik tim pengembang Rahina dan dilindungi oleh Undang-Undang
                            Hak Cipta Republik Indonesia.
                        </p>
                    </section>

                    {/* BAGIAN 5: KETERSEDIAAN LAYANAN */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            5. Ketersediaan Layanan & Pembaruan
                        </h2>
                        <p className="mt-3">
                            Kami senantiasa berusaha menjaga platform beroperasi tanpa henti dengan performa optimal.
                            Namun, kami tidak menjamin layanan akan selalu bebas dari keterlambatan akibat pemeliharaan
                            sistem berkala, kendala jaringan telekomunikasi pihak ketiga, atau peristiwa di luar
                            kendali wajar kami (<em>force majeure</em>). Kami berhak memperbarui fitur dan ketentuan
                            ini sewaktu-waktu demi penyempurnaan layanan.
                        </p>
                    </section>

                    {/* BAGIAN 6: HUKUM YANG BERLAKU */}
                    <section className="rounded-2xl border border-nila-200 bg-white p-6 shadow-xs">
                        <div className="flex items-center gap-3">
                            <Gavel size={24} weight="bold" className="text-nila" />
                            <h2 className="font-serif text-lg font-bold text-mangsi sm:text-xl">
                                6. Hukum yang Mengatur & Penyelesaian Masalah
                            </h2>
                        </div>
                        <p className="mt-2 text-xs leading-relaxed text-abu-600 sm:text-sm">
                            Syarat dan Ketentuan ini diatur dan ditafsirkan sesuai dengan hukum Negara Kesatuan Republik
                            Indonesia. Segala perselisihan yang timbul sehubungan dengan penggunaan platform Rahina akan
                            diselesaikan secara musyawarah dan kekeluargaan dengan mengedepankan semangat persaudaraan
                            (<em>Paras Paros Sarpanaya</em>).
                        </p>
                    </section>
                </div>
            </main>

            {/* FOOTER */}
            <SiteFooter />
        </div>
    );
}
