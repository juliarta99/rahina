import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckShield,
    Database,
    EyeSlash,
    Key,
    LockKey,
    ShieldCheck,
    UserCheck,
    WhatsappLogo,
} from '@phosphor-icons/react';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { SiteFooter } from '@/components/rahina/site-footer';

export default function PrivacyPage() {
    return (
        <div className="min-h-screen bg-kertas font-sans text-mangsi antialiased selection:bg-nila-100 selection:text-nila">
            <Head>
                <title>Kebijakan Privasi - Rahina Kalender Bali</title>
                <meta
                    name="description"
                    content="Kebijakan Privasi Rahina: Komitmen perlindungan data pengguna, enkripsi nomor WhatsApp, keamanan akun, dan privasi konsultasi AI Tanya Wariga."
                />
            </Head>

            {/* HEADER FIXED */}
            <RahinaHeader current="privacy" />

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
                        <ShieldCheck size={14} weight="bold" />
                        <span>Keamanan & Transparansi Data</span>
                    </div>
                    <h1 className="mt-4 font-serif text-3xl font-bold tracking-tight text-mangsi sm:text-4xl">
                        Kebijakan Privasi
                    </h1>
                    <p className="mt-2 text-xs text-abu-600 sm:text-sm">
                        Terakhir diperbarui: 6 November 2026.
                    </p>
                </div>

                {/* KONTEN KEBIJAKAN PRIVASI */}
                <div className="mt-8 space-y-10 text-sm leading-relaxed text-abu-700 sm:text-base">
                    {/* BAGIAN 1: PENDAHULUAN */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            1. Komitmen Kami terhadap Privasi Anda
                        </h2>
                        <p className="mt-3">
                            Selamat datang di platform <strong>Rahina</strong>. Kami sangat menghargai privasi
                            Anda dan berkomitmen penuh untuk melindungi informasi pribadi yang Anda percayakan
                            kepada kami. Dokumen Kebijakan Privasi ini menjelaskan jenis data apa saja yang kami
                            kumpulkan, bagaimana data tersebut diproses, serta hak kendali penuh yang Anda miliki.
                        </p>
                    </section>

                    {/* BAGIAN 2: DATA YANG DIKUMPULKAN */}
                    <section className="space-y-4">
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            2. Data yang Dikumpulkan dan Penggunaannya
                        </h2>

                        <div className="rounded-2xl border border-abu-200 bg-white p-5 shadow-xs sm:p-6">
                            <div className="flex items-start gap-3.5">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-nila-100 text-nila shrink-0">
                                    <Key size={22} weight="bold" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-mangsi">Akun & Autentikasi Pengguna</h3>
                                    <p className="mt-1 text-xs text-abu-600 sm:text-sm">
                                        Saat Anda mendaftar, kami mengumpulkan nama dan alamat email untuk keperluan
                                        autentikasi dan pengelolaan profil. Kata sandi dienkripsi dengan standar hash
                                        kriptografi modern (Bcrypt/Argon2) sehingga tidak dapat dibaca oleh siapa pun,
                                        termasuk staf Rahina. Kami juga menyediakan fitur Autentikasi Dua Faktor (2FA)
                                        untuk keamanan akun tingkat lanjut.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-abu-200 bg-white p-5 shadow-xs sm:p-6">
                            <div className="flex items-start gap-3.5">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 shrink-0">
                                    <WhatsappLogo size={22} weight="bold" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-mangsi">Layanan Notifikasi WhatsApp Harian</h3>
                                    <p className="mt-1 text-xs text-abu-600 sm:text-sm">
                                        Bila Anda mengaktifkan pengiriman ringkasan harian rahinan dan dewasa ayu,
                                        nomor WhatsApp Anda diverifikasi melalui kode acak dan disimpan dengan enkripsi.
                                        Nomor ini <strong>hanya digunakan</strong> untuk mengirim pesan otomatis pukul
                                        07:00 WITA. Anda dapat memperbarui jam pengiriman atau menonaktifkannya kapan
                                        saja melalui menu Profil.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-abu-200 bg-white p-5 shadow-xs sm:p-6">
                            <div className="flex items-start gap-3.5">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800 shrink-0">
                                    <EyeSlash size={22} weight="bold" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-mangsi">Konsultasi AI Tanya Wariga</h3>
                                    <p className="mt-1 text-xs text-abu-600 sm:text-sm">
                                        Tanya Wariga memproses pertanyaan seputar kalender dan tanggal lahir untuk
                                        keperluan komputasi astrologi/wewaran. Kami <strong>tidak pernah meminta</strong>
                                        data privat sensitif (seperti NIK, KTP, nomor rekening, atau alamat rumah).
                                        Riwayat interaksi yang dicatat di database server hanya menyimpan hash anonim
                                        (SHA-256) untuk pemantauan performa sistem tanpa menyimpan identitas pengguna.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* BAGIAN 3: KEAMANAN & PENYIMPANAN DATA */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            3. Keamanan dan Perlindungan Data
                        </h2>
                        <p className="mt-3">
                            Kami menerapkan protokol keamanan berlapis, termasuk enkripsi Transport Layer Security
                            (HTTPS/TLS 1.3) untuk seluruh transmisi data antara peramban Anda dan server kami. Kami
                            juga menerapkan proteksi Cross-Site Request Forgery (CSRF), pembatasan laju permintaan
                            (rate-limiting), serta pemantauan akses untuk mencegah penyalahgunaan sistem.
                        </p>
                    </section>

                    {/* BAGIAN 4: PEMBAGIAN DATA DENGAN PIHAK KETIGA */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            4. Pembagian Data Pihak Ketiga
                        </h2>
                        <p className="mt-3">
                            <strong>Kami tidak menjual, menyewakan, atau memperdagangkan data pribadi Anda kepada
                            pihak mana pun untuk keperluan periklanan atau pemasaran.</strong> Pertukaran data dengan
                            pihak ketiga hanya dilakukan sebatas yang esensial untuk beroperasinya fitur:
                        </p>
                        <ul className="mt-3 list-disc space-y-2 pl-5 text-xs sm:text-sm">
                            <li>
                                <strong>Google Gemini API:</strong> Digunakan untuk pemrosesan bahasa alami pada asisten
                                Tanya Wariga. Data diproses melalui API enterprise yang tidak digunakan untuk melatih
                                model publik pihak ketiga.
                            </li>
                            <li>
                                <strong>Penyedia Gateway WhatsApp:</strong> Digunakan untuk transmisi pesan notifikasi
                                ringkasan kalender harian secara otomatis.
                            </li>
                        </ul>
                    </section>

                    {/* BAGIAN 5: HAK PENGGUNA */}
                    <section>
                        <h2 className="font-serif text-xl font-bold text-mangsi sm:text-2xl">
                            5. Hak Kendali Penuh Pengguna
                        </h2>
                        <p className="mt-3">
                            Sebagai pemilik data, Anda memiliki hak penuh untuk:
                        </p>
                        <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div className="flex items-start gap-2.5 rounded-xl border border-abu-200 bg-white p-3.5">
                                <UserCheck size={18} weight="bold" className="text-nila shrink-0 mt-0.5" />
                                <span className="text-xs sm:text-sm">Mengakses dan menyunting data profil akun kapan saja.</span>
                            </div>
                            <div className="flex items-start gap-2.5 rounded-xl border border-abu-200 bg-white p-3.5">
                                <LockKey size={18} weight="bold" className="text-nila shrink-0 mt-0.5" />
                                <span className="text-xs sm:text-sm">Mengaktifkan atau menonaktifkan Autentikasi 2FA.</span>
                            </div>
                            <div className="flex items-start gap-2.5 rounded-xl border border-abu-200 bg-white p-3.5">
                                <WhatsappLogo size={18} weight="bold" className="text-nila shrink-0 mt-0.5" />
                                <span className="text-xs sm:text-sm">Menghapus pendaftaran nomor WhatsApp secara mandiri.</span>
                            </div>
                            <div className="flex items-start gap-2.5 rounded-xl border border-abu-200 bg-white p-3.5">
                                <Database size={18} weight="bold" className="text-nila shrink-0 mt-0.5" />
                                <span className="text-xs sm:text-sm">Menghapus akun beserta seluruh data yang tersimpan.</span>
                            </div>
                        </div>
                    </section>

                    {/* BAGIAN 6: KONTAK */}
                    <section className="rounded-2xl border border-nila-200 bg-nila-50/60 p-6">
                        <h2 className="font-serif text-lg font-bold text-nila sm:text-xl">
                            Pertanyaan Seputar Kebijakan Privasi?
                        </h2>
                        <p className="mt-2 text-xs leading-relaxed text-abu-700 sm:text-sm">
                            Bila Anda memiliki pertanyaan, saran, atau permintaan klarifikasi terkait kebijakan
                            privasi dan pengelolaan data di platform Rahina, silakan hubungi tim kami melalui
                            halaman resmi atau layanan kontak yang tersedia.
                        </p>
                    </section>
                </div>
            </main>

            {/* FOOTER */}
            <SiteFooter />
        </div>
    );
}
