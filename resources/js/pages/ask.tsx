import { Head } from '@inertiajs/react';
import {
    ArrowClockwise,
    BookOpen,
    CloudSlash,
    FlowerLotus,
    HouseLine,
    PaperPlaneRight,
    Plant,
    Storefront,
} from '@phosphor-icons/react';
import type { Icon } from '@phosphor-icons/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import { CulturalNotice } from '@/components/rahina/cultural-notice';
import { DateRefCard } from '@/components/rahina/date-ref-card';
import { DayDialog } from '@/components/rahina/day-dialog';
import { SiteNav } from '@/components/rahina/site-nav';
import { id } from '@/lang/id';
import { store as askStore } from '@/routes/ask';
import type { AgentAnswer, AskPageProps } from '@/types/rahina';

type Message =
    | { role: 'user'; text: string }
    | { role: 'assistant'; answer: AgentAnswer; question: string };

type Pending = { question: string; category: string | null };

const SUGGESTION_ICONS: Record<string, Icon> = {
    pertanian: Plant,
    dagang: Storefront,
    pembangunan: HouseLine,
    upacara: FlowerLotus,
};

const MAX_LENGTH = 500;

/** Token CSRF Laravel dari cookie XSRF-TOKEN (endpoint POST /tanya dilindungi CSRF). */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function isAnswer(body: unknown): body is AgentAnswer {
    return (
        typeof body === 'object' &&
        body !== null &&
        'answer' in body &&
        'dates' in body
    );
}

export default function Ask(_props: AskPageProps) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [pending, setPending] = useState<Pending | null>(null);
    const [error, setError] = useState<{
        message: string;
        retry: Pending;
    } | null>(null);
    const [selected, setSelected] = useState<string | null>(null);
    const endRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        endRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [messages, pending, error]);

    const send = async (question: string, category: string | null = null) => {
        const text = question.trim();
        if (text.length < 2 || pending !== null) return;

        // Riwayat singkat (teks saja) agar pertanyaan lanjutan dipahami; maks 4 giliran.
        const history = messages
            .slice(-4)
            .map((m) =>
                m.role === 'user'
                    ? { role: 'user' as const, text: m.text }
                    : { role: 'assistant' as const, text: m.answer.answer },
            );

        setError(null);
        if (category === null) {
            setMessages((prev) => [...prev, { role: 'user', text }]);
        }
        setInput('');
        setPending({ question: text, category });

        try {
            const res = await fetch(askStore.url(), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    question: text,
                    category,
                    history,
                }),
            });
            const body: unknown = await res.json().catch(() => null);
            if (!res.ok || !isAnswer(body)) {
                const message =
                    typeof body === 'object' &&
                    body !== null &&
                    'message' in body &&
                    typeof body.message === 'string'
                        ? body.message
                        : id.ask.error;
                setError({ message, retry: { question: text, category } });

                return;
            }
            setMessages((prev) => [
                ...prev,
                { role: 'assistant', answer: body, question: text },
            ]);
        } catch {
            setError({
                message: id.ask.error,
                retry: { question: text, category },
            });
        } finally {
            setPending(null);
            inputRef.current?.focus();
        }
    };

    const onSubmit = (event: FormEvent) => {
        event.preventDefault();
        void send(input);
    };

    const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            void send(input);
        }
    };

    return (
        <div className="min-h-screen bg-lontar font-sans text-mangsi">
            <Head title={id.ask.title} />

            <header className="border-b border-abu-200 bg-kertas">
                <div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <span className="font-display text-[22px] leading-7 font-semibold">
                        {id.appName}
                    </span>
                    <SiteNav current="ask" />
                </div>
            </header>

            <main className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-6">
                <section
                    aria-labelledby="judul-tanya"
                    className="flex flex-col gap-3"
                >
                    <h1
                        id="judul-tanya"
                        className="font-display text-[28px] leading-[34px] font-bold"
                    >
                        {id.ask.title}
                    </h1>
                    <p className="text-abu-600">{id.ask.intro}</p>
                </section>

                {messages.length === 0 && pending === null && (
                    <section aria-label={id.ask.suggestionsLabel}>
                        <h2 className="mb-2 text-sm font-medium text-abu-600">
                            {id.ask.suggestionsLabel}
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {id.ask.suggestions.map((s) => {
                                const SuggestionIcon =
                                    SUGGESTION_ICONS[s.icon] ?? Plant;

                                return (
                                    <button
                                        key={s.text}
                                        type="button"
                                        onClick={() => void send(s.text)}
                                        className="flex min-h-11 items-center gap-2 rounded-sm border border-abu-200 bg-kertas px-3 py-2 text-left text-sm hover:border-nila focus-visible:outline-2 focus-visible:outline-nila"
                                    >
                                        <SuggestionIcon
                                            size={18}
                                            className="shrink-0 text-nila"
                                            aria-hidden="true"
                                        />
                                        {s.text}
                                    </button>
                                );
                            })}
                        </div>
                    </section>
                )}

                <div aria-live="polite" className="flex flex-col gap-4">
                    {messages.map((m, i) =>
                        m.role === 'user' ? (
                            <div
                                key={i}
                                className="max-w-[85%] self-end rounded-[16px_16px_4px_16px] bg-nila-100 px-4 py-3"
                            >
                                <span className="sr-only">{id.ask.you}: </span>
                                {m.text}
                            </div>
                        ) : (
                            <AnswerBubble
                                key={i}
                                answer={m.answer}
                                disabled={pending !== null}
                                onOpen={setSelected}
                                onChoose={(code) => void send(m.question, code)}
                            />
                        ),
                    )}

                    {pending !== null && <Working />}

                    {error !== null && (
                        <div
                            role="alert"
                            className="flex flex-col items-start gap-2 rounded-lg border border-abu-200 bg-kertas p-4 text-abu-600"
                        >
                            <CloudSlash size={32} aria-hidden="true" />
                            <p>{error.message}</p>
                            <button
                                type="button"
                                onClick={() =>
                                    void send(
                                        error.retry.question,
                                        error.retry.category,
                                    )
                                }
                                className="flex h-11 items-center gap-1.5 rounded-md border-[1.5px] border-mangsi bg-kertas px-4 font-medium text-mangsi focus-visible:outline-2 focus-visible:outline-nila"
                            >
                                <ArrowClockwise size={18} aria-hidden="true" />
                                {id.ask.retry}
                            </button>
                        </div>
                    )}
                    <div ref={endRef} />
                </div>

                <form
                    onSubmit={onSubmit}
                    className="sticky bottom-0 flex flex-col gap-2 border-t border-abu-200 bg-lontar pt-3 pb-4"
                >
                    <label htmlFor="pertanyaan" className="sr-only">
                        {id.ask.inputLabel}
                    </label>
                    <div className="flex items-end gap-2">
                        <textarea
                            id="pertanyaan"
                            ref={inputRef}
                            value={input}
                            maxLength={MAX_LENGTH}
                            rows={2}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={onKeyDown}
                            placeholder={id.ask.placeholder}
                            className="min-h-12 flex-1 resize-none rounded-md border border-abu-200 bg-kertas px-3 py-2 text-mangsi placeholder:text-abu-600 focus-visible:border-nila focus-visible:outline-2 focus-visible:outline-nila-100"
                        />
                        <button
                            type="submit"
                            disabled={
                                pending !== null || input.trim().length < 2
                            }
                            className="flex h-12 items-center gap-1.5 rounded-md bg-nila px-4 font-medium text-white hover:bg-nila-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nila disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <PaperPlaneRight
                                size={18}
                                weight="bold"
                                aria-hidden="true"
                            />
                            {id.ask.send}
                        </button>
                    </div>
                    <p className="text-[13px] leading-[18px] text-abu-600">
                        {id.ask.privacy}
                    </p>
                </form>
            </main>

            <footer className="pb-8 text-center text-[13px] leading-[18px] text-abu-600">
                {id.disclaimer}
            </footer>

            <DayDialog date={selected} onClose={() => setSelected(null)} />
        </div>
    );
}

