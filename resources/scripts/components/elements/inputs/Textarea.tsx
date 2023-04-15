import ErrorMessage from '@/components/elements/ErrorMessage'
import styled from '@emotion/styled'
import { ExclamationCircleIcon } from '@heroicons/react/24/outline'
import { ComponentProps, FocusEvent, forwardRef, useEffect, useState } from 'react'
import tw from 'twin.macro'
import { TextareaProps as MantineTextareaProps, Textarea as MantineTextarea } from '@mantine/core'

export interface TextareaProps extends MantineTextareaProps {}

const StyledTextarea = styled(MantineTextarea)`
    .mantine-Textarea-label {
        ${tw`text-xs font-medium text-accent-500 mb-1`}
    }

    .mantine-Textarea-input {
        ${tw`bg-background`}
        ${({ error }) =>
            error
                ? tw`border-error placeholder:text-error-lighter text-error`
                : tw`border-accent-200 placeholder:text-accent-400 focus:border-accent-500`}
    }
`

const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(({ error, ...props }, ref) => {
    return <StyledTextarea ref={ref} error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined} {...props} />
})

export default Textarea
