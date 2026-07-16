/**
 * Mirrors the server rules in UpdatePasswordRequest: length only, no character composition.
 *
 * There are deliberately no uppercase/number/symbol criteria. NIST SP 800-63B says verifiers
 * SHOULD NOT impose composition rules — they push people toward `Password1!` and discourage the
 * passphrases that are actually strong — and the server does not enforce them, so showing them
 * would advertise requirements that do not exist.
 *
 * The breach check (HIBP) is server-side only; it needs the network and cannot be mirrored here,
 * so it surfaces as a validation error on submit rather than as a live criterion.
 */

export const PASSWORD_MIN_LENGTH = 12

/** bcrypt hashes at most 72 bytes and ignores the rest — see UpdatePasswordRequest. */
export const PASSWORD_MAX_BYTES = 72

export interface PasswordCriterion {
    label: string
    isFulfilled: boolean
}

const byteLength = (pwd: string): number => new TextEncoder().encode(pwd).length

export const evaluatePassword = (pwd: string): PasswordCriterion[] => {
    const criteria: PasswordCriterion[] = [
        {
            label: `Uses at least ${PASSWORD_MIN_LENGTH} characters`,
            isFulfilled: pwd.length >= PASSWORD_MIN_LENGTH,
        },
    ]

    // Only worth showing once it is in play: it is a ceiling almost nobody meets, and listing it
    // up front reads as a target rather than a limit.
    if (byteLength(pwd) > PASSWORD_MAX_BYTES) {
        criteria.push({
            label: `Uses at most ${PASSWORD_MAX_BYTES} bytes`,
            isFulfilled: false,
        })
    }

    return criteria
}
