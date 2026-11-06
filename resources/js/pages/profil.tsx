import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowClockwise,
    CheckCircle,
    Info,
    Pause,
    PencilSimple,
    PhoneCall,
    Play,
    SignOut,
    Trash,
    WhatsappLogo,
} from '@phosphor-icons/react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { RahinaHeader } from '@/components/rahina/rahina-header';
import { id } from '@/lang/id';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import { edit as accountSettings } from '@/routes/profile';
import {
    code as newCode,
    destroy,
    enable,
    update,
} from '@/routes/rahina/whatsapp';
import type { ProfilePageProps } from '@/types/rahina';

const button = {
    primary:
        'inline-flex h-11 items-center justify-center gap-1.5 rounded-md bg-nila px-4 font-medium text-white hover:bg-nila-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-nila disabled:opacity-60 cursor-pointer',
    secondary:
        'inline-flex h-11 items-center justify-center gap-1.5 rounded-md border-[1.5px] border-mangsi bg-kertas px-4 font-medium text-mangsi hover:bg-lontar focus-visible:outline-2 focus-visible:outline-nila disabled:opacity-60 cursor-pointer',
    ghost: 'inline-flex h-11 items-center gap-1.5 font-medium text-nila hover:underline focus-visible:outline-2 focus-visible:outline-nila cursor-pointer',
    danger: 'inline-flex h-11 items-center justify-center gap-1.5 rounded-md bg-ala px-4 font-medium text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ala disabled:opacity-60 cursor-pointer',
};

function Card({
    title,
    icon,
    children,
}: {
    title: string;
    icon?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="flex flex-col gap-4 rounded-lg border border-abu-200 bg-kertas p-5">
            <div className="flex items-center gap-3">
                {icon}
                <h2 className="font-display text-[22px] leading-7 font-semibold">
                    {title}
                </h2>
            </div>
            {children}
        </section>
    );
}

function longDate(date: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Makassar',
    }).format(new Date(`${date}T12:00:00+08:00`));
}

export default function Profil({
    subscription,
    link,
    deviceReady,
    otonan,
    sendTime,
    accountPhone,
    rawAccountPhone,
}: ProfilePageProps) {
    const { auth, flash } = usePage<{ flash?: { status?: string } }>().props;
    const status = (flash as { status?: string } | undefined)?.status;
    const t = id.profile;

    return (
        <div className="min-h-screen bg-lontar font-sans text-mangsi">
            <Head title={t.title} />

            <RahinaHeader current="profile" />

            <main className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h1 className="font-display text-[28px] leading-[34px] font-bold">
                        {t.title}
                    </h1>
                    <Link
                        href={accountSettings.url()}
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-nila hover:underline"
                    >
                        {t.accountSettings} →
                    </Link>
                </div>

                {status && (
                    <p
                        role="status"
                        className="flex items-center gap-2 rounded-md bg-ayu-100 p-3 text-ayu"
                    >
                        <CheckCircle
                            size={20}
                            weight="bold"
                            aria-hidden="true"
                        />
                        {status === 'whatsapp-deleted'
                            ? t.deleted
                            : status === 'whatsapp-connected'
                              ? t.connectedDirectly
                              : status === 'whatsapp-phone-updated'
                                ? t.phoneChanged
                                : t.saved}
                    </p>
                )}

                <Card
                    title={t.waTitle}
                    icon={
                        <img
                            src={
                                subscription?.status === 'active'
                                    ? '/assets/images/maskots/success.png'
                                    : '/assets/images/maskots/waiting.png'
                            }
                            alt="Maskot WhatsApp"
                            className="size-8 object-contain"
                        />
                    }
                >
                    <p className="text-abu-600">{t.waIntro(sendTime)}</p>
                    {subscription === null ? (
                        <EnableForm
                            sendTime={sendTime}
                            accountPhone={accountPhone}
                            rawAccountPhone={rawAccountPhone}
                            deviceReady={deviceReady}
                        />
                    ) : subscription.status === 'pending' ? (
                        <Pending
                            subscription={subscription}
                            link={link}
                            deviceReady={deviceReady}
                            accountPhone={accountPhone}
                            rawAccountPhone={rawAccountPhone}
                        />
                    ) : (
                        <Active
                            subscription={subscription}
                            otonan={otonan}
                            accountPhone={accountPhone}
                            rawAccountPhone={rawAccountPhone}
                        />
                    )}
                    <p className="flex gap-2 text-[13px] leading-[18px] text-abu-600">
                        <Info
                            size={16}
                            className="mt-0.5 shrink-0"
                            aria-hidden="true"
                        />
                        {t.privacy}
                    </p>
                </Card>

                <Card
                    title={t.account}
                    icon={
                        <img
                            src="/assets/images/logo_rahina.png"
                            alt="Logo Rahina"
                            className="size-8 object-contain"
                        />
                    }
                >
                    <p>
                        <b>{auth.user?.name}</b>
                        <br />
                        <span className="text-abu-600">
                            Nomor HP Masuk: {accountPhone ?? '-'}
                        </span>
                    </p>
                    <div className="flex flex-wrap gap-3">
                        <Link
                            href={accountSettings.url()}
                            className={button.secondary}
                        >
                            {t.accountSettings}
                        </Link>
                        <Link
                            href={logout.url()}
                            method="post"
                            as="button"
                            className={button.ghost}
                        >
                            <SignOut size={18} aria-hidden="true" />
                            {t.logout}
                        </Link>
                    </div>
                </Card>
            </main>

            <footer className="pb-8 text-center text-[13px] leading-[18px] text-abu-600">
                {id.disclaimer}
            </footer>
        </div>
    );
}

