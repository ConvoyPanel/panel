import { cn } from '@/utils'
import { IconChevronRight } from '@tabler/icons-react'
import { forwardRef } from 'react'

interface Props {
    title: string
    description: string
    onClick?: () => void
    /**
     * Renders the row as a statement rather than a control: no chevron, no
     * hover, nothing to press. Used when the operator has taken this change
     * away — the value is still worth showing, the way in is not.
     */
    disabled?: boolean
}

const AuthSetting = forwardRef<HTMLButtonElement, Props>(
    ({ title, description, onClick, disabled }, ref) => {
        return (
            <button
                ref={ref}
                type={'button'}
                disabled={disabled}
                className={cn(
                    'flex w-full items-center justify-between gap-4 rounded-md bg-muted/50 p-4 text-left outline-none',
                    !disabled &&
                        'transition-colors hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    disabled && 'cursor-default'
                )}
                onClick={onClick}
            >
                <div className='space-y-0.5'>
                    <p className='text-sm font-medium leading-snug'>{title}</p>
                    <p className='text-sm text-muted-foreground'>
                        {description}
                    </p>
                </div>
                {!disabled && (
                    <IconChevronRight
                        className={'h-4 w-4 shrink-0 text-muted-foreground'}
                    />
                )}
            </button>
        )
    }
)

export default AuthSetting
