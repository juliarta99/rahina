import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <section className="flex flex-col gap-4 rounded-lg border border-ala-100 bg-kertas p-5">
            <div>
                <h2 className="font-display text-[22px] leading-7 font-semibold text-ala">
                    Hapus Akun
                </h2>
                <p className="mt-1 text-sm text-abu-600">
                    Hapus akun Anda secara permanen. Semua data akun, preferensi, dan langganan WhatsApp akan dihapus.
                </p>
            </div>

            <div className="rounded-md bg-ala-100/50 p-4 border border-ala/20">
                <p className="text-sm text-ala font-medium">
                    Perhatian: Tindakan ini tidak dapat dibatalkan.
                </p>
                <p className="mt-1 text-xs text-abu-600">
                    Jika Anda menghapus akun, semua data dan riwayat tidak akan bisa dipulihkan kembali.
                </p>

                <div className="mt-4">
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="destructive"
                                data-test="delete-user-button"
                                className="bg-ala hover:bg-red-700 text-white"
                            >
                                Hapus Akun Saya
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="bg-kertas border-abu-200">
                            <DialogTitle className="font-display text-lg font-bold">
                                Apakah Anda yakin ingin menghapus akun?
                            </DialogTitle>
                            <DialogDescription className="text-sm text-abu-600">
                                Setelah akun dihapus, seluruh sumber daya dan data akan dihapus secara permanen. Masukkan kata sandi akun Anda untuk mengonfirmasi penghapusan.
                            </DialogDescription>

                            <Form
                                {...ProfileController.destroy.form()}
                                options={{
                                    preserveScroll: true,
                                }}
                                onError={() => passwordInput.current?.focus()}
                                resetOnSuccess
                                className="space-y-4 pt-2"
                            >
                                {({ resetAndClearErrors, processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="password">
                                                Kata Sandi
                                            </Label>

                                            <PasswordInput
                                                id="password"
                                                name="password"
                                                ref={passwordInput}
                                                placeholder="Masukkan kata sandi akun"
                                                autoComplete="current-password"
                                                className="border-abu-200"
                                            />

                                            <InputError message={errors.password} />
                                        </div>

                                        <DialogFooter className="gap-2 sm:gap-0">
                                            <DialogClose asChild>
                                                <Button
                                                    variant="secondary"
                                                    onClick={() => resetAndClearErrors()}
                                                    className="border-abu-200"
                                                >
                                                    Batal
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                variant="destructive"
                                                disabled={processing}
                                                className="bg-ala hover:bg-red-700 text-white"
                                                asChild
                                            >
                                                <button
                                                    type="submit"
                                                    data-test="confirm-delete-user-button"
                                                >
                                                    Ya, Hapus Akun
                                                </button>
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </section>
    );
}
