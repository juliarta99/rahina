import { Link } from '@inertiajs/react';
import { id } from '@/lang/id';
import { home } from '@/routes';

export function BrandLogo({ className }: { className?: string }) {
    return (
        <Link
            href={home.url()}
            className={`flex items-center gap-2.5 transition-opacity hover:opacity-90 ${className ?? ''}`}
        >
            <img
                src="/assets/images/logo_rahina.png"
                alt="Logo Rahina"
                className="size-8 shrink-0 object-contain sm:size-9"
            />
            <span className="font-display text-[22px] leading-7 font-semibold text-mangsi">
                {id.appName}
            </span>
        </Link>
    );
}

