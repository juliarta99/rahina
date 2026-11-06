import { BookOpen, CloudSlash } from '@phosphor-icons/react';
import { useEffect, useState } from 'react';
import { CulturalNotice } from '@/components/rahina/cultural-notice';
import { longDate } from '@/components/rahina/day-cell';
import { DewasaRow } from '@/components/rahina/dewasa-row';
import { MoonFull, MoonNew } from '@/components/rahina/moon-phase';
import { VerdictTag } from '@/components/rahina/verdict-tag';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { id } from '@/lang/id';
import { show as dayShow } from '@/routes/day';
import type { CalendarDay, DayDetail } from '@/types/rahina';

type State =
    | { status: 'loading' }
    | { status: 'error'; message: string }
    | { status: 'ready'; detail: DayDetail };

function sasihLine(day: CalendarDay): string {
    const t = id.today;
    const phase = day.is_penanggal ? t.penanggal : t.pangelong;
    const days =
        day.sasih_day_end !== null
            ? `${day.sasih_day}/${day.sasih_day_end} (${t.ngunaratri})`
            : `${day.sasih_day}`;

    return `${phase} ${days}`;
}

/** Popup detail tanggal: ringkasan (AI lewat OutputGuard / template) + data perhitungan + dewasa & sumber. */
export function DayDialog({
    date,
    onClose,
}: {
    date: string | null;
    onClose: () => void;
}) {
    const [state, setState] = useState<State>({ status: 'loading' });
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        if (date === null) {
            return;
        }
        const controller = new AbortController();
        setState({ status: 'loading' });

        fetch(dayShow.url(date), {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then(async (res) => {
                const body: unknown = await res.json();
                if (!res.ok) {
                    const message =
                        typeof body === 'object' &&
                        body !== null &&
                        'message' in body &&
                        typeof body.message === 'string'
                            ? body.message
                            : id.detail.loadError;
                    setState({ status: 'error', message });

                    return;
                }
                setState({ status: 'ready', detail: body as DayDetail });
            })
            .catch(() => {
                if (!controller.signal.aborted) {
                    setState({ status: 'error', message: id.detail.loadError });
                }
            });

        return () => controller.abort();
    }, [date, attempt]);

    return (
        <Dialog
            open={date !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="max-h-[90vh] w-[calc(100%-1.5rem)] max-w-2xl overflow-y-auto rounded-lg border-abu-200 bg-kertas p-0 text-mangsi sm:max-w-2xl">
                {date !== null && (
                    <>
                        <DialogHeader className="border-b border-abu-200 px-5 pt-5 pb-4 text-left">
                            <DialogTitle className="font-display text-[22px] leading-7 font-semibold text-mangsi">
                                {longDate(date)}
                            </DialogTitle>
                            <DialogDescription
                                lang="ban"
                                className="text-[13px] leading-[18px] text-abu-600"
                            >
                                {state.status === 'ready'
                                    ? `${state.detail.day.wewaran.saptawara?.name ?? ''} ${state.detail.day.wewaran.pancawara?.name ?? ''} · ${id.today.wuku} ${state.detail.day.wuku.name} · ${id.today.sasih} ${state.detail.day.sasih.name} · ${id.today.saka} ${state.detail.day.saka_year}`
                                    : id.calendar.hint}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="flex flex-col gap-6 px-5 pt-4 pb-6">
                            {state.status === 'loading' && <Loading />}
                            {state.status === 'error' && (
                                <div className="flex flex-col items-start gap-2 text-abu-600">
                                    <CloudSlash size={32} aria-hidden="true" />
                                    <p>{state.message}</p>
                                    <button
                                        type="button"
                                        onClick={() => setAttempt((n) => n + 1)}
                                        className="h-11 rounded-md border-[1.5px] border-mangsi bg-kertas px-4 font-medium text-mangsi focus-visible:outline-2 focus-visible:outline-nila"
                                    >
                                        {id.detail.retry}
                                    </button>
                                </div>
                            )}
                            {state.status === 'ready' && (
                                <Detail detail={state.detail} />
                            )}
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

function Loading() {
    return (
        <div
            aria-live="polite"
            className="flex flex-col gap-4 rounded-xl border border-abu-200 bg-kertas p-4 sm:flex-row sm:items-start sm:gap-5"
        >
            <img
                src="/assets/images/maskots/waiting.png"
                alt="Menyusun ringkasan"
                width={80}
                height={80}
                loading="eager"
                decoding="sync"
                className="size-16 shrink-0 self-center object-contain sm:size-20 sm:self-start"
            />
            <div className="flex flex-1 flex-col gap-2.5">
                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm font-semibold text-mangsi">
                        {id.detail.loading}
                    </p>
                    <span
                        className="flex h-3.5 items-end gap-1"
                        aria-hidden="true"
                    >
                        <span
                            className="h-full w-1 rounded-full bg-nila animate-wave-bar"
                            style={{ animationDelay: '0ms' }}
                        />
                        <span
                            className="h-full w-1 rounded-full bg-kunyit animate-wave-bar"
                            style={{ animationDelay: '150ms' }}
                        />
                        <span
                            className="h-full w-1 rounded-full bg-nila animate-wave-bar"
                            style={{ animationDelay: '300ms' }}
                        />
                        <span
                            className="h-full w-1 rounded-full bg-kunyit animate-wave-bar"
                            style={{ animationDelay: '450ms' }}
                        />
                    </span>
                </div>
                <p className="text-xs text-abu-600">
                    Membaca wewaran, sasih, wuku, dan menyusun ringkasan rahinan...
                </p>
                <div className="flex flex-col gap-2 pt-1" aria-hidden="true">
                    <div className="h-4.5 w-4/5 rounded-md animate-shimmer-wave" />
                    <div
                        className="h-3.5 w-full rounded-md animate-shimmer-wave"
                        style={{ animationDelay: '150ms' }}
                    />
                    <div
                        className="h-3.5 w-11/12 rounded-md animate-shimmer-wave"
                        style={{ animationDelay: '300ms' }}
                    />
                    <div
                        className="h-3.5 w-3/4 rounded-md animate-shimmer-wave"
                        style={{ animationDelay: '450ms' }}
                    />
                </div>
            </div>
        </div>
    );
}

function Detail({ detail }: { detail: DayDetail }) {
    const { day, brief, dewasa } = detail;
    const t = id.today;

    return (
        <>
            <section aria-labelledby="ringkasan">
                <div className="flex items-center gap-2.5">
                    <img
                        src="/assets/images/logo_rahina.png"
                        alt="Maskot Rahina"
                        className="size-7 shrink-0 object-contain"
                    />
                    <h2 id="ringkasan" className="text-lg font-bold">
                        {t.summaryHeading}
                    </h2>
                </div>
                {brief !== null && (
                    <>
                        <p className="mt-2 font-display text-lg leading-6 font-semibold">
                            {brief.headline}
                        </p>
                        <ul className="mt-3 flex flex-col gap-3">
                            {brief.points.map((point, i) => (
                                <li
                                    key={i}
                                    className="flex flex-col items-start gap-1.5 sm:flex-row sm:gap-3"
                                >
                                    <VerdictTag
                                        verdict={point.verdict}
                                        className="sm:mt-0.5"
                                    />
                                    <span className="leading-6">
                                        {point.text}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        {brief.rahinan_note !== null && (
                            <p className="mt-3 rounded-md bg-kunyit-100 px-3 py-2 text-kunyit-800">
                                {brief.rahinan_note}
                            </p>
                        )}
                        {brief.generated_by === 'template' && (
                            <p className="mt-3 flex gap-2 text-[13px] leading-[18px] text-abu-600">
                                <CloudSlash
                                    size={16}
                                    className="mt-0.5 shrink-0"
                                    aria-hidden="true"
                                />
                                {t.templateNotice}
                            </p>
                        )}
                    </>
                )}
                {detail.culturalNotice && (
                    <div className="mt-3">
                        <CulturalNotice />
                    </div>
                )}
                <p className="mt-3 flex items-center gap-2 text-[13px] leading-[18px] text-abu-600">
                    <BookOpen size={20} aria-hidden="true" />
                    {t.sourceLine(detail.sourceCount)}
                </p>
            </section>

            <section aria-labelledby="info-utama">
                <h2 id="info-utama" className="text-lg font-bold">
                    {id.detail.mainInfo}
                </h2>
                <div className="mt-2 grid gap-3 sm:grid-cols-2">
                    <InfoCard title={t.wuku} value={day.wuku.name}>
                        {id.detail.urip}: {day.wuku.urip}
                    </InfoCard>
                    <InfoCard
                        title={t.sasih}
                        value={`${day.sasih.name}${day.sasih.is_nampih ? ` (${t.nampih})` : ''}`}
                    >
                        <span className="flex items-center gap-1.5">
                            {sasihLine(day)}
                            {day.is_purnama && (
                                <>
                                    <MoonFull size={14} /> {t.purnama}
                                </>
                            )}
                            {day.is_tilem && (
                                <>
                                    <MoonNew size={14} /> {t.tilem}
                                </>
                            )}
                        </span>
                    </InfoCard>
                </div>
                {day.rahinan.length > 0 && (
                    <p className="mt-3">
                        <span className="text-abu-600">{t.rahinan}: </span>
                        <span lang="ban" className="font-bold">
                            {day.rahinan.map((r) => r.name).join(', ')}
                        </span>
                    </p>
                )}
                <p className="mt-1 text-[13px] leading-[18px] text-abu-600">
                    {t.uripTotal}: {day.urip_total}
                </p>
            </section>

            <section aria-labelledby="wewaran">
                <h2 id="wewaran" className="text-lg font-bold">
                    {id.detail.wewaran}
                </h2>
                <dl className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5">
                    {Object.entries(day.wewaran).map(([key, value]) => (
                        <div
                            key={key}
                            className="rounded-md border border-abu-200 p-2"
                        >
                            <dt className="text-[11px] leading-4 tracking-[.01em] text-abu-600 uppercase">
                                {id.wewaran[key] ?? key}
                            </dt>
                            <dd lang="ban" className="font-bold">
                                {value?.name ?? '—'}
                            </dd>
                            {value?.urip !== null &&
                                value?.urip !== undefined && (
                                    <dd className="text-[13px] leading-[18px] text-abu-600">
                                        {id.detail.urip} {value.urip}
                                    </dd>
                                )}
                        </div>
                    ))}
                </dl>
            </section>

            <section aria-labelledby="paringkelan">
                <h2 id="paringkelan" className="text-lg font-bold">
                    {id.detail.paringkelan}
                </h2>
                <dl className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    {[
                        ['ingkel', day.ingkel] as const,
                        ...Object.entries(day.paringkelan),
                    ].map(([key, value]) => (
                        <div
                            key={key}
                            className="rounded-md border border-abu-200 p-2"
                        >
                            <dt className="text-[11px] leading-4 tracking-[.01em] text-abu-600 uppercase">
                                {key === 'ingkel'
                                    ? t.ingkel
                                    : (id.paringkelan[key] ?? key)}
                            </dt>
                            <dd lang="ban" className="font-bold">
                                {value ?? '—'}
                            </dd>
                        </div>
                    ))}
                </dl>
                <p className="mt-2 text-[13px] leading-[18px] text-abu-600">
                    {id.detail.paringkelanNote}
                </p>
            </section>

            <section aria-labelledby="dewasa">
                <h2 id="dewasa" className="text-lg font-bold">
                    {t.dewasaHeading}
                </h2>
                {dewasa.length === 0 ? (
                    <p className="mt-2 text-abu-600">{t.noDewasa}</p>
                ) : (
                    <ul className="mt-1">
                        {dewasa.map((d) => (
                            <DewasaRow key={d.rule_id} dewasa={d} />
                        ))}
                    </ul>
                )}
            </section>

            <p className="text-[13px] leading-[18px] text-abu-600">
                {id.disclaimer}
            </p>
        </>
    );
}

function InfoCard({
    title,
    value,
    children,
}: {
    title: string;
    value: string;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-md border border-abu-200 p-3">
            <p className="text-[11px] leading-4 tracking-[.01em] text-abu-600 uppercase">
                {title}
            </p>
            <p lang="ban" className="font-display text-xl font-semibold">
                {value}
            </p>
            <div className="mt-1 text-[13px] leading-[18px] text-abu-600">
                {children}
            </div>
        </div>
    );
}
