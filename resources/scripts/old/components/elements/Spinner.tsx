import styled from '@emotion/styled'
import { ReactNode, Suspense } from 'react'
import tw from 'twin.macro'

import LogoOutline from '@/components/elements/LogoOutline'

interface Props {
    screen?: boolean
    flat?: boolean
}

interface Spinner extends React.FC<Props> {
    Suspense: React.FC<{
        children: ReactNode
        screen?: boolean
    }>
}

const SpinnerContainer = styled.div`
    svg {
        ${tw`w-14`}
    }
    svg path {
        animation: dash 2s cubic-bezier(0.45, 0.09, 0.6, 0.89) infinite;
    }
    @keyframes dash {
        0% {
            stroke-dasharray: 100 2652;
            stroke-dashoffset: 0;
        }
        50% {
            stroke-dasharray: 2352 400;
            stroke-dashoffset: 2752;
        }
        100% {
            stroke-dasharray: 100 2652;
            stroke-dashoffset: 5504;
        }
    }
`

const Spinner: Spinner = ({ screen, flat }: Props) => {
    return (
        <div
            className={`grid place-items-center w-full ${
                screen ? 'h-screen' : 'h-40'
            } ${flat && 'dark:bg-black'}`}
        >
            <SpinnerContainer>
                <LogoOutline
                    className='text-foreground'
                    strokeWidth={'25px'}
                    viewBox={'-13 -13 538 538'}
                />
            </SpinnerContainer>
        </div>
    )
}

Spinner.Suspense = ({ children, screen }) => {
    return (
        <Suspense fallback={<Spinner screen={screen} />}>{children}</Suspense>
    )
}

export default Spinner
