import { CheckCircle, Scales, XCircle } from '@phosphor-icons/react';
import { id } from '@/lang/id';
import type { DewasaItem } from '@/types/rahina';

const icons = { ayu: CheckCircle, ala: XCircle, campur: Scales };
const tones = { ayu: 'text-ayu', ala: 'text-ala', campur: 'text-campur' };

/** Baris dewasa + indikator alahing 5 segmen + sumber (DESIGN_SYSTEM.md §6.4). */
export function DewasaRow({ dewasa }: { dewasa: DewasaItem }) {
    const Icon = icons[dewasa.verdict];
    const level = dewasa.alahing_level;

    return (
        <li className="flex gap-3 border-b border-abu-200 py-3 last:border-b-0">
            <Icon
                size={20}
                weight="bold"
                className={`mt-0.5 shrink-0 ${tones[dewasa.verdict]}`}
                aria-label={id.verdict[dewasa.verdict]}
            />
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <span lang="ban" className="font-bold text-mangsi">
                        {dewasa.name}
                    </span>
                    {level !== null && (
                        <span className="flex items-center gap-2 text-[13px] text-abu-600">
                            {id.today.alahing} {level}
                            <span className="flex gap-0.5" aria-hidden="true">
                                {[1, 2, 3, 4, 5].map((i) => (
                                    <span
                                        key={i}
                                        className={`h-2.5 w-1.5 rounded-[1px] ${i <= level ? 'bg-mangsi' : 'bg-abu-200'}`}
                                    />
                                ))}
                            </span>
                        </span>
                    )}
                </div>
                <p className="mt-1 text-mangsi">{dewasa.summary}</p>
                <p className="mt-1 text-[13px] leading-[18px] text-abu-600">
                    {id.today.source}: {dewasa.source.title}
                    {dewasa.source.page !== null &&
                        `, ${id.today.page} ${dewasa.source.page}`}
                </p>
            </div>
        </li>
    );
}
