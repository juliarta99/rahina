import { Head, Link } from '@inertiajs/react';
import {
    BookOpen,
    CalendarBlank,
    ChatCircleText,
    CheckCircle,
    Compass,
    Feather,
    Heart,
    Info,
    Scroll,
    Sparkle,
    Star,
    WarningCircle,
} from '@phosphor-icons/react';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { SiteFooter } from '@/components/rahina/site-footer';

export default function AboutPage() {
    const pustakaList = [
        {
            title: 'Lontar Sundarigama',
            category: 'Pedoman Hari Suci & Rahinan',
            desc: 'Sastra suci yang menjadi rujukan utama tata pelaksanaan hari-hari suci keagamaan di Bali. Menjelaskan makna pemujaan Ista Dewata pada Purnama, Tilem, Galungan, Kuningan, Saraswati, Pagerwesi, Tumpek, hingga Nyepi.',
            accent: 'border-amber-400 bg-amber-50/50',
            icon: Scroll,
        },
        {
            title: 'Lontar Medangkemulan',
            category: 'Intisari Padewasan & Dewasa Ayu',
            desc: 'Pustaka induk penataan waktu (ala ayuning dewasa) untuk berbagai aktivitas kehidupan manusia (kerti): bercocok tanam (pertanian), membangun rumah, perdagangan, hingga penentuan hari pantangan (wewaran mala).',
            accent: 'border-teal-400 bg-teal-50/50',
            icon: Compass,
        },
        {
            title: 'Lontar Tri Pramana',
            category: 'Kearifan Perjodohan & Dinamika Urip',
            desc: 'Naskah klasik yang menguraikan perhitungan keselarasan jodoh calon pasangan melalui sintesis urip Sapta Wara, Panca Wara, dan Sad Wara dengan modulo 16 sebagai sarana kehati-hatian membangun mahligai rumah tangga.',
            accent: 'border-rose-400 bg-rose-50/50',
            icon: Heart,
        },
        {
            title: 'Lontar Wariga Gemet',
            category: 'Karakter Kelahiran, Wuku & Lintang',
            desc: 'Sumber ajaran mengenai sifat dasar kelahiran manusia berdasarkan pertemuan Wewaran, 30 Wuku, Lintang perbintangan tradisional, serta hukum sebab-akibat waktu (Pratithi Samutpada).',
            accent: 'border-indigo-400 bg-indigo-50/50',
            icon: Star,
        },
        {
            title: 'Buku Pedoman Kalender Bali',
            category: 'Karya I Ketut Bangbang Gde Rawi',
            desc: 'Rujukan modern warisan pakar kalender legendaris Bali yang menyelaraskan naskah-naskah lontar kuno dengan presisi perhitungan peredaran surya-candra dan ketetapan Dinas Kebudayaan Provinsi Bali.',
            accent: 'border-emerald-400 bg-emerald-50/50',
            icon: BookOpen,
        },
    ];

    const pilarList = [
        {
            title: 'Otentisitas Sastra Leluhur',
            desc: 'Setiap aturan perhitungan, wewaran (dari Ekawara hingga Dasawara), wuku, dan nama dewasa bersumber langsung dari naskah lontar terverifikasi tanpa rekaan sembarangan.',
            icon: Feather,
        },
        {
            title: 'Presisi Komputasi Modern',
            desc: 'Menerapkan formula deterministik matematis untuk siklus 210 hari Pawukon dan penanggal-pangelong sasih, menghasilkan akurasi kalender tanpa kesalahan hitung manual.',
            icon: CheckCircle,
        },
        {
            title: 'Etika & Kecerdasan Buatan Terjaga',
            desc: 'Asisten AI Tanya Wariga dilengkapi sistem guardrail ketat (OutputGuard) yang memastikan model tidak pernah mengarang tanggal di luar sastra dan data terverifikasi.',
            icon: Sparkle,
        },
    ];

    return (
        <div className="min-h-screen bg-kertas font-sans text-mangsi antialiased selection:bg-nila-100 selection:text-nila">
            <Head>
                <title>Tentang Kami & Silsilah Pustaka - Rahina Kalender Bali</title>
                <meta
                    name="description"
                    content="Mengenal Rahina: Platform Kalender Bali dan Wariga digital berlandaskan Lontar Sundarigama, Medangkemulan, Tri Pramana, dan Wariga Gemet."
                />
            </Head>

            {/* HEADER FIXED */}
            <RahinaHeader current="about" />

            <main className="mx-auto max-w-5xl px-4 py-12 sm:px-6 sm:py-16">
                {/* HERO SECTION */}
                <section className="text-center">
                    <div className="inline-flex items-center gap-2 rounded-full border border-nila/20 bg-nila-100/70 px-4 py-1.5 text-xs font-semibold text-nila">
                        <Feather size={14} weight="bold" />
                        <span>Silsilah Wariga & Tradisi Bali</span>
                    </div>

                    <h1 className="mt-4 font-serif text-3xl font-bold tracking-tight text-mangsi sm:text-4xl lg:text-5xl">
                        Menjaga Warisan Leluhur Melalui Presisi Digital
                    </h1>

                    <p className="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-abu-600 sm:text-base">
                        Rahina hadir sebagai jembatan antara kekayaan sastra wariga para leluhur Bali
                        dan kemudahan teknologi modern, agar kearifan hari baik dan perayaan suci
                        tetap lestari dan mudah diakses oleh seluruh umat.
                    </p>
                </section>

                {/* PILAR NILAI RAHINA */}
                <section className="mt-14 sm:mt-18">
                    <h2 className="text-center font-serif text-2xl font-bold text-mangsi sm:text-3xl">
                        Tiga Nilai Fondasi Rahina
                    </h2>
                    <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
                        {pilarList.map((pilar, idx) => (
                            <div
                                key={idx}
                                className="rounded-2xl border border-abu-200 bg-white p-6 shadow-xs transition-shadow hover:shadow-md"
                            >
                                <div className="flex size-12 items-center justify-center rounded-xl bg-nila-100 text-nila">
                                    <pilar.icon size={26} weight="duotone" />
                                </div>
                                <h3 className="mt-4 text-base font-bold text-mangsi">
                                    {pilar.title}
                                </h3>
                                <p className="mt-2 text-xs leading-relaxed text-abu-600 sm:text-sm">
                                    {pilar.desc}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* SILSILAH PUSTAKA & NASKAH RUJUKAN */}
                <section className="mt-16 sm:mt-20">
                    <div className="rounded-3xl border border-abu-200 bg-white p-6 sm:p-10 shadow-xs">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                                <Scroll size={22} weight="bold" />
                            </div>
                            <div>
                                <h2 className="font-serif text-2xl font-bold text-mangsi sm:text-3xl">
                                    Silsilah Pustaka Wariga Rujukan
                                </h2>
                                <p className="text-xs text-abu-600 sm:text-sm">
                                    Kompilasi naskah lontar dan kepustakaan tradisional yang menjadi acuan logika Rahina.
                                </p>
                            </div>
                        </div>

                        <div className="mt-8 space-y-4">
                            {pustakaList.map((item, idx) => (
                                <div
                                    key={idx}
                                    className={`rounded-2xl border p-5 transition-all sm:p-6 ${item.accent}`}
                                >
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-baseline sm:justify-between">
                                        <div className="flex items-center gap-2.5">
                                            <item.icon size={22} weight="duotone" className="text-mangsi shrink-0" />
                                            <h3 className="text-base font-bold text-mangsi sm:text-lg">
                                                {item.title}
                                            </h3>
                                        </div>
                                        <span className="text-xs font-semibold uppercase tracking-wider text-abu-600">
                                            {item.category}
                                        </span>
                                    </div>
                                    <p className="mt-2 text-xs leading-relaxed text-abu-700 sm:text-sm">
                                        {item.desc}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* MENGENAL GEK INA & TANYA WARIGA */}
                <section className="mt-16 sm:mt-20">
                    <div className="overflow-hidden rounded-3xl border border-nila-200 bg-gradient-to-br from-nila-50 via-white to-amber-50/40 p-6 sm:p-10 shadow-xs">
                        <div className="grid grid-cols-1 items-center gap-8 md:grid-cols-12">
                            <div className="md:col-span-8">
                                <span className="inline-flex items-center gap-1.5 rounded-md bg-nila-100 px-3 py-1 text-xs font-semibold text-nila">
                                    <Sparkle size={14} weight="bold" />
                                    <span>Pemandu Cerdas & Ramah</span>
                                </span>
                                <h2 className="mt-3 font-serif text-2xl font-bold text-mangsi sm:text-3xl">
                                    Gek Ina: Sahabat Konsultasi Wariga Anda
                                </h2>
                                <p className="mt-3 text-xs leading-relaxed text-abu-600 sm:text-sm">
                                    <strong>Gek Ina</strong> adalah maskot resmi Rahina, sosok gadis Bali
                                    berpakaian adat madya dengan selendang kuning yang ramah, santun, dan penuh
                                    semangat pembelajaran. Bersama asisten AI Tanya Wariga, Gek Ina siap
                                    membantu Anda memahami hari baik untuk upacara, pertanian, pembangunan, hingga
                                    menjelaskan arti wewaran dan zodiak secara mudah dipahami.
                                </p>
                                <div className="mt-6 flex flex-wrap items-center gap-3">
                                    <Link
                                        href="/tanya"
                                        className="inline-flex h-11 items-center gap-2 rounded-xl bg-nila px-5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-nila-700"
                                    >
                                        <ChatCircleText size={18} weight="bold" />
                                        <span>Bicara dengan Gek Ina</span>
                                    </Link>
                                    <Link
                                        href="/kalender"
                                        className="inline-flex h-11 items-center gap-2 rounded-xl border border-abu-300 bg-white px-5 text-sm font-semibold text-mangsi transition-colors hover:border-nila hover:text-nila"
                                    >
                                        <CalendarBlank size={18} weight="bold" />
                                        <span>Buka Kalender Bali</span>
                                    </Link>
                                </div>
                            </div>
                            <div className="flex justify-center md:col-span-4">
                                <div className="relative">
                                    <div className="absolute -inset-2 rounded-full bg-nila-100/50 blur-lg" />
                                    <img
                                        src="/assets/images/maskots/om.png"
                                        alt="Maskot Gek Ina Rahina"
                                        className="relative size-44 object-contain drop-shadow-md sm:size-52"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CATATAN ETIKA BUDAYA: MULAT SARIRA */}
                <section className="mt-12 rounded-2xl border border-amber-200 bg-amber-50/70 p-5 sm:p-6">
                    <div className="flex items-start gap-3.5">
                        <WarningCircle size={24} weight="bold" className="mt-0.5 text-amber-700 shrink-0" />
                        <div>
                            <h3 className="text-sm font-bold text-amber-900 sm:text-base">
                                Catatan Budaya & Etika Konsultasi Wariga (Mulat Sarira)
                            </h3>
                            <p className="mt-1.5 text-xs leading-relaxed text-amber-900/90 sm:text-sm">
                                Dalam tradisi Hindu di Bali, wariga dan ala ayuning dewasa adalah sarana mawas diri
                                (<em>mulat sarira</em>) untuk menyelaraskan kehendak manusia dengan ritme alam semesta
                                (<em>Tri Hita Karana</em>), bukan sebuah vonis takdir yang kaku. Untuk penyelenggaraan
                                upacara keagamaan berskala utama atau madya (Panca Yadnya), umat senantiasa disarankan
                                berkonsultasi langsung dengan Sulinggih, Ida Pedanda, atau Pemangku di desa adat setempat.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            {/* FOOTER */}
            <SiteFooter />
        </div>
    );
}