function Checkbox({
    name,
    checked,
    onChange,
    label,
    error,
}: {
    name: string;
    checked: boolean;
    onChange: (v: boolean) => void;
    label: string;
    error?: string;
}) {
    return (
        <div className="flex flex-col gap-1">
            <label className="flex cursor-pointer items-start gap-3">
                <input
                    type="checkbox"
                    name={name}
                    checked={checked}
                    onChange={(e) => onChange(e.target.checked)}
                    aria-invalid={error ? 'true' : undefined}
                    className="mt-1 size-5 shrink-0 accent-nila"
                />
                <span>{label}</span>
            </label>
            {error && <p className="text-sm text-ala">{error}</p>}
        </div>
    );
}

function BirthDateInput({
    value,
    onChange,
    error,
}: {
    value: string;
    onChange: (v: string) => void;
    error?: string;
}) {
    return (
        <div className="flex flex-col gap-1">
            <label htmlFor="birth_date" className="text-sm font-medium">
                {id.profile.birthDate}
            </label>
            <input
                id="birth_date"
                type="date"
                value={value}
                max={new Date().toISOString().slice(0, 10)}
                onChange={(e) => onChange(e.target.value)}
                className="h-11 w-full max-w-xs rounded-md border border-abu-200 bg-kertas px-3 focus-visible:border-nila focus-visible:outline-2 focus-visible:outline-nila-100"
            />
            {error && <p className="text-sm text-ala">{error}</p>}
        </div>
    );
}

function EnableForm({
    sendTime,
    accountPhone,
    rawAccountPhone,
    deviceReady,
}: {
    sendTime: string;
    accountPhone: string | null;
    rawAccountPhone?: string | null;
    deviceReady: boolean;
}) {
    const t = id.profile;
    const [useCustomPhone, setUseCustomPhone] = useState(false);
    const form = useForm({
        consent_daily: false,
        phone: rawAccountPhone ?? '',
        birth_date: '',
        consent_birthdate: false,
        connect_now: true,
    });

    const submit = (connectDirectly: boolean) => (e: FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            consent_daily: d.consent_daily ? '1' : '',
            phone: useCustomPhone ? d.phone : (rawAccountPhone ?? d.phone),
            birth_date: d.birth_date || null,
            consent_birthdate: d.consent_birthdate ? '1' : '',
            connect_now: connectDirectly ? '1' : '',
        }));
        form.post(enable.url(), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit(true)} className="flex flex-col gap-4">
            <div className="flex flex-col gap-2 rounded-md border border-abu-200 bg-lontar p-3">
                <span className="text-sm font-medium">Nomor WhatsApp Tujuan</span>
                {!useCustomPhone ? (
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <span className="font-mono text-base font-semibold">
                            {accountPhone ?? 'Nomor akun belum ada'}
                        </span>
                        <button
                            type="button"
                            onClick={() => setUseCustomPhone(true)}
                            className="text-xs text-nila underline"
                        >
                            Pakai nomor WhatsApp lain
                        </button>
                    </div>
                ) : (
                    <div className="flex flex-col gap-2">
                        <input
                            type="tel"
                            value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)}
                            placeholder={t.newPhonePlaceholder}
                            className="h-10 w-full rounded-md border border-abu-200 bg-kertas px-3 text-sm focus-visible:border-nila focus-visible:outline-2 focus-visible:outline-nila-100"
                        />
                        {form.errors.phone && (
                            <p className="text-xs text-ala">{form.errors.phone}</p>
                        )}
                        <button
                            type="button"
                            onClick={() => {
                                form.setData('phone', rawAccountPhone ?? '');
                                setUseCustomPhone(false);
                            }}
                            className="self-start text-xs text-nila underline"
                        >
                            {t.useAccountPhone(accountPhone)}
                        </button>
                    </div>
                )}
            </div>

            <Checkbox
                name="consent_daily"
                checked={form.data.consent_daily}
                onChange={(v) => form.setData('consent_daily', v)}
                label={t.consentDaily(sendTime)}
                error={form.errors.consent_daily}
            />
            <BirthDateInput
                value={form.data.birth_date}
                onChange={(v) => form.setData('birth_date', v)}
                error={form.errors.birth_date}
            />
            {form.data.birth_date !== '' && (
                <Checkbox
                    name="consent_birthdate"
                    checked={form.data.consent_birthdate}
                    onChange={(v) => form.setData('consent_birthdate', v)}
                    label={t.consentBirthdate}
                    error={form.errors.consent_birthdate}
                />
            )}
            <div className="flex flex-wrap gap-3 pt-2">
                <button
                    type="submit"
                    disabled={form.processing}
                    className={button.primary}
                >
                    <WhatsappLogo size={20} aria-hidden="true" />
                    {t.enable}
                </button>
                {deviceReady && (
                    <button
                        type="button"
                        onClick={(e) => submit(false)(e)}
                        disabled={form.processing}
                        className={button.secondary}
                    >
                        {t.connectViaWa}
                    </button>
                )}
            </div>
        </form>
    );
}

