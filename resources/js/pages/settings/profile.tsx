import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { id } from '@/lang/id';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

/** Pengaturan akun: nama & nomor HP (identitas masuk). */
export default function Profile() {
    const { auth } = usePage<PageProps>().props;
    const t = id.auth;

    return (
        <div className="flex flex-col gap-6">
            <Head title="Pengaturan Akun" />

            <section className="flex flex-col gap-4 rounded-lg border border-abu-200 bg-kertas p-5">
                <div>
                    <h2 className="font-display text-[22px] leading-7 font-semibold">
                        Informasi Akun
                    </h2>
                    <p className="mt-1 text-sm text-abu-600">
                        Ubah nama tampilan dan nomor HP yang digunakan untuk masuk ke akun Rahina.
                    </p>
                </div>

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="name">{t.name}</Label>

                                <Input
                                    id="name"
                                    className="h-11 border-abu-200 bg-kertas focus-visible:border-nila"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder={t.namePlaceholder}
                                />

                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="phone">{t.phone}</Label>

                                <Input
                                    id="phone"
                                    type="tel"
                                    inputMode="tel"
                                    className="h-11 border-abu-200 bg-kertas focus-visible:border-nila"
                                    defaultValue={
                                        typeof auth.user.phone === 'string'
                                            ? `0${auth.user.phone.slice(2)}`
                                            : ''
                                    }
                                    name="phone"
                                    required
                                    autoComplete="tel"
                                    placeholder={t.phonePlaceholder}
                                />

                                <p className="text-xs text-abu-600">
                                    Nomor ini digunakan untuk masuk ke akun Rahina. Jika Anda mengganti nomor akun, langganan WhatsApp lama akan diputus agar dapat dihubungkan kembali dengan nomor baru.
                                </p>

                                <InputError message={errors.phone} />
                            </div>

                            <div className="pt-2">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                    className="h-11 bg-nila px-5 font-medium text-white hover:bg-nila-700"
                                >
                                    Simpan Perubahan
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </section>

            <DeleteUser />
        </div>
    );
}
