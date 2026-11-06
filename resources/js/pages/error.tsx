import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarBlank,
    ChatCircleText,
    House,
    MagnifyingGlass,
    ShieldSlash,
    WarningOctagon,
    Wrench,
} from '@phosphor-icons/react';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { SiteFooter } from '@/components/rahina/site-footer';

interface ErrorPageProps {
    status?: number;
    message?: string;
}

export default function ErrorPage({ status = 404, message }: ErrorPageProps) {
    const errorDetails: Record<
        number,
        {
            title: string;
            subtitleBali: string;
            description: string;
            Icon: typeof WarningOctagon;
            badgeColor: string;
        }
    > = {
        404: {
            title: 'Halaman Tidak Ditemukan',
            subtitleBali: 'Kaca Sane Katuju Nenten Katemu',
            description:
                'Alamat halaman yang Anda tuju tidak ditemukan atau mungkin telah berpindah. Mari kembali menelusuri penanggalan dan hari baik di kalender Rahina.',
            Icon: MagnifyingGlass,
            badgeColor: 'border-amber-300 bg-amber-50 text-amber-800',
        },
        500: {
            title: 'Terjadi Kendala pada Server',
            subtitleBali: 'Wenten Pikobet Ring Sistem',
            description:
                'Mohon maaf, peladen kami sedang mengalami hambatan teknis sementara. Tim kami sedang menanganinya agar sistem kalender dapat beroperasi normal kembali.',
            Icon: WarningOctagon,
            badgeColor: 'border-rose-300 bg-rose-50 text-rose-800',
        },
        403: {
            title: 'Akses Tidak Diizinkan',
            subtitleBali: 'Akses Kaca Nenten Kalugina',
            description:
                'Anda tidak memiliki izin untuk membuka halaman ini. Silakan periksa kembali tautan Anda atau masuk dengan akun yang sesuai.',
            Icon: ShieldSlash,
            badgeColor: 'border-nila-300 bg-nila-50 text-nila-800',
        },
        503: {
            title: 'Layanan Sedang Pemeliharaan',
            subtitleBali: 'Sistem Sedang Kawangun / Karesikan',
            description:
                'Kami sedang melakukan pemeliharaan berkala demi menjaga presisi komputasi kalender Bali. Layanan akan segera aktif kembali beberapa saat lagi.',
            Icon: Wrench,
            badgeColor: 'border-teal-300 bg-teal-50 text-teal-800',
        },
    };

    const currentError = errorDetails[status] ?? errorDetails[404];
    const ErrorIcon = currentError.Icon;

    return (
        <div className="flex min-h-screen flex-col bg-kertas font-sans text-mangsi antialiased selection:bg-nila-100 selection:text-nila">
            <Head>
                <title>{`${status} - ${currentError.title} | Rahina`}</title>
                <meta name="description" content={currentError.description} />
            </Head>

            {/* HEADER FIXED */}
            <RahinaHeader current="error" />

            {/* KONTEN UTAMA ERROR */}
            <main className="flex flex-1 items-center justify-center px-4 py-16 sm:px-6 sm:py-24">
                <div className="w-full max-w-xl text-center">
                    {/* BADGE KODE ERROR */}
                    <div className="inline-flex items-center gap-2 rounded-full border px-4 py-1 text-xs font-semibold shadow-2xs sm:text-sm">
                        <ErrorIcon size={16} weight="bold" />
                        <span>Kode Kesalahan: {status}</span>
                    </div>

                    {/* ANGKA BESAR DEKORATIF */}
                    <div className="relative mt-6 select-none font-serif text-7xl font-bold tracking-tight text-abu-200 sm:text-9xl">
                        {status}
                        <div className="absolute inset-0 flex items-center justify-center">
                            <img
                                src="/assets/images/maskot_waiting.png"
                                alt="Maskot Gek Ina"
                                className="size-24 object-contain drop-shadow-md sm:size-32"
                            />
                        </div>
                    </div>

                    {/* JUDUL DAN DESKRIPSI */}
                    <h1 className="mt-6 font-serif text-2xl font-bold text-mangsi sm:text-3xl lg:text-4xl">
                        {currentError.title}
                    </h1>

                    <p className="mt-1 font-serif text-sm italic text-nila sm:text-base">
                        "{currentError.subtitleBali}"
                    </p>

                    <p className="mx-auto mt-4 max-w-md text-xs leading-relaxed text-abu-600 sm:text-sm">
                        {message || currentError.description}
                    </p>

                    {/* TOMBOL PANDUAN AKSI */}
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link
                            href="/"
                            className="inline-flex h-11 items-center gap-2 rounded-xl bg-nila px-5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-nila-700 focus-visible:outline-2 focus-visible:outline-nila"
                        >
                            <House size={18} weight="bold" />
                            <span>Kembali ke Beranda</span>
                        </Link>

                        <Link
                            href="/kalender"
                            className="inline-flex h-11 items-center gap-2 rounded-xl border border-abu-300 bg-white px-5 text-sm font-semibold text-mangsi shadow-2xs transition-colors hover:border-nila hover:text-nila focus-visible:outline-2 focus-visible:outline-nila"
                        >
                            <CalendarBlank size={18} weight="bold" />
                            <span>Buka Kalender Bali</span>
                        </Link>

                        <Link
                            href="/tanya"
                            className="inline-flex h-11 items-center gap-2 rounded-xl border border-abu-300 bg-white px-5 text-sm font-semibold text-mangsi shadow-2xs transition-colors hover:border-nila hover:text-nila focus-visible:outline-2 focus-visible:outline-nila"
                        >
                            <ChatCircleText size={18} weight="bold" />
                            <span>Tanya Wariga AI</span>
                        </Link>
                    </div>

                    {/* CATATAN KECIL */}
                    <p className="mt-8 text-xs text-abu-500">
                        Bila Anda merasa ini adalah kesalahan sistem, silakan hubungi tim Rahina.
                    </p>
                </div>
            </main>

            {/* FOOTER */}
            <SiteFooter />
        </div>
    );
}
