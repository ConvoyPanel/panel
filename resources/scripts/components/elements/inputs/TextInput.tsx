import styled from '@emotion/styled';
import { ComponentProps, ReactNode, useState } from 'react';
import tw from 'twin.macro';

interface Props {
    icon?: ReactNode
    rightSection?: ReactNode
    label?: string
    size?: 'lg' | 'md'
}

const StyledTextInput = styled.input<Props>`
    ${tw`px-3 text-foreground  placeholder:text-accent-400 bg-transparent outline-0 py-1 disabled:cursor-not-allowed w-full`}

    ${({ size }) => size === 'lg' ? tw`h-12`: tw`h-9 text-sm`})}
`

const TextInput = ({label, icon, rightSection, ...props}: Omit<ComponentProps<'input'>, 'ref' | 'onFocus' | 'onBlur'> & Props) => {
    const [focused, setFocused] = useState(false)

    return <div>
        { label && <label className='text-xs font-medium text-accents-500'>{label}</label>}
        <div className={`flex border ${focused ? 'border-accent-500' : 'border-accent-200'} bg-background transition-colors rounded ${label && 'mt-1'} ${props.disabled && 'bg-accent-100 cursor-not-allowed'}`}>
            { icon && <div className='grid place-items-center px-3 -mr-3'>{ icon }</div>}
            <StyledTextInput onFocus={() => setFocused(true)} onBlur={() => setFocused(false)} {...props} />
            { rightSection && <div className='grid place-items-center px-3 -ml-3'>{ rightSection }</div>}
        </div>
    </div>
}

export default TextInput