import { Link } from '@inertiajs/react';
import { id } from '@/lang/id';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/** Tata letak halaman masuk/daftar dengan token Rahina (DESIGN_SYSTEM.md): latar lontar, kartu kertas. */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-lontar p-6 font-sans text-mangsi md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-6 rounded-lg border border-abu-200 bg-kertas p-6">
                    <div className="flex flex-col items-center gap-3">
                        <Link
                            href={home()}
                            className="font-display text-[28px] leading-[34px] font-bold text-nila focus-visible:outline-2 focus-visible:outline-nila"
                        >
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
            </div>
        </div>
    );
}
