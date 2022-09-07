export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string
  root_admin: boolean
  created_at: string
  updated_at: string
}

export interface AuthInterface {
  user: User
}

export interface DefaultProps {
  auth: AuthInterface
}

export interface LinkInterface {
  previous?: string
  next?: string
}

export interface PaginationInterface {
  total: number
  count: number
  per_page: number
  current_page: number
  total_pages: number
  links: LinkInterface
}

export interface PaginatedInterface<T> {
  data: T
  meta: {
    pagination: PaginationInterface
  }
}