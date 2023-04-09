import { t } from 'i18next'
import { z } from 'zod'

export const hostname = () =>
    z
        .string()
        .regex(
            /^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/,
            {
                message: t('errors.invalid_string.hostname', {
                    ns: 'zod',
                    validation: t('hostname', { ns: 'strings' }).toLowerCase(),
                })!,
            }
        )
