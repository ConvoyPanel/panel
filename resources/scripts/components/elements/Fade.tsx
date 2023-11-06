import styled from '@emotion/styled'
import React, { useRef } from 'react'
import CSSTransition, {
    CSSTransitionProps,
} from 'react-transition-group/CSSTransition'
import tw from 'twin.macro'

interface Props extends Omit<CSSTransitionProps, 'timeout' | 'classNames'> {
    timeout: number
}

const Container = styled.div<{ timeout: number }>`
    .fade-enter,
    .fade-exit,
    .fade-appear {
        will-change: opacity;
    }

    .fade-enter,
    .fade-appear {
        ${tw`opacity-0`};

        &.fade-enter-active,
        &.fade-appear-active {
            ${tw`opacity-100 transition-opacity ease-in`};
            transition-duration: ${props => props.timeout}ms;
        }
    }

    .fade-exit {
        ${tw`opacity-100`};

        &.fade-exit-active {
            ${tw`opacity-0 transition-opacity ease-in`};
            transition-duration: ${props => props.timeout}ms;
        }
    }
`

const Fade: React.FC<Props> = ({ timeout, children, ...props }) => {
    const ref = useRef(null)

    return (
        <Container timeout={timeout}>
            <CSSTransition
                nodeRef={ref}
                timeout={timeout}
                classNames='fade'
                {...props}
            >
                <div ref={ref}>{children}</div>
            </CSSTransition>
        </Container>
    )
}
Fade.displayName = 'Fade'

export default Fade
