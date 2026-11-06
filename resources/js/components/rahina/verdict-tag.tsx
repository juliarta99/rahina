import { CheckCircle, Info, Scales, XCircle } from '@phosphor-icons/react';
import type { Icon } from '@phosphor-icons/react';
import { id } from '@/lang/id';
import { cn } from '@/lib/utils';
import type { Verdict } from '@/types/rahina';

const styles: Record<Verdict, { icon: Icon; className: string }> = {
    ayu: { icon: CheckCircle, className: 'bg-ayu-100 text-ayu' },
    ala: { icon: XCircle, className: 'bg-ala-100 text-ala' },
    campur: { icon: Scales, className: 'bg-kunyit-100 text-campur' },
    info: { icon: Info, className: 'bg-nila-100 text-nila' },
};

/** Status selalu ikon + teks + warna, tidak pernah warna saja (DESIGN_SYSTEM.md §2.4, §6.5). */
export function VerdictTag({
    verdict,
    className,
}: {
    verdict: Verdict;
    className?: string;
}) {
    const { icon: IconComponent, className: tone } = styles[verdict];

    return (
        <span
            className={cn(
                'inline-flex h-6 shrink-0 items-center gap-1 rounded-sm px-2 text-[13px] leading-none font-medium',
                tone,
                className,
            )}
        >
            <IconComponent size={16} weight="bold" aria-hidden="true" />
            {id.verdict[verdict]}
        </span>
    );
}
