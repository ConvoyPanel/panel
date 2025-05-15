import { searchSchema } from '@/routes/auth.login/index.tsx'
import { createFileRoute } from '@tanstack/react-router'

export const Route = createFileRoute('/auth/login/authenticator')({
  validateSearch: searchSchema,
  staticData: {
    title: '2FA Required',
  },
})
