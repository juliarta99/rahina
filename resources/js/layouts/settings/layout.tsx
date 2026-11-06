import { Link } from '@inertiajs/react';
import { ArrowLeft, Key, User } from '@phosphor-icons/react';
import type { PropsWithChildren } from 'react';
import { SiteNav } from '@/components/rahina/site-nav';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { id } from '@/lang/id';
import { cn, toUrl } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { profile } from '@/routes/rahina';
import { edit as editSecurity } from '@/routes/security';

const navItems = [
    {
        title: 'Akun & Kontak',
        href: edit(),
        icon: User,
    },
    {
        title: 'Keamanan & Kata Sandi',
        href: editSecurity(),
        icon: Key,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="min-h-screen bg-lontar font-sans text-mangsi">
            <header className="border-b border-abu-200 bg-kertas">
                <div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <span className="font-display text-[22px] leading-7 font-semibold">
                        {id.appName}
                    </span>
                    <SiteNav current="profile" />
                </div>
            </header>

            <main className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-6">
                <div>
                    <Link
                        href={profile.url()}
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-nila hover:underline"
                    >
                        <ArrowLeft size={16} aria-hidden="true" />
                        Kembali ke Profil
                    </Link>
                    <h1 className="mt-2 font-display text-[28px] leading-[34px] font-bold">
                        Pengaturan Akun
                    </h1>
                    <p className="mt-1 text-sm text-abu-600">
                        Kelola informasi akun masuk dan keamanan akun Rahina Anda.
                    </p>
                </div>

                <nav
                    className="flex flex-wrap gap-2 border-b border-abu-200 pb-3"
                    aria-label="Pengaturan"
                >
                    {navItems.map((item, index) => {
                        const active = isCurrentOrParentUrl(item.href);
                        return (
                            <Link
                                key={`${toUrl(item.href)}-${index}`}
                                href={item.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'flex h-10 items-center gap-2 rounded-md px-4 text-sm font-medium transition-colors',
                                    active
                                        ? 'bg-nila text-white'
                                        : 'border border-abu-200 bg-kertas text-mangsi hover:bg-lontar',
                                )}
                            >
                                <item.icon size={18} aria-hidden="true" />
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                <div className="flex flex-col gap-6">{children}</div>
            </main>

            <footer className="pb-8 text-center text-[13px] leading-[18px] text-abu-600">
                {id.disclaimer}
            </footer>
        </div>
    );
}
