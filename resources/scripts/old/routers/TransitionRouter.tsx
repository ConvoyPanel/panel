import styled from '@emotion/styled'
import { ReactNode } from 'react'
import { useLocation } from 'react-router-dom'
import { SwitchTransition } from 'react-transition-group'
import tw from 'twin.macro'

import Fade from '@/components/elements/Fade'


const StyledSwitchTransition = styled(SwitchTransition)`
    ${tw`relative`};

    & section {
        ${tw`absolute w-full top-0 left-0`};
    }
`

interface Props {
    children: ReactNode
}

const TransitionRouter = ({ children }: Props) => {
    const location = useLocation()

    return (
        <StyledSwitchTransition>
            <Fade
                timeout={150}
                key={location.pathname + location.search}
                in
                appear
                unmountOnExit
            >
                {children}
            </Fade>
        </StyledSwitchTransition>
    )
}

export default TransitionRouter