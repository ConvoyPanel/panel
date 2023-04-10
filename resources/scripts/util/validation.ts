import { t } from 'i18next'
import { ZodString, z } from 'zod'

export const hostname = (string?: ZodString) =>
    (string ?? z.string()).regex(
        /^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/,
        {
            message: t('errors.invalid_string.hostname', {
                ns: 'zod',
                validation: t('hostname', { ns: 'strings' }).toLowerCase(),
            })!,
        }
    )

export const englishKeyboardCharacters = (string?: ZodString) =>
    (string ?? z.string()).regex(/^[A-Za-z0-9!@#$%^&*()_+\-=[\]{}|;':",.\/<>?\\ ]*$/, {
        message: t('errors.invalid_string.english_keyboard_characters', {
            ns: 'zod',
            validation: t('english_keyboard_characters', { ns: 'strings' }).toLowerCase(),
        })!,
    })

export const password = (string?: ZodString) =>
    (string ?? z.string()).regex(/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/, {
        message: t('errors.invalid_string.password', {
            ns: 'zod',
        })!,
    })
