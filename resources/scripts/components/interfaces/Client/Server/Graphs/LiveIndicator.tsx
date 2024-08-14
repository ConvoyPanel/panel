import { cn } from '@/utils'

const LiveIndicator = ({ className }: { className?: string }) => {
    return (
        <span className={cn('relative flex h-2 w-2', className)}>
            <span className='absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-600 opacity-75 dark:bg-blue-500' />
            <span
                className={
                    'relative inline-flex h-2 w-2 rounded-full bg-blue-600 dark:bg-blue-500'
                }
            />
        </span>
    )
}

export default LiveIndicator
