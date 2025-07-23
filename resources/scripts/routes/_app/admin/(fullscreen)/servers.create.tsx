import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/(fullscreen)/servers/create')(
    {
        staticData: {
            title: 'Servers',
        },
    }
)
