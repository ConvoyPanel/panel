import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/')({
    component: () => <div>Hello /_app/servers/$serverUuid/!</div>,
    // @ts-ignore
    meta: () => [{ title: 'Dashboard' }],
})
