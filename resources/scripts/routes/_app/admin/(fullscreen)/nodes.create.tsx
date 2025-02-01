import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/_app/admin/(fullscreen)/nodes/create')({
    staticData: {
        title: 'Nodes',
    },
})
