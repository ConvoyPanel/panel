import { createFileRoute } from '@tanstack/react-router'
import { z } from 'zod'

export const searchSchema = z.object({
    // Set by the OAuth callback after a link attempt (success carries the provider id, failure a
    // code) so the security page can toast the outcome.
    oauth_linked: z.string().optional(),
    oauth_error: z.string().optional(),
})

export const Route = createFileRoute('/_app/_dashboard/security')({
    validateSearch: searchSchema,
    staticData: {
        title: 'Security',
    },
})
