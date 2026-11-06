export type Verdict = 'ayu' | 'ala' | 'campur' | 'info';

export interface WarigaValue {
    id: number;
    name: string;
    urip: number | null;
}

export interface RahinanRef {
    id: number;
    slug: string;
    name: string;
    kind: string;
}

export interface CalendarDay {
    date: string;
    saka_year: number;
    wuku: WarigaValue;
    wewaran: Record<string, WarigaValue | null>;
    urip_total: number;
    sasih: WarigaValue & { is_nampih: boolean };
    sasih_day: number;
    sasih_day_end: number | null;
    is_penanggal: boolean;
    is_purnama: boolean;
    is_tilem: boolean;
    is_nguna_ratri: boolean;
    ingkel: string | null;
    rahinan: RahinanRef[];
    paringkelan: Record<string, string | null>;
    engine: string;
}

export interface BriefPoint {
    verdict: Verdict;
    text: string;
    rule_ids: number[];
}

export interface DailyBrief {
    date: string;
    headline: string;
    points: BriefPoint[];
    rahinan_note: string | null;
    generated_by: 'ai' | 'template';
    model: string;
    prompt_version: string;
}

export interface DewasaEffect {
    category: string;
    category_name: string;
    domain: string;
    effect: 'baik' | 'tidak_baik';
}

export interface DewasaItem {
    rule_id: number;
    code: string;
    name: string;
    summary: string;
    verdict: 'ayu' | 'ala' | 'campur';
    alahing_level: number | null;
    source: { title: string; page: string | null };
    is_validated: boolean;
    effects: DewasaEffect[];
    matched_on: { field: string; op: string; actual: unknown }[];
}

export interface CalendarCell {
    date: string;
    in_month: boolean;
    available: boolean;
    saptawara?: string;
    pancawara?: string;
    wuku?: string;
    sasih?: string;
    sasih_day?: number;
    sasih_day_end?: number | null;
    is_penanggal?: boolean;
    is_purnama?: boolean;
    is_tilem?: boolean;
    is_nguna_ratri?: boolean;
    rahinan?: string[];
    dewasa?: { baik: number; tidak_baik: number };
}

export interface UpcomingRahinan {
    date: string;
    name: string;
    slug: string;
    kind: string;
    days_until: number;
}

export interface CalendarPageProps {
    month: string;
    today: string;
    minMonth: string;
    maxMonth: string;
    weeks: CalendarCell[][];
    sasihInMonth: string[];
    todayDay: CalendarDay | null;
    upcoming: UpcomingRahinan[];
}

export interface DayDetail {
    date: string;
    day: CalendarDay;
    brief: DailyBrief | null;
    dewasa: DewasaItem[];
    sourceCount: number;
    culturalNotice: boolean;
}

export interface RuleRef {
    id: number;
    name: string;
}

/** Kartu tanggal di jawaban Tanya Wariga; semua isinya berasal dari hasil tool (bukan teks model). */
export interface AnswerDate {
    date: string;
    saptawara: string;
    pancawara: string;
    wuku: string;
    sasih: string;
    rahinan: string[];
    verdict: Verdict;
    reason: string;
    support: RuleRef[];
    block: RuleRef[];
}

export interface AgentAnswer {
    answer: string;
    dates: AnswerDate[];
    needs_clarification: boolean;
    clarification_options: { code: string; name: string }[];
    cultural_notice: boolean;
    out_of_scope: boolean;
    generated_by: 'ai' | 'template';
    dropped_dates: string[];
    steps: string[];
    source_count: number;
}

export interface AskPageProps {
    today: string;
}

export type SubscriptionStatus =
    | 'pending'
    | 'active'
    | 'paused'
    | 'opted_out'
    | 'failed';

export interface ProfilePageProps {
    subscription: {
        status: SubscriptionStatus;
        phone: string | null;
        display_phone?: string | null;
        raw_phone?: string | null;
        verified_at: string | null;
        has_birth_date: boolean;
        birth_date: string | null;
        last_sent_on: string | null;
    } | null;
    link: {
        code: string;
        url: string;
        direct_url: string;
        minutes: number;
    } | null;
    accountPhone: string | null;
    rawAccountPhone?: string | null;
    deviceReady: boolean;
    otonan: { date: string; days_until: number; label: string | null } | null;
    sendTime: string;
}
