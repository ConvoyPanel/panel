export interface BaseUser {
    id: number
    name: string
    email: string
    /** Where this account's picture is served from, or null when it has none. */
    avatarUrl: string | null
    rootAdmin: boolean
}

export interface AuthenticatedUser extends BaseUser {
    /**
     * What the panel-wide account policy lets this account change about itself.
     * Present on every endpoint that returns the *signed-in* user; what the
     * account screen reads to decide which fields to offer.
     */
    accountCapabilities: App.Data.User.AccountCapabilitiesData
}
