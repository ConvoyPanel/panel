import { ReactNode } from 'react'

import Lighthouse from '@/components/ui/Illustrations/Lighthouse.tsx'

interface Props {
    children: ReactNode
}

const ErrorPageLayout = ({ children }: Props) => {
    return (
        <div
            className={
                'relative flex h-full w-full flex-col items-center justify-center text-center'
            }
        >
            <Lighthouse
                className={
                    'fixed bottom-4 right-0 -z-10 w-[20rem] text-muted-foreground/10 sm:w-[30rem] md:w-[40rem]'
                }
            />
            {children}
        </div>
    )
}

export const ErrorCode = ({ children }: Props) => {
    return <h1 className={'text-sm font-semibold'}>{children}</h1>
}

export const ErrorTitle = ({ children }: Props) => {
    return (
        <h2 className={'mt-2 text-4xl font-semibold tracking-tight'}>{children}</h2>
    )
}

export const ErrorDescription = ({ children }: Props) => {
    return <div className={'mt-4 text-muted-foreground'}>{children}</div>
}

export const ErrorFooter = ({ children }: Props) => {
    return <div className={'mt-8 flex gap-3'}>{children}</div>
}

export default ErrorPageLayout
