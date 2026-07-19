export type UserRole = 'user' | 'moderator' | 'admin';

export type User = {
    id: number;
    role: UserRole;
    name: string;
    username: string;
    username_changed_at: string | null;
    email: string;
    bio: string | null;
    avatar_path: string | null;
    country: string | null;
    region: string | null;
    profile_visibility: 'private' | 'public';
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
