import { css } from '@emotion/react'
import styled from '@emotion/styled'
import { ReactNode, useCallback, useEffect, useRef, useState } from 'react'
import tw from 'twin.macro'

interface OverlayProps {
    position: 'left' | 'middle' | 'right' | 'none'
}

const Overlay = styled.div<OverlayProps>`
    ${tw`absolute w-full h-full z-[2] overflow-hidden`}
    transform: scale3d(1.01,1.01,1);
    pointer-events: none;
    touch-action: none;
    --scroller-gradient: #fff 0, hsla(0, 0%, 100%, 0.7) 8px, hsla(0, 0%, 100%, 0.45) 14px, hsla(0, 0%, 100%, 0.3) 19px,
        hsla(0, 0%, 100%, 0.26) 21px, hsla(0, 0%, 100%, 0.19) 25px, hsla(0, 0%, 100%, 0.12) 29px,
        hsla(0, 0%, 100%, 0.06) 32px, hsla(0, 0%, 100%, 0.03) 34px, hsla(0, 0%, 100%, 0.02) 36px,
        hsla(0, 0%, 100%, 0.008) 38px, hsla(0, 0%, 100%, 0.002) 39px, hsla(0, 0%, 100%, 0) 40px;

    &::before {
        background: linear-gradient(to bottom, var(--scroller-gradient)),
            linear-gradient(to right, var(--scroller-gradient));
        background-position-x: -40px;
        background-position-y: -40px;
        ${({ position }) =>
            position !== 'left' && position !== 'none'
                ? css`
                      background-position-x: left;
                  `
                : ''}
    }

    &::after {
        background: linear-gradient(to top, var(--scroller-gradient)),
            linear-gradient(to left, var(--scroller-gradient));
        background-position-x: calc(100% + 40px);
        background-position-y: calc(100% + 40px);
        ${({ position }) =>
            position !== 'right' && position !== 'none'
                ? css`
                      background-position-x: right;
                  `
                : ''}
    }

    &::before,
    &::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        will-change: background;
        transition: background-position 0.3s ease;
        transform: translateZ(0);
        background-repeat: no-repeat;
        background-size: 200% 200%;
    }
`
interface Props {
    children?: ReactNode
}

const Scroller = ({ children }: Props) => {
    const [position, setPosition] = useState<'left' | 'middle' | 'right' | 'none'>('middle')
    const ref = useRef<HTMLDivElement>(null)

    const checkPosition = useCallback(() => {
        if (ref.current) {
            const { scrollLeft, scrollWidth, clientWidth, offsetWidth } = ref.current
            if (scrollWidth <= offsetWidth) {
                setPosition('none')
            } else if (scrollLeft === 0) {
                setPosition('left')
            } else if (scrollLeft + clientWidth === scrollWidth) {
                setPosition('right')
            } else {
                setPosition('middle')
            }
        }
    }, [])

    useEffect(() => {
        window.addEventListener('resize', checkPosition)

        return () => {
            window.removeEventListener('resize', checkPosition)
        }
    }, [])

    useEffect(() => {
        checkPosition()
    }, [ref])

    useEffect(() => {
        console.log(position)
    }, [position])

    const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
        console.log(e.currentTarget.scrollWidth, e.currentTarget.scrollLeft, e.currentTarget.offsetWidth)
        if (e.currentTarget.scrollWidth <= e.currentTarget.offsetWidth) {
            setPosition('none')
        } else if (e.currentTarget.scrollLeft === 0) {
            setPosition('left')
        } else if (e.currentTarget.scrollWidth - e.currentTarget.scrollLeft === e.currentTarget.clientWidth) {
            setPosition('right')
        } else {
            setPosition('middle')
        }
    }

    return (
        <div className='relative overflow-hidden'>
            <Overlay position={position} />
            <div onScroll={handleScroll} ref={ref} className='relative overflow-auto w-full h-full scrollbar-hide'>
                {children}
            </div>
        </div>
    )
}

export default Scroller
