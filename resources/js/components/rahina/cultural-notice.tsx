import { Info } from '@phosphor-icons/react';
import { id } from '@/lang/id';

/** Wajib tampil di konteks upacara (DESIGN_SYSTEM.md §6.9, CLAUDE.md §2.5). */
export function CulturalNotice() {
    return (
        <div
            role="note"
            className="flex gap-2 rounded-md bg-nila-100 p-3 text-nila"
        >
            <Info
                size={20}
                weight="bold"
                className="mt-0.5 shrink-0"
                aria-hidden="true"
            />
            <p>{id.culturalNotice}</p>
        </div>
    );
}
