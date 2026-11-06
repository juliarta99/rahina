import { ArrowRight } from '@phosphor-icons/react';
import { longDate } from '@/components/rahina/day-cell';
import { VerdictTag } from '@/components/rahina/verdict-tag';
import { id } from '@/lang/id';
import type { AnswerDate } from '@/types/rahina';

/**
 * Referensi tanggal di jawaban Tanya Wariga (DESIGN_SYSTEM.md §6.6). Isinya dari hasil tool di server,
 * sehingga menjadi bukti bahwa jawaban berasal dari data perhitungan.
 */
export function DateRefCard({
    item,
    onOpen,
}: {
    item: AnswerDate;
    onOpen: (date: string) => void;
}) {
    const t = id.ask;
    const names = (rules: AnswerDate['support']) =>
        rules.length === 0 ? t.none : rules.map((r) => r.name).join(', ');

    return (
        <article className="flex flex-col gap-2 rounded-lg border border-abu-200 bg-kertas p-4">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <h3 className="font-display text-[18px] leading-6 font-semibold capitalize">
                    {longDate(item.date)}
                </h3>
                <VerdictTag verdict={item.verdict} />
            </div>
            <p lang="ban" className="text-[13px] leading-[18px] text-abu-600">
                {item.saptawara} {item.pancawara} · {id.today.wuku} {item.wuku}{' '}
                · {id.today.sasih} {item.sasih}
                {item.rahinan.length > 0 && ` · ${item.rahinan.join(', ')}`}
            </p>
            <p>{item.reason}</p>
            <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
                <dt className="font-medium text-ayu">{t.support}</dt>
                <dd lang="ban">
                    <b>{names(item.support)}</b>
                </dd>
                <dt className="font-medium text-ala">{t.block}</dt>
                <dd lang="ban">
                    <b>{names(item.block)}</b>
                </dd>
            </dl>
            <button
                type="button"
                onClick={() => onOpen(item.date)}
                className="flex h-11 items-center gap-1.5 self-start font-medium text-nila hover:underline focus-visible:outline-2 focus-visible:outline-nila"
            >
                {t.seeDetail}
                <ArrowRight size={16} weight="bold" aria-hidden="true" />
            </button>
        </article>
    );
}
