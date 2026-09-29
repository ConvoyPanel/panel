export interface BaseUser {
    id: number
    name: string
    email: string
    /** Where this account's picture is served from, or null when it has none. */
    avatarUrl: string | null
    /**
     * Where the account came from. A guest exists only because somebody shared a server with an
     * address that had no account; it is never a customer the provider provisioned.
     */
    type: App.Enums.User.UserType
    /** Holds the unrestricted Superadmin role. A narrower admin role answers false. */
    rootAdmin: boolean
    /** The admin role, or null for an account with no admin access at all. */
    adminRole: App.Data.Admin.AdminRoleData | null
}

export interface AuthenticatedUser extends BaseUser {
    /**
     * What the panel-wide account policy lets this account change about itself.
     * Present on every endpoint that returns the *signed-in* user; what the
     * account screen reads to decide which fields to offer.
     */
    accountCapabilities: App.Data.User.AccountCapabilitiesData
    /**
     * What this account may reach in the admin area. Only on the signed-in user, and what the
     * admin sidebar reads so it never offers a section that would answer 403.
     */
    adminPermissions: App.Enums.Admin.AdminPermission[]
}
