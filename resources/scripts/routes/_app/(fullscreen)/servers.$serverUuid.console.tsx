import { createFileRoute } from '@tanstack/react-router'
import { z } from 'zod'

export const Route = createFileRoute(
    '/_app/(fullscreen)/servers/$serverUuid/console'
)({
    validateSearch: z.object({
        type: z.enum(['novnc', 'xtermjs']).catch('novnc'),
    }),
    staticData: { title: 'Console' },
})
