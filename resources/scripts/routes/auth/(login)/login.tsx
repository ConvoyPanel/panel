import { createFileRoute } from '@tanstack/react-router'
import { z } from 'zod'

export const searchSchema = z.object({
    redirect: z.string().optional(),
    // Set by the OAuth callback when a federated sign-in fails, so the login page can surface why.
    oauth_error: z.string().optional(),
})

export const Route = createFileRoute('/auth/(login)/login')({
    validateSearch: searchSchema,
    staticData: {
        title: 'Login',
    },
})
