import { ZodString, z } from 'zod'

export const hostname = (string?: ZodString) =>
    (string ?? z.string()).regex(
        /^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/,
        {
            message: 'Invalid hostname',
        }
    )
