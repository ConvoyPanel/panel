import styled from '@emotion/styled';
import { ExclamationCircleIcon } from '@heroicons/react/24/outline';
import { ComponentProps, ReactNode, useEffect, useState } from 'react';
import tw from 'twin.macro';

export type Size = 'md' | 'lg'

interface Props {
    prefix?: ReactNode
    suffix?: ReactNode
    error?: string
    label?: string
    size?: Size
    wrapperClassName?: string
}

const StyledTextInput = styled.input<{
    size?: Size
    error?: string
}>`
    ${tw`px-3 bg-transparent outline-0 py-1 disabled:cursor-not-allowed w-full`}

    ${({error}) => error ? tw`placeholder:text-error-lighter text-error` : tw`placeholder:text-accent-400 text-foreground`}

    ${({ size }) => size === 'lg' ? tw`h-12`: tw`h-9 text-sm`})}
`

const TextInput = ({label, className, prefix, suffix, wrapperClassName, error, ...props}: Omit<ComponentProps<'input'>, 'size' | 'ref' | 'prefix' | 'onFocus' | 'onBlur'> & Props) => {
    const [focused, setFocused] = useState(false)

    useEffect(() => {
        if (props.disabled) {
            setFocused(false)
        }
    }, [props.disabled])

    return <div className={wrapperClassName}>
        { label && <label className='text-xs font-medium text-accents-500'>{label}</label>}
        <div className={`flex border ${error ? 'border-error' : focused ? 'border-accent-500' : 'border-accent-200'} transition-colors rounded ${label && 'mt-1'} ${props.disabled ? 'bg-accent-100 cursor-not-allowed' : 'bg-background'} ${className}`}>
            { prefix && <div className='grid place-items-center px-3 -mr-3'>{ prefix }</div>}
            <StyledTextInput onFocus={() => setFocused(true)} onBlur={() => setFocused(false)} error={error} {...props} />
            { suffix && <div className='grid place-items-center px-3 -ml-3'>{ suffix }</div>}
        </div>
        { error && <div className='flex space-x-1 mt-2'>
            <ExclamationCircleIcon className='h-5 w-5 text-error'/> <p className='text-sm text-error'>{error}</p>
        </div>}
    </div>
}

export default TextInput