function Working() {
    return (
        <div aria-busy="true" className="flex flex-col gap-3">
            <p className="text-abu-600">{id.ask.working}</p>
            {[0, 1].map((i) => (
                <div
                    key={i}
                    className="h-24 rounded-lg border border-abu-200 bg-[repeating-linear-gradient(90deg,var(--color-abu-200)_0_12px,transparent_12px_16px)] opacity-40"
                />
            ))}
        </div>
    );
}

function AnswerBubble({
    answer,
    disabled,
    onOpen,
    onChoose,
}: {
    answer: AgentAnswer;
    disabled: boolean;
    onOpen: (date: string) => void;
    onChoose: (code: string) => void;
}) {
    const t = id.ask;

    return (
        <div className="flex max-w-full flex-col gap-3 self-start rounded-lg border border-abu-200 bg-kertas p-4 sm:max-w-[90%]">
            <p className="text-[18px] leading-7">{answer.answer}</p>

            {answer.needs_clarification &&
                answer.clarification_options.length > 0 && (
                    <div className="flex flex-col gap-2">
                        <p className="text-sm font-medium">{t.clarifyLabel}</p>
                        <div className="flex flex-wrap gap-2">
                            {answer.clarification_options.map((o) => (
                                <button
                                    key={o.code}
                                    type="button"
                                    disabled={disabled}
                                    onClick={() => onChoose(o.code)}
                                    className="flex h-11 items-center rounded-sm border border-nila bg-kertas px-3 text-sm font-medium text-nila hover:bg-nila-100 focus-visible:outline-2 focus-visible:outline-nila disabled:opacity-60"
                                >
                                    {o.name}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

            {answer.dates.length > 0 && (
                <div className="flex flex-col gap-3">
                    {answer.dates.map((d) => (
                        <DateRefCard key={d.date} item={d} onOpen={onOpen} />
                    ))}
                </div>
            )}

            {answer.cultural_notice && <CulturalNotice />}

            {(answer.dates.length > 0 ||
                answer.generated_by === 'template') && (
                <div className="flex flex-col gap-1 text-[13px] leading-[18px] text-abu-600">
                    {answer.dates.length > 0 && (
                        <p className="flex items-center gap-1.5">
                            <BookOpen size={16} aria-hidden="true" />
                            {id.today.sourceLine(answer.source_count)}
                        </p>
                    )}
                    {answer.generated_by === 'template' && (
                        <p>{t.templateNotice}</p>
                    )}
                    {answer.dropped_dates.length > 0 && (
                        <p>{t.dropped(answer.dropped_dates.length)}</p>
                    )}
                </div>
            )}

            {answer.steps.length > 0 && (
                <details className="text-[13px] leading-[18px] text-abu-600">
                    <summary className="cursor-pointer">{t.stepsLabel}</summary>
                    <ol className="mt-1 list-decimal pl-5">
                        {answer.steps.map((s, i) => (
                            <li key={i}>{s}</li>
                        ))}
                    </ol>
                </details>
            )}
        </div>
    );
}
