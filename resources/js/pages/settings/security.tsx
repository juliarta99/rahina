import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Props = {
    passwordRules: string;
} & ManageTwoFactorProps;

export default function Security(props: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <div className="flex flex-col gap-6">
            <Head title="Keamanan & Kata Sandi" />

            <section className="flex flex-col gap-4 rounded-lg border border-abu-200 bg-kertas p-5">
                <div>
                    <h2 className="font-display text-[22px] leading-7 font-semibold">
                        Ubah Kata Sandi
                    </h2>
                    <p className="mt-1 text-sm text-abu-600">
                        Pastikan akun Anda menggunakan kata sandi yang kuat dan aman untuk menjaga keamanan data.
                    </p>
                </div>

                <Form
                    {...SecurityController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnError={[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]}
                    resetOnSuccess
                    onError={(errors) => {
                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="current_password">
                                    Kata Sandi Saat Ini
                                </Label>

                                <PasswordInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    className="h-11 border-abu-200 bg-kertas focus-visible:border-nila"
                                    autoComplete="current-password"
                                    placeholder="Masukkan kata sandi saat ini"
                                />

                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="password">Kata Sandi Baru</Label>

                                <PasswordInput
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    className="h-11 border-abu-200 bg-kertas focus-visible:border-nila"
                                    autoComplete="new-password"
                                    placeholder="Masukkan kata sandi baru"
                                    passwordrules={props.passwordRules}
                                />

                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="password_confirmation">
                                    Konfirmasi Kata Sandi Baru
                                </Label>

                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    className="h-11 border-abu-200 bg-kertas focus-visible:border-nila"
                                    autoComplete="new-password"
                                    placeholder="Ulangi kata sandi baru"
                                    passwordrules={props.passwordRules}
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="pt-2">
                                <Button
                                    disabled={processing}
                                    data-test="update-password-button"
                                    className="h-11 bg-nila px-5 font-medium text-white hover:bg-nila-700"
                                >
                                    Perbarui Kata Sandi
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </section>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />
        </div>
    );
}
