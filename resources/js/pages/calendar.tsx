import { Head, Link } from '@inertiajs/react';
import { CaretLeft, CaretRight, Info } from '@phosphor-icons/react';
import { useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { DayCell } from '@/components/rahina/day-cell';
import { DayDialog } from '@/components/rahina/day-dialog';
import { MoonFull, MoonNew } from '@/components/rahina/moon-phase';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { TodayPanel } from '@/components/rahina/today-panel';
import { id } from '@/lang/id';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { CalendarPageProps } from '@/types/rahina';

function shiftMonth(month: string, delta: number): string {
    const [y, m] = month.split('-').map(Number);
    const d = new Date(Date.UTC(y, m - 1 + delta, 1));

    return `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, '0')}`;
}

function monthUrl(month: string, today: string): string {
    const query: Record<string, string> = {};
    if (month !== today.slice(0, 7)) query.bulan = month;

    return home.url({ query });
}

const WEEKDAYS = Array.from({ length: 7 }, (_, i) =>
    new Intl.DateTimeFormat('id-ID', {
        weekday: 'short',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(2023, 0, 1 + i))),
);

export default function Calendar({
    month,
    today,
    minMonth,
    maxMonth,
    weeks,
    sasihInMonth,
    todayDay,
    upcoming,
}: CalendarPageProps) {
    const [selected, setSelected] = useState<string | null>(null);
    const gridRef = useRef<HTMLDivElement>(null);
    const [y, m] = month.split('-').map(Number);
    const title = new Intl.DateTimeFormat('id-ID', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(Date.UTC(y, m - 1, 1)));
    const prev = shiftMonth(month, -1);
    const next = shiftMonth(month, 1);
    const cells = weeks.flat();
    // Roving tabindex: satu sel bisa di-Tab (terpilih → hari ini → tanggal 1 bulan ini).
    const pick = (date: string | null) =>
        date === null ? -1 : cells.findIndex((c) => c.date === date);
    const focusIndex = [
        pick(selected),
        pick(today),
        cells.findIndex((c) => c.in_month),
    ].find((i) => i >= 0);

    // Navigasi panah di grid kalender (DESIGN_SYSTEM.md §10).
    const onGridKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        const deltas: Record<string, number> = {
            ArrowLeft: -1,
            ArrowRight: 1,
            ArrowUp: -7,
            ArrowDown: 7,
        };
        const delta = deltas[event.key];
        const target = event.target as HTMLElement;
        if (delta === undefined || target.dataset.index === undefined) return;
        event.preventDefault();
        const nextIndex = Number(target.dataset.index) + delta;
        gridRef.current
            ?.querySelector<HTMLButtonElement>(`[data-index="${nextIndex}"]`)
            ?.focus();
    };

    const navButton =
        'flex size-11 items-center justify-center rounded-md border border-abu-200 bg-kertas text-nila hover:border-nila focus-visible:outline-2 focus-visible:outline-nila';

    return (
        <div className="min-h-screen bg-lontar font-sans text-mangsi">
            <Head title={id.calendar.title} />

            <RahinaHeader current="calendar" />

            <main className="mx-auto grid max-w-6xl gap-6 px-4 py-6 lg:grid-cols-[320px_1fr]">
                <div className="order-2 lg:order-1">
                    <TodayPanel
                        day={todayDay}
                        upcoming={upcoming}
                        onSelect={setSelected}
                    />
                </div>

                <section
                    aria-labelledby="judul-bulan"
                    className="order-1 overflow-hidden rounded-lg border border-abu-200 bg-kertas lg:order-2"
                >
                    <div className="flex flex-wrap items-center justify-between gap-3 bg-nila px-5 py-4 text-white">
                        <div>
                            <h1
                                id="judul-bulan"
                                className="font-display text-[28px] leading-[34px] font-bold capitalize"
                            >
                                {title}
                            </h1>
                            {sasihInMonth.length > 0 && (
                                <p lang="ban" className="text-sm text-nila-100">
                                    {id.calendar.sasihLabel}{' '}
                                    {sasihInMonth.join(' – ')}
                                </p>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            {month !== today.slice(0, 7) && (
                                <Link
                                    href={monthUrl(today.slice(0, 7), today)}
                                    className="flex h-11 items-center rounded-md border border-nila-100/40 px-3 text-sm font-medium hover:bg-nila-700 focus-visible:outline-2 focus-visible:outline-white"
                                >
                                    {id.calendar.today}
                                </Link>
                            )}
                            {prev >= minMonth && (
                                <Link
                                    href={monthUrl(prev, today)}
                                    className={cn(
                                        navButton,
                                        'border-nila-100/40 bg-transparent text-white hover:bg-nila-700',
                                    )}
                                    aria-label={id.calendar.prevMonth}
                                >
                                    <CaretLeft size={20} weight="bold" />
                                </Link>
                            )}
                            {next <= maxMonth && (
                                <Link
                                    href={monthUrl(next, today)}
                                    className={cn(
                                        navButton,
                                        'border-nila-100/40 bg-transparent text-white hover:bg-nila-700',
                                    )}
                                    aria-label={id.calendar.nextMonth}
                                >
                                    <CaretRight size={20} weight="bold" />
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-abu-200 px-5 py-2 text-[13px] leading-[18px] text-abu-600">
                        <span className="flex items-center gap-1.5">
                            <MoonFull size={14} /> {id.calendar.legendPurnama}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <MoonNew size={14} /> {id.calendar.legendTilem}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span className="h-1 w-4 rounded-full bg-kunyit" />
                            {id.calendar.legendToday}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <b className="text-kunyit-800">1</b>
                            {id.calendar.legendPenanggal}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <b className="text-nila">1</b>
                            {id.calendar.legendPangelong}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Info size={14} aria-hidden="true" />
                            <i>{id.calendar.hint}</i>
                        </span>
                    </div>

                    <div
                        ref={gridRef}
                        role="grid"
                        aria-labelledby="judul-bulan"
                        onKeyDown={onGridKeyDown}
                        className="p-2 sm:p-4"
                    >
                        <div
                            role="row"
                            className="grid grid-cols-7 gap-1 sm:gap-2"
                        >
                            {WEEKDAYS.map((w) => (
                                <span
                                    key={w}
                                    role="columnheader"
                                    className="py-2 text-center text-sm font-medium text-abu-600"
                                >
                                    {w}
                                </span>
                            ))}
                        </div>
                        {weeks.map((week, wi) => (
                            <div
                                key={wi}
                                role="row"
                                className="mt-1 grid grid-cols-7 gap-1 sm:mt-2 sm:gap-2"
                            >
                                {week.map((cell) => {
                                    const index = cells.indexOf(cell);

                                    return (
                                        <div key={cell.date} role="gridcell">
                                            <DayCell
                                                cell={cell}
                                                index={index}
                                                isToday={cell.date === today}
                                                isSelected={
                                                    cell.date === selected
                                                }
                                                focusable={index === focusIndex}
                                                onSelect={setSelected}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        ))}
                    </div>
                </section>
            </main>

            <footer className="pb-8 text-center text-[13px] leading-[18px] text-abu-600">
                {id.disclaimer}
            </footer>

            <DayDialog date={selected} onClose={() => setSelected(null)} />
        </div>
    );
}
