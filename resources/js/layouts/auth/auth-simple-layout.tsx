import { Link } from '@inertiajs/react';
import { ArrowLeft, CalendarBlank } from '@phosphor-icons/react';
import { id } from '@/lang/id';
import type { AuthLayoutProps } from '@/types';

/** Tata letak halaman masuk/daftar dengan token Rahina (DESIGN_SYSTEM.md): latar lontar, kartu kertas. */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-4 bg-lontar p-6 font-sans text-mangsi md:p-10">
            <div className="w-full max-w-sm">
                {/* Navigasi Cepat Kembali ke Kalender di Atas Kartu */}
                <div className="mb-3 flex items-center justify-between px-1">
                    <Link
                        href="/kalender"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-abu-600 transition-colors hover:text-nila focus-visible:outline-2 focus-visible:outline-nila"
                    >
                        <ArrowLeft size={16} weight="bold" />
                        <span>Kembali ke Kalender Bali</span>
                    </Link>
                    <Link
                        href="/"
                        className="text-xs text-abu-400 transition-colors hover:text-mangsi hover:underline"
                    >
                        Beranda
                    </Link>
                </div>

                <div className="flex flex-col gap-6 rounded-lg border border-abu-200 bg-kertas p-6 shadow-xs">
                    <div className="flex flex-col items-center gap-3">
                        <Link
                            href="/kalender"
                            className="flex items-center gap-2.5 font-display text-[28px] leading-[34px] font-bold text-nila focus-visible:outline-2 focus-visible:outline-nila"
                        >
                            <img
                                src="/assets/images/logo_rahina.png"
                                alt="Logo Rahina"
                                className="size-9 object-contain"
                            />
                            {id.appName}
                        </Link>
                        <div className="space-y-1 text-center">
                            <h1 className="font-display text-[22px] leading-7 font-semibold">
                                {title}
                            </h1>
                            <p className="text-center text-sm text-abu-600">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>

                {/* Footer Link Kembali ke Kalender */}
                <div className="mt-4 text-center">
                    <Link
                        href="/kalender"
                        className="inline-flex items-center gap-1.5 text-xs font-medium text-abu-600 transition-colors hover:text-nila hover:underline"
                    >
                        <CalendarBlank size={15} weight="bold" />
                        <span>Buka Kalender Bali tanpa masuk</span>
                    </Link>
                </div>
            </div>
        </div>
    );
}
