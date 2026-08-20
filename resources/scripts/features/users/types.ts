import { PASSWORD_MAX_BYTES, PASSWORD_MIN_LENGTH } from '@/utils/password.ts'
import { z } from 'zod'

/**
 * Mirrors App\Rules\PasswordPolicy: length only, no character composition. The breach check runs
 * server-side (it needs the network), so it arrives as a field error on submit rather than as a
 * criterion the strength indicator can tick off.
 */
const password = z
    .string()
    .min(
        PASSWORD_MIN_LENGTH,
        `Password must be at least ${PASSWORD_MIN_LENGTH} characters`
    )
    .refine(
        value => new TextEncoder().encode(value).length <= PASSWORD_MAX_BYTES,
        `Password must be at most ${PASSWORD_MAX_BYTES} bytes`
    )

const baseUserSchema = z.object({
    name: z.string().min(1, 'A name is required').max(191),
    email: z.email('Enter a valid email address').max(191),
    rootAdmin: z.boolean(),
})

export const createUserSchema = baseUserSchema.extend({ password })

/**
 * The one field that differs between creating and editing: an existing account already has a
 * password, so a blank box means "leave it alone" rather than "no password".
 */
export const updateUserSchema = baseUserSchema.extend({
    password: z.literal('').or(password),
})

export type UserInput = z.infer<typeof createUserSchema>
