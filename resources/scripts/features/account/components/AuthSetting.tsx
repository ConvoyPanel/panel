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
                    'flex w-full items-center justify-between rounded-sm text-left ring-8 ring-transparent hover:bg-accent hover:ring-accent'
                }
                onClick={onClick}
            >
                <div className='space-y-0.5'>
                    <p className='text-sm font-medium'>{title}</p>
                    <p className='text-xs text-muted-foreground'>
                        {description}
                    </p>
                </div>
                <IconChevronRight className={'h-4 w-4 text-muted-foreground'} />
            </button>
        )
    }
)

export default AuthSetting
