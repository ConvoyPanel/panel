import {
    getPasskeyAuthenticationOptions,
    verifyPasskeyAuthentication,
} from '@/features/auth/api.ts'
import {
    WebAuthnAbortService,
    browserSupportsWebAuthnAutofill,
    startAuthentication,
} from '@simplewebauthn/browser'
import { useMutation } from '@tanstack/react-query'
import { useNavigate } from '@tanstack/react-router'
import { useCallback, useEffect, useRef } from 'react'

import { toast } from '@/components/ui/Toast'

/**
 * A dismissed prompt is a decision, not a failure. WebAuthn reports the user
 * closing the OS sheet, a conditional request that never had a credential to
 * offer, and a ceremony aborted by the next one all as the same two error
 * names — none of which is worth a toast.
 */
export const isPasskeyDismissal = (e: unknown) =>
    e instanceof Error &&
    (e.name === 'NotAllowedError' || e.name === 'AbortError')

/**
 * `autocomplete` value the sign-in field needs for conditional UI. Without a
 * `webauthn` token on a visible input, `startAuthentication` refuses to arm the
 * conditional request rather than failing silently in the browser.
 */
export const PASSKEY_AUTOFILL_AUTOCOMPLETE = 'username webauthn'

const assertPasskey = async (useBrowserAutofill = false) => {
    const optionsJSON = await getPasskeyAuthenticationOptions()

    const response = await startAuthentication({
        optionsJSON,
        useBrowserAutofill,
    })

    await verifyPasskeyAuthentication(response)
}

const redirectPath = (redirectTo?: string) =>
    redirectTo ? `/${redirectTo.slice(1)}` : '/'

/**
 * The explicit "use a passkey" path — the fallback for when the browser's own
 * offer never appears (no conditional UI support, a security key that has to be
 * plugged in, a credential the autofill list didn't surface).
 */
export const usePasskeyLogin = (redirectTo?: string) => {
    const navigate = useNavigate()

    const { mutate: signIn, isPending } = useMutation({
        mutationFn: async () => {
            // Close the conditional request *before* asking for options: the
            // ceremony and the server-side challenge have to be replaced
            // together, or a passkey picked from the autofill list during the
            // options fetch would be verified against the challenge this call
            // just overwrote.
            WebAuthnAbortService.cancelCeremony()

            await assertPasskey()
        },
        onSuccess: () => navigate({ to: redirectPath(redirectTo) }),
        onError: e => {
            if (isPasskeyDismissal(e)) return

            toast.add({ title: 'That passkey did not work', type: 'error' })
        },
    })

    return { signIn, isPending }
}

/**
 * Arms WebAuthn conditional UI so the browser offers a passkey from inside the
 * email field, with nothing to press first. Nothing renders and nothing changes
 * for an account without one: the request sits open until a credential is
 * picked, the page navigates away, or another ceremony replaces it.
 */
export const usePasskeyAutofill = (redirectTo?: string) => {
    const navigate = useNavigate()
    const armed = useRef(false)

    const arm = useCallback(async () => {
        if (!(await browserSupportsWebAuthnAutofill())) return

        try {
            await assertPasskey(true)
        } catch {
            // Every outcome here is silent by design. A dismissal is a choice,
            // and a genuine failure still leaves the password form sitting
            // right there, already filled in as far as the user got.
            return
        }

        await navigate({ to: redirectPath(redirectTo) })
    }, [navigate, redirectTo])

    useEffect(() => {
        // A ref rather than the effect's own identity: React 18 mounts twice in
        // development, and a second conditional request would abort the first
        // and burn the session challenge it was holding.
        if (armed.current) return

        armed.current = true

        void arm()

        return () => {
            // Leaving the page has to take the ceremony with it, or the browser
            // keeps offering a credential for a route that is no longer mounted.
            WebAuthnAbortService.cancelCeremony()
        }
    }, [arm])
}
