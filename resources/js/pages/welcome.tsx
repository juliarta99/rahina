import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Bell,
    BookOpen,
    CalendarBlank,
    ChatCircleText,
    CheckCircle,
    Compass,
    DeviceMobile,
    Info,
    Moon,
    ShieldCheck,
    Sparkle,
    Sun,
    Users,
    Warning,
} from '@phosphor-icons/react';
import { BrandLogo } from '@/components/rahina/brand-logo';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { id } from '@/lang/id';
import { ask } from '@/routes';
import { profile } from '@/routes/rahina';

interface RahinanItem {
    id?: number;
    name: string;
    kind?: string;
    date?: string;
    days_until?: number;
    daysUntil?: number;
}

interface WelcomeProps {
    today: string;
    todayFormatted: string;
    todayDay?: {
        date: string;
        sakaYear: number;
        wuku: { name: string };
        wewaran: {
            saptawara: { name: string; urip: number };
            pancawara: { name: string; urip: number };
            sadwara?: { name: string };
        };
        sasih: { name: string };
        sasihDay: number;
        isPurnama: boolean;
        isTilem: boolean;
        isPenanggal: boolean;
        rahinan: RahinanItem[];
        uripTotal: number;
        paringkelan?: Record<string, string>;
    } | null;
    summary?: string | null;
    upcoming?: RahinanItem[];
}

