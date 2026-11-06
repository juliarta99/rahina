import { MoonFull, MoonNew } from '@/components/rahina/moon-phase';
import { id } from '@/lang/id';
import { cn } from '@/lib/utils';
import type { CalendarCell } from '@/types/rahina';

const WITA = 'Asia/Makassar';

export function longDate(date: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: WITA,
    }).format(new Date(`${date}T12:00:00+08:00`));
}

function sasihDayText(cell: CalendarCell): string {
    const phase = cell.is_penanggal ? id.today.penanggal : id.today.pangelong;
    const end =
        cell.sasih_day_end !== null && cell.sasih_day_end !== undefined
            ? `/${cell.sasih_day_end}`
            : '';

    return `${phase} ${cell.sasih_day}${end}`;
}

function ariaLabel(cell: CalendarCell): string {
    if (!cell.available) {
        return `${longDate(cell.date)}, ${id.calendar.notAvailable}`;
    }
    const parts = [
        longDate(cell.date),
        `${cell.saptawara} ${cell.pancawara}`,
        `${id.today.wuku} ${cell.wuku}`,
        `${sasihDayText(cell)} ${id.today.sasih} ${cell.sasih}`,
    ];
    if (cell.is_purnama) parts.push(id.today.purnama);
    if (cell.is_tilem) parts.push(id.today.tilem);
    if (cell.rahinan && cell.rahinan.length > 0)
        parts.push(cell.rahinan.join(', '));
    if (cell.dewasa && cell.dewasa.baik + cell.dewasa.tidak_baik > 0) {
        parts.push(
            id.calendar.dewasaStrip(cell.dewasa.baik, cell.dewasa.tidak_baik),
        );
    }

    return parts.join(', ');
}

/** Sel kalender bulanan (DESIGN_SYSTEM.md §6.2). */
export function DayCell({
    cell,
    index,
    isToday,
    isSelected,
    focusable,
    onSelect,
}: {
    cell: CalendarCell;
    index: number;
    isToday: boolean;
    isSelected: boolean;
    focusable: boolean;
    onSelect: (date: string) => void;
}) {
    const day = Number(cell.date.slice(8, 10));
    const showSasihName = cell.available && cell.sasih_day === 1;
    const baik = cell.dewasa?.baik ?? 0;
    const buruk = cell.dewasa?.tidak_baik ?? 0;

    return (
        <button
            type="button"
            data-index={index}
            tabIndex={focusable ? 0 : -1}
            onClick={() => onSelect(cell.date)}
            disabled={!cell.available}
            aria-label={ariaLabel(cell)}
            aria-current={isToday ? 'date' : undefined}
            aria-selected={isSelected}
            className={cn(
                'relative flex h-full min-h-[84px] w-full flex-col items-stretch rounded-md border p-1.5 text-left transition-colors duration-150 ease-rahina focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-nila sm:min-h-[104px] sm:p-2',
                isSelected
                    ? 'border-nila bg-nila-100'
                    : isToday
                      ? 'border-t-[3px] border-abu-200 border-t-kunyit bg-kunyit-100'
                      : 'border-abu-200 bg-kertas hover:border-nila',
                !cell.in_month && 'bg-lontar',
                !cell.available && 'cursor-not-allowed',
            )}
        >
            <span className="flex items-start justify-between gap-1">
                {cell.available ? (
                    <span
                        className={cn(
                            'text-[11px] leading-4 font-bold tabular-nums',
                            cell.is_penanggal ? 'text-kunyit-800' : 'text-nila',
                            !cell.in_month && 'opacity-60',
                        )}
                        aria-hidden="true"
                    >
                        {cell.sasih_day}
                        {cell.sasih_day_end !== null &&
                            cell.sasih_day_end !== undefined &&
                            `/${cell.sasih_day_end}`}
                    </span>
                ) : (
                    <span />
                )}
                {cell.is_purnama && <MoonFull size={14} />}
                {cell.is_tilem && <MoonNew size={14} />}
            </span>

            <span
                className={cn(
                    'mt-0.5 text-center font-display text-xl leading-6 font-semibold tabular-nums sm:text-2xl sm:leading-8',
                    cell.in_month ? 'text-mangsi' : 'text-abu-400',
                )}
                aria-hidden="true"
            >
                {day}
            </span>

            {cell.available && (
                <span
                    lang="ban"
                    className={cn(
                        'mt-0.5 text-center text-[11px] leading-4',
                        cell.in_month ? 'text-abu-600' : 'text-abu-400',
                    )}
                    aria-hidden="true"
                >
                    <span className="block">{cell.pancawara}</span>
                    <span className="hidden sm:block">{cell.wuku}</span>
                </span>
            )}

            {showSasihName && (
                <span
                    className="mx-auto mt-0.5 rounded-sm bg-kunyit-100 px-1 text-[10px] leading-4 font-bold text-kunyit-800"
                    aria-hidden="true"
                >
                    {cell.sasih}
                </span>
            )}

            {cell.rahinan && cell.rahinan.length > 0 && (
                <span
                    className="mt-auto flex items-center gap-1 pt-1 text-[10px] leading-3 text-nila"
                    aria-hidden="true"
                >
                    <span className="size-1.5 shrink-0 rounded-full bg-nila" />
                    <span className="hidden truncate sm:inline">
                        {cell.rahinan[0]}
                    </span>
                </span>
            )}

            {baik + buruk > 0 && (
                <span
                    className="mt-1 flex h-1 gap-0.5"
                    aria-hidden="true"
                    title={id.calendar.dewasaStrip(baik, buruk)}
                >
                    {baik > 0 && (
                        <span
                            className="h-full rounded-full bg-ayu"
                            style={{ flexGrow: baik }}
                        />
                    )}
                    {buruk > 0 && (
                        <span
                            className="h-full rounded-full bg-ala"
                            style={{ flexGrow: buruk }}
                        />
                    )}
                </span>
            )}
        </button>
    );
}
