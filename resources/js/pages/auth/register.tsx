import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { id } from '@/lang/id';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

/** Daftar dengan nama + nomor HP + kata sandi. Nomor yang sama dipakai untuk Rahina Pagi via WhatsApp. */
export default function Register({ passwordRules }: Props) {
    const t = id.auth;

    return (
        <>
            <Head>
                <title>{`${t.registerTitle} - Rahina`}</title>
                <meta
                    name="description"
                    content="Daftar akun Rahina gratis. Cukup masukkan nama dan nomor WhatsApp aktif untuk menerima kabar rahinan otomatis setiap pagi."
                />
            </Head>
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">{t.name}</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder={t.namePlaceholder}
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">{t.phone}</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    inputMode="tel"
                                    required
                                    tabIndex={2}
                                    autoComplete="tel"
                                    name="phone"
                                    placeholder={t.phonePlaceholder}
                                    aria-describedby="phone-hint"
                                />
                                <p
                                    id="phone-hint"
                                    className="text-[13px] leading-[18px] text-abu-600"
                                >
                                    {t.phoneHint}
                                </p>
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">{t.password}</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder={t.password}
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    {t.passwordConfirm}
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder={t.passwordConfirm}
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-11 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                {t.registerButton}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-abu-600">
                            {t.haveAccount}{' '}
                            <TextLink href={login()} tabIndex={6}>
                                {t.loginLink}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: id.auth.registerHeading,
    description: id.auth.registerDescription,
};
