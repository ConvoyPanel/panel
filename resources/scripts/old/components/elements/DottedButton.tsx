import { EllipsisVerticalIcon } from '@heroicons/react/24/outline'
import { ComponentProps, forwardRef } from 'react'

const DottedButton = forwardRef<
    HTMLButtonElement,
    Omit<ComponentProps<'button'>, 'children'>
>(({ className, ...props }, ref) => {
    return (
        <button
            ref={ref}
            className={`px-2 bg-transparent ${className}`}
            {...props}
        >
            <EllipsisVerticalIcon className='w-5 h-5 min-w-[1rem] text-foreground' />
        </button>
    )
})

export default DottedButton
