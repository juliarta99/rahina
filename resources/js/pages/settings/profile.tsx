import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { id } from '@/lang/id';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

/** Pengaturan akun: nama & nomor HP (identitas masuk). Mengganti nomor memutus langganan WhatsApp lama. */
export default function Profile() {
    const { auth } = usePage<PageProps>().props;
    const t = id.auth;

    return (
        <>
            <Head title="Pengaturan akun" />

            <h1 className="sr-only">Pengaturan akun</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Akun"
                    description="Ubah nama dan nomor HP. Mengganti nomor HP akan memutus ringkasan pagi WhatsApp; aktifkan lagi di halaman Profil."
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">{t.name}</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder={t.namePlaceholder}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">{t.phone}</Label>

                                <Input
                                    id="phone"
                                    type="tel"
                                    inputMode="tel"
                                    className="mt-1 block w-full"
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

                                <InputError
                                    className="mt-2"
                                    message={errors.phone}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Simpan
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan akun',
            href: edit(),
        },
    ],
};
