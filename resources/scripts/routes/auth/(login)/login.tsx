import { createFileRoute } from '@tanstack/react-router'
import { z } from 'zod'

export const searchSchema = z.object({
    redirect: z.string().optional(),
})

export const Route = createFileRoute('/auth/(login)/login')({
    validateSearch: searchSchema,
    staticData: {
        title: 'Login',
    },
})