function Pending({
    subscription,
    link,
    deviceReady,
    accountPhone,
    rawAccountPhone,
}: {
    subscription: NonNullable<ProfilePageProps['subscription']>;
    link: ProfilePageProps['link'];
    deviceReady: boolean;
    accountPhone: string | null;
    rawAccountPhone?: string | null;
}) {
    const t = id.profile;
    const [isEditingPhone, setIsEditingPhone] = useState(false);
    const [phoneInput, setPhoneInput] = useState(
        subscription.raw_phone ?? rawAccountPhone ?? '',
    );
    const [errors, setErrors] = useState<Record<string, string>>({});

    const currentDisplay =
        subscription.display_phone ??
        (subscription.phone ? subscription.phone : accountPhone);

    const handleConnectNow = (phoneToUse?: string) => {
        router.patch(
            update.url(),
            {
                action: 'connect_now',
                phone: phoneToUse ?? phoneInput,
            },
            {
                preserveScroll: true,
                onError: (e) => setErrors(e),
                onSuccess: () => {
                    setErrors({});
                    setIsEditingPhone(false);
                },
            },
        );
    };

    return (
        <div className="flex flex-col gap-4 rounded-md bg-nila-100 p-4 text-mangsi">
            <h3 className="font-bold text-lg">{t.pendingTitle}</h3>

            <div className="flex flex-col gap-1 rounded bg-kertas/80 p-3">
                <span className="text-xs text-abu-600">Nomor WhatsApp yang didaftarkan:</span>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <span className="font-mono text-base font-semibold">
                        {currentDisplay ?? '-'}
                    </span>
                    {!isEditingPhone && (
                        <button
                            type="button"
                            onClick={() => setIsEditingPhone(true)}
                            className="inline-flex items-center gap-1 text-xs text-nila underline"
                        >
                            <PencilSimple size={14} aria-hidden="true" />
                            {t.changePhone}
                        </button>
                    )}
                </div>
            </div>

            {isEditingPhone && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleConnectNow(phoneInput);
                    }}
                    className="flex flex-col gap-2 rounded bg-kertas p-3 shadow-sm"
                >
                    <label htmlFor="pending_phone" className="text-xs font-semibold">
                        {t.newPhoneLabel}
                    </label>
                    <input
                        id="pending_phone"
                        type="tel"
                        value={phoneInput}
                        onChange={(e) => setPhoneInput(e.target.value)}
                        placeholder={t.newPhonePlaceholder}
                        className="h-10 w-full rounded border border-abu-200 px-3 text-sm focus-visible:border-nila focus-visible:outline-none"
                    />
                    {errors.phone && (
                        <p className="text-xs text-ala">{errors.phone}</p>
                    )}
                    <div className="flex flex-wrap gap-2 pt-1">
                        <button type="submit" className={button.primary}>
                            Simpan & Hubungkan
                        </button>
                        {accountPhone && (
                            <button
                                type="button"
                                onClick={() => setPhoneInput(rawAccountPhone ?? '')}
                                className={button.secondary}
                            >
                                {t.useAccountPhone(accountPhone)}
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => setIsEditingPhone(false)}
                            className={button.ghost}
                        >
                            {t.cancel}
                        </button>
                    </div>
                </form>
            )}

            <div className="flex flex-col gap-3 rounded-md border border-nila/20 bg-kertas p-3">
                <span className="text-xs font-semibold text-nila">Pilihan 1: Hubungkan Langsung (Disarankan)</span>
                <p className="text-sm text-abu-600">
                    {t.connectDirectlyHint}
                </p>
                <button
                    type="button"
                    onClick={() => handleConnectNow()}
                    className={cn(button.primary, 'self-start')}
                >
                    <PhoneCall size={18} aria-hidden="true" />
                    {t.connectDirectlyButton}
                </button>
            </div>

            {deviceReady && link ? (
                <div className="flex flex-col gap-3 rounded-md border border-abu-200 bg-kertas p-3">
                    <span className="text-xs font-semibold text-abu-600">Pilihan 2: Hubungkan Lewat Pesan WhatsApp</span>
                    <p className="text-sm text-abu-600">
                        {t.pendingText(currentDisplay)}
                    </p>
                    <div className="flex flex-wrap gap-3">
                        <a
                            href={link.direct_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={button.secondary}
                        >
                            <WhatsappLogo size={20} aria-hidden="true" />
                            {t.connect}
                        </a>
                        <button
                            type="button"
                            onClick={() => router.reload()}
                            className={button.ghost}
                        >
                            <ArrowClockwise size={18} aria-hidden="true" />
                            {t.refresh}
                        </button>
                    </div>
                    <p className="text-[13px] leading-[18px] text-abu-600">
                        {t.otherNumber(link.code, link.minutes)}{' '}
                        <a
                            href={link.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-nila underline"
                        >
                            {t.otherNumberButton}
                        </a>
                    </p>
                </div>
            ) : link === null && (
                <button
                    type="button"
                    onClick={() =>
                        router.post(newCode.url(), {}, { preserveScroll: true })
                    }
                    className={cn(button.secondary, 'self-start')}
                >
                    {t.newCode}
                </button>
            )}

            <DeleteData />
        </div>
    );
}

