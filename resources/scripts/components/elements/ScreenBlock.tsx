import { ExclamationCircleIcon, LinkIcon } from '@heroicons/react/24/outline'
import { Button } from '@mantine/core'
import { ComponentType, ReactNode } from 'react'

import PageContentBlock from '@/components/elements/PageContentBlock'

export interface IconProps {
    className?: string
}

interface BaseProps {
    title: string
    icon?: ComponentType<IconProps>
    message: string
    full?: boolean
    center?: boolean
    children?: ReactNode
    onRetry?: () => void
    onBack?: () => void
}

interface PropsWithRetry extends BaseProps {
    onRetry?: () => void
    onBack?: never
}

interface PropsWithBack extends BaseProps {
    onBack?: () => void
    onRetry?: never
}

export type ScreenBlockProps = PropsWithBack | PropsWithRetry

const ScreenBlock = ({
    title,
    icon: Icon,
    message,
    center,
    onBack,
    onRetry,
    full,
    children,
}: ScreenBlockProps) => {
    return (
        <PageContentBlock
            className={`${
                full && 'grid place-items-center min-h-screen -mt-10'
            }`}
            title={title}
        >
            <div
                className={`w-full sm:max-w-md p-12 md:p-20 bg-white dark:bg-black rounded-md shadow-md text-center ${
                    center && 'mx-auto'
                }`}
            >
                {Icon && (
                    <Icon className='w-16 h-16 border dark:border-stone-600 rounded-md p-3 text-black dark:text-stone-400 mx-auto' />
                )}

                <h2 className='text-stone-900 dark:text-white font-bold text-4xl mt-6'>
                    {title}
                </h2>
                <p className='description-small mt-3'>{message}</p>
                {children}
                {(onBack || onRetry) && (
                    <div className='flex justify-center mt-3'>
                        <Button
                            onClick={() =>
                                onRetry ? onRetry() : onBack ? onBack() : null
                            }
                        >
                            {onBack ? 'Go Back' : 'Retry'}
                        </Button>
                    </div>
                )}
            </div>
        </PageContentBlock>
    )
}

type ErrorMessageProps = (
    | Omit<PropsWithBack, 'icon' | 'title'>
    | Omit<PropsWithRetry, 'icon' | 'title'>
) & {
    title?: string
}

export const ErrorMessage = ({ title, ...props }: ErrorMessageProps) => (
    <ScreenBlock
        title={title || 'Something went wrong'}
        center
        icon={ExclamationCircleIcon}
        {...props}
    />
)

export const NotFound = ({
    title,
    message,
    onBack,
    full,
}: Partial<Pick<BaseProps, 'title' | 'message' | 'onBack' | 'full'>>) => (
    <ScreenBlock
        title={title || '404'}
        message={
            message ||
            "The link is either broken or doesn't exist on the server."
        }
        icon={LinkIcon}
        full={full}
        onBack={onBack}
    />
)

export default ScreenBlock
