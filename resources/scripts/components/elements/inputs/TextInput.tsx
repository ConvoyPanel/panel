import { TextInputProps } from '@mantine/core'

/* import ErrorMessage from '@/components/elements/ErrorMessage'
import styled from '@emotion/styled'
import { ExclamationCircleIcon } from '@heroicons/react/24/outline'
import { ChangeEventHandler, ComponentProps, FocusEvent, ReactNode, useEffect, useState } from 'react'
import tw from 'twin.macro'

export type Size = 'md' | 'lg'

interface Input extends React.FC<TextInputProps> {}

export interface TextInputProps extends Omit<ComponentProps<'input'>, 'size' | 'prefix'> {
    prefix?: ReactNode
    suffix?: ReactNode
    error?: string
    label?: string
    onChange?: ChangeEventHandler<HTMLInputElement>
    placeholder?: string
    size?: Size
    wrapperClassName?: string
}

const StyledTextInput = styled.input<{
    _size?: Size
    error?: string
}>`
    ${tw`px-3 bg-transparent outline-0 py-1 disabled:cursor-not-allowed w-full`}

    ${({ error }) =>
        error ? tw`placeholder:text-error-lighter text-error` : tw`placeholder:text-accent-400 text-foreground`}

    ${({ _size }) => (_size === 'lg' ? tw`h-12` : tw`h-9 text-sm`)})}
`

const TextInput: Input = ({ label, className, prefix, suffix, wrapperClassName, size, error, onBlur, onFocus, ...props }) => {
    const [focused, setFocused] = useState(false)

    useEffect(() => {
        if (props.disabled) {
            setFocused(false)
        }
    }, [props.disabled])

    const handleBlur = (e: FocusEvent<HTMLInputElement>) => {
        setFocused(false)
        onBlur && onBlur(e)
    }

    const handleFocus = (e: FocusEvent<HTMLInputElement>) => {
        setFocused(true)
        onFocus && onFocus(e)
    }

    return (
        <div className={wrapperClassName}>
            {label && <label className='text-xs font-medium text-accent-500'>{label}</label>}
            <div
                className={`flex border ${
                    error ? 'border-error' : focused ? 'border-accent-500' : 'border-accent-200'
                } transition-colors rounded ${label && 'mt-1'} ${
                    props.disabled ? 'bg-accent-100 cursor-not-allowed' : 'bg-background'
                } ${className}`}
            >
                {prefix && <div className='grid place-items-center px-3 -mr-3'>{prefix}</div>}
                <StyledTextInput
                    _size={size}
                    onFocus={handleFocus}
                    onBlur={handleBlur}
                    error={error}
                    {...props}
                />
                {suffix && <div className='grid place-items-center px-3 -ml-3'>{suffix}</div>}
            </div>
            {error && (
                <ErrorMessage>{ error }</ErrorMessage>
            )}
        </div>
    )
}

export default TextInput
 */

import styled from '@emotion/styled'
import { TextInput as MantineTextInput } from '@mantine/core'
import tw from 'twin.macro'
import ErrorMessage from '@/components/elements/ErrorMessage'

const StyledTextInput = styled(MantineTextInput)`
    .mantine-TextInput-label {
        ${tw`text-xs font-medium text-accent-500 mb-1`}
    }

    .mantine-TextInput-input {
        ${tw`bg-background`}
        ${({ error }) =>
            error
                ? tw`border-error placeholder:text-error-lighter text-error`
                : tw`border-accent-200 placeholder:text-accent-400 focus:border-accent-500`}
    }
`

const TextInput = ({ error, ...props }: TextInputProps) => {
    return <StyledTextInput error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined} {...props} />
}

export default TextInput