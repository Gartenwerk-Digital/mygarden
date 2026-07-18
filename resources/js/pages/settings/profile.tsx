import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Globe, Lock, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';
import type { ChangeEvent, FormEventHandler } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { FormSection } from '@/components/mygarden/form-section';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/hooks/use-initials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
    mustVerifyEmail: boolean;
    status?: string;
    nextUsernameChangeAllowedAt?: string | null;
    countries: Record<string, string>;
};

type ProfileForm = {
    name: string;
    username: string;
    email: string;
    bio: string;
    avatar: File | null;
    remove_avatar: boolean;
    country: string;
    region: string;
    profile_visibility: 'private' | 'public';
};

export default function Profile() {
    const {
        auth,
        mustVerifyEmail,
        status,
        nextUsernameChangeAllowedAt,
        countries,
    } = usePage<PageProps>().props;

    const user = auth.user;
    const getInitials = useInitials();

    const avatarUrl = user.avatar_path ? `/storage/${user.avatar_path}` : null;

    const [avatarPreview, setAvatarPreview] = useState<string | null>(
        avatarUrl,
    );

    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, patch, processing, errors, transform } =
        useForm<ProfileForm>({
            name: user.name,
            username: user.username ?? '',
            email: user.email,
            bio: user.bio ?? '',
            avatar: null,
            remove_avatar: false,
            country: user.country ?? '',
            region: user.region ?? '',
            profile_visibility: user.profile_visibility ?? 'private',
        });

    transform((d) => ({
        ...d,
        remove_avatar: d.remove_avatar ? 1 : 0,
    }));

    const handleAvatarChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;

        if (!file) {
            return;
        }

        setData((prev) => ({ ...prev, avatar: file, remove_avatar: false }));
        setAvatarPreview(URL.createObjectURL(file));
    };

    const handleRemoveAvatar = () => {
        setData((prev) => ({ ...prev, avatar: null, remove_avatar: true }));
        setAvatarPreview(null);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(ProfileController.update.url(), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <>
            <Head title="Profileinstellungen" />

            <h1 className="sr-only">Profileinstellungen</h1>

            <form onSubmit={submit}>
                <FormSection
                    title="Dein Profil"
                    description="Anzeigename, Benutzername und Kurzbeschreibung"
                >
                    <div className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Anzeigename</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                autoComplete="name"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="username">Benutzername</Label>
                            <div className="relative">
                                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">
                                    @
                                </span>
                                <Input
                                    id="username"
                                    className="pl-7 lowercase"
                                    value={data.username}
                                    onChange={(e) =>
                                        setData(
                                            'username',
                                            e.target.value.toLowerCase(),
                                        )
                                    }
                                    autoComplete="username"
                                    required
                                />
                            </div>
                            {nextUsernameChangeAllowedAt && (
                                <p className="text-xs text-muted-foreground">
                                    Nächste Änderung möglich ab{' '}
                                    {new Date(
                                        nextUsernameChangeAllowedAt,
                                    ).toLocaleDateString('de-DE')}
                                    .
                                </p>
                            )}
                            <InputError message={errors.username} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="bio">Kurzbeschreibung</Label>
                            <textarea
                                id="bio"
                                name="bio"
                                rows={3}
                                maxLength={500}
                                placeholder="Erzähl etwas über deinen Garten …"
                                value={data.bio}
                                onChange={(e) => setData('bio', e.target.value)}
                                className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                            />
                            <p className="text-xs text-muted-foreground">
                                {data.bio.length}/500 Zeichen
                            </p>
                            <InputError message={errors.bio} />
                        </div>
                    </div>
                </FormSection>

                <FormSection
                    title="Profilbild"
                    description="Optionales Bild für dein Profil"
                >
                    <div className="flex items-start gap-4">
                        <Avatar className="size-16">
                            {avatarPreview ? (
                                <AvatarImage
                                    src={avatarPreview}
                                    alt={user.name}
                                />
                            ) : null}
                            <AvatarFallback className="text-lg">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>

                        <div className="space-y-2">
                            <input
                                ref={fileInputRef}
                                type="file"
                                name="avatar"
                                accept="image/jpg,image/jpeg,image/png,image/webp"
                                className="hidden"
                                onChange={handleAvatarChange}
                            />
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        fileInputRef.current?.click()
                                    }
                                >
                                    <Upload className="mr-1 size-4" />
                                    {avatarPreview
                                        ? 'Bild ändern'
                                        : 'Bild hochladen'}
                                </Button>
                                {avatarPreview && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={handleRemoveAvatar}
                                    >
                                        <X className="mr-1 size-4" />
                                        Entfernen
                                    </Button>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                JPG, PNG oder WebP – max. 2 MB
                            </p>
                            <InputError message={errors.avatar} />
                        </div>
                    </div>
                </FormSection>

                <FormSection
                    title="Standort"
                    description="Optionale Angaben zu deiner Region"
                >
                    <div className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="country">Land</Label>
                            <Select
                                value={data.country}
                                onValueChange={(v) => setData('country', v)}
                            >
                                <SelectTrigger id="country" className="w-full">
                                    <SelectValue placeholder="Land wählen …" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(countries).map(
                                        ([code, name]) => (
                                            <SelectItem key={code} value={code}>
                                                {name}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.country} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="region">
                                Region{' '}
                                <span className="text-muted-foreground">
                                    (optional)
                                </span>
                            </Label>
                            <Input
                                id="region"
                                placeholder="z. B. Bayern"
                                value={data.region}
                                onChange={(e) =>
                                    setData('region', e.target.value)
                                }
                            />
                            <InputError message={errors.region} />
                        </div>
                    </div>
                </FormSection>

                <FormSection
                    title="Sichtbarkeit"
                    description="Wer kann dein Profil sehen?"
                >
                    <div className="space-y-2">
                        {(
                            [
                                {
                                    value: 'private',
                                    label: 'Privat',
                                    description:
                                        'Nur du kannst dein Profil sehen.',
                                    icon: Lock,
                                },
                                {
                                    value: 'public',
                                    label: 'Öffentlich',
                                    description:
                                        'Jeder kann dein Profil sehen.',
                                    icon: Globe,
                                },
                            ] as const
                        ).map(({ value, label, description, icon: Icon }) => (
                            <label
                                key={value}
                                htmlFor={`visibility-${value}`}
                                className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                                    data.profile_visibility === value
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:bg-muted'
                                }`}
                            >
                                <input
                                    type="radio"
                                    id={`visibility-${value}`}
                                    name="profile_visibility"
                                    value={value}
                                    checked={data.profile_visibility === value}
                                    onChange={() =>
                                        setData('profile_visibility', value)
                                    }
                                    className="sr-only"
                                />
                                <Icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">
                                        {label}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {description}
                                    </p>
                                </div>
                            </label>
                        ))}
                        <InputError message={errors.profile_visibility} />
                    </div>
                </FormSection>

                <FormSection
                    title="E-Mail-Adresse"
                    description="Deine Anmelde-E-Mail-Adresse"
                >
                    <div className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="email">E-Mail-Adresse</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                autoComplete="email"
                                required
                            />
                            <InputError message={errors.email} />
                        </div>

                        {mustVerifyEmail && user.email_verified_at === null && (
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Deine E-Mail-Adresse ist noch nicht
                                    bestätigt.{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        className="text-foreground underline decoration-border underline-offset-4 transition-colors hover:decoration-current! dark:decoration-muted-foreground"
                                    >
                                        Bestätigungsmail erneut senden.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <p className="mt-2 text-sm font-medium text-success">
                                        Eine neue Bestätigungsmail wurde
                                        versendet.
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                </FormSection>

                <div className="py-6">
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        Speichern
                    </Button>
                </div>
            </form>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profileinstellungen',
            href: edit(),
        },
    ],
};
