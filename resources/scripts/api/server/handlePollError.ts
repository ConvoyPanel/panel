import { isNotFoundError } from '@/api/http'

/**
 * What a page polling a server does when a poll fails, so it doesn't keep
 * polling a server that is gone or a session that has ended:
 *
 * - 401/419: the session ended. Reload into the sign-in page.
 * - 404: the server was deleted or disconnected. `markMissing` switches
 *   ServerRouter to its "Server not found" screen.
 *
 * Returns whether the error was one of those; anything else (a timeout, a
 * 5xx) is left to the caller, which usually just tries again.
 */
export const handlePollError = (
    error: any,
    markMissing: () => void
): boolean => {
    const status = error?.response?.status

    if (status === 401 || status === 419) {
        window.location.assign('/auth/login')
        return true
    }

    if (isNotFoundError(error)) {
        markMissing()
        return true
    }

    return false
}
