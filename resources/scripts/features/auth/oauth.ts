// OAuth/OIDC federated login helpers. The provider list is injected into `window.SiteConfiguration`
// by the server (IndexController) and only ever contains enabled + configured providers.
// `OAuthProvider` is a global type declared in globals.d.ts.

export const oauthProviders = (): OAuthProvider[] =>
    window.SiteConfiguration?.oauthProviders ?? []

/**
 * Build the URL that starts a provider handshake. This is a full-page browser navigation (the OAuth
 * redirect flow can't ride on `fetch`), optionally carrying the SPA path to land on after login.
 */
export const oauthRedirectUrl = (providerId: string, intended?: string): string => {
    const base = `/api/auth/oauth/${encodeURIComponent(providerId)}/redirect`

    return intended
        ? `${base}?intended=${encodeURIComponent(intended)}`
        : base
}

/** Human-readable copy for the `oauth_error` codes the callback can redirect back with. */
export const oauthErrorMessage = (code: string): string => {
    switch (code) {
        case 'oauth_account_not_provisioned':
            return 'No Convoy account is linked to that identity. Ask an administrator to create your account, or sign in and connect the provider from your account settings.'
        case 'oauth_identity_already_linked':
            return 'That account is already linked to a different Convoy user.'
        case 'oauth_invalid_state':
            return 'The single sign-on request expired or was invalid. Please try again.'
        case 'oauth_provider_not_enabled':
            return 'That single sign-on provider is not available.'
        default:
            return 'Single sign-on failed. Please try again.'
    }
}
