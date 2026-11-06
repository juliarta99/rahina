import { Link } from '@inertiajs/react';
import {
    BookOpen,
    CalendarBlank,
    ChatCircleText,
    FileText,
    Info,
    ShieldCheck,
    WhatsappLogo,
} from '@phosphor-icons/react';
import { BrandLogo } from '@/components/rahina/brand-logo';

export function SiteFooter() {
    return (
        <footer className="border-t border-abu-200 bg-kertas py-12 text-sm text-abu-600">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <div className="grid grid-cols-1 gap-8 md:grid-cols-12 md:gap-10">
                    {/* Kolom 1: Profil & Identitas Brand */}
                    <div className="md:col-span-5">
                        <BrandLogo />
                        <p className="mt-4 max-w-sm text-xs leading-relaxed text-abu-600 sm:text-sm">
                            Platform Kalender Bali dan Wariga digital berlandaskan sastra tradisional
                            Bali dengan presisi komputasi modern. Menghadirkan perhitungan wewaran,
                            ala ayuning dewasa, dan asisten Tanya Wariga untuk kemudahan umat.
                        </p>
                        <div className="mt-4 flex items-center gap-2 text-xs font-medium text-nila">
                            <BookOpen size={16} weight="bold" />
                            <span>Silsilah Lontar Sundarigama, Medangkemulan & Tri Pramana</span>
                        </div>
                    </div>

                    {/* Kolom 2: Layanan & Fitur */}
                    <div className="md:col-span-3 sm:col-span-6">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-mangsi">
                            Layanan Utama
                        </h4>
                        <ul className="mt-4 space-y-2.5 text-xs sm:text-sm">
                            <li>
                                <Link
                                    href="/kalender"
                                    className="inline-flex items-center gap-2 text-abu-600 transition-colors hover:text-nila"
                                >
                                    <CalendarBlank size={16} weight="regular" />
                                    <span>Kalender Bali 16 Tahun</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/tanya"
                                    className="inline-flex items-center gap-2 text-abu-600 transition-colors hover:text-nila"
                                >
                                    <ChatCircleText size={16} weight="regular" />
                                    <span>Tanya Wariga AI</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/profil"
                                    className="inline-flex items-center gap-2 text-abu-600 transition-colors hover:text-nila"
                                >
                                    <WhatsappLogo size={16} weight="regular" />
                                    <span>Ringkasan Harian WhatsApp</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    {/* Kolom 3: Pustaka & Hukum */}
                    <div className="md:col-span-4 sm:col-span-6">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-mangsi">
                            Informasi & Pustaka
                        </h4>
                        <ul className="mt-4 space-y-2.5 text-xs sm:text-sm">
                            <li>
                                <Link
                                    href="/tentang"
                                    className="inline-flex items-center gap-2 text-abu-600 transition-colors hover:text-nila"
                                >
                                    <Info size={16} weight="regular" />
                                    <span>Tentang Kami & Silsilah Pustaka</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/kebijakan-privasi"
                                    className="inline-flex items-center gap-2 text-abu-600 transition-colors hover:text-nila"
                                >
                                    <ShieldCheck size={16} weight="regular" />
                                    <span>Kebijakan Privasi</span>
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href="/syarat-ketentuan"
                                    className="inline-flex items-center gap-2 text-abu-600 transition-colors hover:text-nila"
                                >
                                    <FileText size={16} weight="regular" />
                                    <span>Syarat & Ketentuan Layanan</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                {/* Garis Pemisah & Hak Cipta */}
                <div className="mt-10 border-t border-abu-200/80 pt-6 text-center text-xs text-abu-600 sm:flex sm:items-center sm:justify-between sm:text-left">
                    <p>© {new Date().getFullYear()} Rahina. Mugi Rahayu Sareng Sami.</p>
                    <p className="mt-2 text-abu-500 sm:mt-0">
                        Disusun dengan penghormatan mendalam terhadap tradisi wariga para leluhur Bali.
                    </p>
                </div>
            </div>
        </footer>
    );
}
