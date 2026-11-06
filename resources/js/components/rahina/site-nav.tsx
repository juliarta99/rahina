import { Link, usePage } from '@inertiajs/react';
import {
    CalendarBlank,
    ChatCircleText,
    House,
    SignIn,
    User,
} from '@phosphor-icons/react';
import type { Icon } from '@phosphor-icons/react';
import { id } from '@/lang/id';
import { cn } from '@/lib/utils';
import { ask, home, login, register } from '@/routes';
import { profile } from '@/routes/rahina';

type Current = 'home' | 'calendar' | 'ask' | 'profile';

/**
 * Navigasi utama (DESIGN_SYSTEM.md §7): ikon + label, tab aktif nila.
 * Tamu melihat Masuk/Daftar; pengguna masuk melihat Profil (pengaturan Rahina Pagi).
 */
export function SiteNav({ current }: { current: Current }) {
    const { auth } = usePage().props;
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

    return (
        <nav
            aria-label={id.nav.label}
            className="flex flex-wrap items-center gap-1"
        >
            {items.map(({ key, href, label, Icon }) => (
                <Link
                    key={key}
                    href={href}
                    aria-current={key === current ? 'page' : undefined}
                    className={cn(
                        'flex h-11 items-center gap-1.5 rounded-md px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-nila',
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
                    className="flex h-11 items-center rounded-md bg-nila px-3 text-sm font-medium text-white hover:bg-nila-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nila"
                >
                    {id.nav.register}
                </Link>
            )}
        </nav>
    );
}
