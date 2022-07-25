export interface AuthInterface {
  user: {
    id: number
    name: string
    email: string
    email_verified_at: string
    root_admin: boolean
    created_at: string
    updated_at: string
  }
}

export interface DefaultProps {
  auth: AuthInterface
}
