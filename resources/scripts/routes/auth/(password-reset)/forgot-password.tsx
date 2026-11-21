import { passwordResetsEnabled } from '@/features/auth/password-reset.ts'
import { createFileRoute, notFound } from '@tanstack/react-router'

export const Route = createFileRoute('/auth/(password-reset)/forgot-password')({
    // With resets off the endpoint answers 404, so the form would only ever fail.
    beforeLoad: () => {
        if (!passwordResetsEnabled()) throw notFound()
    },
    staticData: {
        title: 'Forgot password',
    },
})
