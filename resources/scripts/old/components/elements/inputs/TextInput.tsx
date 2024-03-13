import styled from '@emotion/styled'
import { TextInput as MantineTextInput, TextInputProps } from '@mantine/core'
import { forwardRef } from 'react'
import tw from 'twin.macro'

import ErrorMessage from '@/components/elements/ErrorMessage'

const StyledTextInput = styled(MantineTextInput)`
    .mantine-TextInput-label {
        ${tw`text-xs font-medium text-accent-500 mb-1`}
    }

    .mantine-TextInput-input {
        ${tw`bg-background disabled:bg-accent-100 disabled:text-accent-500 disabled:placeholder:text-accent-300`}
        ${({ error }) =>
            error
                ? tw`border-error placeholder:text-error-lighter text-error`
                : tw`border-accent-200 placeholder:text-accent-400 focus:border-accent-500`}
    }
`

const TextInput = forwardRef<HTMLInputElement, TextInputProps>(
    ({ error, ...props }, ref) => {
        return (
            <StyledTextInput
                ref={ref}
                error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined}
                {...props}
            />
        )
    }
)

export default TextInput
