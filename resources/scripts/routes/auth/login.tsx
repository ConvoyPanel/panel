import { createFileRoute } from '@tanstack/react-router'
import { z } from 'zod'

const searchSchema = z.object({
    redirect: z.string().optional(),
})

export const Route = createFileRoute('/auth/login')({
    validateSearch: searchSchema,
})
