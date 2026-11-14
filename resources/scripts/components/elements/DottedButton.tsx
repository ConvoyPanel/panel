import { EllipsisVerticalIcon } from '@heroicons/react/24/outline'
import { ComponentProps, forwardRef } from 'react'
import { useTranslation } from 'react-i18next'

const DottedButton = forwardRef<
    HTMLButtonElement,
    Omit<ComponentProps<'button'>, 'children'>
>(({ className, ...props }, ref) => {
    const { t } = useTranslation('strings')

    // Only an icon inside, so it needs a name for screen readers; callers
    // can pass a more specific one.
    return (
        <button
            ref={ref}
            type='button'
            aria-label={t('more_actions')}
            className={`px-2 bg-transparent ${className}`}
            {...props}
        >
            <EllipsisVerticalIcon className='w-5 h-5 min-w-[1rem] text-foreground' />
        </button>
    )
})

export default DottedButton
