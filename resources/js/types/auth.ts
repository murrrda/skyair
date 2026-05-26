export type User = {
    id: number;
    name: string;
    first_name: string | null;
    last_name: string | null;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    date_of_birth: string | null;
    address: string | null;
    phone_number: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type UserRole = 'admin' | 'agent' | 'pilot' | 'cabin_crew' | 'dispatcher' | 'kupac' | null;

export type Auth = {
    user: User;
    role?: UserRole;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