function Active({
    subscription,
    otonan,
    accountPhone,
    rawAccountPhone,
}: {
    subscription: NonNullable<ProfilePageProps['subscription']>;
    otonan: ProfilePageProps['otonan'];
    accountPhone: string | null;
    rawAccountPhone?: string | null;
}) {
    const t = id.profile;
    const active = subscription.status === 'active';
    const [birth, setBirth] = useState(subscription.birth_date ?? '');
    const [consent, setConsent] = useState(subscription.has_birth_date);
    const [isEditingPhone, setIsEditingPhone] = useState(false);
    const [newPhone, setNewPhone] = useState(
        subscription.raw_phone ?? rawAccountPhone ?? '',
    );
    const [errors, setErrors] = useState<Record<string, string>>({});

    const patch = (data: Record<string, string | null>) =>
        router.patch(update.url(), data, {
            preserveScroll: true,
            onError: (e) => setErrors(e),
            onSuccess: () => {
                setErrors({});
                setIsEditingPhone(false);
            },
        });

    return (
        <div className="flex flex-col gap-4">
            <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2">
                <dt className="text-abu-600">{t.status}</dt>
                <dd
                    className={cn(
                        'font-medium',
                        active ? 'text-ayu' : 'text-campur',
                    )}
                >
                    {t.statuses[subscription.status]}
                </dd>

                <dt className="text-abu-600">Nomor WhatsApp</dt>
                <dd className="flex flex-wrap items-center gap-3">
                    <span className="font-mono font-semibold">
                        {subscription.display_phone ?? subscription.phone ?? '-'}
                    </span>
                    {!isEditingPhone && (
                        <button
                            type="button"
                            onClick={() => setIsEditingPhone(true)}
                            className="inline-flex items-center gap-1 text-xs text-nila underline"
                        >
                            <PencilSimple size={14} aria-hidden="true" />
                            {t.changePhone}
                        </button>
                    )}
                </dd>

                <dt className="text-abu-600">{t.lastSent}</dt>
                <dd>
                    {subscription.last_sent_on
                        ? longDate(subscription.last_sent_on)
                        : t.never}
                </dd>
            </dl>

            {isEditingPhone && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        patch({
                            action: 'change_phone',
                            phone: newPhone,
                        });
                    }}
                    className="flex flex-col gap-2 rounded-md border border-abu-200 bg-lontar p-4"
                >
                    <label htmlFor="new_wa_phone" className="text-sm font-semibold">
                        {t.changePhoneTitle}
                    </label>
                    <input
                        id="new_wa_phone"
                        type="tel"
                        value={newPhone}
                        onChange={(e) => setNewPhone(e.target.value)}
                        placeholder={t.newPhonePlaceholder}
                        className="h-10 w-full max-w-sm rounded border border-abu-200 bg-kertas px-3 text-sm focus-visible:border-nila focus-visible:outline-none"
                    />
                    {errors.phone && (
                        <p className="text-xs text-ala">{errors.phone}</p>
                    )}
                    <div className="flex flex-wrap gap-2 pt-1">
                        <button type="submit" className={button.primary}>
                            {t.savePhone}
                        </button>
                        {accountPhone && (
                            <button
                                type="button"
                                onClick={() => setNewPhone(rawAccountPhone ?? '')}
                                className={button.secondary}
                            >
                                {t.useAccountPhone(accountPhone)}
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => setIsEditingPhone(false)}
                            className={button.ghost}
                        >
                            {t.cancel}
                        </button>
                    </div>
                </form>
            )}

            {otonan && (
                <p className="rounded-md bg-kunyit-100 p-3 text-kunyit-800">
                    {t.otonan(otonan.days_until, otonan.label)} ·{' '}
                    {longDate(otonan.date)}
                </p>
            )}

            <div className="flex flex-wrap gap-3">
                {active ? (
                    <button
                        type="button"
                        onClick={() => patch({ action: 'pause' })}
                        className={button.secondary}
                    >
                        <Pause size={18} aria-hidden="true" />
                        {t.pause}
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={() => patch({ action: 'resume' })}
                        className={button.primary}
                    >
                        <Play size={18} aria-hidden="true" />
                        {t.resume}
                    </button>
                )}
            </div>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    patch({
                        action: 'birth_date',
                        birth_date: birth || null,
                        consent_birthdate: consent ? '1' : '',
                    });
                }}
                className="flex flex-col gap-3 border-t border-abu-200 pt-4"
            >
                <BirthDateInput
                    value={birth}
                    onChange={setBirth}
                    error={errors.birth_date}
                />
                {birth !== '' && (
                    <Checkbox
                        name="consent_birthdate"
                        checked={consent}
                        onChange={setConsent}
                        label={t.consentBirthdate}
                        error={errors.consent_birthdate}
                    />
                )}
                <div className="flex flex-wrap gap-3">
                    <button type="submit" className={button.secondary}>
                        {t.saveBirthDate}
                    </button>
                    {subscription.has_birth_date && (
                        <button
                            type="button"
                            onClick={() => {
                                setBirth('');
                                patch({
                                    action: 'birth_date',
                                    birth_date: null,
                                });
                            }}
                            className={button.ghost}
                        >
                            {t.removeBirthDate}
                        </button>
                    )}
                </div>
            </form>

            <DeleteData />
        </div>
    );
}

/** Konfirmasi hapus di halaman (tanpa dialog browser). */
function DeleteData() {
    const t = id.profile;
    const [confirming, setConfirming] = useState(false);

    if (!confirming) {
        return (
            <button
                type="button"
                onClick={() => setConfirming(true)}
                className={cn(button.ghost, 'self-start text-ala')}
            >
                <Trash size={18} aria-hidden="true" />
                {t.deleteData}
            </button>
        );
    }

    return (
        <div
            role="alert"
            className="flex flex-col gap-3 rounded-md bg-ala-100 p-3 text-ala"
        >
            <p>{t.deleteConfirm}</p>
            <div className="flex flex-wrap gap-3">
                <button
                    type="button"
                    onClick={() =>
                        router.delete(destroy.url(), { preserveScroll: true })
                    }
                    className={button.danger}
                >
                    {t.deleteYes}
                </button>
                <button
                    type="button"
                    onClick={() => setConfirming(false)}
                    className={button.secondary}
                >
                    {t.cancel}
                </button>
            </div>
        </div>
    );
}
