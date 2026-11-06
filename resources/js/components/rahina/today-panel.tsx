import { CalendarBlank } from '@phosphor-icons/react';
import { MoonFull, MoonNew } from '@/components/rahina/moon-phase';
import { id } from '@/lang/id';
import type { CalendarDay, UpcomingRahinan } from '@/types/rahina';

const WITA = 'Asia/Makassar';

function fmt(date: string, options: Intl.DateTimeFormatOptions): string {
    return new Intl.DateTimeFormat('id-ID', {
        ...options,
        timeZone: WITA,
    }).format(new Date(`${date}T12:00:00+08:00`));
}

/** Panel kiri: ringkas data hari ini + rahinan mendatang (H-n). */
export function TodayPanel({
    day,
    upcoming,
    onSelect,
}: {
    day: CalendarDay | null;
    upcoming: UpcomingRahinan[];
    onSelect: (date: string) => void;
}) {
    const t = id.today;

    if (day === null) {
        return (
            <aside className="rounded-lg border border-abu-200 bg-kertas p-5 text-abu-600">
                {t.notAvailable}
            </aside>
        );
    }

    const rows: [string, string][] = [
        ...(day.wewaran.ekawara
            ? [
                  [id.wewaran.ekawara, day.wewaran.ekawara.name] as [
                      string,
                      string,
                  ],
              ]
            : []),
        [id.wewaran.saptawara, day.wewaran.saptawara?.name ?? '—'],
        [id.wewaran.pancawara, day.wewaran.pancawara?.name ?? '—'],
        [id.wewaran.dasawara, day.wewaran.dasawara?.name ?? '—'],
        [t.wuku, day.wuku.name],
        [t.sasih, day.sasih.name],
        [
            day.is_penanggal ? t.penanggal : t.pangelong,
            day.sasih_day_end !== null
                ? `${day.sasih_day}/${day.sasih_day_end}`
                : String(day.sasih_day),
        ],
    ];

    return (
        <aside className="flex flex-col gap-5 rounded-lg border border-abu-200 bg-kertas p-5">
            <div className="flex items-center gap-3 rounded-lg border border-nila/15 bg-nila-100/50 p-3">
                <img
                    src="/assets/images/maskots/halo.png"
                    alt="Maskot Rahina"
                    className="size-12 shrink-0 object-contain sm:size-14"
                />
                <div className="flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-nila">
                        Rahajeng Rahina
                    </span>
                    <span className="text-xs text-abu-600">
                        Prakiraan dewasa ayu & kalender Bali hari ini
                    </span>
                </div>
            </div>

            <div className="flex items-center justify-between">
                <span className="rounded-sm bg-mangsi px-2 py-0.5 text-[13px] font-medium text-white">
                    {id.calendar.today}
                </span>
                <span className="rounded-sm bg-nila-100 px-2 py-0.5 text-[13px] font-medium text-nila">
                    {t.saka} {day.saka_year}
                </span>
            </div>

            <button
                type="button"
                onClick={() => onSelect(day.date)}
                className="-m-2 flex items-center gap-3 rounded-md p-2 text-left hover:bg-nila-100 focus-visible:outline-2 focus-visible:outline-nila"
            >
                <span className="flex size-12 shrink-0 items-center justify-center rounded-md border border-abu-200 text-nila">
                    <CalendarBlank size={24} aria-hidden="true" />
                </span>
                <span>
                    <span className="block font-display text-[22px] leading-7 font-semibold">
                        {fmt(day.date, { weekday: 'long' })}
                    </span>
                    <span className="block text-abu-600">
                        {fmt(day.date, {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        })}
                    </span>
                </span>
            </button>

            <dl className="flex flex-col divide-y divide-abu-200 border-y border-abu-200">
                {rows.map(([label, value]) => (
                    <div key={label} className="flex justify-between py-2">
                        <dt className="text-abu-600">{label}</dt>
                        <dd lang="ban" className="font-bold">
                            {value}
                        </dd>
                    </div>
                ))}
            </dl>

            {upcoming.length > 0 && (
                <section aria-labelledby="akan-datang">
                    <h2
                        id="akan-datang"
                        className="flex items-center gap-2 font-bold"
                    >
                        <CalendarBlank size={20} aria-hidden="true" />
                        {id.calendar.upcoming}
                    </h2>
                    <ul className="mt-2 flex flex-col gap-2">
                        {upcoming.map((item) => (
                            <li key={`${item.date}-${item.slug}`}>
                                <button
                                    type="button"
                                    onClick={() => onSelect(item.date)}
                                    className="flex min-h-11 w-full items-center gap-3 rounded-md border border-abu-200 px-3 py-2 text-left hover:border-nila focus-visible:outline-2 focus-visible:outline-nila"
                                >
                                    {item.slug === 'purnama' ? (
                                        <MoonFull size={16} />
                                    ) : item.slug === 'tilem' ? (
                                        <MoonNew size={16} />
                                    ) : (
                                        <span className="size-2 shrink-0 rounded-full bg-nila" />
                                    )}
                                    <span className="min-w-0 flex-1">
                                        <span
                                            lang="ban"
                                            className="block truncate font-bold"
                                        >
                                            {item.name}
                                        </span>
                                        <span className="block text-[13px] leading-[18px] text-abu-600">
                                            {fmt(item.date, {
                                                day: 'numeric',
                                                month: 'long',
                                            })}
                                        </span>
                                    </span>
                                    <span className="shrink-0 rounded-sm bg-lontar px-2 py-0.5 text-[13px] font-medium tabular-nums">
                                        {id.calendar.daysUntil(item.days_until)}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </aside>
    );
}
