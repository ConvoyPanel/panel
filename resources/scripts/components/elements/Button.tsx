import LoadingDots from '@/components/elements/LoadingDots'
import { css } from '@emotion/react'
import styled from '@emotion/styled'
import { ComponentProps } from 'react'
import tw from 'twin.macro'

export interface Props extends ComponentProps<'button'> {
  variant?: 'outline' | 'filled'
  color?: 'success' | 'danger' | 'accent'
  size?: 'sm'
  loading?: boolean
}

const getBorderStyles = (variant: Props['variant'], color: Props['color']) => {
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

const getBackgroundStyles = (variant: Props['variant'], color: Props['color']) => {
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

const StyledButton = styled.button<Props>`
  ${tw`border text-sm transition-colors rounded font-medium disabled:!text-accent-400 disabled:!bg-accent-100 disabled:!border-accent-200 disabled:cursor-not-allowed disabled:!bg-accent-100 disabled:!border-accent-200 disabled:!text-accent-400`}

  ${({ size }) => (size === 'sm' ? tw`px-3 h-8 min-w-[5rem]` : tw`px-5 h-9`)}

  ${({ variant = 'outline', color = 'accent' }) => css`${getBorderStyles(variant, color)}`}

  ${({ variant = 'outline', color = 'accent' }) => css`${getBackgroundStyles(variant, color)}`}
`

const Button = ({ loading, disabled, ...props}: Props) => {
  return <StyledButton disabled={loading || disabled} {...props}>
    {loading ? <div className='w-full h-full grid place-items-center'><LoadingDots size={5} /></div> : props.children}
  </StyledButton>
}

export default Button
