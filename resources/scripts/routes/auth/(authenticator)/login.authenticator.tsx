import { searchSchema } from '@/routes/auth/(login)/login.tsx'
import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute(
    '/auth/(authenticator)/login/authenticator'
)({
    validateSearch: searchSchema,
    staticData: {
        title: '2FA Required',
    },
})