export default function Welcome({
    today,
    todayFormatted,
    todayDay,
    summary,
    upcoming = [],
}: WelcomeProps) {
    const [faqOpen, setFaqOpen] = useState<number | null>(null);

    const toggleFaq = (idx: number) => {
        setFaqOpen(faqOpen === idx ? null : idx);
    };

    const sapta = todayDay?.wewaran?.saptawara?.name ?? 'Redite';
    const panca = todayDay?.wewaran?.pancawara?.name ?? 'Umanis';
    const wuku = todayDay?.wuku?.name ?? 'Sinta';
    const sasih = todayDay?.sasih?.name ?? 'Kasa';
    const saka = todayDay?.sakaYear ?? 1948;

    const faqs = [
        {
            q: 'Apakah layanan Kalender Rahina & Tanya Wariga ini gratis?',
            a: 'Ya, seluruh fitur Rahina-mulai dari penelusuran kalender 2020-2035, pengecekan dewasa ayu, asisten Tanya Wariga bertenaga AI, hingga pengiriman pesan ringkasan otomatis via WhatsApp-disediakan sepenuhnya gratis untuk umat dan masyarakat umum.',
        },
        {
            q: 'Mengapa untuk upacara besar tetap disarankan berkonsultasi dengan sulinggih?',
            a: 'Wariga memuat kaidah penanggalan umum (ala ayuning dewasa). Namun, upacara yadnya berskala agung (seperti Karya Agung, Ngenteg Linggih, Pediksaan, atau Pawiwahan agung) melibatkan faktor sosiokultural, desa kala patra, serta tinjauan spiritual mendalam yang menjadi wewenang penuh Ida Peranda, Sulinggih, atau Pemangku setempat.',
        },
        {
            q: 'Bagaimana cara mengaktifkan atau menghentikan notifikasi WhatsApp Rahina Pagi?',
            a: 'Cukup masuk ke akun Anda, buka halaman Profil, dan masukkan nomor WhatsApp aktif Anda. Setiap pagi pukul 06:00 WITA, ringkasan hari ini akan dikirimkan otomatis. Jika ingin berhenti, Anda cukup membalas pesan WhatsApp bot dengan kata "STOP" kapan saja.',
        },
        {
            q: 'Pustaka apa saja yang menjadi landasan perhitungan wariga di Rahina?',
            a: 'Perhitungan sistematis Rahina mengacu pada kaidah manuskrip Lontar Sundarigama, Medangkemulan, Lontar Tri Pramana (untuk perjodohan & otonan), serta sistem kalender Bali standar berurutan wewaran 1 hingga 10.',
        },
    ];

    return (
        <div className="min-h-screen bg-kertas font-sans text-mangsi selection:bg-kunyit-100 selection:text-nila">
            <Head>
                <title>Rahina - Sistem Kalender Bali, Dewasa Ayu & Tanya Wariga AI</title>
                <meta
                    name="description"
                    content="Aplikasi kalender Bali. Cek wewaran, 30 wuku, dewasa ayu (hari baik), konsultasi asisten Tanya Wariga AI, dan pengingat WhatsApp harian."
                />
                <link rel="canonical" href="/" />
                <script type="application/ld+json">
                    {JSON.stringify({
                        '@context': 'https://schema.org',
                        '@type': 'FAQPage',
                        mainEntity: faqs.map((f) => ({
                            '@type': 'Question',
                            name: f.q,
                            acceptedAnswer: {
                                '@type': 'Answer',
                                text: f.a,
                            },
                        })),
                    })}
                </script>
            </Head>

            {/* Header Terpadu */}
            <RahinaHeader current="home" />

            {/* HERO SECTION */}
            <section className="relative overflow-hidden border-b border-abu-200 bg-gradient-to-b from-kertas via-[#FFF8F3] to-kertas py-12 sm:py-16 lg:py-20">
                {/* Aksen Latar Budaya */}
                <div
                    className="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-kunyit/15 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="pointer-events-none absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-nila/10 blur-3xl"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="grid items-center gap-10 lg:grid-cols-12 lg:gap-12">
                        {/* Kolom Kiri: Teks & Aksi */}
                        <div className="lg:col-span-7">
                            <div className="inline-flex items-center gap-2 rounded-full border border-nila/40 bg-nila-100 px-3.5 py-1 text-xs font-semibold tracking-wide text-nila uppercase shadow-xs">
                                <span>Kalender Bali Tradisional & AI Wariga Cerdas</span>
                            </div>

                            <h1 className="mt-4 font-display text-3xl font-semibold tracking-tight text-mangsi sm:text-4xl lg:text-[44px] lg:leading-[1.18]">
                                Menyambung Tradisi Luhur Wariga Bali dengan Kemudahan Era Digital
                            </h1>

                            <p className="mt-4 text-base leading-relaxed text-abu-600 sm:text-lg">
                                Temukan hari baik (dewasa ayu), tanyakan kearifan wariga pada asisten AI yang beretika, dan dapatkan ringkasan harian otomatis setiap pagi langsung di WhatsApp Anda.
                            </p>

                            {/* Tombol CTA */}
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                <Link
                                    href="/kalender"
                                    className="inline-flex h-12 items-center justify-center gap-2.5 rounded-lg bg-nila px-6 text-base font-semibold text-white shadow-md transition-all hover:bg-nila-700 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nila"
                                >
                                    <CalendarBlank size={22} weight="bold" />
                                    <span>Buka Kalender Bali</span>
                                </Link>

                                <Link
                                    href={ask.url()}
                                    className="inline-flex h-12 items-center justify-center gap-2.5 rounded-lg border-2 border-nila/30 bg-white px-6 text-base font-semibold text-nila transition-all hover:border-nila hover:bg-nila-100/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nila"
                                >
                                    <ChatCircleText size={22} weight="bold" />
                                    <span>Tanya Wariga AI</span>
                                </Link>
                            </div>

                            {/* Poin Ringkas Kepercayaan */}
                            <div className="mt-8 flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-abu-600">
                                <span className="flex items-center gap-1.5 font-medium">
                                    <CheckCircle size={16} weight="fill" className="text-ayu" />
                                    100% Gratis & Bebas Iklan
                                </span>
                                <span className="flex items-center gap-1.5 font-medium">
                                    <CheckCircle size={16} weight="fill" className="text-ayu" />
                                    Rujukan Lontar Sundarigama & Tri Pramana
                                </span>
                                <span className="flex items-center gap-1.5 font-medium">
                                    <CheckCircle size={16} weight="fill" className="text-ayu" />
                                    Notifikasi WhatsApp Pukul 06:00 WITA
                                </span>
                            </div>
                        </div>

                        {/* Kolom Kanan: Visual Maskot & Floating Card Real-time */}
                        <div className="relative flex justify-center lg:col-span-5 lg:justify-end">
                            <div className="relative w-full max-w-md">
                                {/* Lingkaran Aksen Emas & Maskot */}
                                <div className="relative flex items-center justify-center">
                                    <div className="absolute inset-0 rounded-3xl bg-gradient-to-tr from-kunyit/20 via-nila/10 to-transparent blur-xl" />
                                    <img
                                        src="/assets/images/maskots/halo.png"
                                        alt="Maskot Rahina menyapa"
                                        className="relative z-10 max-h-[340px] w-auto drop-shadow-2xl transition-transform duration-300 hover:scale-105"
                                    />
                                </div>

                                {/* Kartu Mengambang: Pratinjau Kalender Hari Ini */}
                                <div className="relative -mt-6 z-20 rounded-2xl border border-abu-200 bg-white/95 p-5 shadow-xl backdrop-blur-sm sm:-mt-10">
                                    <div className="flex items-center justify-between border-b border-abu-200/70 pb-3">
                                        <div className="flex items-center gap-2">
                                            <span className="flex size-3 items-center justify-center rounded-full bg-emerald-500 animate-pulse" />
                                            <span className="text-xs font-semibold tracking-wide text-nila uppercase">
                                                Rahina Ini (Hari Ini)
                                            </span>
                                        </div>
                                        <span className="rounded-md bg-lontar px-2 py-0.5 font-display text-xs font-medium text-abu-600">
                                            Saka {saka}
                                        </span>
                                    </div>

                                    <div className="mt-3">
                                        <div className="font-display text-xl font-bold text-mangsi">
                                            {sapta} {panca}
                                        </div>
                                        <div className="mt-0.5 text-xs text-abu-600">
                                            Wuku {wuku} · Sasih {sasih} · {todayFormatted}
                                        </div>
                                    </div>

                                    {/* Rahinan Hari Ini Jika Ada */}
                                    {todayDay?.rahinan && todayDay.rahinan.length > 0 ? (
                                        <div className="mt-3 flex flex-wrap gap-1.5">
                                            {todayDay.rahinan.map((r, i) => (
                                                <span
                                                    key={i}
                                                    className="inline-flex items-center gap-1 rounded-md bg-kunyit/25 px-2.5 py-1 text-xs font-semibold text-kunyit-800"
                                                >
                                                    <Sparkle size={13} weight="fill" className="text-kunyit" />
                                                    {r.name}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        upcoming.length > 0 && (
                                            <div className="mt-3 rounded-lg bg-lontar/60 p-2.5 text-xs text-abu-600">
                                                <span className="font-semibold text-mangsi">Rahinan terdekat:</span>{' '}
                                                <span className="font-bold text-mangsi">{upcoming[0].name}</span>{' '}
                                                <span className="text-abu-600">
                                                    ({(upcoming[0].days_until ?? upcoming[0].daysUntil) === 0
                                                        ? 'Hari ini'
                                                        : (upcoming[0].days_until ?? upcoming[0].daysUntil) === 1
                                                          ? 'Besok'
                                                          : `dalam ${upcoming[0].days_until ?? upcoming[0].daysUntil} hari`})
                                                </span>
                                            </div>
                                        )
                                    )}

                                    {/* Cuplikan Ringkasan Singkat */}
                                    {summary && (
                                        <div className="mt-3 border-t border-abu-200/60 pt-3 text-xs leading-relaxed text-abu-600 line-clamp-2">
                                            {summary}
                                        </div>
                                    )}

                                    <div className="mt-3 flex justify-end">
                                        <Link
                                            href="/kalender"
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-nila hover:underline"
                                        >
                                            Lihat detail kalender hari ini
                                            <ArrowRight size={13} weight="bold" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* SECTION KEUNGGULAN RAHINA */}
            <section id="keunggulan" className="border-b border-abu-200 py-16 sm:py-20">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="mx-auto max-w-2xl text-center">
                        <span className="text-xs font-bold tracking-wider text-nila uppercase">
                            Keunggulan Aplikasi
                        </span>
                        <h2 className="mt-2 font-display text-2xl font-semibold text-mangsi sm:text-3xl lg:text-4xl">
                            Mengapa Krama Bali Memilih Rahina?
                        </h2>
                        <p className="mt-3 text-sm text-abu-600 sm:text-base">
                            Perpaduan komputasi deterministik yang cermat dengan asistensi kecerdasan buatan yang berpegang teguh pada keluhuran sastra agama Hindu Bali.
                        </p>
                    </div>

                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {/* Keunggulan 1 */}
                        <div className="group flex flex-col rounded-2xl border border-abu-200 bg-white p-6 shadow-xs transition-all hover:-translate-y-1 hover:border-nila/40 hover:shadow-md">
                            <div className="flex size-14 items-center justify-center rounded-xl bg-nila-100 text-nila">
                                <CalendarBlank size={28} weight="bold" />
                            </div>
                            <h3 className="mt-5 font-display text-lg font-semibold text-mangsi">
                                Perhitungan Wariga Presisi & Lengkap
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600 sm:text-sm">
                                Mengkalkulasi sistem wewaran Ekawara hingga Dasawara, 30 wuku, sasih nampih/mala, penanggal/pangelong, dan otonan secara matematis tanpa kesalahan asumsi.
                            </p>
                        </div>

                        {/* Keunggulan 2 */}
                        <div className="group flex flex-col rounded-2xl border border-abu-200 bg-white p-6 shadow-xs transition-all hover:-translate-y-1 hover:border-nila/40 hover:shadow-md">
                            <div className="flex size-14 items-center justify-center rounded-xl bg-kunyit-100 text-kunyit-800">
                                <ChatCircleText size={28} weight="bold" />
                            </div>
                            <h3 className="mt-5 font-display text-lg font-semibold text-mangsi">
                                Tanya Wariga Bertenaga AI Gemini
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600 sm:text-sm">
                                Didukung RAG (Retrieval-Augmented Generation) Lontar Sundarigama, Medangkemulan, Tri Pramana, 12 zodiak bintang, dan 9 angka garis hidup untuk menjawab pertanyaan Anda.
                            </p>
                        </div>

                        {/* Keunggulan 3 */}
                        <div className="group flex flex-col rounded-2xl border border-abu-200 bg-white p-6 shadow-xs transition-all hover:-translate-y-1 hover:border-nila/40 hover:shadow-md">
                            <div className="flex size-14 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800">
                                <DeviceMobile size={28} weight="bold" />
                            </div>
                            <h3 className="mt-5 font-display text-lg font-semibold text-mangsi">
                                Rahina Pagi via WhatsApp Otomatis
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600 sm:text-sm">
                                Tidak perlu repot membuka website setiap hari. Nikmati pesan ringkasan rahinan dan intisari dewasa ayu hari itu tepat pukul 06:00 WITA langsung di WhatsApp.
                            </p>
                        </div>

                        {/* Keunggulan 4 */}
                        <div className="group flex flex-col rounded-2xl border border-abu-200 bg-white p-6 shadow-xs transition-all hover:-translate-y-1 hover:border-nila/40 hover:shadow-md">
                            <div className="flex size-14 items-center justify-center rounded-xl bg-amber-100 text-amber-900">
                                <ShieldCheck size={28} weight="bold" />
                            </div>
                            <h3 className="mt-5 font-display text-lg font-semibold text-mangsi">
                                Etika Budaya & Guardrail Sakral
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600 sm:text-sm">
                                Dirancang selaras dengan dresta Bali. Menolak ramalan nasib spekulatif, memvalidasi ambigu, dan mengarahkan konsultasi upacara besar kepada sulinggih.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* SECTION TATA CARA PENGGUNAAN */}
            <section id="tata-cara" className="border-b border-abu-200 bg-lontar/40 py-16 sm:py-20">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="mx-auto max-w-2xl text-center">
                        <span className="text-xs font-bold tracking-wider text-nila uppercase">
                            Panduan Praktis
                        </span>
                        <h2 className="mt-2 font-display text-2xl font-semibold text-mangsi sm:text-3xl lg:text-4xl">
                            Tata Cara Menggunakan Rahina
                        </h2>
                        <p className="mt-3 text-sm text-abu-600 sm:text-base">
                            Empat langkah mudah untuk memanfaatkan seluruh fitur penanggalan dan konsultasi wariga.
                        </p>
                    </div>

                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {/* Langkah 1 */}
                        <div className="relative rounded-2xl border border-abu-200 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between">
                                <span className="flex size-10 items-center justify-center rounded-full bg-nila text-base font-bold text-white">
                                    1
                                </span>
                                <CalendarBlank size={24} className="text-abu-400" />
                            </div>
                            <h3 className="mt-4 font-display text-base font-semibold text-mangsi">
                                Buka Kalender Bali
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600">
                                Pilih bulan dan tahun di navigasi kalender. Perhatikan indikator warna untuk Purnama (kuning), Tilem (gelap), serta hari raya Hindu yang ditandai titik merah.
                            </p>
                        </div>

                        {/* Langkah 2 */}
                        <div className="relative rounded-2xl border border-abu-200 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between">
                                <span className="flex size-10 items-center justify-center rounded-full bg-nila text-base font-bold text-white">
                                    2
                                </span>
                                <BookOpen size={24} className="text-abu-400" />
                            </div>
                            <h3 className="mt-4 font-display text-base font-semibold text-mangsi">
                                Klik Tanggal untuk Detail
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600">
                                Klik sel tanggal mana saja untuk membuka popup Detail Hari. Anda dapat melihat wewaran lengkap, ingkel, paringkelan, dan daftar kegiatan yang baik atau dilarang.
                            </p>
                        </div>

                        {/* Langkah 3 */}
                        <div className="relative rounded-2xl border border-abu-200 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between">
                                <span className="flex size-10 items-center justify-center rounded-full bg-nila text-base font-bold text-white">
                                    3
                                </span>
                                <ChatCircleText size={24} className="text-abu-400" />
                            </div>
                            <h3 className="mt-4 font-display text-base font-semibold text-mangsi">
                                Tanya Asisten Wariga AI
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600">
                                Butuh hari baik untuk pernikahan, bangun rumah, atau ingin tahu karakter kelahiran dan otonan? Tanyakan langsung di halaman Tanya Wariga dalam bahasa santai.
                            </p>
                        </div>

                        {/* Langkah 4 */}
                        <div className="relative rounded-2xl border border-abu-200 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between">
                                <span className="flex size-10 items-center justify-center rounded-full bg-nila text-base font-bold text-white">
                                    4
                                </span>
                                <Bell size={24} className="text-abu-400" />
                            </div>
                            <h3 className="mt-4 font-display text-base font-semibold text-mangsi">
                                Pasang Pengingat WhatsApp
                            </h3>
                            <p className="mt-2 text-xs leading-relaxed text-abu-600">
                                Daftarkan nomor telepon Anda di halaman Profil untuk menerima ringkasan otomatis Rahina Pagi setiap pukul 06:00 WITA tanpa repot membuka aplikasi.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* SECTION INFORMASI PENTING & ETIKA BUDAYA */}
            <section className="border-b border-abu-200 py-16 sm:py-20">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="rounded-3xl border-2 border-nila/20 bg-gradient-to-br from-kunyit-100/50 via-white to-nila-100/40 p-8 sm:p-12">
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:gap-10">
                            <div className="shrink-0">
                                <img
                                    src="/assets/images/maskots/om.png"
                                    alt="Maskot Rahina bersikap panganjali"
                                    className="mx-auto h-40 w-auto object-contain sm:h-48"
                                />
                            </div>

                            <div className="flex-1">
                                <div className="inline-flex items-center gap-2 rounded-md bg-nila/10 px-2.5 py-1 text-xs font-semibold text-nila">
                                    <Info size={16} weight="bold" />
                                    <span>Pemberitahuan Penting & Etika Konsultasi Wariga</span>
                                </div>

                                <h3 className="mt-3 font-display text-xl font-bold text-mangsi sm:text-2xl">
                                    Pedoman Pelaksanaan Upacara Yadnya Berskala Besar
                                </h3>

                                <div className="mt-3 space-y-3 text-sm leading-relaxed text-abu-600">
                                    <p>
                                        Hasil perhitungan dewasa ayu dan asistensi AI dalam aplikasi Rahina disusun sebagai sarana literasi awal dan referensi umum berdasarkan kaidah lontar wariga yang baku.
                                    </p>
                                    <div className="flex items-start gap-3 rounded-xl border border-nila/20 bg-white/80 p-4 font-medium text-mangsi">
                                        <Warning size={20} weight="fill" className="mt-0.5 shrink-0 text-nila" aria-hidden="true" />
                                        <p className="leading-relaxed">
                                            <strong>Catatan Utama:</strong> Untuk upacara yadnya yang berskala besar (seperti Karya Agung, Ngenteg Linggih, Pediksaan, Pawiwahan agung, atau Ngasti), umat tetap diwajibkan meminta petunjuk serta panuntun langsung dari <strong>Ida Sulinggih</strong> atau <strong>Pemangku</strong> desa adat setempat.
                                        </p>
                                    </div>
                                    <p className="text-xs text-abu-600">
                                        Data pribadi nomor WhatsApp Anda hanya dipergunakan untuk pengiriman ringkasan harian dan tidak akan diperjualbelikan maupun dikirimkan ke pihak layanan AI mana pun.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* SECTION FAQ (TANYA JAWAB POPULER) */}
            <section className="border-b border-abu-200 bg-white py-16 sm:py-20">
                <div className="mx-auto max-w-4xl px-4 sm:px-6">
                    <div className="text-center">
                        <span className="text-xs font-bold tracking-wider text-nila uppercase">
                            Tanya Jawab
                        </span>
                        <h2 className="mt-2 font-display text-2xl font-semibold text-mangsi sm:text-3xl">
                            Pertanyaan yang Sering Diajukan
                        </h2>
                    </div>

                    <div className="mt-10 divide-y divide-abu-200 rounded-2xl border border-abu-200 bg-kertas/50">
                        {faqs.map((faq, idx) => (
                            <div key={idx} className="p-5 sm:p-6">
                                <button
                                    type="button"
                                    onClick={() => toggleFaq(idx)}
                                    className="flex w-full items-center justify-between text-left font-display text-base font-semibold text-mangsi focus:outline-hidden"
                                >
                                    <span>{faq.q}</span>
                                    <span className="ml-4 text-nila">
                                        {faqOpen === idx ? '−' : '+'}
                                    </span>
                                </button>
                                {faqOpen === idx && (
                                    <p className="mt-3 text-sm leading-relaxed text-abu-600">
                                        {faq.a}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* SECTION CTA BAWAH */}
            <section className="bg-gradient-to-b from-white to-lontar/60 py-16 text-center sm:py-20">
                <div className="mx-auto max-w-3xl px-4 sm:px-6">
                    <h2 className="font-display text-2xl font-semibold text-mangsi sm:text-3xl lg:text-4xl">
                        Mulai Eksplorasi Kalender & Hari Baik Anda Hari Ini
                    </h2>
                    <p className="mt-3 text-sm text-abu-600 sm:text-base">
                        Akses kalender secara instan atau konsultasikan kebutuhan kegiatan Anda langsung dengan Tanya Wariga.
                    </p>

                    <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <Link
                            href="/kalender"
                            className="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-nila px-6 text-base font-semibold text-white shadow-md hover:bg-nila-700"
                        >
                            <CalendarBlank size={20} weight="bold" />
                            <span>Buka Kalender Bali</span>
                        </Link>
                        <Link
                            href={ask.url()}
                            className="inline-flex h-12 items-center justify-center gap-2 rounded-lg border-2 border-nila/30 bg-white px-6 text-base font-semibold text-nila hover:bg-nila-100"
                        >
                            <ChatCircleText size={20} weight="bold" />
                            <span>Mulai Tanya Wariga</span>
                        </Link>
                    </div>
                </div>
            </section>

            {/* FOOTER RESMI RAHINA */}
            <footer className="border-t border-abu-200 bg-kertas py-12 text-sm text-abu-600">
                <div className="mx-auto max-w-6xl px-4 sm:px-6">
                    <div className="flex flex-col items-center justify-between gap-6 sm:flex-row">
                        <BrandLogo />
                        <div className="flex flex-wrap items-center justify-center gap-6 text-xs sm:text-sm">
                            <Link href="/kalender" className="hover:text-nila hover:underline">
                                Kalender Bali
                            </Link>
                            <Link href={ask.url()} className="hover:text-nila hover:underline">
                                Tanya Wariga AI
                            </Link>
                            <Link href={profile.url()} className="hover:text-nila hover:underline">
                                Profil & WhatsApp
                            </Link>
                        </div>
                    </div>

                    <div className="mt-8 border-t border-abu-200/80 pt-6 text-center text-xs text-abu-600 sm:flex sm:items-center sm:justify-between sm:text-left">
                        <p>© {new Date().getFullYear()} Rahina. Mugi Rahayu Sareng Sami.</p>
                        <p className="mt-2 sm:mt-0">
                            Berlandaskan Lontar Sundarigama, Medangkemulan & Tri Pramana.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
