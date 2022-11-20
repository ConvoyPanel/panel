import styled from '@emotion/styled';
import { ExclaimationCircleIcon } from '@heroicons/react/24/outline';
import { ComponentProps, ReactNode, useState } from 'react';
import tw from 'twin.macro';

interface Props {
    prefix?: ReactNode
    suffix?: ReactNode
    error?: string
    label?: string
    size?: 'lg' | 'md'
}

const StyledTextInput = styled.input<Props>`
    ${tw`px-3 bg-transparent outline-0 py-1 disabled:cursor-not-allowed w-full`}

    ${({error}) => error ? tw`placeholder:text-error-lighter text-error` : tw`placeholder:text-accent-400 text-foreground`}

    ${({ size }) => size === 'lg' ? tw`h-12`: tw`h-9 text-sm`})}
`

const TextInput = ({label, prefix, suffix, error, ...props}: Omit<ComponentProps<'input'>, 'ref' | 'prefix' | 'onFocus' | 'onBlur'> & Props) => {
    const [focused, setFocused] = useState(false)

    return <div>
        { label && <label className='text-xs font-medium text-accents-500'>{label}</label>}
        <div className={`flex border ${error ? 'border-error' : focused ? 'border-accent-500' : 'border-accent-200'} bg-background transition-colors rounded ${label && 'mt-1'} ${props.disabled && 'bg-accent-100 cursor-not-allowed'}`}>
            { prefix && <div className='grid place-items-center px-3 -mr-3'>{ prefix }</div>}
            <StyledTextInput onFocus={() => setFocused(true)} onBlur={() => setFocused(false)} error={error} {...props} />
            { suffix && <div className='grid place-items-center px-3 -ml-3'>{ suffix }</div>}
        </div>
        { error && <div className='flex space-x-1 mt-2'>
            <ExclaimationCircleIcon className='h-5 w-5 text-error'/> <p className='text-sm text-error'>{error}</p>
        </div>}
    </div>
}

export default TextInput