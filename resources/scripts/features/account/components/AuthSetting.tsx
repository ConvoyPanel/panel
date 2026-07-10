import { IconChevronRight } from '@tabler/icons-react'
import { forwardRef } from 'react'

interface Props {
    title: string
    description: string
    onClick?: () => void
}

const AuthSetting = forwardRef<HTMLButtonElement, Props>(
    ({ title, description, onClick }, ref) => {
        return (
            <button
                ref={ref}
                className={
                    'flex w-full items-center justify-between gap-4 rounded-md bg-muted/50 p-4 text-left transition-colors outline-none hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50'
                }
                onClick={onClick}
            >
                <div className='space-y-0.5'>
                    <p className='text-sm font-medium leading-snug'>{title}</p>
                    <p className='text-sm text-muted-foreground'>
                        {description}
                    </p>
                </div>
                <IconChevronRight
                    className={'h-4 w-4 shrink-0 text-muted-foreground'}
                />
            </button>
        )
    }
)

export default AuthSetting
