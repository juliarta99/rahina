import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    CalendarBlank,
    ChatCircleText,
    House,
    List,
    SignIn,
    User,
    X,
} from '@phosphor-icons/react';
import type { Icon } from '@phosphor-icons/react';
import { id } from '@/lang/id';
import { cn } from '@/lib/utils';
import { ask, home, login, register } from '@/routes';
import { profile } from '@/routes/rahina';

export type NavCurrent =
    | 'home'
    | 'calendar'
    | 'ask'
    | 'profile'
    | 'about'
    | 'privacy'
    | 'terms'
    | 'error';

/**
 * Navigasi utama responsif (DESIGN_SYSTEM.md §7):
 * - Layar desktop (md+): menu horizontal terpadu.
 * - Layar ponsel (<md): tombol hamburger + drawer menu vertikal ramah sentuh.
 */
export function SiteNav({ current }: { current: NavCurrent }) {
    const { auth } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);

    const items: {
        key: Current | 'login';
        href: string;
        label: string;
        Icon: Icon;
    }[] = [
        {
            key: 'home',
            href: '/',
            label: id.nav.home,
            Icon: House,
        },
        {
            key: 'calendar',
            href: '/kalender',
            label: id.nav.calendar,
            Icon: CalendarBlank,
        },
        {
            key: 'ask',
            href: ask.url(),
            label: id.nav.ask,
            Icon: ChatCircleText,
        },
        auth.user
            ? {
                  key: 'profile',
                  href: profile.url(),
                  label: id.nav.profile,
                  Icon: User,
              }
            : {
                  key: 'login',
                  href: login.url(),
                  label: id.nav.login,
                  Icon: SignIn,
              },
    ];

    const toggleMobile = () => setMobileOpen((prev) => !prev);
    const closeMobile = () => setMobileOpen(false);

    return (
        <nav aria-label={id.nav.label} className="relative">
            {/* Desktop Navigation (>= md) */}
            <div className="hidden items-center gap-1 md:flex">
                {items.map(({ key, href, label, Icon }) => (
                    <Link
                        key={key}
                        href={href}
                        aria-current={key === current ? 'page' : undefined}
                        className={cn(
                            'flex h-11 items-center gap-1.5 rounded-md px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-nila',
                            key === current
                                ? 'bg-nila-100 text-nila'
                                : 'text-mangsi hover:text-nila hover:underline',
                        )}
                    >
                        <Icon
                            size={20}
                            weight={key === current ? 'bold' : 'regular'}
                            aria-hidden="true"
                        />
                        {label}
                    </Link>
                ))}
                {!auth.user && (
                    <Link
                        href={register.url()}
                        className="ml-1 flex h-11 items-center rounded-md bg-nila px-4 text-sm font-semibold text-white shadow-xs transition-all hover:bg-nila-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nila"
                    >
                        {id.nav.register}
                    </Link>
                )}
            </div>

            {/* Mobile Hamburger Button (< md) */}
            <div className="flex items-center gap-1.5 md:hidden">
                {!auth.user && (
                    <Link
                        href={login.url()}
                        className="flex h-10 items-center gap-1 rounded-md px-2.5 text-xs font-semibold text-nila hover:bg-nila-100/60"
                    >
                        <SignIn size={18} weight="bold" />
                        <span>{id.nav.login}</span>
                    </Link>
                )}

                <button
                    type="button"
                    onClick={toggleMobile}
                    aria-expanded={mobileOpen}
                    aria-label={mobileOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'}
                    className="flex size-11 items-center justify-center rounded-lg border border-abu-200 bg-kertas text-mangsi transition-colors hover:border-nila hover:text-nila focus-visible:outline-2 focus-visible:outline-nila"
                >
                    {mobileOpen ? (
                        <X size={24} weight="bold" aria-hidden="true" />
                    ) : (
                        <List size={24} weight="bold" aria-hidden="true" />
                    )}
                </button>
            </div>

            {/* Mobile Dropdown Menu (< md) */}
            {mobileOpen && (
                <>
                    {/* Backdrop tap to close */}
                    <div
                        className="fixed inset-0 top-[65px] z-30 bg-black/20 backdrop-blur-xs md:hidden"
                        onClick={closeMobile}
                        aria-hidden="true"
                    />

                    {/* Menu Panel */}
                    <div className="absolute top-[calc(100%+12px)] right-0 z-40 w-64 rounded-2xl border border-abu-200 bg-white p-3 shadow-2xl md:hidden">
                        <div className="flex flex-col gap-1">
                            {items.map(({ key, href, label, Icon }) => (
                                <Link
                                    key={key}
                                    href={href}
                                    onClick={closeMobile}
                                    aria-current={key === current ? 'page' : undefined}
                                    className={cn(
                                        'flex h-11 items-center gap-3 rounded-lg px-3.5 text-sm font-medium transition-colors',
                                        key === current
                                            ? 'bg-nila-100 font-semibold text-nila'
                                            : 'text-mangsi hover:bg-lontar hover:text-nila',
                                    )}
                                >
                                    <Icon
                                        size={20}
                                        weight={key === current ? 'bold' : 'regular'}
                                        aria-hidden="true"
                                    />
                                    <span>{label}</span>
                                </Link>
                            ))}

                            {!auth.user && (
                                <div className="mt-2 border-t border-abu-200/70 pt-2">
                                    <Link
                                        href={register.url()}
                                        onClick={closeMobile}
                                        className="flex h-11 w-full items-center justify-center rounded-lg bg-nila text-sm font-semibold text-white shadow-xs transition-colors hover:bg-nila-700"
                                    >
                                        {id.nav.register}
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </>
            )}
        </nav>
    );
}
