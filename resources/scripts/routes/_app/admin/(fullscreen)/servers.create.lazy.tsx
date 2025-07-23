import { createLazyFileRoute } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'

export const Route = createLazyFileRoute(
  '/_app/admin/(fullscreen)/servers/create',
)({
  component: CreateServerPage,
})

function CreateServerPage() {
    const form = useForm({

    })

  return <div>Hello "/_app/admin/(fullscreen)/servers/create"!</div>
}
