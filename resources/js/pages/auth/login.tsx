import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { id } from '@/lang/id';
import { register } from '@/routes';
import { store } from '@/routes/login';

type Props = {
    status?: string;
};

/** Masuk dengan nomor HP + kata sandi (tanpa email & passkey). */
export default function Login({ status }: Props) {
    const t = id.auth;

    return (
        <>
            <Head>
                <title>{`${t.loginTitle} - Rahina`}</title>
                <meta
                    name="description"
                    content="Masuk ke akun Rahina untuk mengelola notifikasi WhatsApp ringkasan harian kalender Bali dan preferensi waktu pengiriman."
                />
            </Head>

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">{t.phone}</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    inputMode="tel"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="tel"
                                    placeholder={t.phonePlaceholder}
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">{t.password}</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder={t.password}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">{t.remember}</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 h-11 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {t.loginButton}
                            </Button>
                        </div>

                        <p className="text-center text-[13px] leading-[18px] text-abu-600">
                            {t.forgotHint}
                        </p>

                        <div className="text-center text-sm text-abu-600">
                            {t.noAccount}{' '}
                            <TextLink href={register()} tabIndex={5}>
                                {t.registerLink}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-ayu">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: id.auth.loginHeading,
    description: id.auth.loginDescription,
};
