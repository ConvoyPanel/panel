import styled from '@emotion/styled'
import tw from 'twin.macro'

interface Props {
  variant?: 'outline' | 'filled'
  color?: 'primary' | 'danger' | 'accent'
  size?: 'sm'
}

const Button = styled.button<Props>`
  ${tw`border text-sm transition-colors rounded font-medium disabled:!text-accent-400 disabled:!bg-accent-100 disabled:!border-accent-200 disabled:cursor-not-allowed`}

  ${({ size }) => (size === 'sm' ? tw`px-3 py-1.5` : tw`px-5 h-9`)}

  ${({ variant = 'outline', color = 'accent' }) => variant === 'outline' ? tw`border-accent-200 hover:border-foreground active:border-foreground text-accent-500 hover:text-foreground active:text-foreground active:bg-accent-200 bg-background` : ''}
`

export default Button
