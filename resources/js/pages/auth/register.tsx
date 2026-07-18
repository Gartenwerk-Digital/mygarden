import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

type Props = {
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
};

type RegisterForm = {
    name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
    age_confirmation: boolean;
    terms_accepted: boolean;
};

export default function Register({ passwordRules, teamInvitation }: Props) {
    const { data, setData, post, processing, errors } = useForm<RegisterForm>({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
        age_confirmation: false,
        terms_accepted: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store.url(), {
            onFinish: () => {
                setData('password', '');
                setData('password_confirmation', '');
            },
        });
    };

    return (
        <>
            <Head title="Registrieren" />
            <form onSubmit={submit} className="flex flex-col gap-6">
                {teamInvitation && (
                    <TeamInvitationAlert
                        invitation={teamInvitation}
                        action="Register"
                    />
                )}

                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Anzeigename</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="name"
                            placeholder="Dein Name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="username">Benutzername</Label>
                        <div className="relative">
                            <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">
                                @
                            </span>
                            <Input
                                id="username"
                                type="text"
                                required
                                tabIndex={2}
                                autoComplete="username"
                                placeholder="deinname"
                                className="pl-7 lowercase"
                                value={data.username}
                                onChange={(e) =>
                                    setData(
                                        'username',
                                        e.target.value.toLowerCase(),
                                    )
                                }
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            3–30 Zeichen, nur Kleinbuchstaben, Ziffern und
                            Unterstriche.
                        </p>
                        <InputError message={errors.username} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">E-Mail-Adresse</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            tabIndex={3}
                            autoComplete="email"
                            placeholder="deine@email.de"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Passwort</Label>
                        <PasswordInput
                            id="password"
                            required
                            tabIndex={4}
                            autoComplete="new-password"
                            placeholder="Passwort"
                            passwordrules={passwordRules}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Passwort bestätigen
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            required
                            tabIndex={5}
                            autoComplete="new-password"
                            placeholder="Passwort bestätigen"
                            passwordrules={passwordRules}
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>

                    <div className="space-y-3">
                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="age_confirmation"
                                tabIndex={6}
                                checked={data.age_confirmation}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'age_confirmation',
                                        Boolean(checked),
                                    )
                                }
                                className="mt-0.5"
                            />
                            <Label
                                htmlFor="age_confirmation"
                                className="cursor-pointer leading-snug font-normal"
                            >
                                Ich bin mindestens 16 Jahre alt.
                            </Label>
                        </div>
                        <InputError message={errors.age_confirmation} />

                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="terms_accepted"
                                tabIndex={7}
                                checked={data.terms_accepted}
                                onCheckedChange={(checked) =>
                                    setData('terms_accepted', Boolean(checked))
                                }
                                className="mt-0.5"
                            />
                            <Label
                                htmlFor="terms_accepted"
                                className="cursor-pointer leading-snug font-normal"
                            >
                                Ich habe die Nutzungsbedingungen und
                                Datenschutzhinweise zur Kenntnis genommen und
                                akzeptiert.
                            </Label>
                        </div>
                        <InputError message={errors.terms_accepted} />
                    </div>

                    <Button
                        type="submit"
                        className="mt-2 w-full"
                        tabIndex={8}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Konto erstellen
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Bereits ein Konto?{' '}
                    <TextLink
                        href={
                            teamInvitation
                                ? login.url({
                                      query: {
                                          invitation: teamInvitation.code,
                                      },
                                  })
                                : login()
                        }
                        data-test="team-invitation-login-link"
                        tabIndex={9}
                    >
                        Anmelden
                    </TextLink>
                </div>
            </form>
        </>
    );
}

Register.layout = {
    title: 'Konto erstellen',
    description: 'Gib deine Daten ein, um dein myGarden-Konto zu erstellen',
};
