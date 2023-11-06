import styled from '@emotion/styled'
import { ComponentProps, ElementType } from 'react'
import tw from 'twin.macro'

import LoadingDots from '@/components/elements/LoadingDots'

export type ButtonSize = 'sm'
export type ButtonShape = 'square'
export type ButtonVariant = 'outline' | 'filled'
export type ButtonColor = 'success' | 'danger' | 'accent'

export interface ButtonProps extends ComponentProps<'button'> {
    variant?: ButtonVariant
    color?: ButtonColor
    size?: ButtonSize
    loading?: boolean
    shape?: ButtonShape
    as?: ElementType<any>
}

const getBorderStyles = (variant: ButtonVariant, color: ButtonColor) => {
    if (variant === 'filled') {
        switch (color) {
            case 'success':
                return tw`border-success`
            case 'danger':
                return tw`border-error`
            default:
                return tw`border-foreground`
        }
    }

    switch (color) {
        case 'success':
            return tw`border-success`
        case 'danger':
            return tw`border-error`
        default:
            return tw`border-accent-200 sm:hover:border-foreground active:border-accent-200 sm:active:border-foreground `
    }
}

const getBackgroundStyles = (variant: ButtonVariant, color: ButtonColor) => {
    if (variant === 'filled') {
        switch (color) {
            case 'success':
                return tw`text-white sm:hover:text-success active:text-success bg-success sm:hover:bg-background active:bg-success-lighter sm:active:bg-success-lighter`
            case 'danger':
                return tw`text-white sm:hover:text-error active:text-error bg-error sm:hover:bg-background active:bg-error-lighter sm:active:bg-error-lighter`
            default:
                return tw`text-background sm:hover:text-foreground active:text-foreground bg-foreground sm:hover:bg-background active:bg-accent-200 sm:active:bg-accent-200`
        }
    }

    switch (color) {
        case 'success':
            return tw`text-success bg-background active:bg-success-lighter sm:active:bg-success-lighter`
        case 'danger':
            return tw`text-error bg-background active:bg-error-lighter sm:active:bg-error-lighter`
        default:
            return tw`text-accent-500 sm:hover:text-foreground active:text-foreground active:bg-accent-200 sm:active:bg-accent-200 bg-background`
    }
}

const getSizes = ({ size, shape }: ButtonProps) => {
    if (shape === 'square') {
        switch (size) {
            case 'sm':
                return tw`w-8 h-8 min-w-[5rem]`
            default:
                return tw`w-9 h-9`
        }
    }

    switch (size) {
        case 'sm':
            return tw`px-3 h-8 min-w-[5rem]`
        default:
            return tw`px-5 h-9`
    }
}

const StyledButton = styled.button<ButtonProps>`
    ${tw`border text-sm transition-colors rounded font-medium disabled:!text-accent-400 disabled:!bg-accent-100 disabled:!border-accent-200 disabled:cursor-not-allowed disabled:!bg-accent-100 disabled:!border-accent-200 disabled:!text-accent-400`}

    ${props => getSizes(props)}

  ${({ variant = 'outline', color = 'accent' }) =>
        getBorderStyles(variant, color)}

  ${({ variant = 'outline', color = 'accent' }) =>
        getBackgroundStyles(variant, color)}
`

const Button = ({ loading, disabled, ...props }: ButtonProps) => {
    return (
        <StyledButton disabled={loading || disabled} {...props}>
            {loading ? (
                <div className='w-full h-full grid place-items-center'>
                    <LoadingDots size={5} />
                </div>
            ) : null}
            <span className={loading ? 'invisible' : undefined}>
                {props.children}
            </span>
        </StyledButton>
    )
}

export default Button
