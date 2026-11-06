import { BrandLogo } from '@/components/rahina/brand-logo';
import { NavCurrent, SiteNav } from '@/components/rahina/site-nav';

export function RahinaHeader({
    current,
}: {
    current: NavCurrent;
}) {
    return (
        <>
            <header className="fixed top-0 inset-x-0 z-40 border-b border-abu-200 bg-kertas/95 backdrop-blur-md shadow-xs">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <BrandLogo />
                    <SiteNav current={current} />
                </div>
            </header>
            {/* Spacer penyeimbang layout fixed agar konten halaman tidak tertutup header */}
            <div className="h-[65px] sm:h-[68px]" aria-hidden="true" />
        </>
    );
}

